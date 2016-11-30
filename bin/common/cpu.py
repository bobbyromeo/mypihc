#!/usr/bin/env python
import sys
import os
import logging
import logging.config
from subprocess import PIPE, Popen

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '..', 'common')))
from utils import change_file_owner, sendEmail, sendGVSMS, CONFIG

log = logging.getLogger(__name__)

change_file_owner('www-data', os.path.abspath(os.path.join(os.path.dirname(__file__), '../..', 'mypihc.log')))


def get_cpu_temperature():
    process = Popen(['vcgencmd', 'measure_temp'], stdout=PIPE)
    output, _error = process.communicate()
    return float(output[output.index('=') + 1:output.rindex("'")])


def get_cpu_frequency():
    process = Popen(['cat', '/sys/devices/system/cpu/cpu0/cpufreq/scaling_cur_freq'], stdout=PIPE)
    output, _error = process.communicate()
    return int(output) / 1000


def main():
    log.info("Checking CPU frequency and temperature...")
    t = get_cpu_temperature()
    f = get_cpu_frequency()

    if not all([f, t]):
        log.error("Unable to retrieve frequency/temperature data from DHT22 CPU")
        sys.exit(1)

    if CONFIG['temp_in_fahrenheit']:
        t = (t * (9.0 / 5.0)) + 32

    sys.stdout.write("[%i,%0.1f]" % (f, t))

    log.info("CPU Frequency / Temperature: %i, %0.1f" % (f, t))
    log.info("Checking CPU temperature over threshold: %.1f > %.1f = %s" % (t, float(CONFIG['cpu_temp_threshold']), t > float(CONFIG['cpu_temp_threshold'])))

    if (t > float(CONFIG['cpu_temp_threshold']) and CONFIG['cpu_temp_threshold_alerts']):
        try:
            log.info("Attempting to send GV SMS...")
            sendGVSMS(CONFIG['gv_user'], CONFIG['gv_passwd'], CONFIG['sms_num'], 'CPU Temperature reading of: %0.1f degrees is over threshold!' % t)

            log.info("Attempting to send Email...")
            sendEmail(CONFIG['email_send_to'], CONFIG['email_from_addr'], 'CPU Temperature reading on mypihc', 'CPU Temperature reading of: %0.1f degress is over threshold!' % t, CONFIG['email_smtp'], CONFIG['email_smtp_port'], CONFIG['email_user'], CONFIG['email_passwd'])
        except Exception, e:
            log.error(str(e))

if __name__ == '__main__':
    main()
