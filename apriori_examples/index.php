
<?php
  
  include "../Apriori.php";



    //Load Relevant Json data-sets
    $training_dataset["simple_1"]      =  array_values(json_decode(file_get_contents("../data/apriori_datasets/proof_dataset_1.json"), true));
    $training_dataset["simple_2"]      =  array_values(json_decode(file_get_contents("../data/apriori_datasets/proof_dataset_2.json"), true));
    $training_dataset["fruits"]        =  array_values(json_decode(file_get_contents("../data/apriori_datasets/retail_dataset.json"), true));
    
    //default params 
    $default_params =  [
        "simple_1" => [ "support_percentage" => 0.5   , "confidence_percentage" => 0.4],
        "simple_2" => [ "support_percentage" => 0.25  , "confidence_percentage" => 0.2],
        "fruits"   => [ "support_percentage" => 0.17  , "confidence_percentage" => 0.3]
    ];

    //Parameter Array
    $parameters = [];
    $prediction_text = null;
    
    $parameters["target_dataset"] = null;
    $parameters["query_set_string"] = null;

   //Extract parameters from HTTP POST
    foreach ($parameters as $key => $value) {
        $parameters[$key] =  isset($_POST[$key]) ? $_POST[$key] : $value;
    }

    //Call Early Return if No Query Provided
    if ( $parameters["query_set_string"] == null ){

        $prediction_text = "<h3 style=\"color:blue\"> No Query Attributes/Set Provided ! </h3> ";

    }else{

    //Sanitize and normalize the query
    $query_arr = explode(",", trim($parameters["query_set_string"]));
    $parameters["sanitized_query"] = [];
    foreach ($query_arr as $Q) {
        array_push($parameters["sanitized_query"] , trim($Q));
    }

    //Inject Defalt Parameters    
    $parameters["support_percentage"] = $default_params[$parameters["target_dataset"]]["support_percentage"];
    $parameters["confidence_percentage"] = $default_params[$parameters["target_dataset"]]["confidence_percentage"];
    
   
    //Initialize Main Apriori Algorithm Class With Required Required support and confidence percentage, for all 3 datasets
     $apriori_associator =  new Apriori( $parameters["support_percentage"] , $parameters["confidence_percentage"]);

     //init train
     $apriori_associator->train( $training_dataset[$parameters["target_dataset"]]);
     
     //Make a prediction
     $predictions =  $apriori_associator->predict($parameters["sanitized_query"]);
    
     $prediction_text = "<br><b>Predictions are</b> <br>";
     $prediction_text .= "<table>";
     /*
      Predictions contains multi-dimensional array structure, Since it can return multiple predictions against multiple queries
      The First Array contains Query-Independenct Predictions, in other words at each index there is a seperate prediction structure related to a particular query
        The Second Level of arrays are the predictions for a particular Query, there can be multiple predictions against a query hence a array is present at this level
          Finally Second Level of arrays contains another array at each index which is made up of the actual prediction set
     */
     $index  = 1;
     foreach ($predictions[0] as  $prediction_array_set) { 
         $local_prediction_text = " ";
         foreach ($prediction_array_set as $prediction_array_set_element) {
            $local_prediction_text .= " <td> $prediction_array_set_element</td>";
        }
        // $filtered =  substr( $local_prediction_text , 0  ,  strlen($local_prediction_text) -1 );
        $prediction_text .= "<tr> <td class='index'>($index)</td>  $local_prediction_text </tr>";
        $index++;
     }

     $prediction_text .= "</table>";

    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apriori UI</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <h1 id="heading">Apriori Algorithm Exercise</h1>

   
   <form action="./index.php" method="post">
  
    <label for="target_dataset">Please select a dataset :</label>
    <select name="target_dataset">
        <option value="simple_1">A,B,C Dataset</option>
        <option value="simple_2">I1,I2,I3 Dataset</option>
        <option value="fruits">Fruits Dataset</option>
    </select>

    <h4>Please Provide a comma delimeted Query related to the dataset e.g <span style="color: brown; font-size: 1.1rem;"> "cat,dog,cammel"</span> </h4>
    <h4>Query is case sensitive</h4>

    <label for="query_set_string"> Query words </label>
    <input type="text" name="query_set_string"/>
    <br>
    <button name="submit">Get Predictions</button>
   </form>
    

       <?php
          if( $parameters["target_dataset"] != null){ //Prediction Request was made, render result
                echo $prediction_text;
          }
       
       ?>

</body>
</html>
