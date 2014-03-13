#!/bin/bash

SCRIPTPATH=$( cd $(dirname $0) ; pwd -P )
SPOOLDIR="/in/AEM/spool/"
INOTIFYWAIT="$(which inotifywait) -mqe create ${SPOOLDIR}" || exit 1
LOG="/in/AEM/logs/spool_daemon.log"

${INOTIFYWAIT} | while read LINE ; do ${SCRIPTPATH}/aemSpoolProcess.php >${LOG} 2>&1 ; done 
#while read LINE <$(${INOTIFYWAIT}) ; do ${SCRIPTPATH}/aemSpoolProcess.php >${LOG} 2>&1 ; done 
