# fiat_vehicle_mqtt
Access Fiat vehicle data and send it to an MQTT server.

## Sources
This software is based on [api.php](https://github.com/schmidmuc/fiat_vehicle/blob/main/api.php) from https://github.com/schmidmuc/fiat_vehicle with some small adjustments.

## Prerequisites
This software needs PHP 7.x to work and a running MQTT server.

## Configuration
After downloading the software you just need to adjust the configfile __fiat.cfg__ with the different settings for your MQTT server and your Uconnect account data.

## Using docker to run the software
In the download directory of this software just run __docker build -t fiat__ and __docker-compose up -d__ to configure and start a docker container named __fiat__ running this software. Maybe you want to adjust the TZ variable in __Dockerfile__.

## Running this software from the command line
The Debian packages __php-xml php-curl composer__ (or similar ones from other distributions) needs to be installed. Afterwards run __composer install__ in the directory where you have downloaded this software. Now run __php fiat_mqtt.php__.
