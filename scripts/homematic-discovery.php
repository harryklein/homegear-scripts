#!/usr/bin/env php
<?php
	include_once("/var/lib/homegear/scripts/Connect.php");

	function usage(&$argv){
		echo "Aufruf $argv[0] HM-ES-PMSw1-Pl|HM-WDS10-TH-O|HM-CC-TC|HM-Sec-SC\n";
	}

	if (count($argv) == 1 ){
		usage($argv);
		exit(1);
	}

	switch ($argv[1]) {
		case "HM-ES-PMSw1-Pl":
		case "HM-WDS10-TH-O":
		case "HM-CC-TC":
		case "HM-Sec-SC":
			$deviceType=$argv[1];
			break;
		default:
			usage($argv);
			exit(1);
	}

	$data = $Client->send("listDevices", array());
	// print_r($data);

	$result = array();

	foreach ( $data as $item ){
		if( empty($item["PARENT"])){
			if ($item["TYPE"] == $deviceType) {
				$i = array();
				$i['{#ADDRESS}'] = $item["ADDRESS"];
				$i['{#ID}'] = $item["ID"];
				$i['{#FIRMWARE}'] = $item["FIRMWARE"];
				$deviceInfo = $Client->send("getDeviceInfo", array($item["ID"]));
				$name = $deviceInfo["NAME"];
				$name = str_replace('"','',$name);
				$i['{#NAME}'] = $name;
				$i['{#RSSI}'] = $deviceInfo["RSSI"];
				$result[] = $i;

			}
		}
	}
	$resultList = array();
	$resultList['data'] = $result;
	echo json_encode($resultList);
	echo "\n";
?>
