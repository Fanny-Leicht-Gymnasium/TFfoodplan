# ABOUT

This is some php code that parses the [.xlsx file containing the foodplan](http://www.treffpunkt-fanny.de/images/stories/dokumente/Essensplaene/current.xlsx) and outputs the data via a REST-API and as an HTML Table.  

# USAGE
## TFfoodplanAPI
### Location
[http://www.treffpunkt-fanny.de/images/stories/dokumente/Essensplaene/api/TFfoodplanAPI.php](http://www.treffpunkt-fanny.de/images/stories/dokumente/Essensplaene/api/TFfoodplanAPI.php)

### HTML Parameters
| name           | type                   | example                                      |
| ---------------| -----------------------|----------------------------------------------|
| fields:        | Array                  | ?fields[]=cookteam&fields[]=mainDish         |
| fields[ignore] | Bool                   | ?fields[ignore]=true                         |
| dateFormat     | php date format string | ?dateFormat=j/n/Y                            |
| dataFromTime   | (php) time string      | ?dataFromTime=now || ?dataFromTime=1.1.2019  |
| dataMode       | 'days' || 'weeks'      | ?dataMode=days                               |
| dataCount      | int                    | ?dataCount=2                                 |

### Example
Get the data for the next 10 days:  
[http://www.treffpunkt-fanny.de/images/stories/dokumente/Essensplaene/api/TFfoodplanAPI.php?dataCount=10&dataMode=days&dateFormat=U&dataFromTime=now](http://www.treffpunkt-fanny.de/images/stories/dokumente/Essensplaene/api/TFfoodplanAPI.php?dataCount=10&dataMode=days&dateFormat=U&dataFromTime=now)


## TFfoodplanHTML
This code can be used in a Joomla Article to make it display the foodplan table.  
It can be injected by using the [Sourcerer plugin for Joomla](https://extensions.joomla.org/extension/sourcerer/).  

```php
<?php
echo "<!-- -->";
define(TFf_BASEPATH, JPATH_BASE."/images/stories/dokumente/Essensplaene/api"); // THIS HAS TO BE ADJUSTED IN CASE THE LOCATION OF THE API FILES IS MODIFIED
require(TFf_BASEPATH."/TFfoodplanParser.php"); 
$TFfoodplanParser = new TFfoodplanParser(TFf_BASEPATH.'/../current.xlsx', 'Essensplan', 'Xlsx'); 

$dateFormat = "j\.n\.Y";    // the format of the date (php date format string)
$daysPerWeek = 4;           // days per table
$weekCount = 2;             // table count
$fields = array("cookteam", "date", "mainDish", "mainDishVeg", "garnish", "dessert");   // displayed fields
$highlightedCount = 2;      // highlited fields (from top)

TFfoodplanHTMLTable($TFfoodplanParser, $dateFormat, $daysPerWeek, $weekCount, $fields, $highlightedCount);
?>
```

## TFfoodplanParser
If the format of the .xlsx foofdplan file changes, the parser can be adjusted like this:  

```php
<?php
require("/path/to/TFfoodplanParser.php");

// array containing all filters
// if "ignore" is set to true -> given values will not be shown
// "count" is the data set count (weeks or days)
$filters = array( "fields"=>array("ignore"=>true, "dataKey1", "dataKey2"), "data"=>array("fromTime"=>strtotime("time sting(e.g. 'now')"), "mode"=>"data filter mode ('days' or 'weeks')", "count"=>2));

// example for a data structure (it describes how the data is structured inside the table)
$dataCols = array("B", "D", "F", "H");
$salesCols = array("C", "E", "G", "I");

$dataStructure = array (
	array("key"=>"Datum", "dataCols"=>$dataCols, "keyCol"=>"A", "dataIndex"=>0, "name"=>"date"),
	
	array("key"=>"Team", "dataCols"=>$dataCols, "keyCol"=>"A", "dataIndex"=>1, "name"=>"cookteam"),
	array("key"=>"Team", "dataCols"=>$salesCols, "keyCol"=>"A", "dataIndex"=>1, "name"=>"allSales"),

	array("key"=>"Hauptgang", "dataCols"=>$dataCols, "keyCol"=>"A", "dataIndex"=>2, "name"=>"mainDish"),
	array("key"=>"Hauptgang", "dataCols"=>$salesCols, "keyCol"=>"A", "dataIndex"=>2, "name"=>"mainDishSales"),

	array("key"=>"vegetarisch", "dataCols"=>$dataCols, "keyCol"=>"A", "dataIndex"=>3, "name"=>"mainDishVeg"),
	array("key"=>"vegetarisch", "dataCols"=>$salesCols, "keyCol"=>"A", "dataIndex"=>3, "name"=>"mainDishVegSales"),

	array("key"=>"Salat", "dataCols"=>$dataCols, "keyCol"=>"A", "dataIndex"=>4, "name"=>"garnish"),
	array("key"=>"Salat", "dataCols"=>$salesCols, "keyCol"=>"A", "dataIndex"=>4, "name"=>"garnishSales"),

	array("key"=>"Nachtisch", "dataCols"=>$dataCols, "keyCol"=>"A", "dataIndex"=>5, "name"=>"dessert"),
	array("key"=>"Nachtisch", "dataCols"=>$salesCols, "keyCol"=>"A", "dataIndex"=>5, "name"=>"dessertSales"),

	array("key"=>"Bemerkung", "dataCols"=>$dataCols, "keyCol"=>"A", "dataIndex"=>6, "name"=>"note")
);

// construct the parser (the values in this example are the default ones -> you could run it without any and your achive the same result)
$TFfoodplanParser = new TFfoodplanParser('essensplan_2018_2019.xlsx', 'Essensplan 2018-2019', 'Xlsx', $dataStructure);
// apply the filters
$TFfoodplanParser->setFilters($filters);
// parse the table
$TFfoodplanParser->parse();
// get the parsed data as an array with the date in the given format
$foodplanDays = $TFfoodplanParser->getData("j/n/Y");

/*
	$foodplanDays now looks like this:
	[
	["date"=>"<some date>", "cookteam"=>"<some cookteam>", ...],
	[...]
	]
*/
?>
```
