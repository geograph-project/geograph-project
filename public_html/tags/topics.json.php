<?php
/**
 * $Project: GeoGraph $
 * $Id: tags.json.php 7263 2011-05-22 21:21:54Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 Barry Hunter (geo@barryhunter.co.uk)
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

require_once('geograph/global.inc.php');


if (!empty($_GET['callback'])) {
	header('Content-type: text/javascript');
} else {
	header('Content-type: application/json');
}

customExpiresHeader(360000);

$topics = array();

if (!empty($_GET['string'])) {
	$string = $_GET['string'];

        ###################
        # Yahoo Term Extraction API

        $mkey = md5($string);
        $value =& $memcache->name_get('term',$mkey);

        if (empty($value)) {
                $yahoo_appid = "R7drYPbV34FffYJ1XzR0uw2hACglcoZKtAALrgk3xShTg3M04lzPf9spFg_QEZh.xA--";

                $value = termExtraction($string);

                $memcache->name_set('term',$mkey,$value,$memcache->compress,$memcache->period_med);
        }
        if (!empty($value) && !empty($value['ResultSet']) && !empty($value['ResultSet']['Result'])) {
                foreach ($value['ResultSet']['Result'] as $topic) {
                        $topics[] = array('tag'=>$topic);
                }
        }

        ###################
        # tagthe.net extraction API


        $url = "http://tagthe.net/api/?text=".urlencode($string)."&view=json";

        require_once '3rdparty/JSON.php';
        $json = new Services_JSON();


        $mkey = md5($url);
        $value =& $memcache->name_get('rpc',$mkey);

        if (empty($value)) {
                ini_set('user_agent', 'Geograph Britain and Ireland - Tagging Interface (+http://www.geograph.org.uk)');

                $value = $json->decode(@file_get_contents($url));

                if ($value)
                        $memcache->name_set('rpc',$mkey,$value,$memcache->compress,$memcache->period_med);
        }
        if (!empty($value) && !empty($value->memes)) {
                foreach ($value->memes as $meme) {
                        if (!empty($meme->dimensions)) {
                                if (!empty($meme->dimensions->topic)) {
                                        foreach ($meme->dimensions->topic as $topic) {
                                                $topics[] = array('tag'=>$topic);
                                        }
                                }
                                if (!empty($meme->dimensions->location)) {
                                        foreach ($meme->dimensions->location as $location) {
                                                $topics[] = array('tag'=>$location,'prefix'=>'place');
                                        }
                                }
                                if (!empty($meme->dimensions->person)) {
                                        foreach ($meme->dimensions->person as $person) {
                                                $topics[] = array('tag'=>$person,'prefix'=>'person');
                                        }
                                }
                        }
                }
        }


}


if (!empty($_GET['callback'])) {
        $callback = preg_replace('/[^\w\.-]+/','',$_GET['callback']);
        echo "{$callback}(";
}

require_once '3rdparty/JSON.php';
$json = new Services_JSON();
print $json->encode($topics);

if (!empty($_GET['callback'])) {
        echo ");";
}



function termExtraction($context,$query = '') {
        global $yahoo_appid;

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
        curl_setopt($session, CURLOPT_USERAGENT, 'Geograph Britain and Ireland - Tagging Interface (+http://www.geograph.org.uk)');

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
                case 403:
                case 400:
                default:
                        return 0; //
                        die('Your call to Yahoo Web Services returned an unexpected HTTP status of:' . $status_code[1]."\n");
        }
        $response = strstr($response, 'a:');

        return unserialize($response);
}

