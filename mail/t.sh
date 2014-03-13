#!/usr/bin/ksh
count=1
ls -1 email.?? | while read line; do
	mv $line email.$count.log
	count=$(($count+1))
done

