#!/usr/bin/env php
<?php
	/**
	*
	* Unterstützt HM-ES-PMSw1-Pl, HM-WDS10-TH-O, HM-CC-TC und HM-Sec-SC
	*
	*
	*/


	if ( isset($argv[1])){
		$address = $argv[1];
	}

	if ( isset($argv[2])){
                $value = $argv[2];
        }

	if ( ! isset($address) || ! isset($value)){
		echo "Aufruf $argv[0] ADDRESS TEMPERATURE|HUMIDITY für HM-WDS10-TH-O und HM-CC-TC\n";
		echo "Aufruf $argv[0] ADDRESS BOOT|CURRENT|ENERGY_COUNTER|FREQUENCY|POWER|VOLTAGE|RSSI|NAME für HM-ES-PMSw1-Pl\n";
		exit;
	}

	switch($value) {
		case "TEMPERATURE":
		case "HUMIDITY":
		case "STATE":
			$info = 0;
			$channel = 1;
			break;
		case "SETPOINT":
		case "ADJUSTING_COMMAND":
			$info = 0;
                        $channel = 2;
			break;
		case "BOOT":
		case "CURRENT":
		case "ENERGY_COUNTER":
		case "FREQUENCY":
		case "POWER":
		case "VOLTAGE":
			$info = 0;
			$channel = 2;
			break;
		case "RSSI":
		case "NAME":
		case "FIRMWARE":
			$info = 1;
                        $channel = 2;
                        break;
		default:
			echo "Aufruf $argv[0] ADDRESS TEMPERATURE|HUMIDITY\n";
			echo "Aufruf $argv[0] ADDRESS BOOT|CURRENT|ENERGY_COUNTER|FREQUENCY|POWER|VOLTAGE|RSSI|NAME\n";
                	exit;
	}	


	include_once("/var/lib/homegear/scripts/Connect.php");
	$data = $Client->send("listDevices", array());
	//print_r($data);

	foreach ( $data as $item ){
		if ($item["ADDRESS"] == $address)
		{
			$id = $item["ID"];
			break;
		}	

	}
 	if ($info){
		$deviceInfo = $Client->send("getDeviceInfo", array($id));
		echo $deviceInfo[$value] ."\n";
	}
	else {
		$value = $Client->send("getValue", array($id, $channel, $value)) ;
		if (empty($value)){
			$value = 0;
		}
		echo $value . "\n" ;
	}

?>
