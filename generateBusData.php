<?php
	ini_set("memory_limit","150M");
	include 'lib/simple_html_dom.php';
	include 'lib/gPoint.php';
	
	define("RADIUS_EARTH",6371);
	
	//Get the bus name with lineID.
	function getDescriptionNameByLineID($nameLine){
		$allDescriptions = array(
			"L80" => "Barcelona - Gavà",
			"L81" => "Barcelona - Gavà",
			"L82" => "L'Hospitalet - Gavà",
			"L85" => "L'Hospitalet - Gavà",
			"L86" => "Barcelona - Viladecans",
			"L87" => "Barcelona - Viladecans",
			"L88" => "Viladecans RENFE - Sant Climent",
			"L94" => "Barcelona - Les Botigues de Sitges",
			"L95" => "Barcelona - Castelldefels",
			"L96" => "Castelldefels - Sant Boi",
			"L97" => "Barcelona - Castelldefels",
			"L99" => "Castelldefels - Aeroport T1",
			"VB1" => "Viladecans RENFE - Can Guardiola",
			"VB2" => "Viladecans RENFE - Can Palmer",
			"N14" => "Barcelona - Castelldefels",
			"N16" => "Barcelona - Castelldefels"
		);
		return $allDescriptions[$nameLine];
	}
	
	//Generate BusMarkers from xml files.
	function generateBusMarkers($lineIDS,$cities,$markers){
		foreach($lineIDS as $lineID){
			$xmlString = file_get_contents("http://www.ambmobilitat.cat/Principales/GeneradorLineas.aspx?LIN_Id=".$lineID."&VER_Id=1&ida=&Color=0158c3&Idi=3&Correspondencias=true");
			$xmlContent = simplexml_load_string($xmlString);
			foreach($xmlContent->children() as $name => $marker){
				if($name == "marker"){
					//obtain the basic data of the marker.
					$par_id = strval($marker["parId"]);
					$lat = strval($marker["lat"]);
					$lng = strval($marker["lng"]);
					$isReturn = strval($marker["tipoLinea"]);
					$htmlContent = $marker["html"];
					$htmlMarker = simplexml_load_string($htmlContent);
					$htmlMarkerChildren = $htmlMarker->children();
					$nameBusMarker = strval($htmlMarkerChildren[1]);
					//$directionBusMarker = strval($htmlMarkerChildren[2]);
					$cityBusMarker = ucfirst(mb_strtolower(trim($htmlMarkerChildren[3]),'UTF-8'));
					$cityExists = in_array($cityBusMarker,$cities);
					//Verify if the current city exists in Global Vector.
					if(!$cityExists){
						array_push($cities,$cityBusMarker);
						$indexOfCity = count($cities)-1;
					}
					else {
						$indexOfCity = array_search($cityBusMarker,$cities);
					}		
					$stringCity = strval($indexOfCity);	
					
					//Obtains the lines with the bus collided.
					$lines = $htmlMarkerChildren[5];
					$ulLines = $lines->children();
					if(count($ulLines) < 0) continue;
					$ulLines = $ulLines[0];
					$correspondLines = array();
					foreach($ulLines as $li){
						$a = $li->children();
						$a = $a[0];
						array_push($a,$correspondLines);
					}
					
					$currentMarker = array("par_id"=>$par_id,"lat"=>$lat,"lng"=>$lng,"tipoLinea"=>$isReturn,"name"=>$nameBusMarker,"city"=>$stringCity,"lines"=>$correspondLines);
					
					$markerExists = in_array($markers,$currentMarker);
					
					if($isReturn == "I"){
						//Verify if the current marker exists in Global Vector.
						if(!$markerExists){
							array_push($markers,$busMarkers);
						}
					}
					else if($isReturn == "V"){
						//Verify if the current marker exists in Global Vector.
						if(!$markerExists){
							array_push($markers,$busMarkers);
						}
					}
				}
			}
		}
		return $busMarkers;
	}
	
	//generate busMarkers from manual CSV data.
	function generateCSVMarkers($nameFile){
		$errLat = 5 * 1e-6;
		$errLng = 3 * 1e-6;
		$csvContent = file_get_contents($nameFile);
		$csvContent = explode("\n",$csvContent);
		$busMarkers = array();
		$busLinesWithMarkers = array();
		foreach($csvContent as $line){
			$lineArray = explode(";",$line);
			$city = ucfirst(mb_strtolower(trim($lineArray[0]),'UTF-8'));
			$codeID = strval($lineArray[2]);
			$name = ucfirst(mb_strtolower(trim($lineArray[3]),'UTF-8'));
			$utmX = $lineArray[4];
			$utmY = $lineArray[5];
			$busName = $lineArray[6];
			$busDirection = trim($lineArray[7]);
			
			$gPoint = new gPoint('WGS 84');
			$gPoint->setUTM($utmX,$utmY,"31T"); 
			$gPoint->convertTMtoLL();	
			$busMarker = array(
			"city" => $city,
			"name" => $name,
			"lat" => strval($gPoint->lat + $errLat),
			"lng" => strval($gPoint->long  + $errLng),
			"codeID" => $codeID
			);
			$markerExists = isset($busMarkers[$codeID]);
			if(!$markerExists){
				$busMarkers[$codeID] = array(
					"busLines" => array(array("name" => $busName , "directions" => array($busDirection))),
					"marker" => $busMarker);
			}
			else {
				array_push($busMarkers[$codeID]["busLines"],array("name" => $busName , "directions" => array($busDirection)));
			}
			$busNameExists = isset($busLinesWithMarkers[$busName]);
			if(!$busNameExists){
				$busLinesWithMarkers[$busName] = array();
			}
			array_push($busLinesWithMarkers[$busName],$codeID);
		}
		$jsonContent = json_encode(array("busMarkers" => $busMarkers,"busLinesWithMarkers" => $busLinesWithMarkers));
		file_put_contents("content/markers.json",$jsonContent);	
		echo "busMarkers: ".count($busMarkers)."\n";
	}
	
	
	//Generate busStops from content.
	function generateBusStops($departure,$stops,$cities){
		$currentStops = array();
		//Calculate busStops for this busLine.
		$busStops = $departure->children(3);
		$busStops = $busStops->children(1); //tbody
		//Foreach tr...
		foreach($busStops->children() as $tr){
			$cityBusStop = ucfirst(mb_strtolower(trim($tr->children(0)->innertext),'UTF-8'));
			$nameBusStop = trim($tr->children(2)->first_child()->innertext);
			$cityExists = in_array($cityBusStop,$cities);
			//Verify if the current city exists in Global Vector.
			if(!$cityExists){
				array_push($cities,$cityBusStop);
				$indexOfCity = count($cities)-1;
			}
			else {
				$indexOfCity = array_search($cityBusStop,$cities);
			}	
			$stringCity = strval($indexOfCity);
			$stop = array("name"=>$nameBusStop,"city"=>$stringCity);
			//Verify if the current stop exists in Global Vector.
			$stopExists = in_array($stop,$stops);
			if(!$stopExists){
				array_push($stops,$stop);
				$indexOfStop = count($stops)-1;
			}
			else {
				$indexOfStop = array_search($stop,$stops);
			}
			array_push($currentStops,strval($indexOfStop));
		
		}
		return $currentStops;
	}
	
	//Check if text contains a hour in the first word.
	function containsHour($text){
		$words = explode(" ", $text);
		$posibleHour = $words[0];
		$isHour = preg_match('/(?:[01][0-9]|2[0-4]|[0-9]):[0-5][0-9]/',$posibleHour);
		return ($isHour == 1);
	}
	
	//Generate timetable from 1 bus and 1 direction.
	function generateBusDirectionTimetable($lineID,$nameStopRound){
		$ddArray = array();
		$versionsArray = array(1,4,6,3,2); //sort by relevance
		for($i=0;$i<count($versionsArray);$i++){
			$html = file_get_html("http://www.ambmobilitat.cat/Principales/HorariosParada.aspx?linea=".$lineID."&amp;version=".$versionsArray[$i]."&amp;punto=".	$nameStopRound);
			$scheduleContenedor = $html->getElementById('tira')->first_child();
			if($scheduleContenedor == NULL){
				$html->clear();
				unset($html);
				if($i == count($versionsArray)-1)	return array();
			}
			else break;
		}
		
		$pos = 0;
		foreach($scheduleContenedor->children() as $dl){
			$hoursDescription = "";
			$hoursArray = array();
			$pos = 0;
			foreach($dl->children() as $dd){
				if($dd->innertext != ""){
					if($pos == 0){
						$hoursDescription = $dd->innertext;
						//echo "Description : ".$hoursDescription."\n";
					}
					else if(strlen ($dd->innertext)<7){
						array_push($hoursArray, $dd->innertext);
						//echo "Hour : ".$dd->innertext."\n";
					}
				}
				$pos++;
				
			}
			if(count($hoursArray)>0 && $hoursDescription != ""){
				array_push($ddArray, array("description" => $hoursDescription,"hours" => $hoursArray));
				echo "Introduit horari ".$hoursDescription."…\n";
			}
		}
		$html->clear();
		unset($html);
		
		return $ddArray;
	}
	
	//Generate timetable for 1 bus.
	function generateBusTimetable($lineID){
		$timetable = array();
		$html = file_get_html("http://www.ambmobilitat.cat/Principales/TiraLinea.aspx?linea=".$lineID."&horarios=true");
		$nameStopRound = $html->getElementById('ddlParadasIda')->children(0)->value;
		$nameStopReturn = $html->getElementById('ddlParadasVuelta')->children(0)->value;		
		$scheduleRound = generateBusDirectionTimetable($lineID,$nameStopRound);
		$scheduleReturn = generateBusDirectionTimetable($lineID,$nameStopReturn);
		$html->clear();
		unset($html);
		return array("round"=>$scheduleRound,"return"=>$scheduleReturn);
	}
	
	//generate all data from 1 bus.
	function generateBusData($lineID,$stops,$cities,$markers){
		$html = file_get_html("http://www.ambmobilitat.cat/Principales/TiraLinea.aspx?linea=".$lineID);
		$departure = $html->getElementById('panelTiraIda');
		$nameBusLine = $departure->first_child();
		$nameBusLine = $nameBusLine->first_child();
		$arrival = $html->getElementById('panelTiraVuelta');

		//Calculate name of line.
		$nameLine = $nameBusLine->first_child()->first_child()->innertext;
		echo "Loading ".$nameLine;		
		//Calculate origin and end name of the busLine.
		$nameBusLine = $nameBusLine->children(1);
		//$originName = $nameBusLine->children(1)->first_child()->innertext;
		//$endName = $nameBusLine->children(2)->first_child()->innertext;
		$description = getDescriptionNameByLineID($nameLine);

		//Calculate all busStop for this busLine.
		$currentBusStopsRound = array(); //contains the stops for this busLine.
		$currentBusStopsReturn = array(); //contains the stops for this busLine.
		$currentBusStopsRound = generateBusStops($departure,&$stops,&$cities);
		if($arrival){
			$currentBusStopsReturn = generateBusStops($arrival,&$stops,&$cities);
		}
		
		echo ".";
		
		//Calculate markers for this busline...
		//$currentBusMarkers = generateBusMarkers($lineID,&$cities,&$markers);
		echo ".";
		//Calculate timetable for this busLine...
		$timetableLine = generateBusTimetable($lineID);
		
		echo ".";
		
		$line = array("numLine"=>$nameLine,"description"=>$description,"busStopsRound"=>$currentBusStopsRound,"busStopsReturn"=>$currentBusStopsReturn,"timetable"=>$timetableLine);//,"markersRound"=>$currentBusMarkers["round"],"markersReturn"=>$currentBusMarkers["return"]);
		
		$html->clear(); 
		unset($html);
		
		echo "\n";
		
		return $line;
	}
	
	//return a set of all lineIDs.
	function getAllNumberLines(){
		$html = file_get_html("http://www.ambmobilitat.cat/Principales/BusquedaLinea.aspx");
		$selectNumberLines = $html->getElementById('ddlLineas');
		$numberLines = array();
		foreach($selectNumberLines->children() as $sl){
			$numberLine = $sl->value;
			if($numberLine != -1){
				array_push($numberLines,$numberLine);
			}
		}
		$html-clear();
		unset($html);
		return $numberLines;
	}
	
	//generate all information about the selected lineIDs.
	function generateAllBusData($nameFile){
		//$markers = array(); //contains all markers
		$stops = array(); //contains all stops
		$cities = array(); //contains all cities (Cornella and Gava does not work correctly.)
		$lines = array(202,203,204,206,207,208,209,210,211,212,213,268,264,265,221,223);//getAllNumberLines();
		$busLines = array(); //contains all busLines
		
		foreach($lines as $lineID){
			$busLine = generateBusData($lineID,&$stops,&$cities,&$markers);
			array_push($busLines,$busLine);
		}
		
		$JSONGlobalcontent = json_encode(array("stops"=>$stops,"cities"=>$cities,"lines"=>$busLines));		
		file_put_contents($nameFile, $JSONGlobalcontent);
	}
	
	//Return all bus Data
	function getAllBusData($nameFile,$fileID=""){
		$fileVersionID = md5_file($nameFile);
		if($fileVersionID == $fileID){
			echo json_encode(array("Update" => "OK"));
		}
		else {
			$JSONBusDatacontent = file_get_contents($nameFile);
			echo $JSONBusDatacontent;
		}
	}
	
	//Return the markers around a selected radius with an initial point.
	function calculateMarkersAroundRadius($initialPointLat,$initialPointLng,$radius,$nameFile){
		$initLat = deg2rad(floatval($initialPointLat));
		$initLng = deg2rad(floatval($initialPointLng));
		$selectedMarkers = array();	
		$JSONMarkerContent = file_get_contents($nameFile);
		$busMarkers = json_decode($JSONMarkerContent, true);
		foreach($busMarkers["busMarkers"] as $busMarker){
			$lat =  deg2rad($busMarker["marker"]["lat"]);
			$lng =  deg2rad($busMarker["marker"]["lng"]);
			//Calulate Distance between initial point and the current point
			$distance = RADIUS_EARTH * acos(cos($lat)*cos($initLat)* cos($initLng - $lng) + sin($lat) * sin($initLat));
			if($distance < $radius){
				array_push($selectedMarkers,$busMarker);
			}
		}
		$JSONMarkerContent = json_encode($selectedMarkers);
		echo $JSONMarkerContent;
	}
	
	//Return the markers that contains a specific busLine.
	function calculateMarkersWithBusLine($busLine,$nameFile){
		$selectedMarkers = array();	
		$JSONMarkerContent = file_get_contents($nameFile);
		$busMarkers = json_decode($JSONMarkerContent, true);
		if(isset($busMarkers["busLinesWithMarkers"][$busLine])){
			foreach($busMarkers["busLinesWithMarkers"][$busLine] as $busStopCode){
				if(isset($busMarkers["busMarkers"][$busStopCode])){
					$busStopData = $busMarkers["busMarkers"][$busStopCode];
					array_push($selectedMarkers,$busStopData);
				}
			}
		}
		$JSONMarkerContent = json_encode($selectedMarkers);
		echo $JSONMarkerContent;				
	}
	//---------USE CASE-----------
	//Generate the Markers with the CSV data.
	//generateCSVMarkers("content/markersContent.csv");
	//calculateMarkersAroundRadius("41.376765466263","2.1520424580862","0.4","content/markers.json","content/selectedMarkers.json");
	//Generate all bus data except markers.
	//generateAllBusData("content/global.json");
	
?>