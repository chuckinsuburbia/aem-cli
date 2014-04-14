#!/usr/bin/ksh
alert=$1
shift
if [[ "$1" == "CTM_CTMS_ORDER" ]]; then
	job=`echo $2 |awk -F"_" '{print $1}'`
#	wget -O - http://controlm/bip/ctmservice.php?job=$job 2>/dev/null
	curl --insecure https://controlm/bip/ctmservice.php?job=$job 2>/dev/null
else
	echo $3
fi
