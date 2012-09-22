#!/usr/bin/perl
#
# $Project: GeoGraph $
# $Id$
# 
# GeoGraph geographic photo archive project
# This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
# 
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
# 
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
#
#---------------------------------------------------------------------------
# This script maintains an rrdtool database of daily photo submission
# stats, producing a daily graph. To use it, you need rrdtool available 
# from http://people.ee.ethz.ch/~oetiker/webtools/rrdtool/index.html
#
# The script should be run from cron on a daily basis
#---------------------------------------------------------------------------


use strict;
use DBI;
use Getopt::Long;

#-------------------------------------------------
# configuration
#-------------------------------------------------

#where is rrdtool?
my $rrdtool='/usr/bin/rrdtool';

#db credentials
my $db="geograph";     #database name
my $host="localhost";    #hostname
my $user="geograph";    #hostname
my $password="banjo";    #hostname

#other paramters
my $basepath="/home/www/geograph.elphin/";
my $now=-1;
my $show_help=0;
my $init=0;
my $graph=1;

#-------------------------------------------------
# get configuration overides from cmd line
#-------------------------------------------------
GetOptions 
(
    'help', \$show_help, 
    'init=s', \$init, 
    'update=s', \$now, 
    'base=s', \$basepath,
    'user=s', \$user,
    'pass=s', \$password,
    'db=s', \$db,
    'graph=s', \$graph,
    
);

$now=time() if ($now==0);


#-------------------------------------------------
# Show help?
#-------------------------------------------------
if ($show_help)
{
	
print <<END_HELP;

-------------------------------------
  Geograph Graph Updater
-------------------------------------

update_submission_graph.pl [options] 

  --help          : display this message
  --init=t        : initialise rrd database stating from time t
  --update=t      : unix timestamp to update (0=now)
  --base=path     : path to base directory of geograph project
  --user=username : mysql database user
  --pass=password : mysql database password
  --db=database   : mysql database name

This script will update or create a graph of photo submissions using rrdtool

#initialise submission graph from 2005-02-21
update_submission_graph.pl --init=1108944000

#update graph with current data
update_submission_graph.pl --update=0

#just redraw graph
update_submission_graph.pl

END_HELP
exit;

}

#-------------------------------------------------
# Initialise RRD?
#-------------------------------------------------

if ($init>0)
{
	#round init downwards to nearest day
	$init=$init - $init%86400;
	
	#create the database
	my $cmd="$rrdtool create $basepath/submissions.rrd ".
		"--start $init ".
		"--step 86400 ".
		"DS:pending:GAUGE:432000:U:U ".
		"DS:accepted:GAUGE:432000:U:U ".
		"DS:geograph:GAUGE:432000:U:U ".
		"RRA:LAST:0.5:1:730";
	
	`$cmd`;
		
	print "#!/bin/bash\n";
	print "#rrd database initialised - prime it as follows:\n\n";
	#now output the commands needed to update
	$now=time();
	
	my $t;
	for ($t=$init+86400; $t<$now; $t+=86400)
	{
		print "perl update_submission_graph.pl --update=$t --graph=0\\\n".
		"    --base=$basepath --db=$db --user=$user --pass=$password\n";
        }
	
	#draw graph
	print "perl update_submission_graph.pl --update=$t\\\n".
		"    --base=$basepath --db=$db --user=$user --pass=$password\n";
		
	print "\n";
	exit;
	
}


#-------------------------------------------------
# Update the RRD data?
#-------------------------------------------------

if ($now > 0)
{

	my $dbref="DBI:mysql:database=$db;host=$host";
	my $dbh = DBI->connect($dbref, $user, $password, {RaiseError => 1}) 
	   || die $DBI::errstr;


	my $pending=0;
	my $accepted=0;
	my $geograph=0;


	#copy all outstanding logs into the archive table
	my $sthCount = $dbh->prepare("select count(*) as cnt from gridimage where ".
		"(moderation_status=?) and ".
		"(unix_timestamp(submitted) < $now)");

	$sthCount->execute('pending') || die($DBI::errstr);
	if (my $row = $sthCount->fetchrow_hashref())
	{
	    $pending=$row->{"cnt"};
	}


	$sthCount->execute('accepted') || die($DBI::errstr);
	if (my $row = $sthCount->fetchrow_hashref())
	{
	    $accepted=$row->{"cnt"};
	}

	$sthCount->execute('geograph') || die($DBI::errstr);
	if (my $row = $sthCount->fetchrow_hashref())
	{
	    $geograph=$row->{"cnt"};
	}





	#update rrdtool
	my $cmd="$rrdtool update ".
		"$basepath/submissions.rrd  ".
		"--template pending:accepted:geograph $now:$pending:$accepted:$geograph";
	`$cmd`;

	$sthCount->finish();
	$dbh->disconnect();
}

#-------------------------------------------------
# We always update the graph...
#-------------------------------------------------

if ($graph)
{
	my $cmd="$rrdtool graph ".
		"$basepath/public_html/img/submission_graph.png ".
		"--lower-limit=0 ".
		"--start -1year ".
		"--end now ".
#		"DEF:pending=$basepath/submissions.rrd:pending:LAST ".
		"DEF:accepted=$basepath/submissions.rrd:accepted:LAST ".
		"DEF:geograph=$basepath/submissions.rrd:geograph:LAST ".
		"AREA:accepted#006000:supplemental ".
		"STACK:geograph#00C000:geographs ".
#		"STACK:pending#80FF80:'pending moderation'".
		"";

	`$cmd`;
}



