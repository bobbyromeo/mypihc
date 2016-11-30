#!/bin/bash
PROG="cpu"
DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
PATH_TO_BIN="$DIR/cpu.py"
source "$DIR/../common/utils.sh"
if [ $# -ne 0 ]
then
    echo_log "Requires no arguments"
    exit 1
fi
CODE=$1
command -v $PATH_TO_BIN >/dev/null 2>&1 || { echo_log >&2 "I require cpu.py but it's not installed. Aborting."; exit 1; }
echo_log "Running: $PATH_TO_BIN 2>&1&"

OUTPUT=`$PATH_TO_BIN 2>&1&`
RET=$?
if [[ $RET -eq 0 ]]
then
    echo_log "Successfully ran $PROG, Output: $OUTPUT"
    echo $OUTPUT
else
    echo_log "Error: Command $PROG, Output: $OUTPUT, Returned: $RET"
    exit $RET
fi
