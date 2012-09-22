#!/usr/bin/perl -w

use strict;
use Getopt::Long;
use File::Find;
use File::Copy;

#-------------------------------------------------
# configuration
#-------------------------------------------------

my $show_help=0;
my $tmp="/tmp";


#-------------------------------------------------
# get configuration overides from cmd line
#-------------------------------------------------
GetOptions 
(
    'help', \$show_help, 
    'temp=s', \$tmp,
);



#-------------------------------------------------
# Show help?
#-------------------------------------------------
if ($show_help)
{
	
print <<END_HELP;

-------------------------------------
  Geograph Table Dumper
-------------------------------------

This script rebuild the dumps of static tables, replacing
them only if their contents differ. This allows you to run
the tool and only check in those dumps which have actually
changed.

This script should be executed from within the directory
where the existing files are placed

refresh_table_dumps.pl [options] 

  --help          : display this message
  --user=username : mysql database user
  --pass=password : mysql database password
  --db=database   : mysql database name
  --temp=path     : path to use for temporary files

END_HELP
exit;

}


my $updates=0;

sub ProcessFile 
{
	#file has .mysql.bz2 extension?
	if (m/^(.*)\.mysql\.bz2$/)
	{
		my $tbl=$1;
		print "Processing $tbl";
		
		my $dots=30-length($tbl);
		for(my $d=0;$d<$dots;$d++)
		{
			print ".";
		}
		
		#dump current data
		`mysqldump --opt geograph $tbl > $tmp/new.mysql`;
		
		#extract older dump
		copy("$tbl.mysql.bz2", "$tmp/old.mysql.bz2");
		`bunzip2 $tmp/old.mysql.bz2`;
		
		#diff them
		my $diff=`diff --brief $tmp/new.mysql $tmp/old.mysql`;
		
		if ($diff =~ /differ/)
		{
			unlink("$tbl.mysql.bz2");
			copy("$tmp/new.mysql", "$tbl.mysql");
			`bzip2 $tbl.mysql`;
			
			$updates++;
			print "UPDATED\n";
		
		}
		else
		{
			print "OK\n";
		}
		
		#cleanup
		unlink("$tmp/new.mysql");
		unlink("$tmp/old.mysql");
		
		
	}
}

#-------------------------------------------------
# Process tables...
#-------------------------------------------------


print "\n";			
find(\&ProcessFile, "./");

print "\n";			
if ($updates)
{
	print "$updates table dumps updated - you should commit these to CVS\n\n";
}