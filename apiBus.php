<?php

include "generateBusData.php";

//Parser the current URI.
function URIParser($uriRequest){
	$uriArray = explode("/",$uriRequest);
	$command = $uriArray[2];
	switch($command){
		case 'refresh':
			echo "refresh the busLines API.\n";
			generateAllBusData("content/global.json");
			break;
		case 'generateMarker':
			echo "generate Markers with CSV data.\n";
			generateCSVMarkers("content/markersContent.csv");
			break;
		case 'markersWithRadius':
			echo "Marker Round a selected radius.\n";
			$lat = "41.376765466263";//$uriArray[3];
			$lng = "2.1520424580862";//$uriArray[4];
			$radius = "0.4";//$uriArray[5];
			$inputNameFile = "content/markers.json"//$uriArray[6];
			$outputNameFile = "content/selectedMarkers.json"//$uriArray[7];
			calculateMarkersAroundRadius($lat,$lng,$radius,$inputNameFile,$outputNameFile);
			break;
	}
}

URIParser($_SERVER["REQUEST_URI"]);
?>