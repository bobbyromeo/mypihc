Power Control
==================

A web interface to remotely control/schedule 433MHz power switches, a PIR sensor alarm, and record from foscam IP cameras all through a Raspberry Pi. At the very least it needs IP cameras and of course a Raspberry Pi to function.

Check [this article](http://bobbyromeo.com/wp/diy-alarm-monitoring-system-w-raspberry-pi-foscam-sensors/) for instructions and screenshots.


Configuration
==================
All configuration is handle in the file: config.ini

    i) Configure your cameras in the appropriate sections [camera1], [camera2]

    ii) Enable the camera module "use_camera_module=" in section [config]. Switches d, e handle
        the recording for the cameras

    iii) Configure a save directory in the [config] section, "save_to_dir="

    iii) Alter the crontab so user www-data can modify crontabs under the user pi, also make sure the pi can use sudi w/o a password

    www-data ALL=(pi) NOPASSWD: /usr/bin/crontab
    pi ALL=(ALL) NOPASSWD:ALL

    iv) Install ffmpeg (See below)


Requirements
==================

1) Install lighttpd and php5 from http://chris-labs.de/hardware/raspberrypi/2013/10/10/raspberrypi-433mhz-switch-control.html

    i) sudo apt-get install lighttpd php5-common php5-cgi php5 php5-cli

    ii) Tell lighttpd to use fastcgi-php and reload the server:
        sudo lighty-enable-mod fastcgi-php
        sudo service lighttpd force-reload

    iii) Set permissions for the pi-user:

        sudo chown www-data:www-data /var/www
        sudo chmod 775 /var/www
        sudo usermod -a -G www-data pi

    iv) sudo chmod -vR g+w /var/www/mypihc
        sudo chown -R www-data:www-data /var/www/mypihc


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

    i) Follow the instructions to install the wiringpi library. After that you can compile the example programs by executing make. Install WiringPi from https://projects.drogon.net/raspberry-pi/wiringpi/download-and-install//

    ii) git clone git://github.com/ninjablocks/433Utils.git

    iii) cd 433Kit/RPi_utils

    iv) Change line "mySwitch.send(code, 26);" in codesend.cpp (I had to do this in my case as my codes were longer), execute make all

    v) Copy codesend to {PATH_TO_WWW}/mypihc/bin

    vi) **NOTE** You will need to use RFSniffer to get your specific codes and then enter them in the config.ini in the appropriate section.
        Switches a, b in the config file handle this.

    vii) Enable the module "use_433mhz_module=" in section [config]

    viii) For the 433mhz emitter if using it
        www-data ALL=NOPASSWD: {PATH_TO_WWW}/mypihc/bin/codesend

5) If you want to use a PIR Sensor (OPTIONAL)

    i) PIR setup instructions http://www.raspberrypi-spy.co.uk/2013/01/cheap-pir-sensors-and-the-raspberry-pi-part-1/

    ii) Enable the module "use_pir_module=" in section [config]. Switch c handles this.

    iii) Configure the options in the [pir] section

        - "email_on_motion": will email you provided you fill out the [email] section
        - "record_on_motion": kicks off a recording from a give camera when motion is triggered (record_with_camera1)
        - "arm_camera": turns on the camera's built-in email images feature (of course you need to configure the camera appropriately)
        - "send_sms": sends an email to your phone (to the email set in "email_sms" configured in [email] section)

    iv) Grant sudo access
    www-data ALL=NOPASSWD: /{PATH_TO_WWW}/mypihc/bin/pir/pir.sh

6) If you want to use the DHT22 temperature and humdity sensor (OPTIONAL)
    (Watch for more info: https://www.youtube.com/watch?v=IHTnU1T8ETk)

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

    iii) Compile instructions:
        cd ~
        git clone https://github.com/adafruit/Adafruit_Python_DHT.git
        cd Adafruit_Python_DHT
        sudo python setup.py install

    iv) Enable the module "use_dht22_module=" in section [config]

    v) Grant sudo access
    www-data ALL=NOPASSWD: /{PATH_TO_WWW}/mypihc/bin/dht22/dht22.py






