#!/usr/bin/env php
<?php

function usage()
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

function getDeviceDataViaIdOrAddress($ID, $address)
{
    global $Client;
    $data = $Client->send("listDevices", array());
    $foundIdOrAddress = false;
    foreach ($data as $item) {
        if (empty($item["PARENT"])) {
            if (($item["ID"] == $ID) || ($address == $item["ADDRESS"])) {
                $address = $item["ADDRESS"];
                $ID = intval($item["ID"]);
                $foundIdOrAddress = true;
                break;
            }
        }
    }
    if (! $foundIdOrAddress) {
        echo "Id [$ID] bzw. Adresse [$address] nicht gefunden. Abbruch\n";
        exit(1);
    }
    $data = array();
    $data[] = $item;
    return $data;
}

function getDeviceDataFromAll()
{
    global $Client;
    $data = $Client->send("listDevices", array());
    return $data;
}

function printDeviceInfo($data, $viewDetails = true)
{
    global $Client;

    foreach ($data as $item) {
        if (empty($item["PARENT"])) {

            $name = utf8_encode($item["NAME"]);
            if (empty($name)){
                $name = 'New';
            }
            
            printSubSeparator();
            printLine("Id", $item["ID"]);
            printLine("Address", $item["ADDRESS"]);
            printLine("Type", $item["TYPE"]);
            printLine("Firmware", $item["FIRMWARE"]);
            printLine("Name", $name);

            if (! $viewDetails) {
                continue;
            }

            $allKeys = array_keys($item);
            foreach ($allKeys as $key) {
                switch ($key) {
                    case 'ID':
                    case 'ADDRESS':
                    case 'TYPE':
                    case 'FIRMWARE':
                    case 'NAME':
                        continue 2;
                }
                if (is_array($item[$key])) {
                    printLine($key, implode(',', $item[$key]));
                } else {
                    printLine($key, $item[$key]);
                }
            }

            $amountChannel = count($item["CHANNELS"]);
            for ($channel = 0; $channel < $amountChannel; $channel ++) {

                printHeader("Channel", $channel);
                foreach (array(
                    "VALUES",
                    "MASTER",
                    "LINK"
                ) as $type) {
                    printHeader2($type);
                    $paramSet = $Client->send("getParamset", array(
                        $item["ID"],
                        $channel,
                        $type
                    ));
                    foreach (array_keys($paramSet) as $key) {
                        printSubLine($key, $paramSet[$key]);
                    }
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

// ===========================================================
// = Helper =
// ===========================================================

/**
 * Ausgabe " <KEY> .........
 * : <VALUE>"
 *
 * Die Anzahl der Leerzeichen vor <KEY> legt $level fesst. Die Position vom
 * linken Rand bis zum Doppelpunkt
 * ist immer 35
 */
function printLine($key, $value = '', $level = 0)
{
    $indent = 35 - $level;
    $key = $key . ' ';
    if (is_array($value)) {
        $value = implode(',', $value);
    }
    $format = "%' " . $level . "s%'.-" . $indent . "s : %s\n";
    printf($format, "", $key, $value);
}

/**
 * Ausgabe " <KEY> .........
 * : <VALUE>"
 *
 * Die Anzahl der Leerzeichen vor <KEY> ist 4. Die Position vom linken Rand bis
 * zum Doppelpunkt
 * ist immer 35
 */
function printSubLine($key, $value = '')
{
    printLine($key, $value, 4);
}

/**
 * Ausgabe "<KEY> <VALUE>"
 */
function printHeader($key, $value = '')
{
    printf("%s %s\n", $key, $value);
}

/**
 * Ausgabe " = <VALUE> ="
 */
function printHeader2($value = '')
{
    printf("%' 2s= %s =\n", '', $value);
}

/**
 * Ausgabe "-------------------------------------------------------"
 * (55 Zeichen)
 */
function printSubSeparator()
{
    printf("%'--55s\n", '');
}

?>
