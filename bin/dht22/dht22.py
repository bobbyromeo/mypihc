#!/usr/bin/env python
import sys
import os
import logging
import logging.config
from decimal import Decimal
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '..', 'pir')))

from pir import c, sendGVSMS, sendEmail, change_file_owner

logging.config.fileConfig(os.path.abspath(os.path.join(os.path.dirname(__file__), '../..', 'logging.conf')))
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

    h, t = dht.read_retry(dht.DHT22, 4)
    sys.stdout.write("[%0.1f,%0.1f]" % (t, h))
    log.info("Temperature/humidity reading: %0.1f, %0.1f" % (t, h))

    log.info("Checking temperature over threshold: %.1f > %.1f = %s" % (t, Decimal(c['temp_threshold_cel']), t > Decimal(c['temp_threshold_cel'])))

    if (t > Decimal(c['temp_threshold_cel']) and c['temp_threshold_alerts']):
        try:
            log.info("Attempting to send GV SMS...")
            sendGVSMS(c['gv_user'], c['gv_passwd'], c['sms_num'], 'Temperature reading of: %0.1f degrees is over threshold!' % t)

            log.info("Attempting to send Email...")
            sendEmail(c['email_send_to'], c['email_from_addr'], 'Temperature reading on mypihc', 'Temperature reading of: %0.1f degress is over threshold!' % t, c['email_smtp'], c['email_smtp_port'], c['email_user'], c['email_passwd'])
        except Exception, e:
            log.error(str(e))


if __name__ == '__main__':
    main()
