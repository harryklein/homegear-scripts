#!/usr/bin/env php
<?php

function usage ()
{
    ?>
Gibt allgemeine Informationen (Id, Name, Type und Firmware) zu allen bekannten Geräten aus bzw. alle aktuellen Werte.    

Aufruf [--id ID]|[--address ADDRESS]|[--all]|[--list]|[--help]
	--list                    : Listet alle bekannten Geräte mit Id, Name, Type, Firmware und Name auf
	--details-id ID           : Ausgabe aller Details eines Gerätes mit der Id ID
	--details-address ADDRESS : Ausgabe aller Details eines Gerätes mit der Adresse ADDRESS
	--details-all             : Ausgabe Details aller bekannten Geräte
	--help                    : Diese Hilfe

<?php
}

function getDeviceDataViaIdOrAddress ($ID, $address)
{
    global $Client;
    $data = $Client->send("listDevices", array());
    $foundId = false;
    foreach ($data as $item) {
        if (empty($item["PARENT"])) {
            if (($item["ID"] == $ID) || ($address == $item["ADDRESS"])) {
                $address = $item["ADDRESS"];
                $ID = intval($item["ID"]);
                $foundId = true;
                break;
            }
        }
    }
    if (! $foundId) {
        echo "Id [$ID] bzw. Adresse [$address] nicht gefunden. Abbruch\n";
        exit(1);
    }
    $data = array();
    $data[] = $item;
    return $data;
}

function getDeviceDataFromAll ()
{
    global $Client;
    $data = $Client->send("listDevices", array());
    return $data;
}

function printDeviceInfo ($data, $viewDetails = true)
{
    global $Client;
    
    foreach ($data as $item) {
        if (empty($item["PARENT"])) {
            $deviceInfo = $Client->send("getDeviceInfo",
                    array(
                            $item["ID"]
                    ));
            $item["NAME"] = utf8_encode($deviceInfo["NAME"]);
            echo "------------------------------" . "\n";
            echo "Id ....... : " . $item["ID"] . "\n";
            echo "Address .. : " . $item["ADDRESS"] . "\n";
            echo "Type ..... : " . $item["TYPE"] . "\n";
            echo "Firmware . : " . $item["FIRMWARE"] . "\n";
            echo "Name ..... : " . $item["NAME"] . "\n";
            
            echo "JSON:" . json_encode($item) . "\n";
            
            if (! $viewDetails) {
                continue;
            }
            $amountChannel = count($item["CHANNELS"]);
            for ($channel = 0; $channel < $amountChannel; $channel ++) {
                echo "Channel : $channel \n";
                echo "=VALUES=\n";
                $paramSet = $Client->send("getParamset", 
                        array(
                                $item["ID"],
                                $channel,
                                "VALUES"
                        ));
                foreach (array_keys($paramSet) as $key) {
                    printf("- %-25s : %s\n", $key, $paramSet[$key]);
                }
                echo "=MASTER=\n";
                $paramSet = $Client->send("getParamset", 
                        array(
                                $item["ID"],
                                $channel,
                                "MASTER"
                        ));
                foreach (array_keys($paramSet) as $key) {
                    printf("- %-25s : %s\n", $key, $paramSet[$key]);
                }
                echo "=LINK=\n";
                $paramSet = $Client->send("getParamset", 
                        array(
                                $item["ID"],
                                $channel,
                                "LINK"
                        ));
                foreach (array_keys($paramSet) as $key) {
                    printf("- %-25s : %s\n", $key, $paramSet[$key]);
                }
            }
        }
    }
}

// ===========================================================
// = Main =
// ===========================================================
include_once ("/var/lib/homegear/scripts/Connect.php");

$ID = 0;
$list = 0;
$address = "";

if (count($argv) > 1) {
    switch ($argv[1]) {
        case "--list":
            $data = getDeviceDataFromAll();
            printDeviceInfo($data, false);
            break;
        case "--details-id":
            $data = getDeviceDataViaIdOrAddress($argv[2], '');
            printDeviceInfo($data);
            break;
        case "--details-address":
            $data = getDeviceDataViaIdOrAddress('', $argv[2]);
            printDeviceInfo($data);
            break;
        case "--help":
            usage();
            break;
        case "--details-all":
            $data = getDeviceDataFromAll();
            printDeviceInfo($data);
            break;
        default:
            usage();
            exit(1);
    }
    exit(0);
}

usage();
exit(2);

?>
