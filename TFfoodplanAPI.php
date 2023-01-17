<?php

// example request: TFfoodplanAPI.php?fields[]=dessertSales&fields[]=garnishSales&fields[ignore]=true&dataFromTime=now&dataCount=2&dataMode=days&dateFormat=j/n/Y

// parameters for request:
    // name             type                    example
    // fields:          Array                   ?fields[]=cookteam&fields[]=mainDish
    // fields[ignore]   Bool                    ?fields[ignore]=true
    // dateFormat       php date format string  ?dateFormat=j/n/Y
    // dataFromTime     (php) time string       ?dataFromTime=now || ?dataFromTime=1.1.2019
    // dataMode         'days' || 'weeks'       ?dataMode=days
    // dataCount        int                     ?dataCount=2
//end
error_reporting(E_ERROR | E_PARSE);
define('TFf_BASEPATH', "./");
require("./TFfoodplanParser.php");

$fields = array();
$dateFormat = "j\.n\.Y";
$dataCount = -1;
$dataFromTime = "now";
$dataMode = "weeks";

if(isset($_GET['fields'])){
    $fields = $_GET['fields'];
}

if(isset($_GET['dateFormat'])){
    $dateFormat = $_GET['dateFormat'];
}

if(isset($_GET['dataFromTime'])){
    $dataFromTime = $_GET['dataFromTime'];
}

if(isset($_GET['dataMode'])){
    $dataMode = $_GET['dataMode'];
}

if(isset($_GET['dataCount'])){
    $dataCount = $_GET['dataCount'];
}

$filters = array( "fields"=>$fields, "data"=>array("fromTime"=>strtotime($dataFromTime), "mode"=>$dataMode, "count"=>$dataCount));

$TFfoodplanParser = new TFfoodplanParser('../current.xlsx', 'Essensplan', 'Xlsx');
$TFfoodplanParser->setFilters($filters);
$TFfoodplanParser->parse();
$foodplanDays = $TFfoodplanParser->getData($dateFormat);

header('Access-Control-Allow-Origin: *');
header("Content-Type: application/json");

// convert the object to json and return it
die(json_encode($foodplanDays));



?>
