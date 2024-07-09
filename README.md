# fiat_vehicle_mqtt
Access Fiat vehicle data and send it to an MQTT server.

## Sources
This software is based on [api.php](https://github.com/schmidmuc/fiat_vehicle/blob/main/api.php) from https://github.com/schmidmuc/fiat_vehicle with some small adjustments.

## Prerequisites
This software needs PHP 7.x to work and a running MQTT server.

## Configuration
After downloading the software you just need to adjust the configfile fiat.cfg with the different settings for your MQTT server and your Uconnect account data.

## Using docker to run the software
Just run "docker build -t <name>" and "docker-compose up -d" to configure and start a docker container running this software.

## Running this software directly
You also need to 
