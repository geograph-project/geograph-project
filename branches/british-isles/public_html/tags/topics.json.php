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
require_once('geograph/topics.inc.php');

$topics = array();

if (!empty($_GET['gridimage_id'])) {
	customExpiresHeader(3600);

	$gid = intval($_GET['gridimage_id']);
	
	$db = GeographDatabaseConnection(true);
	
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	$topics = $db->getAll("(SELECT label AS tag,'cluster' AS `prefix` FROM gridimage_group WHERE gridimage_id = $gid ORDER BY score DESC,sort_order) 
		UNION (SELECT result AS tag,'term' AS `prefix` FROM at_home_result WHERE gridimage_id = $gid ORDER BY at_home_result_id)
		UNION (SELECT result AS tag,'term' AS `prefix` FROM at_home_result_archive WHERE gridimage_id = $gid ORDER BY at_home_result_id)
		UNION (SELECT tag,'wiki' AS `prefix` FROM gridimage_wiki WHERE gridimage_id = $gid ORDER BY seq)");
	
	//todo - if none found, perhaps load a string, and do it on the fly?
} 

if (false) {
	$topics[] = array('tag'=>"testing",'prefix'=>'');

} elseif (!empty($_GET['string'])) {
	customExpiresHeader(360000,true);

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

if (false) {

        $url = "http://tagthe.net/api/?text=".urlencode($string)."&view=json";

        require_once '3rdparty/JSON.php';
        $json = new Services_JSON();


        $mkey = md5($url);
        $value =& $memcache->name_get('rpc',$mkey);

        if (empty($value)) {
                ini_set('user_agent', 'Geograph Britain and Ireland - Tagging Interface (+http://www.geograph.org.uk)');

                $value = $json->decode(file_get_contents($url));

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

}
if (isset($_GET['term'])) {
	foreach ($topics as $idx => $row) {
		$topics[$idx] = ($row['prefix'])?"{$row['prefix']}:{$row['tag']}":$row['tag'];
	}
}

outputJSON($topics);

