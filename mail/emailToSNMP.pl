#!/usr/bin/perl
#
# Script to read email and send SNMP trap to PEM
#
use lib './lib';
use BER;
use SNMP_Session;
use Switch;
use HTTP::Request;
use LWP::UserAgent;

$debug=2; # 0 = No log, 1 = Parsed Values  2 = Detailed Log
umask 0111;
$date=`/usr/bin/date "+%Y%m%d"`;
chomp($date);
$log="./log/email.$date.log";
#$log="/in/PEM/u/NetCmmnd/EMAIL/email.test.log";
$condlog="./log/condition.$date.log";
open LOG, "+>>$log";
open CONDLOG, "+>>$condlog";
open LOG, "+>>$log";
open CONDLOG, "+>>$condlog";
# print LOG `echo "hello" | mail -s "test" gaths\@aptea.com`;
@FIELDS = ("EVENT_TYPE","ORIGIN","DOMAIN_CLASS","DOMAIN","OBJECT_CLASS","OBJECT","PARAMETER_NAME","PARAMETER_VALUE","SEVERITY","FREE_TEXT","ORIGIN_KEY","ET","OR","DC","D","OC","O","PN","PV","S","FT","OK");
# Read STDIN or files passed as arg
if($debug > 1){ print LOG localtime(time)."  Received Email\n";}
@ROWS = <>;
%SNMPmsg = ();

if($debug > 1){ print LOG "INCOMING Message:\n";}

foreach $ROW (@ROWS)
{
	if(index($ROW, "TESTTESTTESTTEST")>-1){
		SEND_NSCA(@ROWS);
		exit;
	}
	if(index($ROW, "CTM REPORT")>-1){
#		CTM_REPORT(@ROWS);
		exit;
	}
	if(index($ROW, "BMC TEST CLOSE")>-1){
#		SC_Close(@ROWS);
		exit;
	}
	if(index($ROW, "From: Emergency Alert")>-1){
#		ELert(@ROWS);
		exit;
	}
	if(index($ROW, "Ticket acknowledgement")>-1){
#		SC_Monitor(@ROWS);
		exit;
	}
	if(index($ROW, "Service Center Problem")>-1){
#		SC_Open(@ROWS);
		exit;
	}
	if(index(uc($ROW), "NARROWCAST ADMINISTRATOR")>-1){
#		RDWreport(@ROWS);
		exit;
	}
	if(index($ROW, "EMS Alarm")>-1){
#		DANFOSS(@ROWS);
		exit;
	}
	if(index($ROW, "Subject: CICSEFT LOCKED")>-1){
#		CICSLOCK(@ROWS);
		exit;
	}
	if(index($ROW, "Subject: Device Manager Device Error")>-1){
#		KRONOSCLOCK(@ROWS);
		exit;
	}
	if(index($ROW, "BMC Portal")>-1){
		print LOG "Got BMC Portal Message\n";
#		Portal(@ROWS);
		exit;
	}
	if(index($ROW, "AlarmPoint Message")>-1){
#		ALARMPOINT(@ROWS);
		exit;
	}
	if(index($ROW, "qflex")>-1){
#		QFLEX(@ROWS);
		exit;
	}
	if(index($ROW, "tbstat")>-1){
#		TBSTAT(@ROWS);
		exit;
	}
	if(index($ROW, "Tape Drive Cleaning Report")>-1){
	#	DRIVECLEAN(@ROWS);
		exit;
	}
	if(index($ROW, "CTM_CONDITION")>-1){
#		CTMCOND(@ROWS);
		exit;
	}
	if(index($ROW, "CTM_ORDER")>-1){
#                CTMORDER(@ROWS);
                exit;
        }
	if(index($ROW, "storage_manager")>-1){
#                SANERROR(@ROWS);
                exit;
        }
	if(index($ROW, "From: Google Voice")>-1){
#                IMT(@ROWS);
                exit;
        }
	if(index($ROW, "Expired PLUM batches found")>-1){
#                PLUM(@ROWS);
                exit;
        }
	if(index($ROW, "Subject: T-log Store Transactions")>-1){
#		EMETLOG(@ROWS);
		exit;
	}
}

$messageLine=0;

foreach $ROW (@ROWS)
{
    if($debug > 1){ print LOG $ROW."\n";}
    @LINES = split(/,/,$ROW);
    foreach $LINE (@LINES)
    {
	if($messageLine == 1){
		$SNMPmsg{"MESSAGE"} = $LINE;
		$SNMPmsg{"MESSAGE"} =~ s/\.$//;
		$messageLine=0;
	}else{
            foreach $FIELD (@FIELDS)
            {
                if(index($LINE,$FIELD."=")>-1){
                    $SNMPmsg{$FIELD} = substr($LINE,index($LINE,$FIELD)+length($FIELD)+1);
                    $SNMPmsg{$FIELD} =~ s/\s+$//;
                    $SNMPmsg{$FIELD} =~ s/\$/\[\]/g;
                    $SNMPmsg{$FIELD} =~ s/\.$//;
                }
		if(index($LINE,"Message:")>-1){
			$messageLine=1;
		}
	    }
        }
    }
}
 my $origin = defined($SNMPmsg{"ORIGIN"}) ? $SNMPmsg{"ORIGIN"} : defined($SNMPmsg{"OR"}) ? $SNMPmsg{"OR"} : "UNKNOWN";
 my $eventType = defined($SNMPmsg{"EVENT_TYPE"}) ? $SNMPmsg{"EVENT_TYPE"} : defined($SNMPmsg{"ET"}) ? $SNMPmsg{"ET"} : $origin;
 my $domainClass = defined($SNMPmsg{"DOMAIN_CLASS"}) ? $SNMPmsg{"DOMAIN_CLASS"} : defined($SNMPmsg{"DC"}) ? $SNMPmsg{"DC"} : "";
 my $domain = defined($SNMPmsg{"DOMAIN"}) ? $SNMPmsg{"DOMAIN"} : defined($SNMPmsg{"D"}) ? $SNMPmsg{"D"} : "";
 my $objectClass = defined($SNMPmsg{"OBJECT_CLASS"}) ? $SNMPmsg{"OBJECT_CLASS"} : defined($SNMPmsg{"OC"}) ? $SNMPmsg{"OC"} : "";
 my $object = defined($SNMPmsg{"OBJECT"}) ? $SNMPmsg{"OBJECT"} : defined($SNMPmsg{"O"}) ? $SNMPmsg{"O"} : "";
 my $paramName = defined($SNMPmsg{"PARAMETER_NAME"}) ? $SNMPmsg{"PARAMETER_NAME"} : defined($SNMPmsg{"PN"}) ? $SNMPmsg{"PN"} : "N/A";
 my $paramValue = defined($SNMPmsg{"PARAMETER_VALUE"}) ? $SNMPmsg{"PARAMETER_VALUE"} : defined($SNMPmsg{"PV"}) ? $SNMPmsg{"PV"} : "";
 my $severity = defined($SNMPmsg{"SEVERITY"}) ? $SNMPmsg{"SEVERITY"} : defined($SNMPmsg{"S"}) ? $SNMPmsg{"S"} : "70";
 my $freeText = defined($SNMPmsg{"FREE_TEXT"}) ? $SNMPmsg{"FREE_TEXT"} : defined($SNMPmsg{"FT"}) ? $SNMPmsg{"FT"} : defined($SNMPmsg{"MESSAGE"}) ? $SNMPmsg{"MESSAGE"} : "";
 my $originKey = defined($SNMPmsg{"ORIGIN_KEY"}) ? $SNMPmsg{"ORIGIN_KEY"} : defined($SNMPmsg{"OK"}) ? $SNMPmsg{"OK"} : "";

 if(index($paramName,"VENDOR LINES DOWN")>-1){ $paramName = localtime(time); }
 if(index($domain,"SCIC5RUN")>-1){ $objectClass = "MTE"; }
 if(index($paramName,"EFT STORE")>-1 && $severity ne "10"){
	$object = "EFT";
	$count=`/in/PEM/u/NetCmmnd/anp/getTicketCount.sh \"$domainClass\" \"$domain\" \"$objectClass\" \"$object\"`;
	chomp($count);
	print LOG localtime(time)." - Occurences of this ticket is $count (\"$domainClass\" \"$domain\" \"$objectClass\" \"$object\")\n";
	$store = getURL("http://controlm/bip/getStore.php?site=".$objectClass);
	print LOG getURL("http://nagios/monitor/cicseft.php?store=".$store);
	#SEND_NSCA($store);
	if($count < 10){
		$severity = "60";
	}else{
		print LOG localtime(time).`echo "OR=$origin,ET=$eventType,DC=$domainClass,D=$domain,OC=$objectClass,O=$object,PN=$paramName,PV=$paramValue,S=10" | /in/AEM/EMAIL/emailToSNMP.pl`;
	}
 }

 switch($severity){
 case "Critical" { $severity = "100"; }
 case "Warning"  { $severity = "70";  }
 case "Normal"   { $severity = "10";  }
 case "Clear"   { $severity = "10";  }
 else { }
 }

