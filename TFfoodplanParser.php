<?php

require TFf_BASEPATH.'/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

class TFfoodplanParser {

	private $foodplanDays = array();
	private $dataStructure;
	private $sheetData;
	private $filters = array("fields"=>array(), "data"=>array("fromTime"=>0, "mode"=>"weeks", "count"=>-1));

	public function __construct($inputFileName = 'essensplan_2018_2019.xlsx', $sheetname = 'Essensplan 2018-2019', $inputFileType = 'Xlsx', $dataStructure = null){

		// construct the sheet reader
		$reader = IOFactory::createReader($inputFileType);

		// load only one sheet
		$reader->setLoadSheetsOnly($sheetname);

		// load the document
		$spreadsheet = $reader->load($inputFileName);

		// get the sheet as an array
		$this->sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

		if($dataStructure === null){
			// if the data structure is not set -> set it to the default data structure
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
		}

		$this->dataStructure = $dataStructure;
	}

	public function getData($dateFormat = "n\/j\/Y"){
		
		// set all the dates to the wanted format
		for ($i = 0; $i < count($this->foodplanDays); $i++) {
			if(isset($this->foodplanDays[$i]["date"])){
				$this->foodplanDays[$i]["date"] = date($dateFormat, strtotime($this->foodplanDays[$i]["date"] . " 23:59"));
			}
		}

		return($this->foodplanDays);
	}

	public function setFilters($filters = array( "fields"=>NULL, "data"=>NULL)){

		if(count($filters['fields'])>0 && ($filters['fields']['ignore'])){
			// if the fields filter is set to ignore -> get all available fields and substract to get all not ignored fields
			$filters['fields'] = array_diff($this->getAvailableFields(), $filters['fields']);
		}
		
		// set the filters
		$this->filters = $filters;

	}

	public function getAvailableFields(){

		$availableFields = array();

		// go through the data structure and get all fields
		foreach ($this->dataStructure as $dataSet) {
			array_push($availableFields, $dataSet["name"]);
		}

		return($availableFields);
	}

	private function applyDataFilters($data){

		$tmpFoodplanDays = array();
		$prevIndex = 0;
		$dataSetCount = 0;

		for ($i = 0; $i < count($data); $i++) {
			// add all days, which are stll in the future
	
			$foodplanDay = $data[$i];
	
			// get the date of the foodplanDay
			$foodplanDate = strtotime($foodplanDay["date"] . " 23:59");
	
			// get the date of now
			$currentDate = $this->filters["data"]["fromTime"];

			if($this->filters["data"]["mode"] === "days"){
				// put together a number that starts with the year and ends with the day (with leading zero's) of the current foodplanDay
				$foodplanIndex = date("Y", $foodplanDate) . str_pad(date("z", $foodplanDate), 3, '0', STR_PAD_LEFT);
			
				// put together a number that starts with the year and ends with the day (with leading zero's) of today
				$currentIndex = date("Y", $currentDate) . str_pad(date("z", $currentDate), 3, '0', STR_PAD_LEFT);
			}
			else if($this->filters["data"]["mode"] === "weeks"){
				// look up ^
				$foodplanIndex = date("YW", $foodplanDate);
				// look up ^
				$currentIndex = date("YW", $currentDate);
			}
			
			// subtract the two numbers (negative result -> day is already over!)
			$indexDelta = $foodplanIndex - $currentIndex;
	
			if ($indexDelta >= 0) {
				// if the day is not over yet
				if($foodplanIndex !== $prevIndex){
					// if the current index does not equal the previous index -> the current day does not belong to the same week as the previous one
					// -> increase dataSetCount
					$dataSetCount ++;
				}

				if($dataSetCount <= $this->filters['data']['count'] || $this->filters['data']['count'] === -1){
					// if the data set count has not exceeded the wanted count -> add the current day to the final list
					array_push($tmpFoodplanDays, $foodplanDay);
				}
				else {
					// else -> we got enough data -> break
					break;
				}
			}

			$prevIndex = $foodplanIndex;
		}

		return($tmpFoodplanDays);
	}

