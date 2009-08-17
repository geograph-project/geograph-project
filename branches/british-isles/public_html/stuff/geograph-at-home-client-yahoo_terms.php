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

$VERSION = 0.2;

####################################
# Config Section

$geograph_domain = "www.geograph.org.uk";

$worker_token = "";
#The first time you run script will create a token for you, which will need to enter here
# otherwise get a token at http://$geograph_domain/stuff/at-home.php?getWorkerToken&team=
# optionally add your team name to the end of the url - useful to group workers

$yahoo_appid = "R7drYPbV34FffYJ1XzR0uw2hACglcoZKtAALrgk3xShTg3M04lzPf9spFg_QEZh.xA--";
#if you have one enter it here, otherwise just use ours

#
####################################

// Nothing below should need changing


####################
# few sanity checks... 

if (!function_exists("curl_init")) {
	die("ERROR: curl does not appear to be installed - required for contacting the Yahoo API\n");
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

list($message,$jid) = explode(':',contactGeograph('getJob'),2);

if ($message != 'Success') {
	terminate_script("Message returned from Geograph:\n\n$message,$jid");
	exit;
}

print "Starting on job #$jid\n";
print "Time: ".date('r')."\n\n";

print "Downloading data for #$jid\n";
print "Time: ".date('r')."\n\n";
$csvdata = contactGeograph("downloadJobData=$jid");

if (empty($csvdata)) {
	terminate_script("No data received");
	exit;
} 

if (strpos($csvdata,'ERROR') === 0) {
	terminate_script("Message returned from Geograph:\n\n$csvdata");
	exit;
} 

$results = array();

//we need to write to a temporay file for use with fgetcsv
$temp = tmpfile();
fwrite($temp, $csvdata);
fseek($temp, 0);

print "\n-------\n";

$c = 0;
while (($data = fgetcsv($temp)) !== FALSE) {
	list($gridimage_id,$title,$comment,$imageclass) = $data;

	print "Starting Image #$gridimage_id\n";
	print "  Time: ".date('r')."\n";

	###########################
	$terms = termExtraction($comment,$imageclass);
	###########################
	
	if (isset($terms['ResultSet']['Result'])) {
		if (is_array($terms['ResultSet']['Result'])) {
			$results[$gridimage_id] = implode('|',$terms['ResultSet']['Result']);
		} elseif (strlen($terms['ResultSet']['Result'])) {
			$results[$gridimage_id] = $terms['ResultSet']['Result'];
		}
	} else {
		print "no results\n";
	}
	$c++;
	sleep(10);
	
	if ($c%10 == 0 && count($results)) {
		print " Submitting progress to Geograph\n";
		$message = contactGeograph("submitJobResults=$jid",array('results'=>$results));
		print " Return message: $message\n";
		$results = array();
		sleep(10);
	}
}
fclose($temp); 

if (count($results)) {
	print "Submitting progress to Geograph\n";
	$message = contactGeograph("submitJobResults=$jid",array('results'=>$results));
	print " Return message: $message\n";
}

print "\n-------\nMarking job #$jid as finished\n\n";
contactGeograph("finalizeJob=$jid");

print "\n-------\nGeograph Worker [$worker_token] Finished\n";
print "Time: ".date('r')."\n-------\n\n";

exit;

####################

function contactGeograph($action,$post = '',$task = 'yahoo_terms') {
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
	curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
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

function termExtraction($context,$query = '') {
        global $yahoo_appid;
        global $VERSION;

        // The POST URL and parameters
        $request =  'http://search.yahooapis.com/ContentAnalysisService/V1/termExtraction';

        $postargs = 'output=php&appid='.$yahoo_appid.'&context='.urlencode($context).'&query='.urlencode($query);

        // Get the curl session object
        $session = curl_init($request);

        // Set the POST options.
        curl_setopt ($session, CURLOPT_POST, true);
        curl_setopt ($session, CURLOPT_POSTFIELDS, $postargs);
        curl_setopt($session, CURLOPT_HEADER, true);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($session, CURLOPT_USERAGENT, "Geograph-At-Home/$VERSION");

        // Do the POST and then close the session
        $response = curl_exec($session);
        curl_close($session);

        // Get HTTP Status code from the response
        $status_code = array();
        preg_match('/HTTP\/1.\d (\d\d\d)/s', $response, $status_code);

        // Check for errors
        switch( $status_code[1] ) {
                case 100:
                case 200:
                        // Success
                        break;
                case 503:
                        die('Your call to Yahoo Web Services failed and returned an HTTP status of 503. That means: Service unavailable. An internal problem prevented us from returning data to you.'."\n");
                        break;
                case 403:
                        die('Your call to Yahoo Web Services failed and returned an HTTP status of 403. That means: Forbidden. You do not have permission to access this resource, or are over your rate limit.'."\n");
                        break;
                case 400:
                        // You may want to fall through here and read the specific XML error
                        die('Your call to Yahoo Web Services failed and returned an HTTP status of 400. That means: Bad request. The parameters passed to the service did not match as expected. The exact error is returned in the XML response.'."\n");
                        break;
                default:
                        die('Your call to Yahoo Web Services returned an unexpected HTTP status of:' . $status_code[1]."\n");
        }
        $response = strstr($response, 'a:');

        return unserialize($response);
}


