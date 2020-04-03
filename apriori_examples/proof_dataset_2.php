<?php
  
  include "../Apriori.php";

  //Read Relevant Json data-set
  $training_data =  array_values(json_decode(file_get_contents("../data/apriori_datasets/proof_dataset_2.json"), true));

 //Initialize Main Apriori Algorithm Class With Required Required support and confidence percentage
  $apriori_associator =  new Apriori( $support =  0.25 , $confidence =  0.9);

  /*
  Training Method Expects Array Of Tuples Where Each tuple shows a transaction e.g
  0 = > {A,B,C}
  1 = > {A,C}
  2 = > {A,D}
  3 = > {B, E, F}
  */
  $apriori_associator->train($training_data);
 
  /*
  Predict Method mau be called like following
  $prediction = $apriori_associator->predict("I1");     //Will Return Predictions for A after satisfying support and confidence parameter, incase of multiple predictions an N-Dimensional Array will be returned
  $prediction = $apriori_associator->predict(["I1"]);  // Same as above but passed in as array
  $prediction = $apriori_associator->predict(["I1" , "I2"]); // Will Return all those predictions, that include A,B
  $prediction = $apriori_associator->predict([["I1" , "I2"] , ["I3"]]); //it can also be used to make multiple predictions at once, by passing in multiple arrays at once, all the predictions will be returned in the similar manner e.g in multiple arrays
  */
  $prediction = $apriori_associator->predict(["I5"]); //Will Return [["I1"] , ["I2"]]

  echo "<br><br> <b style=\"color:green;font-size:2rem;\"> Prediction is </b> <br><br>";
  
  echo '<pre>';
  print_r($prediction);
  echo '</pre>';

?>