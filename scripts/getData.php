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

function my_exit ($text, $exitCode = 1)
{
    echo "$text\n";
    
    exit(intval($exitCode));
}

function usage ()
{
    global $argv;
    echo 'Aufruf: ' . $argv[0] . " [--address ADDRESS]|[--id ID] KEY [--set VALUE]\n";
    echo '        ' . $argv[0] . " [--address ADDRESS]|[--id ID] --all\n";
    echo '        ' . $argv[0] . " --list\n";
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

function getValue (DeviceAddress $item, $channel, $value)
{
    global $Client;
    
    $id = $item->getId();
    
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

/**
 * Liefert ID, INTERFACE, NAME und RSSI.
 * Beispiel:
 *
 * [ID] => 12
 * [INTERFACE] => My-HM-CFG-LAN
 * [NAME] => Schlafzimmer
 * [RSSI] => -83
 *
 * @param DeviceAddress $item            
 * @param String $key
 *            Schlüssel aus ID, INTERFACE, NAME und RSSI
 */
function printDeviceInfoDetail (DeviceAddress $item, $key)
{
    global $Client;
    $id = $item->getId();
    $deviceInfo = $Client->send("getDeviceInfo", array(
            $id
    ));
    print_r($deviceInfo);
    echo $deviceInfo[$key] . "\n";
}

/**
 * Verfügbare Informationen:
 * https://www.homegear.eu/index.php/XML_RPC_DeviceDescription
 *
 * @param DeviceAddress $item            
 * @param unknown $key            
 */
function printDeviceDescription (DeviceAddress $item, $key)
{
    global $Client;
    $id = $item->getId();
    $channelDetails = false;
    
    $data = $Client->send("getDeviceDescription", array(
            $id,
            - 1
    ));
    if ($data['ID'] === $id) {
        if (isset($data[$key])) {
            echo $data[$key] . "\n";
            return;
        }
    }
    
    my_exit("Unknown key [$key]", 1);
}

/**
 * Sucht nach einem Gerät, welches entweder die Id oder die Adresse besitzt.
 *
 *
 * @param unknown $id
 *            Id des Gerätes oder leer
 * @param unknown $address
 *            Adresse des Gerätes oder leer
 * @return @DeviceAddress mit den Feldern ADDRESS, ID und TYPE
 */
function searchDevice ($id, $address)
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
        return new DeviceAddress($item["ID"], $item["ADDRESS"], $item["TYPE"]);
    }
}

class DeviceAddress
{

    private $id;

    private $address;

    private $type;

    function __construct ($id, $address, $type)
    {
        $this->id = intval($id);
        $this->address = $address;
        $this->type = $type;
    }

    function getId ()
    {
        return $this->id;
    }

    function getAddress ()
    {
        return $this->address;
    }

    function getType ()
    {
        return $this->type;
    }
}

function mapAddressToId ($address)
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

function setValue (DeviceAddress $item, $channel, $key, $newValue)
{
    global $Client;
    $id = $item->getId();
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

function checkKeyIsValideForType (DeviceAddress $item, $key)
{
    global $map;
    
    $type = $item->getType();
    $address = $item->getAddress();
    
    if (! isset($map[$type])) {
        echo "Die Adresse [$address] hat den Type [$type]. Dieser Type wird nicht unterstützt. Abbruch.";
        exit(1);
    }
    
    if (! in_array($key, $map[$type])) {
        echo "Der Type [$type] untersützt nicht [$key]. Untersützt wird nur [" . implode(',', $map[$type]) . "]. Abbruch.";
        exit(1);
    }
}

function getDeviceDataFromAll ()
{
    global $Client;
    $data = $Client->send("listDevices", array());
    return $data;
}

/**
 * Ausgabe der Device-Informationen in Textform
 *
 * @param unknown $data
 *            Array mit den Daten
 * @param string $viewDetails
 *            bei true werden alle Daten angezeigt, bei false nur id, address,
 *            type, firmware und name
 */
function printDeviceInfo ($data, $viewDetails = true)
{
    global $Client;
    
    if (is_array($data)) {
        $dataAsArray = $data;
    } else {
        $dataAsArray = array();
        $dataAsArray[] = $data;
    }
    
    foreach ($data as $item) {
        if (empty($item["PARENT"])) {
            $deviceInfo = $Client->send("getDeviceInfo", array(
                    $item["ID"]
            ));
            $item["NAME"] = utf8_encode($deviceInfo["NAME"]);
            echo "------------------------------" . "\n";
            echo "Id ....... : " . $item["ID"] . "\n";
            echo "Address .. : " . $item["ADDRESS"] . "\n";
            echo "Type ..... : " . $item["TYPE"] . "\n";
            echo "Firmware . : " . $item["FIRMWARE"] . "\n";
            echo "Name ..... : " . $item["NAME"] . "\n";
            
            // echo "JSON:" . json_encode($item) . "\n";
            
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

if (count($argv) < 2) {
    usage();
    exit(1);
}

$id = 0;
$address = '';
$key = '';
switch ($argv[1]) {
    case "--id":
        $id = $argv[2];
        break;
    case "--address":
        $address = $argv[2];
        break;
    case "--list":
        $data = getDeviceDataFromAll();
        printDeviceInfo($data, false);
        exit(0);
        break;
    case "--help":
        usage();
        exit(0);
        break;
}
$key = $argv[3];

if ($key === "--all") {
    $data = getDeviceDataViaIdOrAddress($id, $address);
    printDeviceInfo($data);
    exit(0);
}

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

checkKeyIsValideForType($item, $key);

switch ($key) {
    case "SET_TEMPERATURE":
        getSetTemperature($item);
        exit(0);
    case "TEMPERATURE":
    case "HUMIDITY":
    case "STATE":
        getValue($item, 1, $key);
        exit(0);
    case "SETPOINT":
        if (isset($newValue)) {
            setValue($item, 2, $key, intVal($newValue));
        }
    case "ADJUSTING_COMMAND":
    case "ADJUSTING_DATA":
    case "BOOT":
    case "CURRENT":
    case "ENERGY_COUNTER":
    case "FREQUENCY":
    case "POWER":
    case "VOLTAGE":
        getValue($item, 2, $key);
        exit(0);
    case "RSSI":
    case "NAME":
        printDeviceInfoDetail($item, $key);
        exit(0);
    case "FIRMWARE":
        printDeviceDescription($item, $key);
        exit(0);
    default:
        usage();
        exit(1);
}

?>
