<?php

require("generateBusData.php");

//Parser the current URI.
function URIParser($uriRequest){
	$folder = "content/";
	$uriArray = explode("/",$uriRequest);
	$command = $uriArray[2];
	switch($command){
		case 'refresh':
			//echo "refresh the busLines API.\n";
			generateAllBusData($folder."global.json");
			break;
		case 'generateMarker':
			//echo "generate Markers with CSV data.\n";
			generateCSVMarkers($folder."markersContent.csv");
			break;
		case 'markersWithRadius':
			//echo "Marker Round a selected radius.\n";
			$lat = $uriArray[3]; //"41.376765466263";
			$lng = $uriArray[4]; //"2.1520424580862";
			$radius = $uriArray[5]; //"0.4";
			$inputNameFile = "markers.json"; //"content/markers.json";
			calculateMarkersAroundRadius($lat,$lng,$radius,$folder.$inputNameFile);
			break;
	}
}

URIParser($_SERVER["REQUEST_URI"]);
?>