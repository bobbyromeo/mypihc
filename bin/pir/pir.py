#!/usr/bin/python
#
# pir.py
# Detect movement using a PIR module
#
# Author : Bobby
# Date   : 21/01/2013

# Import required Python libraries
import RPi.GPIO as GPIO
import time
import signal
import sys
import subprocess
import os
import logging
import logging.config
import datetime
import threading
import glob
import shlex

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '..', 'common')))
from utils import change_file_owner, sendGVSMS, sendEmail, CONFIG, IS_ERROR

# global variables
already_armed = 0
already_sent = 0
already_recording = 0
already_sent_sms = 0

t1_stop = None
t1 = None

log = logging.getLogger(__name__)
change_file_owner('www-data', os.path.abspath(os.path.join(os.path.dirname(__file__), '../..', 'mypihc.log')))


def signal_handler(signal, frame):
    global t1_stop, t1, already_armed, already_recording
    log.warn('Application received signal to exit!')
    if already_armed and CONFIG['arm_camera']:
        log.info("Disarming camera(s)...")
        armCamera(0)

    if already_recording and ['record_on_motion']:
        log.info("Stopping recording...")

        t1_stop.set()
        t1.join()
        if t1.isAlive():
            log.debug("Thread alive")
        else:
            log.debug("Thread dead")
        log.info("Recorded: " + str(countFiles()) + " file(s).")
    log.warn('Program exit!')
    GPIO.cleanup()
    sys.exit(0)


def armCamera(flag):
    global already_armed

    if flag == 1:
        if already_armed == 2:
            log.info("Camera %s already armed" % CONFIG['cam_ip'])
            return
        else:
            log.info("Arming camera %s" % CONFIG['cam_ip'])
            already_armed += 1

    if flag == 0:
        log.info("Disarming camera %s" % CONFIG['cam_ip'])
        already_armed = 0

    command = [
        CONFIG['path_to_wget'], '-q', '-O', '-',
        'http://' + CONFIG['cam_ip'] + '/set_alarm.cgi?motion_armed=1&motion_sensitivity=' + CONFIG['cam_motion_sensitivity'] + '&mail=' + str(flag) + '&user=' + CONFIG['cam_user'] + '&pwd=' + CONFIG['cam_password']
    ]
    log.debug(' '.join(command))
    p = subprocess.Popen(command, stdout=subprocess.PIPE)
    stdout, stderr = p.communicate()
    log.debug('Command Output: ' + stdout.strip(' \t\n\r'))


def startRecord(arg, stop_event):
    p = None

    target_dir = os.path.join(CONFIG['save_to_dir'], 'pir')
    if not os.path.exists(target_dir):
        os.makedirs(target_dir)

    now = time.time()
    for f in os.listdir(target_dir):
        f = os.path.join(target_dir, f)
        if os.stat(f).st_mtime < now - CONFIG['cam_days_to_purge'] * 86400:
            if os.path.isfile(f):
                os.remove(os.path.join(target_dir, f))

    while(not stop_event.is_set()):
        log.debug("In thread...")
        if p:
            p.terminate()

        cmd = CONFIG['path_to_ffmpeg'] + ' -use_wallclock_as_timestamps 1 -f mjpeg -i "http://' + CONFIG['cam_ip'] + '/videostream.cgi?user=' \
            + CONFIG['cam_user'] + '&pwd=' + CONFIG['cam_password'] + '" -i "http://' + CONFIG['cam_ip'] + '/videostream.asf?user=' + CONFIG['cam_user'] \
            + '&pwd=' + CONFIG['cam_password'] + '" -map 0:v -map 1:a -acodec copy -vcodec copy '  \
            + os.path.join(target_dir, CONFIG['cam_prefix_file_name'] + '_' + datetime.datetime.now().strftime('%Y-%m-%d_%H-%M-%S') + '.mkv')
        log.debug(shlex.split(cmd))

        p = subprocess.Popen(
            shlex.split(cmd),
            shell=False,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE)
        stop_event.wait(CONFIG['cam_record_length'])
    log.debug("Stopping thread...")
    p.terminate()


def countFiles():
    return len([f for f in glob.glob(os.path.join(CONFIG['save_to_dir'], CONFIG['cam_prefix_file_name'] + '*.asf')) if os.path.isfile(f)])


def main():
    global already_sent_sms, already_sent, already_recording, t1_stop, t1

    # catch signals
    signal.signal(signal.SIGINT, signal_handler)
    signal.signal(signal.SIGTERM, signal_handler)
    signal.signal(signal.SIGINT, signal_handler)

    if IS_ERROR:
        log.error("Unable to start PIR!")
        return

    # Use BCM GPIO references
    # instead of physical pin numbers
    GPIO.setmode(GPIO.BCM)

    # Alerts OFF
    GPIO.setwarnings(False)

    # Define GPIO to use on Pi
    GPIO_PIR = 7

    log.info("PIR Module Test (CTRL-C to exit)")

    # Set pin as input
    GPIO.setup(GPIO_PIR, GPIO.IN)

    Current_State = 0
    Previous_State = 0

    try:
        log.warn("Waiting for PIR to settle ...")

        # Loop until PIR output is 0
        while GPIO.input(GPIO_PIR) == 1:
            Current_State = 0

        log.info("  Ready")
        # Loop until users quits with CTRL-C
        while True:
            # Read PIR state
            Current_State = GPIO.input(GPIO_PIR)

            if Current_State == 1 and Previous_State == 0:
                # PIR is triggered
                log.info("  Motion detected!")
                # sms
                if not already_sent_sms and CONFIG['send_sms']:
                    log.info("  Sending sms through email...")
                    sendEmail(
                        CONFIG['email_sms'],
                        CONFIG['email_from_addr'],
                        'Motion detected',
                        'There\'s been movement detected in the house.',
                        CONFIG['email_smtp'],
                        CONFIG['email_smtp_port'],
                        CONFIG['email_user'],
                        CONFIG['email_passwd'])
                    already_sent_sms = 1
                # GV sms
                if not already_sent_sms and CONFIG['send_gv_sms']:
                    log.info("  Sending sms through GV...")
                    sendGVSMS(
                        CONFIG['gv_user'],
                        CONFIG['gv_passwd'],
                        CONFIG['sms_num'],
                        'There\'s been movement detected in the house.')
                    already_sent_sms = 1
                # email
                if not already_sent and CONFIG['email_on_motion']:
                    log.info("  Sending email...")
                    sendEmail(
                        CONFIG['email_send_to'],
                        CONFIG['email_from_addr'],
                        'Motion detected',
                        'There\'s been movement detected in the house.',
                        CONFIG['email_smtp'],
                        CONFIG['email_smtp_port'],
                        CONFIG['email_user'],
                        CONFIG['email_passwd'])

                    already_sent = 1
                # record
                if not already_recording and CONFIG['record_on_motion']:
                    log.info("  Starting recording!")
                    t1_stop = threading.Event()
                    t1 = threading.Thread(target=startRecord, args=(
                        1,
                        t1_stop,
                    ))
                    t1.start()
                    already_recording = 1

                # arm camera
                if CONFIG['arm_camera']:
                    armCamera(1)
                # Record previous state
                Previous_State = 1
            elif Current_State == 0 and Previous_State == 1:
                # PIR has returned to ready state
                log.info("  Ready")
                Previous_State = 0

            # Wait for 10 milliseconds
            time.sleep(0.01)

    except KeyboardInterrupt:
        log.warn("  Quit")
        # Reset GPIO settings
        GPIO.cleanup()

if __name__ == '__main__':
    main()
