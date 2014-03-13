#!/bin/bash

if [ -n ${MAIL} ] ; then
	export MAIL="/var/spool/mail/aemuser"
fi

INOTIFYWAIT="$(which inotifywait) -mqe modify ${MAIL}"
SCRIPTPATH=$( cd $(dirname $0) ; pwd -P )
LOG="${SCRIPTPATH}/logs/mail_daemon.log"

${INOTIFYWAIT} | while read LINE ; do ${SCRIPTPATH}/emailHandler.php >${LOG} 2>&1 ; done
#while read LINE <$(${INOTIFYWAIT}); do ${SCRIPTPATH}/emailHandler.php >${LOG} 2>&1 ; done 