if($debug > 0){ print LOG localtime(time)."  Parsed Email - \nORIGIN = $origin\nEVENT_TYPE = $eventType\nDOMAIN_CLASS = $domainClass\nDOMAIN = $domain\nOBJECT_CLASS = $objectClass\nOBJECT = $object\nPARAMETER_NAME = $paramName\nPARAMETER_VALUE = $paramValue\nSEVERITY = $severity\nFREE_TEXT = $freeText\nORIGIN_KEY = $originKey\n"; }
 
trap($origin, $eventType, $domainClass, $domain, $objectClass, $object, $paramName, $paramValue, $severity, $freeText, $originKey);

if($debug > 1){ print LOG localtime(time)."  Sent SNMP Trap\n";}

sub SC_Monitor{
	print LOG localtime(time)." - Received SC Ticket Ack for monitor ".`touch /in/PEM/u/NetCmmnd/anp/scMonitor/email.rec 2>&1`."\n";
}

sub ELert{
	$elert="/in/PEM/u/NetCmmnd/EMAIL/elert.log";
	open elert, ">>$elert";
	print elert "##############################\n## START\n##############################\n";
	$started=0;
	foreach $ROW (@ROWS)
	{
		print elert $ROW;
		if(index($ROW, "icket:")>-1){
			chomp($ROW);
			@data = split(/ +/,$ROW);
			chomp($data[2]);
			print elert "##ROW## ".$ROW."####\n";
			print elert "## ".$data[0]."##".$data[1]."##".$data[2]."\n";
			print LOG localtime(time)." - ELert touched file for $data[1] ".`touch /in/PEM/u/NetCmmnd/EMAIL/ELerts/$data[1]`."\n";
			last;
		}
	}
	print elert "##############################\n## END\n##############################\n";
	close elert;
}

sub EMETLOG{
	my $started=false;
	foreach $ROW (@ROWS)
	{
		if(index($ROW, "--START DATA--")>-1){
			$started=true;
			next;
		}
		if($started){
			@data = split(/,/,$ROW);
			$post = getURL("https://controlm/tlog/emePost.php?store=".$data[0]."&timestamp=".$data[1]);
		}	
	}
}

