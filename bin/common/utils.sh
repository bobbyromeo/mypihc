#!/bin/bash

CONFIG_FILE="$DIR/../../config.ini"
LOG_FILE="$DIR/../../mypihc.log"

chown www-data. $LOG_FILE

function my_date {
    date "+%Y-%m-%d %H:%M:%S"
}

function echo_log {
    echo "$(my_date),*** - $PROG - $1" >> $LOG_FILE
}
