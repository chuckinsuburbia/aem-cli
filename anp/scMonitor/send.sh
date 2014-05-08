#!/bin/bash
LOG=/in/AEM/anp/scMonitor/send.log
TICKET=/in/AEM/anp/scMonitor/current_ticket.txt
EMAIL=/in/AEM/anp/scMonitor/email.rec

if [[ -f "$EMAIL" ]]; then
        rm -f "$EMAIL"
fi

CURDATE=$(date "+%b %d")
FILEDATE=$(date "+%b %d" --reference=$TICKET)

IM=$(cat $TICKET)
if [[ "$CURDATE" != "$FILEDATE" ]]; then
	echo "CLOSE YESTERDAY'S TICKET $IM" >> $LOG
	echo $IM;
	result=$(/in/AEM/servicecenter/anpCloseEntry.php "12345678" "${IM}" 2>&1)
	echo $result >> $LOG
	success=$(echo $result | grep SUCCESS | wc -l)
	if [[ $success == "0" ]]; then
		echo $result
		exit 1
	fi

	echo "OPEN NEW TICKET" >> $LOG
	im=$(/in/AEM/servicecenter/anpCreateEntry.php "12345678" "TEST" "TEST" "TEST SC OUTBOUND EMAIL MONITOR" "Warning" "PEMEMAILTEST" "TEST" "TEST" "TEST" 2>&1)
	success=$?
	echo $im >$TICKET
	if [[ $success == "0" ]]; then
		echo $im
		exit 1
	fi
	echo $im >> $LOG
	echo >> $LOG
fi

echo "UPDATE TICKET $IM" >>$LOG
result=$(/in/AEM/servicecenter/anpUpdateEntry.php "12345678" "$IM" "$(date +%H%M) TEST UPDATE" 2>&1)
echo $result >> $LOG
success=$(echo $result | grep SUCCESS | wc -l)
if [[ $success == "0" ]]; then
	echo $result
	exit 1
fi
exit 0