sub PLUM{
	if($debug > 1){ print LOG "Got 7am expired PLUM Batches\n";}
	my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
	foreach $ROW (@ROWS)
	{
	    if($debug > 1){ print LOG $ROW."\n";}
	    @LINES = split(/,/,$ROW);
	    foreach $LINE (@LINES)
	    {
		if($messageLine == 1){
			$SNMPmsg{"MESSAGE"} = $LINE;
			$SNMPmsg{"MESSAGE"} =~ s/\.$//;
			$messageLine=0;
		}else{
            		foreach $FIELD (@FIELDS)
            		{
                		if(index($LINE,$FIELD."=")>-1){
                		    $SNMPmsg{$FIELD} = substr($LINE,index($LINE,$FIELD)+length($FIELD)+1);
               			    $SNMPmsg{$FIELD} =~ s/\s+$//;
                		    $SNMPmsg{$FIELD} =~ s/\$/\[\]/g;
                		    $SNMPmsg{$FIELD} =~ s/\.$//;
                		}
				if(index($LINE,"Message:")>-1){
					$messageLine=1;
				}
		 	}
        	}
    	    }
	}
	my $origin = defined($SNMPmsg{"ORIGIN"}) ? $SNMPmsg{"ORIGIN"} : defined($SNMPmsg{"OR"}) ? $SNMPmsg{"OR"} : "UNKNOWN";
	my $eventType = defined($SNMPmsg{"EVENT_TYPE"}) ? $SNMPmsg{"EVENT_TYPE"} : defined($SNMPmsg{"ET"}) ? $SNMPmsg{"ET"} : $origin;
	my $domainClass = defined($SNMPmsg{"DOMAIN_CLASS"}) ? $SNMPmsg{"DOMAIN_CLASS"} : defined($SNMPmsg{"DC"}) ? $SNMPmsg{"DC"} : "";
	my $domain = defined($SNMPmsg{"DOMAIN"}) ? $SNMPmsg{"DOMAIN"} : defined($SNMPmsg{"D"}) ? $SNMPmsg{"D"} : "";
	my $objectClass = defined($SNMPmsg{"OBJECT_CLASS"}) ? $SNMPmsg{"OBJECT_CLASS"} : defined($SNMPmsg{"OC"}) ? $SNMPmsg{"OC"} : "";
	my $object = defined($SNMPmsg{"OBJECT"}) ? $SNMPmsg{"OBJECT"} : defined($SNMPmsg{"O"}) ? $SNMPmsg{"O"} : "";
	my $paramName = defined($SNMPmsg{"PARAMETER_NAME"}) ? $SNMPmsg{"PARAMETER_NAME"} : defined($SNMPmsg{"PN"}) ? $SNMPmsg{"PN"} : "N/A";
	my $paramValue = defined($SNMPmsg{"PARAMETER_VALUE"}) ? $SNMPmsg{"PARAMETER_VALUE"} : defined($SNMPmsg{"PV"}) ? $SNMPmsg{"PV"} : "";
	my $severity = defined($SNMPmsg{"SEVERITY"}) ? $SNMPmsg{"SEVERITY"} : defined($SNMPmsg{"S"}) ? $SNMPmsg{"S"} : "70";
	my $freeText = defined($SNMPmsg{"FREE_TEXT"}) ? $SNMPmsg{"FREE_TEXT"} : defined($SNMPmsg{"FT"}) ? $SNMPmsg{"FT"} : defined($SNMPmsg{"MESSAGE"}) ? $SNMPmsg{"MESSAGE"} : "";
	my $originKey = defined($SNMPmsg{"ORIGIN_KEY"}) ? $SNMPmsg{"ORIGIN_KEY"} : defined($SNMPmsg{"OK"}) ? $SNMPmsg{"OK"} : "";

	$result=getURL("http://controlm/bip/plumerror.php?store=".$origin);
	print LOG localtime(time)." result = $result\n";
	if ($hour < 9){
		my $email = $origin."feadmin\@aptea.com";
		#my $email = "gaths\@aptea.com";
		print LOG localtime(time)." Sending Expired PLUM Batch Email to $email\n".`echo "Attention: Scanning administrator or price integrity co-ordinator (PIC). There are expired batches in PLUM. For detailed instructions click on the following link. Any problems, please contact the Help Desk @ 1-800-877-7717.\n\n http://taprightweb1:80/portal/app/portlets/results/viewsolution.jsp?solutionid=041125710505580&isguest=true" | mail -s "Expired PLUM Batches found" $email`
	}else{
		if($debug > 0){ print LOG localtime(time)."  Parsed Email - \nORIGIN = $origin\nEVENT_TYPE = $eventType\nDOMAIN_CLASS = $domainClass\nDOMAIN = $domain\nOBJECT_CLASS = $objectClass\nOBJECT = $object\nPARAMETER_NAME = $paramName\nPARAMETER_VALUE = $paramValue\nSEVERITY = $severity\nFREE_TEXT = $freeText\nORIGIN_KEY = $originKey\n"; }
		trap($origin, $eventType, $domainClass, $domain, $objectClass, $object, $paramName, $paramValue, $severity, $freeText, $originKey);
		if($debug > 1){ print LOG localtime(time)."  Sent SNMP Trap\n";}
	}
}
sub IMT{
	if($debug > 1){ print LOG "Got Google Voice message for IMT\n";}
	print LOG localtime(time)." ".`/in/PEM/nc/Solutions/APAgent/APClient.bin --map-data  PATROL_EM "IMT" 5 "1234" "IMT" "IMT" "IMT" "IMT" "IMT" "IMT" "A voicemail has been received for the Incident Management Team. Please Accept this alert to stop escalation and check the voicemail."`;
}

sub SEND_NSCA{
	my $host = shift;
	print LOG localtime(time)." ".`echo "$host	CICS	2	BOTH CICS LINES OUT OF SERVICE" | /in/PEM/u/NetCmmnd/anp/send_nsca -H nagios -c /in/PEM/u/NetCmmnd/anp/send_nsca.cfg`
}

sub SC_Open{
	if($debug > 1){ print LOG "Got SC Open\n";}
	my (@ROWS) = @_;
        foreach $LINE (@ROWS)
        {
		if(index($LINE,"Alert =")>-1){
			$LINE =~ s/^[ ]+//; 
			$LINE =~ s/=/ = /g;
			@fields = split(/[ ]+/, $LINE);
			my $alert = $fields[2];
			my $caseNo = $fields[5];
			print LOG localtime(time)." ".`/in/PEM/nc/Solutions/PiSC/bin/updateTT.rex -- -ALERTID $alert -SCcaseNo $caseNo 2>&1`;
			print LOG localtime(time)." ".`/in/PEM/u/NetCmmnd/anp/updateCatchup.sh $alert $caseNo 2>&1`;
			if($debug > 0){ print LOG localtime(time)."  Updated PEM Alert: $alert with SC Case No: $caseNo\n";}
		}
	}
}
sub SC_Close{
	my (@ROWS) = @_;
	@FIELDS = ("ServiceCenter Operator:","Number","Alert ID:","Domain:","Location:","Failing Component:","Object ID:","Model:");
	foreach $LINE (@ROWS)
	{
		if($debug > 1){ print LOG $LINE."\n";}
       		foreach $FIELD (@FIELDS)
       		{
       			if(index($LINE,$FIELD)>-1){
              			$SNMPmsg{$FIELD} = substr($LINE,index($LINE,$FIELD)+length($FIELD)+1);
               			$SNMPmsg{$FIELD} =~ s/^\s+//;
               			$SNMPmsg{$FIELD} =~ s/\s+$//;
               			$SNMPmsg{$FIELD} =~ s/\$/\[\]/;
               			$SNMPmsg{$FIELD} =~ s/\.$//;
       			}
       		}
	}
	 my $origin = defined($SNMPmsg{"Location:"}) ? $SNMPmsg{"Location:"} : "UNKNOWN";
	 my $domain = defined($SNMPmsg{"Domain:"}) ? $SNMPmsg{"Domain:"} : "";
	 my $objectClass = defined($SNMPmsg{"Object ID:"}) ? $SNMPmsg{"Object ID:"} : "";
	 my $object = defined($SNMPmsg{"Failing Component:"}) ? $SNMPmsg{"Failing Component:"} : "";
	 my $paramName = defined($SNMPmsg{"Model:"}) ? $SNMPmsg{"Model:"} : "";
	 my $alertId = defined($SNMPmsg{"Alert ID:"}) ? $SNMPmsg{"Alert ID:"} : "";
	 my $operator = defined($SNMPmsg{"ServiceCenter Operator:"}) ? $SNMPmsg{"ServiceCenter Operator:"} : "";
	 my $severity = "10";

	if($debug > 0){ print LOG localtime(time)."  Parsed Email - \nORIGIN = $origin\nEVENT_TYPE = $eventType\nDOMAIN_CLASS = $domainClass\nDOMAIN = $domain\nOBJECT_CLASS = $objectClass\nOBJECT = $object\nPARAMETER_NAME = $paramName\nPARAMETER_VALUE = $paramValue\nSEVERITY = $severity\nFREE_TEXT = $freeText\n"; }

#	trap($origin, $eventType, $domainClass, $domain, $objectClass, $object, $paramName, $paramValue, $severity, $freeText);
	my $ClRet = `/in/PEM/u/NetCmmnd/anp/CloseAlert.sh $alertId "Closed in ServiceCenter By: $operator"`;
	if($debug > 1){ print LOG localtime(time).$ClRet."\n";}
	if($debug > 1){ print LOG localtime(time)."  Sent SNMP Trap\n";}


}
sub DANFOSS{
	if($debug > 1){ print LOG localtime(time)."  Got Danfoss Alert\n";}
	my (@ROWS) = @_;
	my $count=0;
	my $severity=70;
	my $domain="DanFoss";
	my $domainClass,$origin,$objectClass,$object,$paramName,$freeText,$paramValue;
        foreach $ROW (@ROWS){
		if($debug > 1){ print LOG $count." ".$ROW; }
		$ROW =~ s/[ ]+$//;
		$ROW =~ s/^[ ]+//;
		chomp($ROW);
                if (index($ROW,"Subject:")>-1){
			$domainClass=substr($ROW,index($ROW,"Subject:")+14,20);
			$domainClass =~ s/^[ ]+//;
			$count=1;
		}elsif ($count>0){
			switch ($count){
			case 1 { $count++; }
			case 2 { $origin=substr($ROW,0,index($ROW," ")); $count++; }
			case 3 { $count++; }
			case 4 { $objectClass=$ROW; $count++; }
			case 5 { $object=$ROW; $count++; }
			case 6 { $paramName=substr($ROW,index($ROW,"SI:")+4); $count++; }
			case 7 { $count++; }
			case 8 { $paramValue=$ROW; $count++; }
			case 9 { $freeText=$ROW; $count++; }
			else {}
			} 
		} 
	} 	
	if($debug > 0){ print LOG localtime(time)."  Parsed Email - \nORIGIN = $origin\nEVENT_TYPE = $eventType\nDOMAIN_CLASS = $domainClass\nDOMAIN = $domain\nOBJECT_CLASS = $objectClass\nOBJECT = $object\nPARAMETER_NAME = $paramName\nPARAMETER_VALUE = $paramValue\nSEVERITY = $severity\nFREE_TEXT = $freeText\n"; }
 
	trap($origin, $eventType, $domainClass, $domain, $objectClass, $object, $paramName, $paramValue, $severity, $freeText,$originKey);

	if($debug > 1){ print LOG localtime(time)."  Sent SNMP Trap\n";}
}

