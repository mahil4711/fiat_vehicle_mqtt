#!/bin/bash

cd /fiat
while :
do
  php /fiat/fiat_mqtt.php
  sleep 60
done
