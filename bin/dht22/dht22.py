#!/usr/bin/env python
import sys
import os
import logging
import logging.config
from decimal import Decimal

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '..', 'common')))
from utils import change_file_owner, sendEmail, sendGVSMS, CONFIG

log = logging.getLogger(__name__)

change_file_owner('www-data', os.path.abspath(os.path.join(os.path.dirname(__file__), '../..', 'mypihc.log')))


def main():
    log.info("Checking temperature...")
    try:
        import Adafruit_DHT as dht
    except ImportError:
        sys.stdout.write('Adafruit_DHT library not found!')
        log.error('Adafruit_DHT library not found!')
        sys.exit(1)

    try:
        h, t = dht.read_retry(dht.DHT22, 4)
    except Exception, e:
        log.error("Unable to retrieve temperature/humidity data from DHT22 sensor: %s" % str(e))
        sys.exit(1)

    if not all([h, t]):
        log.error("Unable to retrieve temperature/humidity data from DHT22 sensor")
        sys.exit(1)

    if CONFIG['temp_in_fahrenheit']:
        t = (t * (9.0 / 5.0)) + 32

    sys.stdout.write("[%0.1f,%0.1f]" % (t, h))

    log.info("Temperature/humidity reading: %0.1f, %0.1f" % (t, h))
    log.info("Checking temperature over threshold: %.1f > %.1f = %s" % (t, Decimal(CONFIG['temp_threshold']), t > Decimal(CONFIG['temp_threshold'])))

    if (t > Decimal(CONFIG['temp_threshold']) and CONFIG['temp_threshold_alerts']):
        try:
            log.info("Attempting to send GV SMS...")
            sendGVSMS(CONFIG['gv_user'], CONFIG['gv_passwd'], CONFIG['sms_num'], 'Temperature reading of: %0.1f degrees is over threshold!' % t)

            log.info("Attempting to send Email...")
            sendEmail(CONFIG['email_send_to'], CONFIG['email_from_addr'], 'Temperature reading on mypihc', 'Temperature reading of: %0.1f degress is over threshold!' % t, CONFIG['email_smtp'], CONFIG['email_smtp_port'], CONFIG['email_user'], CONFIG['email_passwd'])
        except Exception, e:
            log.error(str(e))


if __name__ == '__main__':
    main()
