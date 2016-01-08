#!/usr/bin/env php
<?php

	function usage(){
?>
Aufruf [--id ID]|[--address ADDRESS] | [--list] | [--help]
	--list           : Listet alle bekannten Geräte mit Id, Name, Type, Firmware und Name auf
	--id ID          : Ausgabe aller Details eines Gerätes mit der Id ID
	--address ADDRESS: Ausgabe aller Details eines Gerätes mit der Adresse ADDRESS
	(ohne)           : Ausgabe Details aller bekannten Geräte
	--help           : Diese Hilfe

<?php

	}
	$ID=0;
	$list=0;
	$address="";
	switch ($argv[1]) {
		case "--list":
			$list=1;
			break;
		case "--id":
			$ID=$argv[2];
			break;
		case "--address":
			$address=$argv[2];
                        break;
		case "--help":
			usage();
			exit(0);
			break;

	}
	include_once("Connect.php");
	$data = $Client->send("listDevices", array());
	if ( $ID > 0 || $address != "" ) {
		$foundId = false;
		foreach ( $data as $item ){
			if( empty($item["PARENT"])){
				if ( ($item["ID"] == $ID) || ($address == $item["ADDRESS"]) ) {
					$address = $item["ADDRESS"];
					$foundId = true;
				}

			}
		}
		if (!$foundId) {
			echo "Id [$ID] nicht gefunden. Abbruch\n";
			exit(1);
		}
		$data = array();
		$data[] = $Client->send("getDeviceDescription", array($address));

	}

	// print_r($data);
	
	foreach ( $data as $item ){
		if( empty($item["PARENT"])){
			echo "------------------------------" . "\n";
			echo "Id ....... : " . $item["ID"] . "\n";
			echo "Address .. : " . $item["ADDRESS"] . "\n";
			echo "Type ..... : " . $item["TYPE"]  . "\n";
			echo "Firmware . : " . $item["FIRMWARE"]  . "\n";
			$deviceInfo = $Client->send("getDeviceInfo", array($item["ID"]));
	                echo "Name ..... : " . utf8_encode($deviceInfo["NAME"]). "\n";
			if ($list == 1){
				continue;
			}
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
		}

	}

?>
