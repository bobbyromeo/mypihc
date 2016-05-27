#!/usr/bin/env python

import os
import re
import logging
import logging.config
import subprocess
import smtplib
import ConfigParser
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText

CONFIG = {}
IS_ERROR = None

logging.config.fileConfig(os.path.abspath(os.path.join(os.path.dirname(__file__), '../..', 'logging.conf')))
log = logging.getLogger(__name__)


def replace_multiple(rep, text):
    rep = dict((re.escape(k), v) for k, v in rep.iteritems())
    pattern = re.compile("|".join(rep.keys()))
    return pattern.sub(lambda m: rep[re.escape(m.group(0))], text)


def clean_creds(text):
    wget_regex = '([&|\?]user\=)(?:[^\&]+)(&pwd\=)(?:[^\&""]+)'
    gv_regex = '(-e)\s*(?:[^\s]+)\s*(-p)\s*(?:[^\s]+)'
    if re.match(r'.*' + wget_regex + r'.*', text):
        return re.sub(r'' + wget_regex + r'', r'\1XXXXXX\2XXXXXX', text)
    if re.match(r'.*' + gv_regex + r'.*', text):
        return re.sub(r'' + gv_regex + r'', r'\1 XXXXXX \2 XXXXXX', text)
    else:
        return text


def remove_file(filename):
    try:
        os.remove(filename)
    except OSError:
        pass


def create_savedir():
    try:
        target_dir = os.path.join(CONFIG['save_to_dir'], 'mypihc', 'pir')
        if not os.path.exists(target_dir):
            os.makedirs(target_dir)
    except Exception, e:
        log.error("Unable to create savedir %s: %s" % (target_dir, str(e)))


def getSettings():
    global CONFIG, IS_ERROR
    camera_to_use_record = None
    filename = "config.ini"
    config = ConfigParser.ConfigParser()
    config.read(os.path.join(os.path.dirname(__file__), '../..', filename))

    CONFIG['email_on_motion'] = config.get('pir', 'email_on_motion')
    CONFIG['record_on_motion'] = config.get('pir', 'record_on_motion')
    CONFIG['arm_camera'] = config.get('pir', 'arm_camera')
    CONFIG['send_sms'] = config.get('pir', 'send_sms')

    CONFIG['send_gv_sms'] = config.get('pir', 'send_gv_sms')
    CONFIG['gv_user'] = config.get('gv', 'gv_user')
    CONFIG['gv_passwd'] = config.get('gv', 'gv_passwd')
    CONFIG['sms_num'] = config.get('gv', 'sms_num')
    CONFIG['temp_threshold'] = config.get('dht22', 'temp_threshold')
    CONFIG['temp_threshold_alerts'] = config.get('dht22', 'temp_threshold_alerts')
    CONFIG['temp_in_fahrenheit'] = config.get('dht22', 'temp_in_fahrenheit')

    CONFIG['use_pir_module'] = config.get('config', 'use_pir_module')
    CONFIG['use_camera_module'] = config.get('config', 'use_camera_module')

    for i in ['camera1', 'camera2']:
        if config.get('pir', 'record_with_' + i):
            camera_to_use_record = i

    if CONFIG['use_pir_module']:
        if (camera_to_use_record in config.sections()):
            CONFIG['cam_ip'] = config.get(camera_to_use_record, 'ip')
            CONFIG['cam_user'] = config.get(camera_to_use_record, 'username')
            CONFIG['cam_password'] = config.get(camera_to_use_record, 'password')
            CONFIG['cam_prefix_file_name'] = config.get(camera_to_use_record, 'name')
            CONFIG['cam_motion_sensitivity'] = config.get(camera_to_use_record, 'cam_motion_sensitivity')

            CONFIG['cam_type'] = config.get(camera_to_use_record, 'type')

            if not all([CONFIG['cam_ip'], CONFIG['cam_user'], CONFIG['cam_password'], CONFIG['cam_prefix_file_name'], CONFIG['cam_motion_sensitivity'], CONFIG['cam_type']]):
                log.error("Missing camera specific parameters for PIR module")
                IS_ERROR = True

            if CONFIG['cam_type'] in config.sections():
                CONFIG['arm_camera_uri'] = config.get(CONFIG['cam_type'], 'arm_camera_uri')
                CONFIG['record_on_motion_command'] = config.get(CONFIG['cam_type'], 'record_on_motion_command')
        else:
            log.error("Error selecting camera recording for PIR module, exiting")
            IS_ERROR = True

    if CONFIG['use_pir_module'] or CONFIG['arm_camera']:
        CONFIG['path_to_wget'] = config.get('config', 'path_to_wget')
        if not (os.path.isfile(CONFIG['path_to_wget']) and os.access(CONFIG['path_to_wget'], os.X_OK)):
            log.error("Path to wget not set correctly")
            IS_ERROR = True

    if CONFIG['use_pir_module'] or CONFIG['use_camera_module']:
        CONFIG['path_to_ffmpeg'] = config.get('config', 'path_to_ffmpeg')
        if not (os.path.isfile(CONFIG['path_to_ffmpeg']) and os.access(CONFIG['path_to_ffmpeg'], os.X_OK)):
            log.error("Path to ffmpeg: " + CONFIG['path_to_ffmpeg'] + " not set correctly")
            IS_ERROR = True

    CONFIG['cam_record_length'] = int(config.get('config', 'cam_record_length'))
    CONFIG['cam_days_to_purge'] = int(config.get('config', 'days_to_purge'))

    CONFIG['email_from_addr'] = config.get('email', 'email_from_addr')
    CONFIG['email_user'] = config.get('email', 'email_user')
    CONFIG['email_passwd'] = config.get('email', 'email_passwd')
    CONFIG['email_smtp'] = config.get('email', 'email_smtp')
    CONFIG['email_smtp_port'] = config.get('email', 'email_smtp_port')
    CONFIG['email_send_to'] = config.get('email', 'email_send_to')
    CONFIG['email_sms'] = config.get('email', 'email_sms')

    if not all([CONFIG['email_from_addr'], CONFIG['email_user'], CONFIG['email_passwd'], CONFIG['email_smtp'], CONFIG['email_smtp_port'], CONFIG['email_send_to']]):
        log.error("Missing parameters for sending emails")
        IS_ERROR = True

    CONFIG['save_to_dir'] = config.get('config', 'save_to_dir')
    # check if save to directory exists
    if not os.path.isdir(CONFIG['save_to_dir']):
        log.error("Directory: " + CONFIG['save_to_dir'] + " does not exist, exiting")
        IS_ERROR = True


