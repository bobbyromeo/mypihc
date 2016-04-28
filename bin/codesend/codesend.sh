#!/bin/bash
PROG="codesend"
DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
PATH_TO_CODESEND="$DIR/codesend"
source "$DIR/../common/utils.sh"
if [ $# -ne 1 ]
then
    echo_log "Requires one argument: <code>"
    exit 1
fi
CODE=$1
command -v $PATH_TO_CODESEND >/dev/null 2>&1 || { echo_log >&2 "I require codesend but it's not installed.  Aborting."; exit 1; }
echo_log "Running: $PATH_TO_CODESEND $CODE 2>&1"
OUTPUT=`$PATH_TO_CODESEND $CODE 2>&1`
RET=$?
if [[ $RET -eq 0 ]]
then
    echo_log "Successfully ran $PROG [ $@ ]"
    echo $OUTPUT
else
    echo_log "Error: Command $PROG [ $@ ] returned $RET"
    exit $RET
fi
