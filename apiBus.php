<?php

include "generateBusData.php";

//Parser the current URI.
function URIParser($uriRequest){
	$uriArray = explode("/",$uriRequest);
	$command = $uriArray[2];
	switch($command){
		case 'refresh':
			echo "refresh the busLines API.\n";
			break;
		case 'generateMarker':
			echo "generate Markers with CSV data.\n";
			break;
		case 'markersWithRadius':
			echo "Marker Round a selected radius.\n";
			break;
	}
}

URIParser($_SERVER["REQUEST_URI"]);
?>