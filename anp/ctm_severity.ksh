#!/usr/bin/ksh
alert=$1
shift
if [[ "$3" != "Clear" && "$1" == "CTM_CTMS_ORDER" ]]; then
	job=`echo $2 |awk -F"_" '{print $1}'`
	curl --insecure https://bip.corp.gaptea.com/bare/control-m-severity?job=$job 2>/dev/null
else
	echo $3
fi
