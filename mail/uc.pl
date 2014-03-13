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


print uc("this is a test");
