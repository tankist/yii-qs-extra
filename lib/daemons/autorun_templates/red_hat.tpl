#!/bin/bash
#
# /etc/rc.d/init.d/@name@
#
# chkconfig: @chkconfig@
# description: @desc@
# processname: @bin_name@
# pidfile: @pid_file@
PID_FILE=@pid_file@
START_COMMAND="/usr/bin/php -f @bin_file@ {PROPERTIES.shellRunArgString}"

PROCESS=`basename @bin_file@`

# Source function library.
. /etc/rc.d/init.d/functions

start() {
    ${START_COMMAND}
    ret=$?
    if [ $ret -eq 0 ]; then
        action $"Starting @name@: " /bin/true
    else
        action $"Starting @name@: " /bin/false
    fi

    return $ret
}

stop() {
    PID=`cat @pid_file@  2>/dev/null `
    if [ -n "$PID" ]; then
        /bin/kill "$PID" >/dev/null 2>&1
        ret=$?
        if [ $ret -eq 0 ]; then
            STOPTIMEOUT=60
            while [ $STOPTIMEOUT -gt 0 ]; do
                /bin/kill -0 "$PID" >/dev/null 2>&1 || break
                sleep 1
                let STOPTIMEOUT=${STOPTIMEOUT}-1
            done
            if [ $STOPTIMEOUT -eq 0 ]; then
                echo "Timeout error occurred trying to stop @name@."
                ret=1
                action $"Stopping @name@: " /bin/false
            else
                action $"Stopping @name@: " /bin/true
            fi
        else
            action $"Stopping @name@: " /bin/false
        fi
    else
        ret=1
        action $"Stopping @name@: " /bin/false
    fi

    return $ret
}

case "$1" in
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
    reload)
        restart
    ;;
    status)
        status $PROCESS
    ;;
    *)
        echo "Usage: @name@ [start|stop|restart|status]"
        exit 1
    ;;
esac
