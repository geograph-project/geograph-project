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
# This script maintains an rrdtool database of 5 min load average
# stats, producing a daily graph. To use it, you need rrdtool available 
# from http://people.ee.ethz.ch/~oetiker/webtools/rrdtool/index.html
#
# The script should be run from cron  every 5 mins
#---------------------------------------------------------------------------


use strict;
use DBI;
use Getopt::Long;

#-------------------------------------------------
# configuration
#-------------------------------------------------

#where is rrdtool?
my $rrdtool='/usr/bin/rrdtool';

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
    'init', \$init, 
    'update', \$now, 
    'base=s', \$basepath,
    'graph=s', \$graph,
    
);


$now=time() if ($now);


#-------------------------------------------------
# Show help?
#-------------------------------------------------
if ($show_help)
{
	
print <<END_HELP;

-------------------------------------
  Geograph Graph Updater
-------------------------------------

update_cpu_graph.pl [options] 

  --help          : display this message
  --init          : initialise rrd database 
  --update        : update with 5 min load average
  --base=path     : path to base directory of geograph project

This script will update or create a graph showing the daily rate photo submissions 
using rrdtool

#initialise new rrd database
update_cpu_graph.pl --init

#update graph with current load average
update_cpu_graph.pl --update

#just redraw graph
update_cpu_graph.pl

END_HELP
exit;

}

#-------------------------------------------------
# Initialise RRD?
#-------------------------------------------------

if ($init)
{
	#round init downwards to nearest day
	$init=time();
        $init=$init - $init%86400;
	
	my $step=300;
	
	#create the database
        #1 week of normal data
        #1 month of 1hr average
	my $cmd="$rrdtool create $basepath/cpu.rrd ".
		"--start $init ".
		"--step $step ".
		"DS:loadavg:GAUGE:1200:U:U ".
		"RRA:AVERAGE:0.5:1:2016 ".
		"RRA:AVERAGE:0.5:12:720";
	
	`$cmd`;
		
	exit;
	
}


#-------------------------------------------------
# Update the RRD data?
#-------------------------------------------------

if ($now > 0)
{
	open(LOADAVG, "/proc/loadavg");
        my $loadavg=<LOADAVG>;
        close(LOADAVG);

	my ($l1,$l2,$l3)=split(/\s+/, $loadavg);


	my $load=$l2;

	#update rrdtool
	my $cmd="$rrdtool update ".
		"$basepath/cpu.rrd  ".
		"--template loadavg $now:$load";
	`$cmd`;
}

#-------------------------------------------------
# We always update the graph...
#-------------------------------------------------

if ($graph)
{
	my $cmd="$rrdtool graph ".
		"$basepath/public_html/img/cpuday.png ".
		"--lower-limit=0 ".
		"--start -1day ".
		"--end now ".
		#"--vertical-label=\"load avg\" ".
		"DEF:loadavg=$basepath/cpu.rrd:loadavg:AVERAGE ".
		"LINE2:loadavg#006000:'5 min load average' ";

	`$cmd`;
}



