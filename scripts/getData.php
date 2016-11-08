#!/usr/bin/env php
<?php
include_once ("/var/lib/homegear/scripts/Connect.php");

$map = array();
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
        'STATE'
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
    print_r($map);
}

function usage ()
{
    global $argv;
    echo "Aufruf: " . $argv[0] . " [--address ADDRESS]|[--id ID] VALUE\n";
}

function getSetTemperature ($id)
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

function getValue ($id, $channel, $value)
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

function getDeviceInfo ($id, $key)
{
    global $Client;
    $deviceInfo = $Client->send("getDeviceInfo", array(
            $id
    ));
    echo $deviceInfo[$key] . "\n";
}

function getDeviceDetails ($id, $key)
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

function getId ($data, $address)
{
    foreach ($data as $item) {
        if ($item["ADDRESS"] == $address) {
            $id = $item["ID"];
            return $id;
        }
    }
    echo "Unknown address [$address]\n";
    exit(1);
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

$data = $Client->send("listDevices", array());
if ($id > 0 || $address != "") {
    $foundId = false;
    foreach ($data as $item) {
        if (empty($item["PARENT"])) {
            if (($item["ID"] == $id) || ($address == $item["ADDRESS"])) {
                $address = $item["ADDRESS"];
                $id = intval($item["ID"]);
                $type = $item['TYPE'];
                $foundId = true;
            }
        }
    }
    if (! $foundId) {
        echo "Id [$id] bzw. Adresse [$address] nicht gefunden. Abbruch\n";
        exit(1);
    }
}

if (! $foundId) {
    echo "Id [$id] bzw. Adresse [$address] nicht gefunden. Abbruch\n";
    exit(1);
}

if (! isset($map[$type])) {
    echo "Die Adresse [$address] hat den Type [$type]. Dieser Type wird nicht unterstützt. Abbruch.";
    exit(1);
}

if (! in_array($value, $map[$type])) {
    echo "Der Type [$type] untersützt nicht [$value]. Untersützt wird nur [" .
             implode(',', $map[$type]) . "]. Abbruch.";
    exit(1);
}

$fields = array(
        "FIRMWARE",
        "ID",
        "ADDRESS"
);
$data = $Client->send("listDevices", array(
        false,
        $fields
));
$id = getId($data, $address);

function setValue ($id, $channel, $key, $newValue)
{
    global $Client;
    $param = array(
            $id,
            $channel,
            "VALUES",
            array(
                    $key => $newValue
            )
    );
    print_r($param);
    // $result = $Client->send("putParamset", $param);
    $result = $Client->send("putParamset", 
            array(
                    $id,
                    2,
                    "VALUES",
                    array(
                            "SETPOINT" => 21.0
                    )
            ));
    echo "RESULT [$result]\n";
}

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
            setValue($id, 2, $value, intVal($newValue));
        }
    case "ADJUSTING_COMMAND":
    case "ADJUSTING_DATA":
    case "BOOT":
    case "CURRENT":
    case "ENERGY_COUNTER":
    case "FREQUENCY":
    case "POWER":
    case "VOLTAGE":
        getValue($id, 2, $value);
        exit(0);
    case "RSSI":
    case "NAME":
        getDeviceInfo($id, $value);
        exit(0);
    case "FIRMWARE":
        getDeviceDetails($id, $value);
        exit(0);
    default:
        usage();
        exit(1);
}

?>
