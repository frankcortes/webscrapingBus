<?php

include "generateBusData.php";

//Parser the current URI.
function URIParser($uriRequest){
	echo "uri Request: ".$uriRequest;
	
}

URIParser($_SERVER["REQUEST_URI"]);
?>