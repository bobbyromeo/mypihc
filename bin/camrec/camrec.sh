#!/bin/bash
PROG="camrec"
DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )

source "$DIR/../common/utils.sh"
if [ $# -ne 2 ]; then
    echo_log "Requires two arguments: camera<1|2> <start|stop|restart|status>"
    exit 1
fi

STATUS=$1
CAMERA=$2
PID_FILE="$DIR/$CAMERA.pid"

is_running(){
    [ -e $PID_FILE ]
}

read_config() {
    search=$1
    tmp_file=$(mktemp)
    sed -n "1,/\[$search\]/d;/\[/,\$d;/^\$/d;p" $CONFIG_FILE > $tmp_file
    oldIFS="$IFS"
    IFS="="
    while read key value
    do
        key=${key// }
        value=${value// }
        eval $key="$value"
    done < $tmp_file
    IFS="$oldIFS"
    #cat $tmp_file
}

start() {
    read_config $CAMERA
    read_config 'config'

    if (is_running); then
        echo_log "Already running $PROG.sh on $CAMERA"
        exit 0
    fi

    if [ -n "$save_to_dir" ] && [ -n "$name" ]
    then
        # [ -d $save_to_dir ] || mkdir $save_to_dir
        [ -d $save_to_dir ] ||  { echo_log "Directory: ${save_to_dir} does not exist. Aborting."; exit 1; }
        mkdir -p ${save_to_dir%/}/mypihc/$name || echo_log "echo_log "Unable to create directory: ${save_to_dir}. Aborting."; exit 1;"
        save_to_dir=${save_to_dir%/}/mypihc/$name
    else
        echo_log "Unable to read config file for data"
        exit 1
    fi

    if [ -n "$days_to_purge" ] && [ -n "$cam_record_length" ] && [ -n "$path_to_ffmpeg" ]
    then
        find $save_to_dir -name "$name*" -ctime +$days_to_purge -exec rm {} \;
    else
        echo_log "Unable to read config file for data"
        exit 1
    fi
    echo_log "Starting $PROG.sh on $CAMERA"
    command -v $path_to_ffmpeg >/dev/null 2>&1 || { echo_log >&2 "I require ffmpeg but it's not installed.  Aborting."; exit 1; }
    echo_log "$path_to_ffmpeg -use_wallclock_as_timestamps 1 -f mjpeg -i \"http://$ip/videostream.cgi?user=XXXXXX&pwd=XXXXXX\" -i \"http://$ip/videostream.asf?user=XXXXXX&pwd=XXXXXX\" -map 0:v -map 1:a -acodec copy -vcodec copy -f segment -segment_time $cam_record_length -reset_timestamps 1 \"$save_to_dir/$name_`date +%F_%H-%M-%S`_%03d.mkv\" > /dev/null 2>&1 &"

    # echo -n "Starting $PROG.sh on camera: $CAMERA"
    $path_to_ffmpeg -use_wallclock_as_timestamps 1 -f mjpeg -i "http://$ip/videostream.cgi?user=$username&pwd=$password" \
        -i "http://$ip/videostream.asf?user=$username&pwd=$password" -map 0:v -map 1:a -acodec copy -vcodec copy -f \
        segment -segment_time $cam_record_length -reset_timestamps 1 "$save_to_dir/$name_`date +%F_%H-%M-%S`_%03d.mkv" > /dev/null 2>&1 &

    echo $! > "$PID_FILE"
    echo_log "started"
}

stop(){
    #echo -n "Stopping $PROG.sh $CAMERA: "
    echo_log "Stopping $PROG.sh on $CAMERA"
    if (is_running); then
        kill -0 `cat $PID_FILE` && kill `cat $PID_FILE`
        #RETVAL=$?
        #echo
        rm "$PID_FILE"
        #return $RETVAL
        echo_log "stopped"
    else
      echo_log "$PID_FILE not found"
    fi

}

status(){
    # echo -n "Checking for $PID_FILE: "
    echo_log "Checking for $PID_FILE: "
    if (is_running); then
        echo_log "found"
    else
        echo_log "not found"
    fi
}

restart(){
    stop
    start
}

case $STATUS in
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
        status
        ;;
    # *) echo "usage: $0 <start|stop|restart|status>"
    *) echo_log "usage: $0 camera<1|2> <start|stop|restart|status>"
        exit
        ;;
esac
