<?php

  include "support/Set.php";

  class Apriori {

    //vars section
      private $support_percentage;
      private $support_count;
      private $confidence_percentage;
      private $rules_table;
      private $global_item_counts = [];
    //end of vars section

    //constructor section
      function __construct($support_percentage   ,  $confidence_percentage){
          $this->support_percentage = $support_percentage;
          $this->confidence_percentage = $confidence_percentage;
          $this->rules_table = [];
      }
    //end of constructor section

  
    //Public Core methods section
      public function printHyperParameters(){
          return "Support is :: ".$this->support_percentage." and Confidence is :: ".$this->confidence_percentage;
      }

      //train() is resposible for bootstraping the coreTrain apriori algorithm
      public function train(Array $transaction_data){
        //init support count property
        $this->support_count =round(( $this->support_percentage  * count($transaction_data)));
      
        //Convert incoming array to an Array of set datastructure
        //Flatten the 2-Dimenisonal Incoming datastructure to only 1-Dimensional Set Array
        //This is being done becasue the coreTrain() works with array of sets, of data
        $training_data_set = $this->flattenToUniqueSetArray($transaction_data);
        $this->coreTrain($training_data_set ,  $transaction_data); 
      }
      
      //predict() is responsible for making sure that internal corePredict Method Always gets an array to work with
      public function predict($to_predict) {
        $predicted_set = [];
        if(is_array($to_predict)){
          $predicted_set =  $this->corePredict($to_predict);
        }else{
          $predicted_set =  $this->corePredict([$to_predict]);
        }
        return $predicted_set;
      }
    //End of Public Core Methods section


    //Private core methods section

      //CoreTrain() is responsible for Running Iterative Apriori Algorithm on sets of data
      private function coreTrain($training_data_set , $transaction_data){
        
        //last known valid L-Set
        $last_known_valid_L = [];
        $elements_par_set = 1;
        $potential_permutations = $training_data_set;
        while(true){


          //An Dictionary of sets as keys along with their counts as values (C1)
          $set_count_dict =  $this->collectSetCountsFromTransactions($potential_permutations, $transaction_data);
          
          //Store each newly calculated set count, into a global item count lookup table, in order to calculate confidence later
          foreach ($set_count_dict as $KK => $SCD) {
            if(array_key_exists($KK,$this->global_item_counts) == false){
              $this->global_item_counts[$KK] = 0;
              $this->global_item_counts[$KK] += $SCD;
            }else{
              $this->global_item_counts[$KK] += $SCD;
            }
          }

          
          //An Array of  the remaining sets that satisfies MIN_SUPPORT hyper-parameter
          $reduced_pure_sets =  $this->purgeMinSupportOutliersThenConvertBackToPureSets($set_count_dict, $elements_par_set);
          
          //Empty would indicate that there were not sets in last permutation that satisfied MIN_SUPPORT
          //Break the loop, our final permutations will be in $last_known_valid_L
          if(empty($reduced_pure_sets)) break;
          
          $last_known_valid_L = $reduced_pure_sets; 

          //Next Permutations of set must contain one more element par set hence increment elements_par_set
          $elements_par_set++;

          //Generate New Permutations.
          $potential_permutations = $this->generateNewPermutations($reduced_pure_sets, $elements_par_set);
        }

        //save the final set 
        $this->rules_table = $last_known_valid_L;

      }
      
      private function corePredict($to_predict){

        $to_predict_array = is_array($to_predict[0])  ? $to_predict : [$to_predict];

        $predicted_sets = [];

          foreach ($to_predict_array as $set_query) {
            $local_prediction = []; //Is used for a single prediction Query Out of N Queries
            foreach($this->rules_table as $rule){
                  /*Now try to match the elements of this rule_copy -> RC with elements of to_predict_array -> TPA
                    Will be valid match if all the elements of TPA are present within RC
                    If so, we can finally add the remaining elements after (RC - TPA)  into predicted_set
                  */
                  $full_match_successful = true;
                  $RC_values = $rule->values();
                  foreach ($set_query as $TPA_elem) {
                    if(in_array($TPA_elem , $RC_values) == false){ //Full Match Failed
                        $full_match_successful = false;
                        break; 
                    }
                  }

                  if ($full_match_successful){

                    $new_matched_set_to_push = array_values(array_diff($RC_values , $set_query));
                    
                    $keys_to_calculate_confidence_against = $set_query; //Since $set_query is an array of string , php will perform a deep-copy 
                    sort($keys_to_calculate_confidence_against); //Imp to sort, in-order to make sure consistency inside the lookup table
                    $key_to_grab_support_count = join("+" , $keys_to_calculate_confidence_against);
                  
                    //if it satisfies minimum confidence
                    if($this->confidenceScoreSatisfied($key_to_grab_support_count)){
                      array_push($local_prediction , $new_matched_set_to_push);
                    }
                  }
            }
            array_push($predicted_sets , $local_prediction);
          }

        return $predicted_sets;
      }

    //End Of Private Core Methods section


    //Helper methods section
      private function flattenToUniqueSetArray($transaction_data){
        $training_data_set_tr = [];
        foreach ($transaction_data as $trans_key => $transaction) {
          foreach ($transaction as $elem_key => $element) {
            $training_data_set_tr[$element] =  new Set([$element], 1); //1 Is the Maximum Allowed Elements Par set
                                                                      //its 1 because in the begining we only work with single element par set
          }
       }
        return array_values($training_data_set_tr);
      }

      //This Method will, Look at each Available set inside the training_data_set 
      //And will count its `set as a whole` occurences inside the transaction_data
      //Finally it will return a Dictionary containing sets where Set will be treated as Key and its value will be its count
      private function collectSetCountsFromTransactions($training_data_set, $transaction_data){
          $set_count_dictionary = [];
          foreach ($training_data_set as $set) { 
            foreach ($transaction_data as $transaction) {
              
              $set_found_in_transaction = true; //Will be false if a set is not completly present inside a transaction  
              foreach ($set->values() as $set_element) {
                
                if(in_array($set_element,$transaction) == false){ //Complete Set-match failed in this transaction, break and goto next transaction
                    $set_found_in_transaction = false;
                    break;
                }

              }
              if( $set_found_in_transaction ){
                $K = $set->serializeToString(); // to be used a key for set count
                if (array_key_exists($K,$set_count_dictionary) == false){
                  $set_count_dictionary[$K] = 0;
                }
                
                $set_count_dictionary[$K] += 1; //For a particular set increment its count
              }

            }

          }
        return $set_count_dictionary;
      }

      //Reducer method to remove all those sets that do not met MIN_SUPPORT hyper parameter
      //It will return an array of reduced sets
      private function purgeMinSupportOutliersThenConvertBackToPureSets($set_count_dict, $set_count_constraint){
        $pure_sets_to_return = [];
        foreach ($set_count_dict as $key => $value) {
          if ($value < $this->support_count){
            unset($set_count_dict[$key]);
          }else{
            array_push($pure_sets_to_return ,  new Set(explode("+", $key), $set_count_constraint));
          }
        }
        return $pure_sets_to_return;
      }

      //New Permutations Generator
      private function generateNewPermutations($sets_to_generate_from, $elements_par_set){
        $new_permutations_dict = [];
        $c = count($sets_to_generate_from);

         //Core Permutations Generator Nested Loops
          for ($i=0; $i < $c ; $i++) { 
            for ($j=$i+1; $j < $c; $j++) { 
            
               $ELEM_K         = $sets_to_generate_from[$i];
               $ELEM_K_plus_1  = $sets_to_generate_from[$j];
               
               $potential_set =  new Set($elements_par_set);
              //Add values from both set
              foreach ($ELEM_K->values() as $elem_k_value) {
                $potential_set->add($elem_k_value);
                if($potential_set->elementCountConstraintOK() == false) break;
              }
              foreach ($ELEM_K_plus_1->values() as $elem_k_plus_value) {
                $potential_set->add($elem_k_plus_value);
                if($potential_set->elementCountConstraintOK() == false) break;
              }

              $new_permutations_dict[$potential_set->serializeToString()] =  $potential_set;
            } 
          }

        return array_values($new_permutations_dict);
      }

      //Will return true or false depending on the confidence of the set
      private function confidenceScoreSatisfied($key_to_predict){
        $_confidence_percentage =  ($this->support_count / $this->global_item_counts[$key_to_predict]);
        return $_confidence_percentage > $this->confidence_percentage;
      }
    //End of Helper Methods section


  }//End of Apriori-Class

?>
