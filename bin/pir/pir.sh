#!/bin/bash
SERVICE="pir"
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
        echo_log "$DATE: $SERVICE service running, everything is fine"
        exit 0
    else
        echo_log "$DATE: $SERVICE is not running"
    fi

    if ls -l $DIR/$SERVICE.log* >/dev/null 2>&1
    then
        echo_log "$DATE: Deleting log file"
        sudo rm $DIR/${SERVICE}.log*
    else
        echo_log "$DATE: No log file found to delete"
    fi

    # PREFIX=$(cat ../../$DIR/config.ini | grep -i name | awk -F= '{print $2}' | tr -d ' ')
    # SAVETODIR=$(cat $DIR/${SERVICE}.ini | grep -i savetodir | awk -F= '{print $2}' | tr -d ' ')
    # echo $DIR $PREFIX $SAVETODIR
    # exit 0
    # if ls -l $SAVETODIR/${PREFIX}_* >/dev/null 2>&1
    # then
    #     echo "$DATE: Deleting old recording file(s)"
    #     sudo rm $SAVETODIR/${PREFIX}_*
    # else
    #     echo "$DATE: No recording file(s) found to delete"
    # fi

    echo_log "$DATE: Starting $SERVICE.py"
    sudo python $DIR/$SERVICE.py > /dev/null 2>&1 &
    echo $! > "$PID_FILE"
    echo_log "$DATE: $SERVICE.py started"
}

stop() {
    if [ "${#OUTPUT}" -eq 0 ]
    then
        echo_log "$DATE: $SERVICE not running"
        exit 0
    else
        echo_log "$DATE: Attempting to stop $SERVICE..."
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
    echo_log "$DATE: $SERVICE.py stopped"
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
            echo_log "$DATE: $SERVICE service running, everything is fine"
        else
            echo_log "$DATE: $SERVICE is not running"
        fi
        ;;
    *) echo_log "usage: $0 <start|stop|restart|status>"
        exit
        ;;
esac
