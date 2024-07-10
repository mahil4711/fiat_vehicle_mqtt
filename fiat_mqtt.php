<?php

include('vendor/autoload.php');

// Include the api file (take care of the correct path)
include("fiat_vehicle/api.php");

use \PhpMqtt\Client\MqttClient;
use \PhpMqtt\Client\ConnectionSettings;

function mqtt_init($cfg, $clientId) {

  $clean_session = false;
  $mqtt_version = MqttClient::MQTT_3_1_1;

  $connectionSettings = (new ConnectionSettings)
    ->setKeepAliveInterval(60)
    ->setLastWillMessage('client disconnect')
    ->setLastWillQualityOfService(1);
    #->setReconnectAutomatically(false)

  if ($cfg['username']) {
    $connectionSettings->setUsername($cfg['username'])
      ->setPassword($cfg['password']);
  }

  try {
    $mqtt = new MqttClient($cfg['server'], $cfg['port'], $clientId, $mqtt_version);
    $mqtt->connect($connectionSettings, $clean_session);
    #printf("client connected\n");
    return $mqtt;
  } catch (Throwable $e) {
    fiat_log("MQTT connection failed: " . $e->getMessage());
  }

}

function mqtt_publish($cfg, $vin, $payload) {
  $clientId = 'Fiat_' . $vin;
  $mqtt = mqtt_init($cfg, $clientId);

  try {
    $mqtt->publish(
      // topic
      'fiat/' . $vin,
      // payload
      json_encode($payload),
      // qos
      1,
      // retain
      true
    );
    $mqtt->disconnect();
  } catch (Throwable $e) {
    fiat_log("MQTT publish failed: " . $e->getMessage());
  }
}

function mqtt_subscribe($cfg) {
  fiat_log("child is starting subscriber for executing commands");
  while(1) {
    try {
      $clientId = 'Fiat_command_subscribe';
      $mqtt = mqtt_init($cfg['mqtt'], $clientId);
      $mqtt->subscribe("fiat/+/command", function ($topic, $message) {
  $commands = array (
    "VF"            => "location",     // UpdateLocation (updates gps location of the car)
    "DEEPREFRESH"   => "ev",           // DeepRefresh (same as "RefreshBatteryStatus")
    "HBLF"          => "remote",       // Blink (blink lights)
    "CNOW"          => "ev/chargenow", // ChargeNOW (starts charging)
    "ROTRUNKUNLOCK" => "remote",       // Unlock trunk
    "ROTRUNKLOCK"   => "remote",       // Lock trunk
    "RDU"           => "remote",       // Unlock doors
    "RDL"           => "remote",       // Lock doors
    "ROPRECOND"     => "remote",       // Turn on/off HVAC
  );
        $cfg = read_cfg();

        $ts = epoche2time(time());
        fiat_log("Received message on topic [$topic]: $message");
        if ($message == "UPDATE") {
          fiat_log("forced reading data");
          fiat_get_data($cfg);
        } elseif (isset($commands[$message])) {
          $vin = (explode("/", $topic))[1];
          fiat_log("running command $message on vin $vin");
          fiat_command($cfg, $vin, $message);
        } else {
          fiat_log("unknown command $message");
        }
      }, 0);

      $mqtt->loop(true);
      $mqtt->disconnect();
    } catch (Throwable $e) {
      fiat_log('Exception catched: ' .  $e->getMessage());
      sleep(2);
      fiat_log("child is restarting subscriber for executing commands");
    }
  }
}

function epoche2time($ts, $divisor = 1) {
  $ts = $ts / $divisor;
  return date("Y-m-d H:i:s", $ts);
}

function fiat_command($cfg, $vin, $command) {
  try {
    $fiat = new apiFiat($cfg['fiat']['username'], $cfg['fiat']['password'], $cfg['fiat']['PIN']);
    if ($res = $fiat->apiCommand($vin, $command)) {
      fiat_log("got response '". $res['responseStatus'] . "' for command $command for vin '$vin'");
    } else {
      $t = $fiat->getLogArray(false);
      $last = array_pop($t);
      fiat_log("command '$command' failed: " . $last['message']->message);
    }
  } catch (Throwable $e) {
    $responseBody = $e->getResponse()->getBody(true);
    print "command exception - $responseBody\n";
    print_r($e);
  }
}

