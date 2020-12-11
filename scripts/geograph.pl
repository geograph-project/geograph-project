#!/usr/bin/perl -w
use strict;
use Getopt::Long;

#servers to sync with
my @servers=('cake', 'cream');

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
my $today=0;
my $filesonly=0;

GetOptions 
(
    'help', \$show_help,
    'cvsupdate!', \$cvsupdate,
    'svnupdate!', \$cvsupdate,
    'makelive!', \$makelive,  
    'apache!', \$apache,
    'today!', \$today,
    'filesonly!',\$filesonly,
    'mysql!', \$mysql,
    'live!', \$docopy, 
    'r=s' => \$revision,
    'fast!', \$fast, 
    "include=s" => \@filelist,
    "exclude=s" => \@exfilelist
);
    
#explode the supplied list to our array
@filelist = split(/,/,join(',',@filelist));
@exfilelist = split(/,/,join(',',@exfilelist));

#any command specified?
if (!$mysql && !$apache && !$today && !$cvsupdate && !$makelive && !$show_help)
{
   print "No action specified, try geograph --help\n\n";
   exit;
}


# Show help?
#-------------------------------------------------
if ($show_help)
{

#print "arg is ". $ARGV[0]."\n";

        print <<END_HELP;

-------------------------------------
  Geograph Administration Tool
-------------------------------------

geograph --today
  Updates stats.geograph.org.uk/today with an immediate webalizer report
  of the current days logfiles. Good for a quick check on new referrers

geograph --svnupdate
  Updates the staging area with files from the local
  subversion repository

geograph --makelive [options] 
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

geograph --apache syntax|start|stop|restart|reload|force-reload [--fast]
  Starts, stops or restarts the geograph apache server
  Always checks config syntax (unless you use --fast) , but if that's 
  all you want to check, use
  
  geograph --apache syntax

  You can skip the syntax check by using --fast
  
geograph --mysql start|stop
  Starts or stops the geograph mysql server  

END_HELP
exit;

}
############################################################################

if ($mysql)
{
   my $arg=$ARGV[0];
   system("sudo /etc/init.d/mysql $arg");
}

############################################################################

if ($apache)
{
   #we've got a separate command to do this, but needs to be root...
   my $fastopt=$fast?"--fast":"";
   system("sudo webservers $fastopt ".$ARGV[0]);
}

############################################################################

if ($today)
{
   print "Copying log files from webservers...\n";

   my $logdir="/var/www/stats/today";

   my $server;
   my $merge="sudo -u geograph sh -c \"clfmerge ";
   foreach $server (@servers)
   {
      #`sudo -u geograph rm $logdir/$server` if (-e "$logdir/$server");
      #`sudo -u geograph scp $server-pvt:/var/www/geograph_live/logs/access_log $logdir/$server`;
       `sudo -u geograph rsync $server-pvt:/var/www/geograph_live/logs/access_log $logdir/$server`;

      $merge.=" $logdir/$server";
   }
   foreach $server (@servers)
   {
      my $file = $server."static";
      #`sudo -u geograph rm $logdir/$file` if (-e "$logdir/$file");
      #`sudo -u geograph scp $server-pvt:/var/www/geograph_static/logs/access_log $logdir/$file`;
       `sudo -u geograph rsync $server-pvt:/var/www/geograph_static/logs/access_log $logdir/$file`;

      $merge.=" $logdir/$file";
   }

   print "Merging and fixing up logfiles...\n";
   $merge.="|ipfix|toclf > $logdir/today.log\"";
   `$merge`;

   if (!$filesonly) {
      print "Running webalizer...\n";
      `sudo -u geograph webalizer -c $logdir/today.conf`;

      print "Done - view today's stats at http://stats.geograph.org.uk/today\n\n";

      print "Running referrers...\n";
      `sudo perl $logdir/refs.pl`;

      print "Done - view today's referers at http://stats.geograph.org.uk/today/refs.php\n\n";
   }
}

############################################################################