sub CTM_REPORT{
	my (@ROWS) = @_;
	my $start=false;
	my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
	$year = $year+1900;
	$mon = $mon+1;
	my $date= $year.$mon.$mday.$hour.$min.$sec;

	foreach $ROW (@ROWS){
		if (index($ROW,"Subject")>-1){
			if (index($ROW,"MONA")>-1){
				$filename= "/in/PEM/u/NetCmmnd/anp/out/MONA_runtimes_".$date.".txt";
				open REPORT, ">".$filename;
				$start=true;
			}
			elsif (index($ROW,"COLC")>-1){
				$filename= "/in/PEM/u/NetCmmnd/anp/out/COLC_runtimes_".$date.".txt";
				open REPORT, ">$filename";
				$start=true;
			}
		}else{
			if ($start){ chomp($ROW);print REPORT $ROW."\n";}
		}
	}
	if ($start){	
		my $ClRet = `/usr/bin/rsync $filename controlm::controlm_data/`;
        	if($debug > 1){ print LOG localtime(time).$ClRet."\n";}
        	if($debug > 1){ print LOG localtime(time)."  Sent Runtime Report $filename\n";}
		my $ClRet = `rm $filename`;
	} 
}
sub RDWreport {
	open RDW, "+>/in/PEM/u/NetCmmnd/anp/rdw/RDWreport.rec";
	print RDW localtime(time);
	close(RDW);
	print LOG localtime(time)." got a rdw report\n";
}
 
sub CICSLOCK {
	if($debug > 1){ print LOG localtime(time)."  Got CICS LOCK\n"; }
	my $severity = 100;
	my $origin = "CICS";
	my $domainClass = "CICS";
	my $domain = "CICSHUNG";
	my $eventType = "CICS";
	my $object = "CICSEFT";
	my $objectClass = "CICS";
	my $paramName = "LOCKED";
	my $paramValue = "LOCKED";

	if($debug > 0){ print LOG localtime(time)."  Parsed Email - \nORIGIN = $origin\nEVENT_TYPE = $eventType\nDOMAIN_CLASS = $domainClass\nDOMAIN = $domain\nOBJECT_CLASS = $objectClass\nOBJECT = $object\nPARAMETER_NAME = $paramName\nPARAMETER_VALUE = $paramValue\nSEVERITY = $severity\nFREE_TEXT = $freeText\n"; }
 
	trap($origin, $eventType, $domainClass, $domain, $objectClass, $object, $paramName, $paramValue, $severity, $freeText, $originKey);

	if($debug > 1){ print LOG localtime(time)."  Sent SNMP Trap\n";}
}

sub KRONOSCLOCK {
	if($debug > 1){ print LOG "KRONOS\n";}
	unless (-e '/in/PEM/nc/Solutions/AgentConnection/conf/enrichment/KRONOS_REBOOT.txt') {
	
	my (@ROWS) = @_;
	my (@checkRows) = @ROWS;
	my $count=0;
	foreach $ROW (@checkRows)
	{
		if(index($ROW,"Device has not communicated since the last server restart.") >-1){
			$count++;
		}
		
	}
	if($count > 10){
		my $severity = 70;
		my $origin = "KRONOS";
		my $domainClass = "TIMECLOCK";
		my $domain = "MASSIVE";
		my $eventType = "KRONOS";
		my $object = "CLOCKDOWN";
		my $objectClass = "TIMECLOCK";
		my $paramName = "COMMUNICATION";
		my $paramValue = "DOWN";
		trap($origin, $eventType, $domainClass, $domain, $objectClass, $object, $paramName, $paramValue, $severity, $freeText, $originKey);
		exit;
	}
	$skip=0;
	$device="";
	foreach $ROW (@ROWS)
	{
	    if($skip==1){ $skip=0;next; }
	    if($debug > 1){ print LOG $ROW."\n";}
	    $field="Device: ";
	    if (index($ROW,$field)>-1){
		$device=substr($ROW,10,5);
		if($debug > 1){ print LOG "Device=".$device."\n";}
		$skip=1;
		next;
	    }
	    if($device != ""){
		if(index($ROW,"Device has not communicated since the last server restart.") >-1){
			if($debug > 0){ print LOG $device." clock is down";}
			my $severity = 70;
			my $origin = "KRONOS";
			my $domainClass = "TIMECLOCK";
			my $domain = $device;
			my $eventType = "KRONOS";
			my $object = "CLOCKDOWN";
			my $objectClass = "TIMECLOCK";
			my $paramName = "COMMUNICATION";
			my $paramValue = "DOWN";
			trap($origin, $eventType, $domainClass, $domain, $objectClass, $object, $paramName, $paramValue, $severity, $freeText, $originKey);
		}
		$device="";
	    }
	}
	}# end unless
}

