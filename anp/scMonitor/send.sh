#!/bin/bash
LOG=/in/AEM/anp/scMonitor/send.log
TICKET=/in/AEM/anp/scMonitor/current_ticket.txt
EMAIL=/in/AEM/anp/scMonitor/email.rec

function dateecho {
	echo "$(date "+%Y%m%d %H%M%S"): $1"
}

function closeTicket {
	dateecho "CLOSE YESTERDAY'S TICKET $IM" >> $LOG
	echo "CLOSE YESTERDAY'S TICKET $IM"
	result=$(/in/AEM/servicecenter/anpCloseEntry.php "12345678" "${IM}" 2>&1)
	dateecho $result >> $LOG
	echo $result
	success=$(echo $result | grep SUCCESS | wc -l)
	#if [[ $success == "0" ]]; then
	if [ ! $(echo $result | grep -q SUCCESS ; echo $?) ]; then
		exit 1
	fi
}

function openTicket {
	dateecho "OPEN NEW TICKET" >> $LOG
	echo "OPEN NEW TICKET"
	im=$(/in/AEM/servicecenter/anpCreateEntry.php "12345678" "TEST" "TEST" "TEST SC OUTBOUND EMAIL MONITOR" "Warning" "PEMEMAILTEST" "TEST" "TEST" "TEST" 2>&1)
	echo $im
	if [[ $? != 0 ]]; then
		exit 1
	fi
	echo $im >$TICKET
	dateecho $im >> $LOG
}

function updateTicket {
	dateecho "UPDATE TICKET $IM" >>$LOG
	echo "UPDATE TICKET $IM"
	result=$(/in/AEM/servicecenter/anpUpdateEntry.php "12345678" "$IM" "$(date +%H%M) TEST UPDATE" 2>&1)
	dateecho $result >> $LOG
	echo $result
	if [ ! $(echo $result | grep -q SUCCESS ; echo $?) ]; then
		exit 1
	fi
}

###############################################################################
# Begin Processing
###############################################################################
if [[ -f "$EMAIL" ]]; then
        rm -f "$EMAIL"
fi

if [[ -f ${TICKET} ]] ; then
	IM=$(cat $TICKET)
	CURDATE=$(date "+%b %d")
	FILEDATE=$(date "+%b %d" --reference=$TICKET)
	if [[ "$CURDATE" != "$FILEDATE" ]]; then
		closeTicket
		openTicket
	else
		updateTicket
	fi
else
	openTicket
fi

exit 0

