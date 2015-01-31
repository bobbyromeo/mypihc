#!/bin/bash
PROG="camrec"
DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
#PID_FILE="$DIR/$PROG.pid"
CONFIG_FILE="$DIR/../../config.ini"

if [ $# -ne 2 ]; then
    echo "Requires two arguments: camera<1|2> <start|stop|restart|status>"
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
    if [ -n "$save_to_dir" ] && [ -n "$name" ]
    then
        save_to_dir=${save_to_dir%/}/$name
        [ -d $save_to_dir ] || mkdir $save_to_dir
    else
        echo "Unable to read config file for data"
        exit 1
    fi
    
    if [ -n "$days_to_purge" ] && [ -n "$cam_record_length" ] && [ -n "$path_to_ffmpeg" ]
    then
        find $save_to_dir -name "$name*" -ctime +$days_to_purge -exec rm {} \;
    else
        echo "Unable to read config file for data"
        exit 1
    fi
    command -v $path_to_ffmpeg >/dev/null 2>&1 || { echo >&2 "I require ffmpeg but it's not installed.  Aborting."; exit 1; }
    # echo "$path_to_ffmpeg -use_wallclock_as_timestamps 1 -f mjpeg -i \"http://$ip/videostream.cgi?user=$username&pwd=$password\"" \
    #     "-i \"http://$ip/videostream.asf?user=$username&pwd=$password\" -map 0:v -map 1:a -acodec copy -vcodec copy -f" \
    #     "segment -segment_time $cam_record_length -reset_timestamps 1 \"$save_to_dir/$name_`date +%F_%H-%M-%S`_%03d.mkv\" > /dev/null 2>&1 &"
    
    echo -n "Starting $PROG.sh $CAMERA: " 
    $path_to_ffmpeg -use_wallclock_as_timestamps 1 -f mjpeg -i "http://$ip/videostream.cgi?user=$username&pwd=$password" \
        -i "http://$ip/videostream.asf?user=$username&pwd=$password" -map 0:v -map 1:a -acodec copy -vcodec copy -f \
        segment -segment_time $cam_record_length -reset_timestamps 1 "$save_to_dir/$name_`date +%F_%H-%M-%S`_%03d.mkv" > /dev/null 2>&1 &

    echo $! > "$PID_FILE"
    echo "started"
}

stop(){
    echo -n "Stopping $PROG.sh $CAMERA: "
    if (is_running); then
      kill -0 `cat $PID_FILE` && kill `cat $PID_FILE`
      #RETVAL=$?
      echo
      rm "$PID_FILE"
      #return $RETVAL
    else
      echo "$PID_FILE not found"
    fi
    echo "stopped"
}

status(){
    echo -n "Checking for $PID_FILE: "
    if (is_running); then
      echo "found"
    else
      echo "not found"
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
    *) echo "usage: $0 <start|stop|restart|status>"
        exit
        ;;
esac