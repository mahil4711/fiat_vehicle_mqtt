FROM debian:bullseye

ENV DEBIAN_FRONTEND noninteractive

ENV TZ=Europe/Berlin

RUN apt-get -qq update && \
    apt-get -y install php-xml php-curl composer procps && \
    ln -fs /usr/share/zoneinfo/$TZ /etc/localtime && \
    dpkg-reconfigure -f noninteractive tzdata

RUN mkdir /fiat
RUN mkdir /fiat/fiat_vehicle
COPY fiat_vehicle/ /fiat/fiat_vehicle
COPY fiat.cfg fiat_mqtt.php fiat.sh composer.json /fiat/
RUN cd /fiat && composer install

CMD bash /fiat/fiat.sh
