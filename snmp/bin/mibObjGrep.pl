#!/usr/bin/perl -w

#Usage
# mibObjGrep.pl -s <syntax pattern> -f <file>

use strict;
use Getopt::Long;

my($mibObj, $syntax, $file);

GetOptions(
    "syntax=s"     => \$syntax,
    "file=s"    => \$file,
    );

die "All arguments are required" unless ($syntax && $file);

if( defined( $file ) && -e $file )
{
    open( IN, $file ) || die "couldn't open file";
}

my @matches;
my $matching;
my $linecount = 0;

my $mibObj = "OBJECT-TYPE";
while( my $line = readline *IN)
{
    $linecount++;
    my $obj = "";
    my $syn = "";
    my $prevLine = "";
    my $desc = "";
    if( $line =~ /(.*$mibObj.*)/ ) {   
	my $obj = $1;
	$line = readline *IN;
	$linecount++;
	if ($line =~ /(SYNTAX.*$syntax.*)/) {
		my $syn = $1;
		my $prevLine = $linecount -1;
		while ( my $line2 = readline *IN) {
			$linecount++;
			if( $line2 =~ /(.*DESCRIPTION.*)/) {
				$desc = "$1\n";
				while (my $line3 = readline *IN) {
					$linecount++;
					if ($line3 =~ /::/) {
						last;
					} else {
						$desc = $desc . $line3;
					}
				}
				last;
			}
		}
        	push( @matches, $file . ": " . $prevLine . ": " . $obj . ":" . $syn . "\n" . $desc . "\n\n");
	}
    }
}

print @matches;

