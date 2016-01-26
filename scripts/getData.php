#!/usr/bin/env php
<?php
	include_once("/var/lib/homegear/scripts/Connect.php");

	function getSetTemperature($id){
                global $Client;
                $channel = 2;
                $date = getDate();
                $time = ($date['hours'] * 60 + $date['minutes']);
                $paramSet = $Client->send("getParamset", array($id,$channel, "MASTER"));

                $weekday = strtoupper($date['weekday']);

                $start = 0;

                for ($i=1; $i<=24; $i++){
                        $KEY_TIME='TIMEOUT_'.$weekday.'_'.$i;
                        $KEY_TEMP='TEMPERATUR_'.$weekday.'_'.$i;
                        $ende = $paramSet[$KEY_TIME];
                        if ( ($time > $start) && ($time <= $ende)){
                                $temperature = $paramSet[$KEY_TEMP];
                                break;
                        }
                        $start = $ende;
                }
                echo "$temperature\n";
        }


	function getId($data, $address){
		foreach ( $data as $item )
		{
			if ($item["ADDRESS"] == $address)
			{
				$id = $item["ID"];
				return $id;
			}
		}
		echo "Unknown address [$address]\n";
		exit(1);
	}



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

	$fields = array("FIRMWARE","ID","ADDRESS");
        $data = $Client->send("listDevices", array(false, $fields));
        $id = getId($data, $address);

	switch($value) {
		case "SET_TEMPERATURE":
			getSetTemperature($id);
			exit (0);
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
			$info = 1;
                        $channel = 2;
                        break;
		case "FIRMWARE":
                        $info = 2;
                        $channel = 0;
                        break;
		default:
			echo "Aufruf $argv[0] ADDRESS TEMPERATURE|HUMIDITY\n";
			echo "Aufruf $argv[0] ADDRESS BOOT|CURRENT|ENERGY_COUNTER|FREQUENCY|POWER|VOLTAGE|RSSI|NAME\n";
                	exit;
	}	


	//$data = $Client->send("listDevices", array());
	//print_r($data);

	$fields = array("FIRMWARE","ID","ADDRESS");
        $data = $Client->send("listDevices", array(false, $fields));
	$id = getId($data, $address);

	switch ($info) {
		case 2:
			foreach ($data as $i) {
				if ($i['ID'] === $id ){
					echo $i[$value] . "\n";
					exit(0);
				} 
			}
			echo "0\n";
                        exit(1);
			break;
 		case 1:
			$deviceInfo = $Client->send("getDeviceInfo", array($id));
			echo $deviceInfo[$value] ."\n";
			break;
		case 0:
			$value = $Client->send("getValue", array($id, $channel, $value)) ;
			if (empty($value)){
				$value = 0;
			}
			echo $value . "\n" ;
			break;
	}

?>
