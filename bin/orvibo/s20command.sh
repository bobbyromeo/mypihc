#!/bin/bash
PROG="s20Command"
DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )

source "$DIR/../common/utils.sh"
if [ $# -ne 3 ]; then
    echo_log "Requires three arguments: <turn_on|turn_off> <MAC> <IP>"
    exit 1
fi

if ! type xxd > /dev/null; then
    echo_log "Requires xxd binary"
    exit 1
fi

if ! type nc > /dev/null; then
    echo_log "Requires nc binary"
    exit 1
fi

ACTION=$1
MAC=$2
IP=$3
REVERSEDMAC=${MAC:10:1}${MAC:11:1}${MAC:8:1}${MAC:9:1}${MAC:6:1}${MAC:7:1}${MAC:4:1}${MAC:5:1}${MAC:2:1}${MAC:3:1}${MAC:0:1}${MAC:1:1}

# Subscribe to the device:
echo '6864001e636c'$MAC'202020202020'$REVERSEDMAC'202020202020' | xxd -r -p | nc -u -w1 -p10000 $IP 10000 | xxd -p 2>&1

case $ACTION in
turn_off)
        # Switch Off
        echo_log "Switching off: $MAC $IP"
        echo '686400176463'$MAC'2020202020200000000000' | xxd -r -p | nc -u -w1 -p10000 $IP 10000 | xxd -p 2>&1
        ;;
turn_on)
        # Switch On
        echo_log "Switching on: $MAC $IP"
        echo '686400176463'$MAC'2020202020200000000001' | xxd -r -p | nc -u -w1 -p10000 $IP 10000 | xxd -p 2>&1
        ;;
esac

exit;
