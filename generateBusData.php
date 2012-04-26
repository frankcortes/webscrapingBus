<?php
	include 'simple_html_dom.php';
	
	$is_gava = True;
	
	function getDescriptionNameByLineID($nameLine){
		$allDescriptions = array(
			"L80" => "Barcelona - Gavà",
			"L81" => "Barcelona - Gavà",
			"L82" => "L'Hospitalet - Gavà",
			"L85" => "L'Hospitalet - Gavà",
			"L86" => "Barcelona - Viladecans",
			"L87" => "Barcelona - Viladecans",
			"L88" => "Viladecans RENFE - Sant Climent",
			"L94" => "Barcelona - Castelldefels",
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
	
	function generateBusMarkers(){
		$html = file_get_html("http://www.ambmobilitat.cat/Principales/GeneradorLineas.aspx?LIN_Id=202&VER_Id=1&ida=&Color=0158c3&Idi=3&Correspondencias=true");
		$markers = $html->find('markers');
		foreach($markers->children() as $mk){
			$lat =  $mk->lat;
			$lng =  $mk->lng;
			echo "latitude: ".$lat." , longitude: ".$lng."\n";
		}
	}
	
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
	
	function containsHour($text){
		$words = explode(" ", $text);
		$posibleHour = $words[0];
		$isHour = preg_match('/(?:[01][0-9]|2[0-4]|[0-9]):[0-5][0-9]/',$posibleHour);
		return ($isHour == 1);
	}
	
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
	
	function generateBusData($lineID,$stops,$cities){
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
		echo ".";
		//Calculate all busStop for this busLine.
		$currentBusStopsRound = array(); //contains the stops for this busLine.
		$currentBusStopsReturn = array(); //contains the stops for this busLine.
		$currentBusStopsRound = generateBusStops($departure,&$stops,&$cities);
		if($arrival){
			$currentBusStopsReturn = generateBusStops($arrival,&$stops,&$cities);
		}
		
		echo ".";
		//Calculate timetable for this busLine...
		$timetableLine = generateBusTimetable($lineID);
		
		echo ".";
		
		$line = array("numLine"=>$nameLine,"description"=>$description,"busStopsRound"=>$currentBusStopsRound,"busStopsReturn"=>$currentBusStopsReturn,"timetable"=>$timetableLine);
		
		$html->clear(); 
		unset($html);
		
		echo "\n";
		
		return $line;
	}
	
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
	
	function generateAllBusData($nameFile){
		$stops = array(); //contains all stops
		$cities = array(); //contains all cities (Cornella and Gava does not work correctly.)
		$lines = array(202,203,204,206,207,208,209,210,211,212,213,268,264,265,221,223);//getAllNumberLines();
		$busLines = array(); //contains all busLines
		
		foreach($lines as $lineID){
			$busLine = generateBusData($lineID,&$stops,&$cities);
			array_push($busLines,$busLine);
		}
		
		$JSONGlobalcontent = json_encode(array("stops"=>$stops,"cities"=>$cities,"lines"=>$busLines));
		
		file_put_contents($nameFile, $JSONGlobalcontent);
	}
	
	generateBusMarkers();
	//generateBusDirectionTimetable(202,17352);
	//generateAllBusData("global.json");
	//generateBusTimetable(209);
?>