sub Portal {
	my (@ROWS) = @_;
	foreach $ROW (@ROWS)
	{
	    if($debug > 1){ print LOG $ROW."\n";}
	    $field="Status: ";
	    if (index($ROW,$field)>-1){
		$start = index($ROW,$field)+length($field);
		$len = length($ROW)-$start+1;
		$STATUS = substr($ROW,$start,$len);
                $STATUS =~ s/^\s+//;
                $STATUS =~ s/\s+$//;
                $STATUS =~ s/\$/\[\]/;
                $STATUS =~ s/\.$//;
	    }
	    if ($STATUS eq ""){
	        $field="Port Responding ";
	        if (index($ROW,$field)>-1){
		    $start = index($ROW,$field)+length($field);
		    $len = index($ROW,".",$start)-$start+1;
		    $STATUS = substr($ROW,$start,$len);
                    $STATUS =~ s/^\s+//;
                    $STATUS =~ s/\s+$//;
                    $STATUS =~ s/\$/\[\]/;
                    $STATUS =~ s/\.$//;
	        }
	    }
	    $field="Element: ";
	    if (index($ROW,$field)>-1){
		$start = index($ROW,$field)+length($field);
		$len = length($ROW)-$start+1;
		$ELEMENT = substr($ROW,$start,$len);
                $ELEMENT =~ s/^\s+//;
                $ELEMENT =~ s/\s+$//;
                $ELEMENT =~ s/\$/\[\]/;
                $ELEMENT =~ s/\.$//;
	    }
	    $field="Parameter: ";
	    if (index($ROW,$field)>-1){
		$start = length($field); #index($ROW,$field)+length($field);
		$len = length($ROW) - length($field); #index($ROW," - ",$start)-$start+1;
		$PARAMETER = substr($ROW,$start,$len);
                $PARAMETER =~ s/^\s+//;
                $PARAMETER =~ s/\s+$//;
                $PARAMETER =~ s/\$/\[\]/;
                $PARAMETER =~ s/\.$//;
	    }
	}
	if($debug > 0){ print LOG "Status:".$STATUS."|ELEMENT:".$ELEMENT."|PARAMETER:".$PARAMETER."|\n";}
	if($STATUS eq "Critical" && $ELEMENT eq "m085lp01" && index($PARAMETER,"Process Status")>-1){
		addCond("AUTO_MSBI_START");
		my $severity = 100;
		my $origin = "Portal";
		my $domainClass = "MSBI";
		my $domain = "m085lp01";
		my $eventType = "AUTO_RESTART";
		my $object = "AUTO_RESTART";
		my $objectClass = "AUTO_RESTART";
		my $paramName = "COMMUNICATION";
		my $paramValue = "DOWN";
		trap($origin, $eventType, $domainClass, $domain, $objectClass, $object, $paramName, $paramValue, $severity, $freeText, $originKey);
	}
        if($STATUS eq "Critical" && $ELEMENT eq "m092lp01" && index($PARAMETER,"Process Status")>-1){
                addCond("AUTO_MSBI_START92");
        }
	if($STATUS eq "Critical" && $ELEMENT eq "m078lp01" && index($PARAMETER,"Process Status")>-1){
                addCond("AUTO_MSBI_START78");
        }
	if($STATUS eq "Warning" && $ELEMENT eq "m085lp01" && index($PARAMETER,"Process Physical Memory")>-1){
		addCond("UGSDMD0015-ENDED-OK");
	}
        if($STATUS eq "Warning" && $ELEMENT eq "m092lp01" && index($PARAMETER,"Process Physical Memory")>-1){
                addCond("92UGSDMD0015-ENDED-OK");
        }
        if($STATUS eq "Warning" && $ELEMENT eq "m078lp01" && index($PARAMETER,"Process Physical Memory")>-1){
                addCond("78UGSDMD0015-ENDED-OK");
        }
        if($STATUS eq "Critical" && $ELEMENT eq "monf10063app" && index($PARAMETER,"MATRA Freedom-Trickle")>-1){
		addCond("AUTO_MATRA_RESTART");
	}
	if($STATUS eq "Warning" && $ELEMENT eq "mqprhub1" && index($PARAMETER,"Process")>-1){
		addCond("AUTO_PATROL_RESTART");
	}
	if($STATUS eq "Warning" && $ELEMENT eq "monkron7app1" && index($PARAMETER,"Port Responding")>-1){
		addCond("AUTO_KRON1_WEB_DOWN");
	}
	if($STATUS eq "Warning" && $ELEMENT eq "monkron7app2" && index($PARAMETER,"Port Responding")>-1){
		addCond("AUTO_KRON2_WEB_DOWN");
	}
	if($STATUS eq "Warning" && $ELEMENT eq "monkron7app3" && index($PARAMETER,"Port Responding")>-1){
		addCond("AUTO_KRON3_WEB_DOWN");
	}
	if($STATUS eq "Warning" && $ELEMENT eq "monkron7app4" && index($PARAMETER,"Port Responding")>-1){
		addCond("AUTO_KRON4_WEB_DOWN");
	}
	if($STATUS eq "OK" && $ELEMENT eq "monkron7app1" && index($PARAMETER,"Port Responding")>-1){
		addCond("AUTO_KRON1_WEB_UP");
	}
	if($STATUS eq "OK" && $ELEMENT eq "monkron7app2" && index($PARAMETER,"Port Responding")>-1){
		addCond("AUTO_KRON2_WEB_UP");
	}
	if($STATUS eq "OK" && $ELEMENT eq "monkron7app3" && index($PARAMETER,"Port Responding")>-1){
		addCond("AUTO_KRON3_WEB_UP");
	}
	if($STATUS eq "OK" && $ELEMENT eq "monkron7app4" && index($PARAMETER,"Port Responding")>-1){
		addCond("AUTO_KRON4_WEB_UP");
	}
	if($STATUS eq "Warning" && $ELEMENT eq "monf09827sps" && index($PARAMETER,"Port Responding")>-1){
		addCond("AUTO_9827_WEB_DOWN");
	}
	if($STATUS eq "Warning" && $ELEMENT eq "monf09828sps" && index($PARAMETER,"Port Responding")>-1){
		addCond("AUTO_9828_WEB_DOWN");
	}
	if($STATUS eq "OK" && $ELEMENT eq "monf09827sps" && index($PARAMETER,"Port Responding")>-1){
		addCond("AUTO_9827_WEB_UP");
	}
	if($STATUS eq "OK" && $ELEMENT eq "monf09828sps" && index($PARAMETER,"Port Responding")>-1){
		addCond("AUTO_9828_WEB_UP");
	}
}	

