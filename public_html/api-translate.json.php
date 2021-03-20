<?php
/**
 * $Project: GeoGraph $
 * $Id: places.php 6407 2010-03-03 20:44:37Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
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

$results = array();

if (empty($_GET['input']) || strlen($_GET['input']) < 5 || strlen($_GET['input']) > 100) {
	die('[]');
}

$url = "https://translation.googleapis.com/language/translate/v2";
$data = array(
	"q" => trim($_GET['input']),
	"target" => 'en',
	"format" => 'text',
	"source" => 'cy',
        "model" => 'base',   // in rudimentry testing, base seems better. Eg the 'nmt', translates 'ffestiniog Eglwys => church', loosing the ffestiniog!
	"key" => $CONF['google_maps_api3_server'],
);

$r = httpPost($url, $data);

if (!empty($r)) {
	@$d = json_decode($r,true);

	if (!empty($d['data']['translations'][0]['translatedText'])) {
		$en = $d['data']['translations'][0]['translatedText'];

		if (strtolower(trim($en)) != strtolower(trim($data['q']))) {
			$results['result'] = $en;
		}
	}
}


//using php curl (sudo apt-get install php-curl)
function httpPost($url, $data){
	global $memcache;

	$mkey = md5($url.'?'.serialize($data));
	if ($r = $memcache->name_get('translate',$mkey)) {
		header('X-Debug: cached');
		return $r;
	}


if ($data['q'] == 'pont y afon gam')
return '{
  "data": {
    "translations": [
      {
        "translatedText": "step river bridge"
      }
    ]
  }
}';


    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($curl);
    curl_close($curl);


    if (empty($response))
	$response = '[]'; //so cache something!
    $memcache->name_set('translate',$mkey,$response,$memcache->compress,$memcache->period_long);

    return $response;
}







if (!empty($_SERVER['HTTP_ORIGIN'])
	&& preg_match('/^https?:\/\/(m|www|schools)\.geograph\.(org\.uk|ie)\.?$/',$_SERVER['HTTP_ORIGIN'])) { //can be spoofed, but SOME protection!

	header('Access-Control-Allow-Origin: *'); //although now this allows everyone to access it!
}

outputJSON($results);
