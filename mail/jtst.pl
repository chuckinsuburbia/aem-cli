#!/usr/bin/perl
#
# Script to read email and send SNMP trap to PEM
#
use lib '/in/PEM/u/NetCmmnd/EMAIL/lib';
use BER;
use SNMP_Session;
use Switch;
use HTTP::Request;
use LWP::UserAgent;

$debug=1; # 0 = No log, 1 = Parsed Values  2 = Detailed Log

open LOG, "+>>/in/PEM/u/NetCmmnd/EMAIL/email.log";

@FIELDS = ("EVENT_TYPE","ORIGIN","DOMAIN_CLASS","DOMAIN","OBJECT_CLASS","OBJECT","PARAMETER_NAME","PARAMETER_VALUE","SEVERITY","FREE_TEXT","ORIGIN_KEY","ET","OR","DC","D","OC","O","PN","PV","S","FT","OK");
# Read STDIN or files passed as arg
if($debug > 1){ print LOG localtime(time)."  Received Email\n";}
@ROWS = <>;
%SNMPmsg = ();

if($debug > 1){ print LOG "INCOMING Message:\n";}

foreach $ROW (@ROWS)
{
	if(index($ROW, "CTM REPORT")>-1){
		CTM_REPORT(@ROWS);
		exit;
	}
	if(index($ROW, "BMC TEST CLOSE")>-1){
		SC_Close(@ROWS);
		exit;
	}
	if(index($ROW, "Service Center Problem")>-1){
		SC_Open(@ROWS);
		exit;
	}
	if(index($ROW, "NarrowCast Administrator")>-1){
		RDWreport(@ROWS);
		exit;
	}
	if(index($ROW, "EMS Alarm")>-1){
		DANFOSS(@ROWS);
		exit;
	}
	if(index($ROW, "Subject: CICSEFT LOCKED")>-1){
		CICSLOCK(@ROWS);
		exit;
	}
	if(index($ROW, "BMC Portal")>-1){
		Portal(@ROWS);
		exit;
	}
	if(index($ROW, "AlarmPoint Message")>-1){
		ALARMPOINT(@ROWS);
		exit;
	}
	if(index($ROW, "qflex")>-1){
		QFLEX(@ROWS);
		exit;
	}
	if(index($ROW, "tbstat")>-1){
		TBSTAT(@ROWS);
		exit;
	}
	if(index($ROW, "Tape Drive Cleaning Report")>-1){
	#	DRIVECLEAN(@ROWS);
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
	my $date= $year.$mon.$mday;

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
		my $ClRet = `/usr/bin/rsync $filename 172.20.89.44::controlm_data/`;
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

sub Portal {
	my (@ROWS) = @_;
	foreach $ROW (@ROWS)
	{
	    if($debug > 1){ print LOG $ROW."\n";}
	    $field="Status ";
	    if (index($ROW,$field)>-1){
		$start = index($ROW,$field)+length($field);
		$len = index($ROW,".",$start)-$start+1;
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
		$start = index($ROW,$field)+length($field);
		$len = index($ROW," - ",$start)-$start+1;
		$PARAMETER = substr($ROW,$start,$len);
                $PARAMETER =~ s/^\s+//;
                $PARAMETER =~ s/\s+$//;
                $PARAMETER =~ s/\$/\[\]/;
                $PARAMETER =~ s/\.$//;
	    }
	}
	if($debug > 1){ print LOG "Status:".$STATUS."|ELEMENT:".$ELEMENT."|PARAMETER:".$PARAMETER."|\n";}
	if($STATUS eq "Critical" && $ELEMENT eq "monf10063app" && index($PARAMETER,"MATRA Freedom-Trickle")>-1){
		addCond("AUTO_MATRA_RESTART");
	}
	if($STATUS eq "Warning" && $ELEMENT eq "mqprhub1" && index($PARAMETER,"Process")>-1){
		addCond("AUTO_PATROL_RESTART");
	}
	if($STATUS eq "Warning" && $ELEMENT eq "monf09827sps" && index($PARAMETER,"Port 8081")>-1){
		addCond("AUTO_9827_WEB_DOWN");
	}
	if($STATUS eq "Warning" && $ELEMENT eq "monf09828sps" && index($PARAMETER,"Port 8081")>-1){
		addCond("AUTO_9828_WEB_DOWN");
	}
	if($STATUS eq "OK" && $ELEMENT eq "monf09827sps" && index($PARAMETER,"Port 8081")>-1){
		addCond("AUTO_9827_WEB_UP");
	}
	if($STATUS eq "OK" && $ELEMENT eq "monf09828sps" && index($PARAMETER,"Port 8081")>-1){
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
			}
		}else{
			if(index($ROW, "TIME=")>-1){
			$message = $ROW;
				$start=1;
			}
		}
	}
	$IM = `/in/PEM/u/NetCmmnd/anp/getIM.sh $PEM_Alert_Id`;
	$emailStr = "Ticket No=$IM\n";
	$emailStr.= $message;
	print LOG `echo '$emailStr' | mail -s "Alarmpoint Notification has not been acknowledged! $IM" gaths\@aptea.com`;
	if($debug > 0){ print LOG localtime(time)." Alarmpoint HelpDesk Last Resort for $IM\n"; }
	if($debug > 1){ print LOG localtime(time)." Sent Following email to HelpDesk\n$emailStr\n"; }

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
 	my $domain = defined($SNMPmsg{"q_alias"}) ? $SNMPmsg{"q_alias"} : "";
 	my $objectClass = defined($SNMPmsg{"q_name"}) ? $SNMPmsg{"q_name"} : "";
 	my $object = defined($SNMPmsg{"q_in"}) ? $SNMPmsg{"q_in"} : "";
 	my $paramValue = defined($SNMPmsg{"q_out"}) ? $SNMPmsg{"q_out"} : "";
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

sub trap {
 my ($origin, $eventType, $domainClass, $domain, $objectClass, $object, $paramName, $paramValue, $severity, $freeText, $originKey) = @_;
 my $trap_receiver = "172.20.89.45";
 my $trap_community = "public";
 my $trap_session = SNMP_Session->open ($trap_receiver, $trap_community, 162);
 my $myIpAddress = "172.20.74.64";
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