sub addCond {
	my $name = shift;
 	    my $ua = LWP::UserAgent->new();
	    $ua->timeout(30);
	    my $request=HTTP::Request->new("GET" => "http://controlm/bip/ctmcond.php?type=ADD&name=$name&date=ODAT");
	    my $response = $ua->request($request);
	    my $count=1;
	    while (! $response->content() || $response->content() eq "") {
	        if ($count++ > 10) {
	            print LOG "ERROR: Could not fetch information from web server\n";
	            sleep 5;
	            return 0;
	        }
	        $response = $ua->request($request);
	        sleep 10;
	    }
	    my @contents = split("\n",$response->content());
	    foreach $LINE (@contents)
	    {
		print LOG $LINE;
	    }
}

sub CTMCOND {
	print CONDLOG "Received CTM_CONDITION...\n";
	my (@ROWS) = @_;
	my @FIELDS = ("TYPE","NAME","ODAT");
	foreach $ROW (@ROWS)
	{
    		if($debug > 1){ print CONDLOG $ROW."\n";}
    		@LINES = split(/,/,$ROW);
    		foreach $LINE (@LINES)
    		{
        	    	foreach $FIELD (@FIELDS)
            		{
                		if(index($LINE,$FIELD."=")>-1){
                    			$cond{$FIELD} = substr($LINE,index($LINE,$FIELD)+length($FIELD)+1);
                    			$cond{$FIELD} =~ s/\s+$//;
                    			$cond{$FIELD} =~ s/\$/\[\]/g;
                    			$cond{$FIELD} =~ s/\.$//;
                		}
        		}
    		}
	}
 	my $type= defined($cond{"TYPE"}) ? $cond{"TYPE"} : "ADD";
 	my $name= defined($cond{"NAME"}) ? $cond{"NAME"} : "UNKNOWN";
 	my $odat= defined($cond{"ODAT"}) ? $cond{"ODAT"} : "ODAT";
	if($name ne "UNKNOWN"){
		print CONDLOG "Recieved CTM CONDTION: Name:$name Type:$type ODate:$odat\n";
 	    my $ua = LWP::UserAgent->new();
	    $ua->timeout(30);
	    my $request=HTTP::Request->new("GET" => "http://controlm/bip/ctmcond.php?type=$type&name=$name&date=$odat");
	    my $response = $ua->request($request);
	    my $count=1;
	    while (! $response->content() || $response->content() eq "") {
	        if ($count++ > 10) {
	            print CONDLOG "ERROR: Could not fetch information from web server\n";
	            sleep 5;
	            return 0;
	        }
	        $response = $ua->request($request);
	        sleep 10;
	    }
	    my @contents = split("\n",$response->content());
	    foreach $LINE (@contents)
	    {
		print CONDLOG $LINE;
	    }
	}	
}

sub CTMORDER {
        print LOG "Received CTM_ORDER...\n";
        my (@ROWS) = @_;
        my @FIELDS = ("TABLE","TYPE","JOB","ODAT");
        foreach $ROW (@ROWS)
        {
                if($debug > 1){ print LOG $ROW."\n";}
                @LINES = split(/,/,$ROW);
                foreach $LINE (@LINES)
                {
                        foreach $FIELD (@FIELDS)
                        {
                                if(index($LINE,$FIELD."=")>-1){
                                        $cond{$FIELD} = substr($LINE,index($LINE,$FIELD)+length($FIELD)+1);
                                        $cond{$FIELD} =~ s/\s+$//;
                                        $cond{$FIELD} =~ s/\$/\[\]/g;
                                        $cond{$FIELD} =~ s/\.$//;
                                }
                        }
                }
        }
        my $table= defined($cond{"TABLE"}) ? $cond{"TABLE"} : "UNKNOWN";
        my $type= defined($cond{"TYPE"}) ? $cond{"TYPE"} : "force";
        my $job= defined($cond{"JOB"}) ? $cond{"JOB"} : "";
        my $odat= defined($cond{"ODAT"}) ? $cond{"ODAT"} : "ODAT";
        if($table ne "UNKNOWN"){
                print LOG "Recieved CTM ORDER: Name:$table Job:$job Type:$type ODate:$odat\n";
            my $ua = LWP::UserAgent->new();
            $ua->timeout(30);
            my $request=HTTP::Request->new("GET" => "http://controlm/bip/ctmorder.php?type=$type&table=$table&job=$job&date=$odat");
            my $response = $ua->request($request);
            my $count=1;
            while (! $response->content() || $response->content() eq "") {
                if ($count++ > 10) {
                    print LOG "ERROR: Could not fetch information from web server\n";
                    sleep 5;
                    return 0;
                }
                $response = $ua->request($request);
                sleep 10;
            }
            my @contents = split("\n",$response->content());
            foreach $LINE (@contents)
            {
                print LOG $LINE;
            }
        }
}

