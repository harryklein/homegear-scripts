#!/usr/bin/env php
<?php
include_once ("/var/lib/homegear/scripts/Connect.php");

$map = array();
$map['HM-ES-PMSw1-Pl-DN-R1'] = array(
    'NAME',
    'RSSI',
    'FIRMWARE',
    'BOOT',
    'CURRENT',
    'ENERGY_COUNTER',
    'FREQUENCY',
    'POWER',
    'VOLTAGE',
    'STATE',
    'POWERUP_ACTION'
);
$map['HM-ES-PMSw1-Pl'] = array(
    'NAME',
    'RSSI',
    'FIRMWARE',
    'BOOT',
    'CURRENT',
    'ENERGY_COUNTER',
    'FREQUENCY',
    'POWER',
    'VOLTAGE',
    'STATE',
    'POWERUP_ACTION'
);
$map['HM-WDS10-TH-O'] = array(
    'NAME',
    'RSSI',
    'FIRMWARE',
    'TEMPERATURE',
    'HUMIDITY'
);
$map['HM-CC-TC'] = array(
    'NAME',
    'RSSI',
    'FIRMWARE',
    'TEMPERATURE',
    'HUMIDITY',
    'SET_TEMPERATURE',
    'SETPOINT',
    'ADJUSTING_COMMAND',
    'ADJUSTING_DATA'
);
$map['HM-Sec-SC'] = array(
    'NAME',
    'RSSI',
    'FIRMWARE',
    'STATE'
);

if (isset($argv[4])) {
    // print_r($map);
}

function usage()
{
    global $argv;
    echo "Aufruf: " . $argv[0] . " [--address ADDRESS]|[--id ID] KEY [--set VALUE]\n";
}

function getSetTemperature($id)
{
    global $Client;
    $channel = 2;
    $date = getDate();
    $time = ($date['hours'] * 60 + $date['minutes']);
    $paramSet = $Client->send("getParamset", array(
        $id,
        $channel,
        "MASTER"
    ));

    $weekday = strtoupper($date['weekday']);

    $start = 0;

    for ($i = 1; $i <= 24; $i ++) {
        $KEY_TIME = 'TIMEOUT_' . $weekday . '_' . $i;
        $KEY_TEMP = 'TEMPERATUR_' . $weekday . '_' . $i;
        $ende = $paramSet[$KEY_TIME];
        if (($time > $start) && ($time <= $ende)) {
            $temperature = $paramSet[$KEY_TEMP];
            break;
        }
        $start = $ende;
    }
    echo "$temperature\n";
}

function getValue($id, $channel, $value)
{
    global $Client;
    $value = $Client->send("getValue", array(
        $id,
        $channel,
        $value
    ));
    if (empty($value)) {
        $value = 0;
    }
    echo $value . "\n";
}

function getParameter($id, $channel, $param)
{
    global $Client;
    $value = $Client->send("getParamset", array(
        $id,
        $channel
    ));
    
    // print_r($value);
    // print_r($param);
    if (is_array($value)) {
        if (isset($value[$param])) {
            $value =  $value[$param];
        } else {
            $value = -2;
        }
    } else {
        $value = -1;
    }
    echo $value . "\n";
}

function getDeviceInfo($id, $key)
{
    global $Client;
    $deviceInfo = $Client->send("getDeviceInfo", array(
        $id
    ));
    echo $deviceInfo[$key] . "\n";
}

function getDeviceDetails($id, $key)
{
    global $Client;
    $fields = array(
        "FIRMWARE",
        "ID",
        "ADDRESS"
    );
    $data = $Client->send("listDevices", array(
        false,
        $fields
    ));
    foreach ($data as $i) {
        if ($i['ID'] === $id) {
            echo $i[$key] . "\n";
            return;
        }
    }
    echo "Unknown key [$key]\n";
}

/**
 * Sucht nach einem Gerät, welches entweder die Id oder die Adresse besitzt.
 *
 *
 * @param string $id
 *            Id des Gerätes oder leer
 * @param string $address
 *            Adresse des Gerätes oder leer
 * @return Array mit den Feldern ADDRESS, ID und TYPE
 */
