#!/usr/bin/env php
<?php
	include_once("Connect.php");
	$data = $Client->send("listDevices", array());
	// print_r($data);
	
	foreach ( $data as $item ){
		if( empty($item["PARENT"])){
			echo "------------------------------" . "\n";
			echo "Id ....... : " . $item["ID"] . "\n";
			echo "Address .. : " . $item["ADDRESS"] . "\n";
			echo "Type ..... : " . $item["TYPE"]  . "\n";
			echo "Firmware . : " . $item["FIRMWARE"]  . "\n";
			$amountChannel = count($item["CHANNELS"]);
			for ($channel=0; $channel < $amountChannel; $channel++){
				echo "Channel : $channel \n";
			 	echo "=VALUES=\n";	
				$paramSet = $Client->send("getParamset", array($item["ID"],$channel, "VALUES"));
				foreach (array_keys($paramSet) as $key){
					printf  ("- %-25s : %s\n", $key,$paramSet[$key]);
				}
				echo "=MASTER=\n";
				$paramSet = $Client->send("getParamset", array($item["ID"],$channel, "MASTER"));
				foreach (array_keys($paramSet) as $key){
                                        printf  ("- %-25s : %s\n", $key,$paramSet[$key]);
                                }
				echo "=LINK=\n";
				$paramSet = $Client->send("getParamset", array($item["ID"],$channel, "LINK"));
				foreach (array_keys($paramSet) as $key){
                                        printf  ("- %-25s : %s\n", $key,$paramSet[$key]);
                                }
			}
			$deviceInfo = $Client->send("getDeviceInfo", array($item["ID"]));
			echo "Name .....: " . utf8_encode($deviceInfo["NAME"]). "\n";
			print_r($deviceInfo);
		}

	}

?>