sub ALARMPOINT {
	if($debug > 1){ print LOG localtime(time)." Received Alarmpoint Notification\n";}
	my (@ROWS) = @_;
	my $start=0;
	my %vars =();
	foreach $ROW (@ROWS)
	{
		if($start == 1 ){
			$message.=$ROW;
			if(index($ROW, "PEM_Alert_Id")>-1){
				@fields = split(/=/, $ROW); 
				$PEM_Alert_Id = $fields[1];
				chomp($PEM_Alert_Id);
			}
			if(index($ROW, "Assignment Group")>-1){
				@fields = split(/=/, $ROW);
				$Group = $fields[1];
				chomp($Group);
				$Group =~ s/ /+/;
			}
			if(index($ROW, "Alert Text")>-1){
				@fields = split(/=/, $ROW);
				$text = $fields[1];
				chomp($text);
			}
			if(index($ROW, "Tool")>-1){
				@fields = split(/=/, $ROW);
				$tool = $fields[1];
				chomp($tool);
			}
			if(index($ROW, "Job=")>-1){
				@fields = split(/=/, $ROW);
				$jobname = $fields[1];
				chomp($jobname);
			}
			if(index($ROW, "OrderId")>-1){
				@fields = split(/=/, $ROW);
				$orderid = $fields[1];
				chomp($orderid);
			}
		}else{
			if(index($ROW, "TIME=")>-1){
			$message = $ROW;
				$start=1;
			}
		}
	}
	$IM = `/in/PEM/u/NetCmmnd/anp/getIM.sh $PEM_Alert_Id`;
	chomp($IM);
	$emailStr = "Alarmpoint Notification has NOT been acknowledged for $Group. Please notify Helpdesk Manager for escalation.\n\n";
	$emailStr .= "Ticket No=$IM\n";
	$emailStr .= "Alert Text: $text\n\n";
	if($tool eq "CONTROLM"){
		$emailStr .= "Job Information:\n\n";
		$jobInfo = getURL("http://controlm.corp.gaptea.com/bip/jd.php?j=".$jobname."_".$orderid."&format=text");
		$emailStr .= "$jobInfo";
	}
	$emailStr .= "\n\nNotification Log:\n\n";
	$emailStr .= `/in/PEM/u/NetCmmnd/anp/getComments.sh  $PEM_Alert_Id`;
	$emailStr .= "\n\nOn Duty Link:\n http://controlm.corp.gaptea.com/bip/apOnDuty.php?group=$Group\n\n";
	$emailStr .= "Alert Details:\n\n$message";
	print LOG `echo "$emailStr" | mail -s "Alarmpoint Notification has NOT been acknowledged for $Group - $IM" gaths\@aptea.com -c shanceyj\@aptea.com `;#-c "shanceyj\@aptea.com dachnowp\@aptea.com" gaths\@aptea.com`;
	$emailStr =~ s/([[:alpha:]]+:\/\/[^<>[:space:]]+[[:alnum:]\/])/<a href="$1">$1<\/a>/;
	$emailStr =~ s/\n/<br>/g;
	#print LOG "/in/PEM/nc/Solutions/APAgent/APClient.bin --map-data anp person_or_group_id: 'gaths|Blackberry' title: 'Alarmpoint Not Acknowledged' html: '$emailStr' line_1: '$emailStr'\n";
	print LOG `/in/PEM/nc/Solutions/APAgent/APClient.bin --map-data anp person_or_group_id: 'walshb|Blackberry' title: 'Alarmpoint Not Acknowledged' html: '$emailStr' line_1: '$emailStr'`;
	if($debug > 0){ print LOG localtime(time)." Alarmpoint HelpDesk Last Resort for $IM\n"; }
	if($debug > 1){ print LOG localtime(time)." Sent Following email to HelpDesk\n$emailStr\n"; }

}

sub SANERROR {
	if($debug > 1){ print LOG localtime(time)." Received SAN ERROR Notification\n";}
	my @FIELDS = ("Node ID","Event Error Code","Event Message","Component type","Component location");
	my (@ROWS) = @_;
	
	foreach $LINE (@ROWS)
	{
    	    if($debug > 1){ print LOG $ROW."\n";}
            foreach $FIELD (@FIELDS)
            {
                if(index($LINE,$FIELD.":")>-1){
                    $SNMPmsg{$FIELD} = substr($LINE,index($LINE,$FIELD)+length($FIELD)+1);
                    $SNMPmsg{$FIELD} =~ s/^\s+//;
                    $SNMPmsg{$FIELD} =~ s/\s+$//;
                    $SNMPmsg{$FIELD} =~ s/\$/\[\]/g;
                    $SNMPmsg{$FIELD} =~ s/\.$//;
                    $SNMPmsg{$FIELD} =~ s/, /_/;
                }
	    }
	}
 	my $origin = "SAN";
 	my $eventType = "Storage Manager Alert";
 	my $domainClass = defined($SNMPmsg{"Component type"}) ? $SNMPmsg{"Component type"} : "";
 	my $domain = defined($SNMPmsg{"Node ID"}) ? $SNMPmsg{"Node ID"} : "";
 	my $objectClass = "SAN";
 	my $object = defined($SNMPmsg{"Component location"}) ? $SNMPmsg{"Component location"} : "";
 	my $paramValue = defined($SNMPmsg{"Event Error Code"}) ? $SNMPmsg{"Event Error Code"} : "";
 	my $paramName = defined($SNMPmsg{"Event Error Code"}) ? $SNMPmsg{"Event Error Code"} : "";
 	my $freeText = defined($SNMPmsg{"Event Message"}) ? $SNMPmsg{"Event Message"} : "";

 	$severity = "70";  

	if($debug > 0){ print LOG localtime(time)."  Parsed Email - \nORIGIN = $origin\nEVENT_TYPE = $eventType\nDOMAIN_CLASS = $domainClass\nDOMAIN = $domain\nOBJECT_CLASS = $objectClass\nOBJECT = $object\nPARAMETER_NAME = $paramName\nPARAMETER_VALUE = $paramValue\nSEVERITY = $severity\nFREE_TEXT = $freeText\nORIGIN_KEY = $originKey\n"; }
 
	trap($origin, $eventType, $domainClass, $domain, $objectClass, $object, $paramName, $paramValue, $severity, $freeText, $originKey);

	if($debug > 1){ print LOG localtime(time)."  Sent SNMP Trap\n";}
}

sub QFLEX {
	if($debug > 1){ print LOG localtime(time)." Received QFLEX Notification\n";}
	my @FIELDS = ("q_manager_name","q_name","q_depth","severity","date","q_alias","q_in","q_out","monitor_name","trig_name");
	my (@ROWS) = @_;
	
	foreach $LINE (@ROWS)
	{
    	    if($debug > 1){ print LOG $ROW."\n";}
            foreach $FIELD (@FIELDS)
            {
                if(index($LINE,$FIELD."=")>-1){
                    $SNMPmsg{$FIELD} = substr($LINE,index($LINE,$FIELD)+length($FIELD)+1);
                    $SNMPmsg{$FIELD} =~ s/\s+$//;
                    $SNMPmsg{$FIELD} =~ s/\$/\[\]/g;
                    $SNMPmsg{$FIELD} =~ s/\.$//;
                }
	    }
	}
 	my $origin = "QFLEX";
 	my $eventType = "MQ Alert";
 	my $domainClass = defined($SNMPmsg{"q_manager_name"}) ? $SNMPmsg{"q_manager_name"} : "";
 	my $domain = defined($SNMPmsg{"q_name"}) ? $SNMPmsg{"q_name"} : "";
 	my $objectClass = defined($SNMPmsg{"q_name"}) ? $SNMPmsg{"q_name"} : "";
 	my $object = defined($SNMPmsg{"trig_name"}) ? $SNMPmsg{"trig_name"} : "";
 	my $paramValue = defined($SNMPmsg{"q_depth"}) ? $SNMPmsg{"q_depth"} : "";
 	my $paramName = defined($SNMPmsg{"trig_name"}) ? $SNMPmsg{"trig_name"} : "";
 	my $severity = defined($SNMPmsg{"severity"}) ? $SNMPmsg{"severity"} : "70";
 	my $freeText = defined($SNMPmsg{"monitor_name"}) ? $SNMPmsg{"monitor_name"} : "";

 	switch($severity){
 	case "Failure" { $severity = "100"; }
 	case "Problem"  { $severity = "70";  }
 	case "Warning"  { $severity = "70";  }
 	case "Info"   { $severity = "10";  }
 	else { }
 	}

	if($debug > 0){ print LOG localtime(time)."  Parsed Email - \nORIGIN = $origin\nEVENT_TYPE = $eventType\nDOMAIN_CLASS = $domainClass\nDOMAIN = $domain\nOBJECT_CLASS = $objectClass\nOBJECT = $object\nPARAMETER_NAME = $paramName\nPARAMETER_VALUE = $paramValue\nSEVERITY = $severity\nFREE_TEXT = $freeText\nORIGIN_KEY = $originKey\n"; }
 
	trap($origin, $eventType, $domainClass, $domain, $objectClass, $object, $paramName, $paramValue, $severity, $freeText, $originKey);

	if($debug > 1){ print LOG localtime(time)."  Sent SNMP Trap\n";}
}

