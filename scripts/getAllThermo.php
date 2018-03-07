#!/usr/bin/env php
<?php
	include_once("/var/lib/homegear/scripts/Connect.php");

function usage(){
	echo "  --help|--info\n";
}

function getAllData($Client){	

	$data = $Client->send("listDevices", array());
        // print_r($data);

	foreach ( $data as $item ){
		if( (empty($item["PARENT"])) && ($item["TYPE"] == "HM-CC-TC")){
			echo "------------------------------" . "\n";
			echo "Id ....... : " . $item["ID"] . "\n";
			echo "Address .. : " . $item["ADDRESS"] . "\n";
			echo "Type ..... : " . $item["TYPE"]  . "\n";
			echo "Firmware . : " . $item["FIRMWARE"]  . "\n";

			$deviceInfo = $Client->send("getDeviceInfo", array($item["ID"]));
	                echo "Name ..... : " . $deviceInfo["NAME"]. "\n";

			$channel=2;
			$paramSet = $Client->send("getParamset", array($item["ID"],$channel, "MASTER"));

			
			$days = array("MONDAY", "TUESDAY", "WEDNESDAY", "THURSDAY", "FRIDAY", "SATURDAY", "SUNDAY") ;
			foreach ($days as $day){

				$last = false;
				for ($i=1; $i <25; $i++){
					if ( $last) {
						break;
					}
					$keyTime = "TIMEOUT_" . $day . "_" . $i;
					$keyTemp = "TEMPERATUR_" . $day ."_" . $i;
					$valueTime =  $paramSet[$keyTime] ;
					$valueTemp =  $paramSet[$keyTemp] ;
					if ($valueTime == 1440) {
						$last = true;
					}
					$time = date("H:i", mktime(0,$valueTime));
					printf ("%-10s => %5s -> %2.1f\n", $day, $time , $valueTemp);
				}
			}
		}

		/*$deviceInfo = $Client->send("getDeviceInfo", array($item["ID"]));
		echo "Name .....: " . $deviceInfo["NAME"]. "\n";
		print_r($deviceInfo);*/
	}
}




switch ($argv[1]) {
	case "--id":
		$id=$argv[2];
		break;
	case "--address":
		$address=$argv[2];
		break;
	case "--list":
		getAllData($Client);
		break;
	case "--help":
		usage();
		exit(0);
		break;
}



?>
