#!/usr/bin/env php
<?php
include_once ("/var/lib/homegear/scripts/Connect.php");

function usage(&$argv)
{
    echo "Aufruf $argv[0] HM-ES-PMSw1-Pl|HM-ES-PMSw1-Pl-DN-R1|HM-WDS10-TH-O|HM-CC-TC|HM-Sec-SC|all\n";
}

if (count($argv) == 1) {
    usage($argv);
    exit(1);
}
$allDevices = false;

switch ($argv[1]) {
    case "HM-ES-PMSw1-Pl":
    case "HM-WDS10-TH-O":
    case "HM-CC-TC":
    case "HM-Sec-SC":
    case "HM-ES-PMSw1-Pl-DN-R1":
        $deviceType = $argv[1];
        break;
    case "all":
        $allDevices = true;
        break;
    default:
        usage($argv);
        exit(1);
}

$data = $Client->send("listDevices", array());
// print_r($data);

$result = array();

foreach ($data as $item) {
    if (empty($item["PARENT"])) {
        if (($allDevices === true) || ($item["TYPE"] == $deviceType)) {
            $i = array();
            $i['{#ADDRESS}'] = $item["ADDRESS"];
            $i['{#ID}'] = $item["ID"];
            $i['{#TYPE}'] = $item["TYPE"];

            $name = $item["NAME"];
            $name = utf8_encode($name);
            $name = str_replace('"', '', $name);
            if (empty($name)) {
                $name = 'New';
            }
            $i['{#NAME}'] = $name;
            $result[] = $i;
        }
    }
}
$resultList = array();
$resultList['data'] = $result;
echo json_encode($resultList);
echo "\n";
?>
