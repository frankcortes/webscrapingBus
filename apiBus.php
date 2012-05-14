<?php

require("generateBusData.php");

//Parser the current URI.
function URIParser($uriRequest){
	$folder = "content/";
	$uriArray = explode("/",$uriRequest);
	$command = $uriArray[3];
	switch($command){
		case 'refresh':
			//echo "refresh the busLines API.\n";
			generateAllBusData($folder."global.json");
			break;
		case 'getBusData':
			$fileID = $uriArray[4]; //md5sum if any 
			//echo "return all bus data.\n";
			getAllBusData($folder."global.json",$fileID);
			break;
		case 'generateMarker':
			//echo "generate Markers with CSV data.\n";
			generateCSVMarkers($folder."markersContent.csv");
			break;
		case 'markersWithRadius':
			$lat = $uriArray[4]; //"41.376765466263";
			$lng = $uriArray[5]; //"2.1520424580862";
			$radius = $uriArray[6]; //"0.4";
			$inputNameFile = "markers.json"; //"content/markers.json";
			calculateMarkersAroundRadius($lat,$lng,$radius,$folder.$inputNameFile);
			break;
		case 'markersWithBusLine':
			$numLine = $uriArray[4]; //N14
			echo $numLine;
			$inputNameFile = "markers.json"; //"content/markers.json";
			calculateMarkersWithBusLine($numLine,$folder.$inputNameFile);
			break;
		default:
			break;
	}
}

URIParser($_SERVER["REQUEST_URI"]);
?>