function searchDevice($id, $address)
{
    global $Client;

    $data = $Client->send("listDevices", array());
    if ($id > 0 || $address != "") {
        $foundId = false;
        foreach ($data as $item) {
            if (empty($item["PARENT"])) {
                if (($item["ID"] == $id) || ($address == $item["ADDRESS"])) {
                    // $address = $item["ADDRESS"];
                    // $id = intval($item["ID"]);
                    // $type = $item['TYPE'];
                    $foundId = true;
                    break;
                }
            }
        }
        if (! $foundId) {
            echo "Id [$id] bzw. Adresse [$address] nicht gefunden. Abbruch\n";
            exit(1);
        }
        return $item;
    }
}

function mapAddressToId($address)
{
    global $Client;
    $fields = array(
        "FIRMWARE",
        "ID",
        "ADDRESS"
    );
    $data = $Client->send("listDevices", array(
        false,
        $fields
    ));
    foreach ($data as $item) {
        if ($item["ADDRESS"] == $address) {
            $id = $item["ID"];
            return $id;
        }
    }
    echo "Unknown address [$address]\n";
    exit(1);
}

function setValue($id, $channel, $key, $newValue)
{
    global $Client;
    $result = $Client->send("putParamset", array($id, 1, "MASTER",array($key => $newValue)));
    
    echo "RESULT [";
    print_r($result);
    echo "]\n";
}

// ===========================================================
// = Main =
// ===========================================================

if (count($argv) < 4) {
    usage();
    exit(1);
}

$id = 0;
$address = '';
$value = '';
switch ($argv[1]) {
    case "--id":
        $id = $argv[2];
        break;
    case "--address":
        $address = $argv[2];
        break;
    case "--help":
        usage();
        exit(0);
        break;
}
$value = $argv[3];

if (isset($argv[4])) {
    switch ($argv[4]) {
        case '--set':
            if (isset($argv[5])) {
                $newValue = $argv[5];
                break;
            }
        // ohne 5. Parameter fallen wir hier weiter ...
        default:
            usage();
            exit(1);
            break;
    }
}

$item = searchDevice($id, $address);

$type = $item['TYPE'];
$id = $item['ID'];

function checkTypeAndKey($item, $value)
{
    global $map;

    $type = $item['TYPE'];
    $address = $item['ADDRESS'];
    if (! isset($map[$type])) {
        echo "Die Adresse [$address] hat den Type [$type]. Dieser Type wird nicht unterstützt. Abbruch.";
        exit(1);
    }

    if (! in_array($value, $map[$type])) {
        echo "Der Type [$type] untersützt nicht [$value]. Untersützt wird nur [" . implode(',', $map[$type]) . "]. Abbruch.";
        exit(1);
    }
}

checkTypeAndKey($item, $value);

switch ($value) {
    case "SET_TEMPERATURE":
        getSetTemperature($id);
        exit(0);
    case "TEMPERATURE":
    case "HUMIDITY":
    case "STATE":
        getValue($id, 1, $value);
        exit(0);
    case "SETPOINT":
        if (isset($newValue)) {
            echo "DISABLE\n";
            // setValue($id, 2, $value, intVal($newValue));
        }
    case "ADJUSTING_COMMAND":
    case "ADJUSTING_DATA":
    case "CURRENT":
    case "ENERGY_COUNTER":
    case "FREQUENCY":
    case "POWER":
    case "VOLTAGE":
    case "BOOT":
        getValue($id, 2, $value);
        exit(0);
    case "RSSI":
    case "NAME":
        getValue($id, 1, $value);
        exit(0);
    case "FIRMWARE":
        getDeviceDetails($id, $value);
        exit(0);
    case "POWERUP_ACTION":
        if (isset($newValue)) {
            echo "SET:";
            getParameter($id, 1, $value);
            setValue($id, 1, 'POWERUP_ACTION', intVal($newValue));
            getParameter($id, 1, $value);
        } else {
            getParameter($id, 1, $value);
        }
        exit(0);
    default:
        usage();
        exit(1);
}

?>
