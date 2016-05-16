#!/bin/bash
SERVICE="pir"
PROG="pir"
DATE=`date`
OUTPUT=$(ps aux | grep -i ${SERVICE}.py | grep -i python | grep -Ev 'grep|sudo' | awk '{ print $2 }')
DIR=`dirname $0`
PID_FILE="$DIR/$SERVICE.pid"

source "$DIR/../common/utils.sh"
if [ $# -ne 1 ]; then
    echo_log "Requires one arguments: <start|stop|restart|status>"
    exit 1
fi

is_running(){
    [ -e $PID_FILE ]
}

start() {
    if [ "${#OUTPUT}" -gt 0 ] && is_running
    then
        echo_log "$SERVICE service running, everything is fine"
        exit 0
    else
        echo_log "$SERVICE is not running"
    fi

    if ls -l $DIR/$SERVICE.log* >/dev/null 2>&1
    then
        sudo rm $DIR/${SERVICE}.log*
    fi

    echo_log "Starting $SERVICE.py"
    sudo python $DIR/$SERVICE.py > /dev/null 2>&1 &
    # sudo python $DIR/$SERVICE.py
    echo $! > "$PID_FILE"
    echo_log "$SERVICE.py started"
}

stop() {
    if [ "${#OUTPUT}" -eq 0 ]
    then
        echo_log "$SERVICE not running"
        exit 0
    else
        echo_log "Attempting to stop $SERVICE..."
    fi

    for p in ${OUTPUT[@]} ; do
        if [[ -n $p ]]
        then
            echo " kill" $p
            sudo kill $p
        else
            break
        fi
    done
    rm "$PID_FILE"
    echo_log "$SERVICE.py stopped"
}

case $1 in
    start)
        start
        ;;
    stop)
        stop
        ;;
    restart)
        stop
        start
        ;;
    status)
        if [ "${#OUTPUT}" -gt 0 ]
        then
            echo_log "$SERVICE service running, everything is fine"
        else
            echo_log "$SERVICE is not running"
        fi
        ;;
    *) echo_log "usage: $0 <start|stop|restart|status>"
        exit
        ;;
esac
