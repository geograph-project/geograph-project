#!/usr/bin/perl -w
use strict;
use Getopt::Long;

#servers to sync with
my @servers=('toast', 'crumpet','scone');

#options
my $show_help=0;
my $docopy=0;
my @filelist=();
my @exfilelist=();
my $makelive=0;
my $cvsupdate=0;
my $apache=0;
my $mysql=0;
my $fast=0;
my $revision='';
my $tracker=0;
my $today=0;
my $filesonly=0;

GetOptions 
(
    'help', \$show_help,
    'cvsupdate!', \$cvsupdate,
    'svnupdate!', \$cvsupdate,
    'makelive!', \$makelive,  
    'live!', \$docopy, 
    'r=s' => \$revision,
    "include=s" => \@filelist,
    "exclude=s" => \@exfilelist
);
    
#explode the supplied list to our array
@filelist = split(/,/,join(',',@filelist));
@exfilelist = split(/,/,join(',',@exfilelist));

#any command specified?
if (!$cvsupdate && !$makelive && !$show_help)
{
   print "No action specified, try channel --help\n\n";
   exit;
}


# Show help?
#-------------------------------------------------
if ($show_help)
{

#print "arg is ". $ARGV[0]."\n";

        print <<END_HELP;

-------------------------------------
  channel Administration Tool
-------------------------------------

channel --svnupdate
  Updates the staging area with files from the local
  subversion repository

channel --makelive [options] 
  Copies files from the staging area to the live site. 
 
  --nolive : dry run - no files copied (default)
  --live   : copy changed files
  --include=pattern - only include files that match pattern
  
  Patterns are rsync patterns using *? as wildcards. here are some examples

  --include "*.o"         would include all filenames matching *.o 
  --include "/foo"        would include a file in the base directory called foo 
  --include "foo/"        would include any directory called foo 
  --include "/foo/*/bar"  would include any file called bar two levels 
                          below a base directory called foo 
  --include "/foo/**/bar" would include any file called bar two or more 
                          levels below a base directory called foo 

END_HELP
exit;

}


if ($cvsupdate)
{
   print "Updating staging area...\n";
   my $rev = ($revision)?" -r $revision":'';
   my $update_out = `cd /var/www/channel_svn && sudo -u geograph svn update $rev`;
   print "$update_out\n\n";

   if ($update_out =~ /^C/m) 
   {
      print "*******************************\n\n\nWARNING: Conflicted Files!\n\n\n*******************************\n\n";
   } 

   #now we need to rsync that to the webserver staging areas

   
   my $server;
   foreach $server (@servers)
   {
       my $cmd="rsync ".
        "--verbose ".
        "--archive ".
        "--links ".
        "--cvs-exclude ".
        "--exclude-from=/var/www/channel_svn/scripts/makelive-exclusion ".
        "/var/www/channel_svn/ ".
        "$server-pvt:/var/www/channel_svn/";

       print "Copying updates to $server...\n";
       `sudo -u geograph $cmd`;

   }


if (my @files = ($update_out =~ /([^ ]+\.js|[^ ]+?\.css)'?$/mg)) {
  &update_revision_file('/var/www/channel_svn',@files);
  foreach my $server (@servers)
    {
       my $cmd="rsync ".
        "/var/www/channel_svn/libs/conf/revisions.conf.php ".
        "$server:/var/www/channel_svn/libs/conf/";

       print "Copying Revision File to $server...\n";
       `sudo -u geograph $cmd`;

    }
}

#my $all = `svn status /var/www/channel_svn/public_html -v`;
#if (my @files = ($all =~ /\/var\/www\/channel_svn\/([^ ]+\.js|[^ ]+?\.css)'?$/mg)) {
#  &update_revision_file('/var/www/channel_svn',@files);
#}


   print "\nDone.\n\n";
}


############################################################################

if ($makelive)
{


#Has the list been specified?
if(!@filelist){
        print "ERROR: *** You did not specify any --include options, Try channel --help\n";
        exit;
}


my $dryrun=($docopy==0)?"--dry-run":"";

my $filter="";
if (@exfilelist) {
        my $exp;
        foreach $exp (@exfilelist)
        {
                $filter.="--exclude=$exp ";
        }
}
my $inc;
foreach $inc (@filelist)
{
        $filter.="--include=$inc ";
}

#allow directories
$filter.="--include '*/' ";

#exclude all files that make it this far
$filter.="--exclude '*' ";


my $cmd="rsync ".
        "--verbose ".
        "--archive ".
        "--links ".
        "--cvs-exclude ".
        "--exclude-from=/var/www/channel_svn/scripts/makelive-exclusion ".
        $filter.
        "--stats $dryrun ".
        "/var/www/channel_svn/ ".
        '/var/www/channel_live/';

print "Executing:\n$cmd\n\n";

my $rsync_out=`sudo -u geograph $cmd`;
print $rsync_out;
print "\n\n";


if ($docopy==0)
{
        print "NOTE: That was a DRY RUN - use --live for real\n\n";
}
else
{

   #replicate to webservers
   my $server;
   foreach $server (@servers)
   {
       my $cmd="rsync ".
        "--verbose ".
        "--archive ".
        "--links ".
        "--cvs-exclude ".
        "--exclude-from=/var/www/channel_svn/scripts/makelive-exclusion ".
        $filter.
        "/var/www/channel_live/ ".
        "$server-pvt:/var/www/channel_live/";

       print "Copying to $server...\n";
       `sudo -u geograph $cmd`;

   }

  if (my @files = ($rsync_out =~ /([^\n\r ]+\.js|[^\n\r ]+?\.css)'?$/msg)) {
    &update_revision_file('/var/www/channel_live',@files);
    foreach my $server (@servers)
    {
       my $cmd="rsync ".
        "/var/www/channel_live/libs/conf/revisions.conf.php ".
        "$server-pvt:/var/www/channel_live/libs/conf/";

       print "Copying Revision File to $server...\n";
       `sudo -u geograph $cmd`;

    }
  }



        print "Files are now live at http://channel-islands.geographs.org/\n\n";
}

}

############################################################################
############################################################################

sub update_revision_file {
	my $folder = shift;
	my @files = @_;
	my $data;
	my %revs;
	
	if (open (CODE,"$folder/libs/conf/revisions.conf.php")) {
		foreach (<CODE>) {
			if (/REVISIONS\['(.*?)'\]=(\d+)/) {
				$revs{$1} = $2;
			}
		}
		close (CODE);
	}
	
	foreach (@files) {
		(my $url = $_) =~ s/public_html//;
		print "checking revision for: $url";
		$data = `svn info /var/www/channel_svn/$_ | grep "Last Changed Rev"`;
		if ($data =~ /: (\d+)/) {
			$revs{$url} = $1;
			print " :: $1\n";
		} else {
			$revs{$url} = 1;
			print " :: unknown\n";
		}
	}
	print "\n";
	
	open (OUT, ">$folder/libs/conf/revisions.conf.php");
	print OUT "<?php\n";
	print OUT "\$REVISIONS = array();\n";
	foreach (sort keys %revs) {
		print OUT "\$REVISIONS['$_']=$revs{$_};\n";
	}
	print OUT "?>";
	close (OUT);
	
}

############################################################################

1;