	public function parse(){

		$sheetData = $this->sheetData;

		for ($currentSheetRow = 0; $currentSheetRow < count($sheetData); $currentSheetRow++) {
			$sheetRow = $sheetData[$currentSheetRow];
		
			// go through all rows and search for one that starts with 'Datum' (that is the first row and respresents the 'date' role)
		
			if ($sheetRow[$this->dataStructure[0]["keyCol"]] == $this->dataStructure[0]["key"]) {
				// when the first key of the data struct was found -> we found a week
				
				$currentWeek = array();
		
				foreach($this->dataStructure as $dataSet){
					// go through all data sets

					if(count($this->filters["fields"]) === 0 || in_array($dataSet["name"], $this->filters["fields"])){
						// if there are no field filters acive or if the current data set is in the active filers

						for($dayIndex = 0; $dayIndex < count($dataSet["dataCols"]); $dayIndex++){
							// go through all data cols of the data set
							if(!isset($currentWeek[$dayIndex])){
								// if the current day does not exist in the current week -> create it
								$currentWeek[$dayIndex] = array();
							}
							
							// create the name of the current data set in the current day
							$currentWeek[$dayIndex][$dataSet["name"]] = 
							// and set it to the sheet row index of the first data set plus the index of the current data set and the current data column
							$sheetData[$currentSheetRow+$dataSet["dataIndex"]][$dataSet["dataCols"][$dayIndex]];
						}
					}
				}
		
				foreach($currentWeek as $day){
					array_push($this->foodplanDays, $day);
				}
			}
		}

		// apply the time range filters to the data
		$this->foodplanDays = $this->applyDataFilters($this->foodplanDays);

		/*
			$foodplanDays now looks like this:
			[
			["date"=>"<some date>", "cookteam"=>"<some cookteam>", ...],
			[...]
			]
		*/

	}

	function printHTMLTable($TFfoodplanParser = null, $dateFormat = "j\.n\.Y", $daysPerWeek = 4, $weekCount = 2, $fields = array("cookteam", "date", "mainDish", "mainDishVeg", "garnish", "dessert"), $highlightedCount = 2){
		// function to get tables with food
	
		// if no parser is given -> create a default one
		if(!isset($TFfoodplanParser)){
			$TFfoodplanParser = $this;
		}
	
		// apply filters
		$TFfoodplanParser->setFilters(array( "fields"=>$fields, "data"=>array("fromTime"=>strtotime("now"), "mode"=>"weeks", "count"=>$weekCount)));
		$TFfoodplanParser->parse();
		// get data with given date format
		$foodplanDays = $TFfoodplanParser->getData($dateFormat);
	
		for($week = 0; $week < $weekCount; $week ++){
			// go through all weeks and create an html <table> for all of them
			echo "<table class=\"speiseplan table\">\n    <tbody>\n        <tr style=\"border: 1px solid #999;\" align=\"center\" valign=\"top\">\n";
	
			for ($day = 0; $day < $daysPerWeek; $day++) {
	
				// go through all days and creat an html <td> for all of them
				echo "            <td width=\"25%\">\n";
	
				for($field = 0; $field < count($fields); $field ++){
					// go through all fields and add them all
	
					// get the value of the current field
					$fieldValue = $foodplanDays[$day + ($week * $daysPerWeek)][$fields[$field]];
	
					if($field < $highlightedCount) {
						// highlited field
						if($field === $highlightedCount-1){
							// if this line is the last highlited one -> add line
							echo "                <strong>" . $fieldValue . "</strong><hr />\n";
						}
						else {
							echo "                <strong>" . $fieldValue . "</strong><br />\n";
						}
					}
					else {
						// normal field
						echo "                " . $fieldValue . "<hr />\n";
					}
				}
				
				// end of day
				echo "            </td>\n";
	
			}
			
			// end of week
			echo "        </tr>\n    </tbody>\n</table>\n<p>&nbsp;</p>\n";
	
		}
	}
}

?>