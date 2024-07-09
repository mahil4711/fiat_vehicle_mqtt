# fiat_vehicle_mqtt
Access Fiat vehicle data and send it periodically to an MQTT broker.

## Sources
This software is based on [api.php](https://github.com/schmidmuc/fiat_vehicle/blob/main/api.php) from https://github.com/schmidmuc/fiat_vehicle with some small adjustments.

## Prerequisites
This software needs PHP 7.x to work and a running MQTT broker.

## Configuration
After downloading the software you just need to create the configfile __fiat.cfg__ with at least the settings for your MQTT server and your Uconnect account in the software directory:
```
[mqtt]
server = <IP of your MQTT broker>
port = <port of your MQTT broker>
username = <username for MQTT broker, leave empty if not needed>
password = <password for MQTT broker, leave empty if not needed>

[fiat]
username = <Uconnect account email>
password = <Uconnect account password>
PIN = <PIN used in the Fiat app>
GoogleApiKey = "<optional GoolgeApi key>"
sleep = 300
sleep_charging = 60
```

The [GoogleApiKey](https://support.google.com/googleapi/answer/6158862?hl=en) is needed to translate the latitude/longitude data to a real address. You could leave this empty if you do not need this.

The __sleep__ parameter defines the wait time between rereading the Fiat vehicle data. The above example means, that the data will be updated every 300 seconds by default. If the car is charing the update time will be set to 60 seconds according to the parameter __sleep_charging__.

## Using docker to run this software
Goto the download directory of this software. Adjust the TZ variable in __Dockerfile__ to your needs and run the following commands:
- docker build -t fiat
- docker-compose up -d

This will configure and start a docker container named __fiat__. 

## Running this software from the command line
The Debian packages __php-xml php-curl composer__ (or similar ones from other distributions) needs to be installed. Afterwards run __composer install__ in the directory where you have downloaded this software. Now run __php fiat_mqtt.php__.
