Power Control
==================

A webinterface to remotely control/schedule 433MHz power switches, a PIR sensor alarm, and record from foscam IP cameras all through a Raspberry Pi. At the very least it needs IP cameras to function.

Check [this article](http://bobbyromeo.com/wp/diy-alarm-monitoring-system-w-raspberry-pi-foscam-sensors/) for instructions and screenshots.


Configuration
==================
All configuration is handle in the file: config.ini

    i) Configure your cameras in the appropriate sections [camera1], [camera2]

    ii) Enable the camera module "use_camera_module=" in section [config]

    iii) Save to path in the [config] section, "save_to_dir=" 

    iii) Alter the crontab so user www-data can modify crontabs under the user pi
    www-data ALL=(pi) NOPASSWD: /usr/bin/crontab

    iv) Install ffmpeg (See below)


Requirements
==================

1) Install lighttpd and php5 from http://chris-labs.de/hardware/raspberrypi/2013/10/10/raspberrypi-433mhz-switch-control.html

    i) sudo apt-get install lighttpd mysql-server php5-common php5-cgi php5 php5-mysql php5-cli

    ii) Tell lighttpd to use fastcgi-php and reload the server:
        sudo lighty-enable-mod fastcgi-php
        sudo service lighttpd force-reload

    iii) Set permissions for the pi-user:

        sudo chown www-data:www-data /var/www
        sudo chmod 775 /var/www
        sudo usermod -a -G www-data pi

    iv) sudo chmod -vR g+w /var/www/powercontrol
        sudo chown -R www-data:www-data /var/www/powercontrol


2) Install Crontab Manager https://github.com/TiBeN/CrontabManager
    
    i) install composer:
        echo "<?php echo file_get_contents('https://getcomposer.org/composer.phar') ?>" | php > composer.phar
        sudo mv composer.phar /usr/local/bin/composer
        sudo chmod +x /usr/local/bin/composer

    ii) composer require tiben/crontab-manager ~1.0

3) Required Linux binaries

    i) sudo apt-get install wget

    ii) ffmpeg (needs to be compiled for latest release)

        git clone git://source.ffmpeg.org/ffmpeg.git ffmpeg
        cd ffmpeg
        ./configure
        make
        sudo make install

4) If you want to use a 433Mhz TX/RX module (OPTIONAL)
    (Cheap transmitter/receiver: http://www.ebay.ca/itm/5pcs-433Mhz-RF-transmitter-and-receiver-kit-for-Arduino-/251673838721?pt=LH_DefaultDomain_0&hash=item3a98ee0481)

    i) git clone git://github.com/ninjablocks/433Utils.git

    ii) cd 433Kit/RPi_utils

    iii) Change line "mySwitch.send(code, 26);" in codesend.cpp ( I had to do this in my case as my codes were longer)

    iv) Install WiringPi from https://projects.drogon.net/raspberry-pi/wiringpi/download-and-install/
    
    v)(As per the original rc_switch distribution) Follow the instructions to install the wiringpi library. After that you can compile the example programs by executing make.

    vi) Copy codesend to /var/www/powercontrol/bin/ or {PATH_TO_WWW}/powercontrol/bin

    vii) **NOTE** You will need to use RFSniffer to get your specific codes and then enter them in the config.ini in the appropriate section

5) If you want to use a PIR Sensor (OPTIONAL)
    PIR setup instructions http://www.raspberrypi-spy.co.uk/2013/01/cheap-pir-sensors-and-the-raspberry-pi-part-1/

6) If you want to use the DHT22 temperature and humdity sensor (OPTIONAL)
    https://www.youtube.com/watch?v=IHTnU1T8ETk

    i) Pinout on the DHT22
    PIN 1 --> VCC, 
    PIN 2 --> GPIO 4, 
    PIN 3 --> NOT USED, 
    PIN 4 --> GROUND

    ii) Voltage divider need to protect GPIO pin

    For DHT22 5V operation connect pin 1 to 5V and pin 4 to ground.

    The following pin 2 connection works for me.  Use at YOUR OWN RISK.

        5V--5K_resistor--+--10K_resistor--Ground
                         |
        DHT22 pin 2 -----+
                         |
        gpio ------------+

    3) Compile instructions:
        cd ~
        git clone https://github.com/adafruit/Adafruit_Python_DHT.git
        cd Adafruit_Python_DHT
        sudo python setup.py install

7) Modify visudo (OPTIONAL)
    
    i) For the 433mhz emitter if using it
    www-data ALL=NOPASSWD: {PATH_TO_WWW}/powercontrol/bin/codesend

    ii) For the PIR sensor if using it
    www-data ALL=NOPASSWD: /{PATH_TO_WWW}/powercontrol/bin/pir/pir.sh

    iii) For the DHT22 module if using it
    www-data ALL=NOPASSWD: /{PATH_TO_WWW}/powercontrol/bin/dht22/dht22.sh