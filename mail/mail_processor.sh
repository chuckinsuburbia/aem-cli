#!/bin/bash

if [ -n ${MAIL} ] ; then
	export MAIL="/var/spool/mail/aemuser"
fi
if [ -n ${AEMBASE} ] ; then
	export AEMBASE="/in/AEM"
fi

INOTIFYWAIT="$(which inotifywait) -mqe modify ${MAIL}"
#SCRIPTPATH=$( cd $(dirname $0) ; pwd -P )
SCRIPTPATH="${AEMBASE}/mail"
LOG="${AEMBASE}/logs/mail_daemon.log"

${INOTIFYWAIT} | while read LINE ; do ${SCRIPTPATH}/emailHandler.php >>${LOG} 2>&1 ; done
