<?php

namespace Format_Helpers;

function formatDatestamp ($datestamp, $precision="") {
  if (!preg_match('/(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)/',$datestamp,$m)
    and !preg_match('/(\d\d\d\d)\-(\d\d)\-(\d\d) (\d\d):(\d\d):(\d\d)/',$datestamp,$m))
	return "(unknown format) $datestamp";
  $mon = $m[2]+0; # converts from a string to omit leading zeros
  $day = $m[3]+0;
  switch ($precision){
	  case "Year": 
		$out = $m[1];
		break;
	  case "Month": 
		$out = "$mon/$m[1]";
		break;
	  case "Day": 
		$out = "$mon/$day/$m[1]";
		break;
	  default:
	    $out = "$mon/$day/$m[1] ".$m[4].':'.$m[5].':'.$m[6].' UTC';
  }
  return $out;
}

?>