sub TBSTAT {
	if($debug > 1){ print LOG localtime(time)." Received ISP TBSTAT Report\n";}
	my (@ROWS) = @_;
	foreach $LINE (@ROWS)
	{
		if(index($LINE,"stores.rdbf")>-1){
			@fields = split(/ +/,$LINE);
			$size=$fields[4];
			$free=$fields[5];
		}elsif(index($LINE, "ISP tbstat")>-1){
			@fields = split(/ +/,$LINE);
			$store = $fields[4];
		}elsif(index($LINE, "From:")>-1){
			@fields = split(/@/,$LINE);
			@hostname = split(/\./,$fields[1]);
			$host=$hostname[$0];
		}
	}
	chomp($host);chomp($store);chomp($size);chomp($free);
	print LOG localtime(time)." Store: ".$store." size: ".$size." free: ".$free."\n";
 	    my $ua = LWP::UserAgent->new();
	    $ua->timeout(30);
	    my $request=HTTP::Request->new("GET" => "http://controlm/bip/isptbstat_insert.php?host=$host&store=$store&size=$size&free=$free");
	    my $response = $ua->request($request);
	    my $count=1;
	    while (! $response->content() || $response->content() eq "") {
	        if ($count++ > 10) {
	            print LOG "ERROR: Could not fetch information from web server\n";
	            sleep 5;
	            return 0;
	        }
	        $response = $ua->request($request);
	        sleep 10;
	    }
	    my @contents = split("\n",$response->content());
	    foreach $LINE (@contents)
	    {
		print LOG $LINE;
	    }
}
sub DRIVECLEAN {
	if($debug > 1){ print LOG localtime(time)." Received Drive Cleaning Report\n";}
	my (@ROWS) = @_;
	foreach $LINE (@ROWS)
	{
		@fields = split(/:/, $LINE);
		if(index($fields[0],"sr")==0){
			$store = $fields[0];
			$store =~ s/sr/72/;
			@status = split(/ +/,$fields[2]);
		}elsif(index($fields[0],"ISP")==0){
			$host = $fields[0];
			$store = $fields[1];
			$store =~ s/-//;
			@status = split(/ +/,$fields[3]);
		}
		if ($status[1] > 30 || $status[3] eq "Needs"){
			if($status[3] eq "Needs"){
				$driveInd="Yes";
			}else{
				$driveInd="No";
			}
			if($debug > 1){ print LOG "Sending Drive Cleaning alert for store $store ($host): Last Clean:".$status[1]." hrs Drive Indicator: ".$driveInd."\n"; } 
			my $origin=$store;
			my $eventType="DRIVECLEAN";
			my $domainClass="ISP";
			my $domain=$host;
			my $objectClass="DRIVE";
			my $object="CLEAN";
			my $paramName="NEEDED";
			my $paramValue=$driveInd;
			my $originKey=$status[1];
			my $severity="70";
			trap($origin, $eventType, $domainClass, $domain, $objectClass, $object, $paramName, $paramValue, $severity, $freeText, $originKey);
		}
				
	}

}

sub getURL {
	my $url = shift;
#	if($debug > 0){ print LOG "Getting URL: $url \n"; }
	my $ua = LWP::UserAgent->new();
	$ua->timeout(30);
	my $request=HTTP::Request->new("GET" => $url);
	my $response = $ua->request($request);
	my $count=1;
	while (! $response->content() || $response->content() eq "") {
	    if ($count++ > 10) {
	        print LOG "ERROR: Could not fetch information from web server\n";
	        sleep 5;
	        return 0;
	    }
	    $response = $ua->request($request);
	    sleep 10;
	}
	return $response->content();
	#my @contents = split("\n",$response->content());
	#return @contents;
}


sub trap {
 my ($origin, $eventType, $domainClass, $domain, $objectClass, $object, $paramName, $paramValue, $severity, $freeText, $originKey) = @_;
 my $trap_receiver = "172.20.86.12";
 my $trap_community = "public";
 my $trap_session = SNMP_Session->open ($trap_receiver, $trap_community, 162);
 my $myIpAddress = "172.20.86.12";
 my $start_time = time;
 my $genericTrap = 6; 
 my $specificTrap = 7;
 my @object = ( 1,3,6,1,4,1,1031,7,5,1 );
 my $upTime = int ((time - $start_time) * 100.0);
 my @myOID = ( 1,3,6,1,4,1,1031 );
 my @OID = ( 1,3,6,1,4,1,1031,7,1 );

 warn "Sending trap failed"
 unless $trap_session->trap_request_send (encode_oid (@myOID),
 encode_ip_address ($myIpAddress),
 encode_int ($genericTrap),
 encode_int ($specificTrap),
 encode_timeticks ($upTime),
 [encode_oid (@object,2),
  encode_string ($domainClass)],
 [encode_oid (@object,3),
  encode_string ($domain)],
 [encode_oid (@object,4),
  encode_string ($objectClass)],
 [encode_oid (@object,5),
  encode_string ($paramName)],
 [encode_oid (@object,6),
  encode_string ($object)],
 [encode_oid (@object,7),
  encode_string ($paramValue)],
 [encode_oid (@object,13),
  encode_string ($originKey)],
 [encode_oid (@OID,3),
  encode_string ($severity)],
 [encode_oid (@OID,5),
  encode_string ($origin)],
 [encode_oid (@OID,12),
  encode_string ($eventType)],
 [encode_oid (@object,14),
  encode_string ($freeText)]
 );
}

