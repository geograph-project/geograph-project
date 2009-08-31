<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2009 Barry Hunter (geo@barryhunter.co.uk)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

$VERSION = 0.11;

####################################
# Config Section

$geograph_domain = "www.geograph.org.uk";

$worker_token = "";
#The first time you run script will create a token for you, which will need to enter here
# otherwise get a token at http://$geograph_domain/stuff/at-home.php?getWorkerToken&team=
# optionally add your team name to the end of the url - useful to group workers

require 'Carrot2.class.php';
#you might change this if you have the class somewhere else...

#
####################################

// Nothing below should need changing


####################
# few sanity checks... 

if (!function_exists("curl_init")) {
	die("ERROR: curl does not appear to be installed - required for contacting the various APIs\n");
}

if (empty($worker_token)) {
	print "Attempting to contact $geograph_domain to obtain token:\n\n";
	
	$r = contactGeograph("getWorkerToken");
	
	print strip_tags($r);
	
	print "\n\n";
	exit;
}

print "\n-------\nGeograph Worker/$VERSION [$worker_token] Starting\n";
print "Time: ".date('r')."\n-------\n";

####################
$c = 0;
$fail = 0;

while (1) {

	list($message,$jid) = explode(':',contactGeograph('getJob'),2);

	if ($message != 'Success') {
		print "Message returned from Geograph:\n\n$message,$jid\n";

		if ($fail > 10) {
			break;
		}

		print "Sleeping for 1 hour\n";
		sleep(3600);

		$fail++;
		continue; //try getting a new job!
	}
	$fail=0;

	print "Starting on job #$jid\n";
	print "Time: ".date('r')."\n\n";

	print "Downloading data for #$jid\n";
	print "Time: ".date('r')."\n\n";

	$results = array();

	//we need to write to a temporay file for use with fgetcsv
	$temp = tmpfile();

	$csvdata = contactGeograph("downloadJobData=$jid");

	fwrite($temp,$csvdata);
	fseek($temp, 0);

	print "\n-------\n";


	$carrot = Carrot2::createDefault();
	$lookup = array();
	while (($data = fgetcsv($temp)) !== FALSE) {
		list($gridimage_id,$title,$comment) = $data;

		$lookup[] = $gridimage_id;
		$carrot->addDocument(
			(string)$gridimage_id,
			(string)utf8_encode(htmlentities($title)),
			strip_tags(str_replace('<br>',' ',utf8_encode(htmlentities($comment))))
		);
	}

	print "\nSubmitting query...\n";

	$c = $carrot->clusterQuery();
	
	print "\nProcessing Results...\n";
	
	if (count($c)) {
		$results = $score = $ids = array();
		foreach ($c as $idx => $cluster) {

			$results[$idx] = $cluster->label;
			$score[$idx] = $cluster->score;

			$ids[$idx] = array();	
			//covert document_id back to gridimage_id
			foreach ($cluster->document_ids as $sort_order => $document_id) {
				$ids[$idx][$sort_order] = $lookup[$document_id];
			}
		}
	
		print "\n-------\nSubmitting Results and Marking job #$jid as finished\n\n";
		$message = contactGeograph("submitJobResults=$jid&finalizeJob=$jid",array('results'=>$results,'score'=>$score,'ids'=>$ids));
	} else {
		print "\n-------\nMarking job #$jid as finished (as empty)\n\n";
		$message = contactGeograph("finalizeJob=$jid");
	}
	print "$message\n\n";
	
	
	sleep(60);
}

print "\n-------\nGeograph Worker [$worker_token] Finished\n";
print "Time: ".date('r')."\n-------\n\n";

exit;

####################

function contactGeograph($action,$post = '',$task = 'carrot2',$f = null) {
	global $geograph_domain;
	global $worker_token;
	global $VERSION;

	$request =  "http://$geograph_domain/stuff/at-home.php?$action&task=$task&worker=$worker_token";

	// Get the curl session object
	$session = curl_init($request);

	if (!empty($post)) {
		// Set the POST options.
		curl_setopt ($session, CURLOPT_POST, true);
		curl_setopt ($session, CURLOPT_POSTFIELDS, http_build_query($post));
	}
	if (empty($f)) {
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
	} else {
		curl_setopt($session, CURLOPT_FILE, $f);
	}
	curl_setopt($session, CURLOPT_USERAGENT, "Geograph-At-Home/$VERSION $task");

	// Do the request and then close the session
	$response = curl_exec($session);
	curl_close($session);

	return ($response);
}

function terminate_script($message) {
	global $VERSION;
	global $worker_token;
	print "\n$message\n";
	print "\n-------\nGeograph Worker/$VERSION [$worker_token] Terminating\n";
	print "Time: ".date('r')."\n-------\n\n";

	exit;
}