function fiat_get_data($cfg) {

  // Create a new instance with your FIAT user account credentials
  $fiat = new apiFiat($cfg['fiat']['username'], $cfg['fiat']['password'], $cfg['fiat']['PIN']);

  // Get all information from all vehicles linked to the user account
  $fiat->apiRequestAll();

  // Finally print all log messages
  #echo $fiat->getLog();

  // Export the vehicle data information
  $json = $fiat->exportInformation();
  $x = json_decode($json, true);
  #print_r($x);
  $vins = array();
  foreach ($x['vehicles'] as $vehicle) {
    $vins[$vehicle['vin']] = array(
      'modelDescription' => $vehicle['modelDescription'],
      'nickname' => $vehicle['nickname']
    );
  }

  $is_charging = false;
  foreach ($vins as $vin => $data) {
    // get the location address
    $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng=" . $x['vehicle'][$vin]['location']['latitude'] . ',' . $x['vehicle'][$vin]['location']['longitude'] . '&key=' . $cfg['fiat']['GoogleApiKey'];
    $result = json_decode(file_get_contents($url));
    $payload = array(
      'vin' => $vin,
      'modelDescription' => $data['modelDescription'],
      'nickname' => $data['nickname'],
      'battery' => $x['vehicle'][$vin]['status']['evInfo']['battery'],
      'evinfo_timestamp' => epoche2time($x['vehicle'][$vin]['status']['evInfo']['timestamp'], 1000),
      'status_timestamp' => epoche2time($x['vehicle'][$vin]['status']['timestamp'], 1000),
      'ignitionStatus' => $x['vehicle'][$vin]['status']['evInfo']['ignitionStatus'],
      'vehicleinfo_timestamp' => epoche2time($x['vehicle'][$vin]['status']['evInfo']['timestamp'], 1000),
      'odometer' => $x['vehicle'][$vin]['status']['vehicleInfo']['odometer']['odometer'],
      'distanceToService' => $x['vehicle'][$vin]['status']['vehicleInfo']['distanceToService']['distanceToService'],
      'location' => $x['vehicle'][$vin]['location'],
      'location_timeStamp' => epoche2time($x['vehicle'][$vin]['location']['timeStamp'], 1000),
      'location_address' => $result->results[0]->formatted_address,
    );
    $is_charging = ($x['vehicle'][$vin]['status']['evInfo']['battery']['chargingStatus'] == "CHARGING") ? true : $is_charging;
    mqtt_publish($cfg['mqtt'], $vin, $payload);
    fiat_log("updated data for " . $data['nickname'] . "($vin)");
  }
  return $is_charging;
}

function read_cfg() {
  $cfg_file = (file_exists('../fiat.cfg')) ? '../fiat.cfg' : '/run/secrets/fiat.cfg';
  fiat_log("reading config file '$cfg_file'");
  return parse_ini_file($cfg_file, 1);;
}

function fiat_log($text) {
  $ts = epoche2time(time());
  print "$ts $text\n";
}

######################################################
#### M A I N
######################################################
$cfg = read_cfg();
$init_subscribe = ($argc > 1) ? true : false;

$pid = pcntl_fork();
if ($pid == -1) {
  die('could not fork');
} else if ($pid) {
  // we are the parent
  fiat_log("forked child with pid $pid");

  // endless loop to read data every sleep seconds
  while(1) {
    #fiat_log("start reading data");
    if (fiat_get_data($cfg)) {
      sleep($cfg['fiat']['sleep_charging']);
    } else {
      sleep($cfg['fiat']['sleep']);
    }
  }
  pcntl_wait($status); //Protect against Zombie children
} else {
  // we are the child
  mqtt_subscribe($cfg);
}