if ($cvsupdate)
{
   print "Updating staging area...\n";
   my $rev = ($revision)?" -r $revision":'';
   my $update_out = `cd /var/www/geograph_svn && sudo -u geograph -H svn update --ignore-externals $rev`;
   print "$update_out\n\n";

   if ($update_out =~ /^C/m) 
   {
      print "*******************************\n\n\nWARNING: Conflicted Files!\n\n\n*******************************\n\n";
   } 

   #now we need to rsync that to the webserver staging areas

       my $cmd="rsync ".
        "--verbose ".
        "--archive ".
        "--links ".
        "--cvs-exclude ".
        "--exclude-from=/mnt/big/geograph_svn/scripts/makelive-exclusion ".
        "/mnt/big/geograph_svn/ ".
        "/var/www/geograph_svn/";

       print "Copying updates to localhost...\n";

print "Executing:\n$cmd\n\n";

$update_out = `sudo -u geograph $cmd`;
print "$update_out\n";

   ############################
   
   my $server;
   foreach $server (@servers)
   {
       my $cmd="rsync ".
        "--verbose ".
        "--archive ".
        "--links ".
        "--cvs-exclude ".
        "--exclude-from=/var/www/geograph_svn/scripts/makelive-exclusion ".
        "/var/www/geograph_svn/ ".
        "$server-pvt:/var/www/geograph_svn/";

       print "Copying updates to $server...\n";
       `sudo -u geograph $cmd`;
   }

   ############################

   if (my @files = ($update_out =~ /([^\n\r ']+\.js|[^\n\r ']+?\.css)'?$/mg)) {
     &update_revision_file('/mnt/big/geograph_svn','/var/www/geograph_svn', @files);
   }

   #my $all = `svn status /var/www/geograph_svn/public_html -v`;
   #if (my @files = ($all =~ /\/var\/www\/geograph_svn\/([^ ]+\.js|[^ ]+?\.css)'?$/mg)) {
   #  &update_revision_file('/var/www/geograph_svn',@files);
   #}

   print "\nDone.\n\n";
}

############################################################################

if ($makelive)
{

   #Has the list been specified?
   if(!@filelist){
      print "ERROR: *** You did not specify any --include options, Try geograph --help\n";
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
           "--exclude-from=/var/www/geograph_svn/scripts/makelive-exclusion ".
           $filter.
           "--stats $dryrun ".
           "/var/www/geograph_svn/ ".
           '/var/www/geograph_live/';

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
              "--exclude-from=/var/www/geograph_svn/scripts/makelive-exclusion ".
              $filter.
              "/var/www/geograph_live/ ".
              "$server-pvt:/var/www/geograph_live/";

          print "Copying to $server...\n";
#print "Executing:\n$cmd\n\n";
          `sudo -u geograph $cmd`;
      }

   ############################

      if (my @files = ($rsync_out =~ /([^\n\r ']+\.js|[^\n\r ']+?\.css)'?$/mg)) {
         &update_revision_file('/mnt/big/geograph_svn','/var/www/geograph_live', @files);
      }

      print "Files are now live at http://www.geograph.org.uk\n\n";
   }
}

############################################################################
############################################################################

sub update_revision_file {
	my $source = shift;
	my $destination = shift;
	my @files = @_;
	my $data;
	my %revs;

##... also it needs to 'build' and copy the file to3 ?!!??!

##... this works, but its should intergrate the minification that the statis server did
## plus this is only for staging, prodiction s3 is similar tho
##sudo -u www-data mkdir -p /mnt/s3-photos-staging/templates/basic/css/
##sudo -u www-data cp public_html/templates/basic/css/basic.css /mnt/s3-photos-staging/templates/basic/css/basic.v9080.css
##sudo -u www-data mkdir -p /mnt/s3-photos-staging/js/
##sudo -u www-data cp public_html/js/geograph.js /mnt/s3-photos-staging/js/geograph.v8582.js

	#####################################
	# read current file
	
	if (open (CODE,"$source/libs/conf/revisions.conf.php")) {
		foreach (<CODE>) {
			if (/REVISIONS\['(.*?)'\]=(\d+)/) {
				$revs{$1} = $2;
			}
		}
		close (CODE);
	}
	
	#####################################
	# update revision(s)

	foreach (@files) {
		(my $url = $_) =~ s/public_html//;
		print "checking revision for: $url";
		$data = `git info $source/$_ | grep "Commit ID"`;
		if ($data =~ /: (\w+)/) {
			$revs{$url} = $1;
			$revs{$url} =~ s/[a-f]+//g;
			$revs{$url} = substr($revs{$url}, 0, 8);

			if ('' eq $revs{$url}) { ##unlikely to have nothing, but just in case!
				$revs{$url} = 111;
			}
			
			print " :: $revs{$url} \n";
		} else {
			$revs{$url} = 1;
			print " :: unknown\n";
		}
	}
	print "\n";
	
	#####################################
	# write the file
	
	open (OUT, ">$source/libs/conf/revisions.conf.php");
	print OUT "<?php\n";
	print OUT "\$REVISIONS = array();\n";
	foreach (sort keys %revs) {
		print OUT "\$REVISIONS['$_']=$revs{$_};\n";
	}
	print OUT "?>";
	close (OUT);

        #####################################
	# sync the file locally

        my $cmd="rsync ".
                "$source/libs/conf/revisions.conf.php ".
           "$destination/libs/conf/";

        print "Copying Revision File locally...\n";
        `sudo -u geograph $cmd`;

        #####################################
	# call the PHP script that copies the file to S3

	print `php $destination/scripts/publish-to-s3.php`;

	#####################################
	# sync the file remotely 

        foreach my $server (@servers)
        {
          my $cmd="rsync ".
                        "$source/libs/conf/revisions.conf.php ".
           "$server:$destination/libs/conf/";

          print "Copying Revision File to $server...\n";
          `sudo -u geograph $cmd`;
        }

	#####################################	
}

############################################################################

1;