def change_file_owner(owner, filename):
    from pwd import getpwuid, getpwnam
    from grp import getgrnam
    try:
        if getpwuid(os.stat(filename).st_uid).pw_name != owner:
            uid = getpwnam(owner).pw_uid
            gid = getgrnam(owner).gr_gid
            os.chown(filename, uid, gid)
    except Exception, e:
        log.error("Unable to change owner %s of file: %s; %s" % (owner, filename, str(e)))


def which(program):
    def is_exe(fpath):
        return os.path.isfile(fpath) and os.access(fpath, os.X_OK)

    fpath, fname = os.path.split(program)
    if fpath:
        if is_exe(program):
            return program
    else:
        for path in os.environ["PATH"].split(os.pathsep):
            path = path.strip('"')
            exe_file = os.path.join(path, program)
            if is_exe(exe_file):
                return exe_file
    return None


def sendEmail(toAddr, fromAddr, subject, body, smtp_server, smtp_port, smtp_user, smtp_passwd):
    if not all([toAddr, fromAddr, smtp_server, smtp_port, smtp_user, smtp_passwd]):
        log.error("Cannot send email due to missing parameters")
        return

    """With this function we send out our html email"""
    # Create message container - the correct MIME type is multipart/alternative here!
    message = MIMEMultipart('alternative')
    message['subject'] = subject
    message['To'] = toAddr
    message['From'] = fromAddr
    message.preamble = """
                        Your mail reader does not support the report format.
                        Please visit us <a href="http://www.mysite.com">online</a>!"""

    # Record the MIME type text/html.
    htmlBody = MIMEText(body, 'html', _charset="UTF-8")

    # Attach parts into message container.
    # According to RFC 2046, the last part of a multipart message, in this case
    # the HTML message, is best and preferred.
    message.attach(htmlBody)

    # The actual sending of the e-mail
    smtp = smtp_server + ':' + str(smtp_port)
    server = smtplib.SMTP(smtp)

    # Print debugging output when testing
    # server.set_debuglevel(1)
    server.starttls()
    server.login(smtp_user, smtp_passwd)
    server.sendmail(fromAddr, toAddr, message.as_string())
    server.quit()


def sendGVSMS(gv_user, gv_passwd, sms_num, sms_msg):
    if not all([gv_user, gv_passwd, sms_num]):
        log.error("Cannot send GV SMS due to missing parameters")
        return

    gvoice_bin = which('gvoice')
    if gvoice_bin is None:
        log.error("Cannot send GV SMS due to missing binary: gvoice")
        return
    # CMD: /usr/bin/gvoice -e $GVACCT -p $GVPASS send_sms $SMSNUM "$SMSMSG"
    command = [
        gvoice_bin,
        '-e', gv_user,
        '-p', gv_passwd,
        'send_sms',
        sms_num,
        sms_msg
    ]
    log.debug(clean_creds(' '.join(command)))
    p = subprocess.Popen(command, stdout=subprocess.PIPE)
    stdout, stderr = p.communicate()
    log.debug('Command Output: ' + stdout.strip(' \t\n\r'))


def main():
    pass

if __name__ == '__main__':
    main()
else:
    # Always run get settings
    getSettings()
