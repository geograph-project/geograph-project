<?php
/**
 * $Project: GeoGraph $
 * $Id: staticpage.php 7628 2012-06-27 15:22:45Z geograph $
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


#http://www.geograph.org.uk/browser/#/takenyear+%222010%22/takenmonth+%22201006%22

	$sort = '';
	$query = '';
	$filters = array();

	foreach (explode('/',$_GET['_escaped_fragment_']) as $bit) {
		if (preg_match('/q=(.*)/',$bit,$m)) {
			$query = urldecode($m[1]);$q=1;
		} elseif (preg_match('/loc=(.*)/',$bit,$m)) {
                        //$loc = urldecode($m[1]);
		} elseif (preg_match('/page=(.*)/',$bit,$m)) {
                        //$page = urldecode($m[1]);
		} elseif (preg_match('/sort=(.*)/',$bit,$m)) {
                        $sort = urldecode($m[1]);
		} elseif (preg_match('/sample=(.*)/',$bit,$m)) {
                        //$sample = urldecode($m[1]);
		} elseif (preg_match('/display=(.*)/',$bit,$m)) {
                        //$display = urldecode($m[1]);
		} elseif (preg_match('/dist=(.*)/',$bit,$m)) {
                        //$dist = urldecode($m[1]);
		} elseif (preg_match('/(\w+)(%20|\+| )-(.*)/',$bit,$m)) {
			$txt = preg_replace('/(^\(?"|"\)?$)/','',preg_replace('/" \| "/',' | ',urldecode($m[3])));
			$filters[] = array($m[1],$txt,preg_replace('/top:/','',$txt),0);
		} elseif (preg_match('/(\w+)(%20|\+| )(.*)/',$bit,$m)) {
			$txt = preg_replace('/(^\(?"|"\)?$)/','',preg_replace('/" \| "/',' | ',urldecode($m[3])));
			$filters[] = array($m[1],$txt,preg_replace('/top:/','',$txt),1);
                }
	}

	if (!empty($query)) {
		$text = "Geograph images matching [$query]";
	} else {
		$text = "";
	}

	if (!empty($filters)) {
		$plus = $minus = 0;
		foreach ($filters as $idx => $filter) {
			if (!empty($text)) {
				$text .= ", and ";
			}
			if ($filter[3]) {
				$text .= "{$filter[0]} is ";
			} else {
				$text .= "{$filter[0]} is not ";
			}
			$text .=$filter[2];

			$query .= " @{$filter[0]} ".($filter[3]?'':'-');
			($filter[3]?($plus++):($minus++));
			if (strpos($filter[1],' | ') !== FALSE) {
				$query .= '("'.str_replace(' | ','" | "',preg_replace('/[\/"\'\(\)@^$-]+/',' ',$filter[1])).'")';
			} elseif (preg_match('/context|tags|terms|groups|snippets|wikis|values/',$filter[0])) {
				$query .= '"_SEP_ '.preg_replace('/\b(\w{2,})/','=$1',preg_replace('/[\/"\'\(\)\|@^$-]+/',' ',$filter[1])).' _SEP_"';
			} else { //TODO - look for 'OR' queries on tags
				$query .= '"^'.preg_replace('/\b(\w{2,})/','=$1',preg_replace('/[\/"\'\(\)\|@^$-]+/',' ',$filter[1])).'$"';
			}
		}
		if ($minus && $plus && empty($q)) {
			$query .= ' @status geograph|supplemental';
		}
	}
	if (empty($q) && !empty($text)) {
		$text = "Geograph images where ".$text;
	} elseif (empty($text)) {
		$text = "Geograph Image Browser";
	}
	print "<title>".htmlentities($text)."</title>";
	print "<h1>Geograph Britain and Ireland</h1>";
	print "<h2>".htmlentities($text)."</h2>";

	$url = "http://api.geograph.org.uk/api-facet.php?q=".urlencode($query)."&limit=5&select=title,grid_reference,realname,hash";

  switch($sort) {
     case 'taken_down': $url .= "&sort=takendays+DESC"; $url .= "&rank=2";  break;
     case 'taken_up': $url .= "&sort=takendays+ASC"; $url .= "&rank=2";  break;
     case 'submitted_down':  $url .= "&sort=@id+DESC"; $url .= "&rank=2";  break;
     case 'submitted_up':  $url .= "&sort=@id+ASC"; $url .= "&rank=2";  break;
     case 'spread':  $url .= "&sort=sequence+ASC"; $url .= "&rank=2";  break;
     case 'distance':  $url .= "&sort=@geodist+ASC"; $url .= "&rank=2";  break;
     case 'random':  $url .= "&sort=@random";  break;
  }
	$data = file_get_contents($url);

	if (!empty($data)) {

		require_once '3rdparty/JSON.php';
		$d = json_decode($data);

		if (!empty($d) && !empty($d->matches)) {
			foreach ($d->matches as $gridimage_id => $row) {

				$thumbnail = getGeographUrl($gridimage_id,$row->attrs->hash,'med');
				print '<a href="http://www.geograph.org.uk/photo/'.$gridimage_id.'" title="'.htmlentities("{$row->attrs->grid_reference} : {$row->attrs->title} by {$row->attrs->realname}").'"><img src="'.$thumbnail.'"></a> ';
			}
			if (!empty($d->total_found) && $d->total_found > 5) {
				print "<br/><b>{$d->total_found} images in total</b>";
			}
		} else {
			print "Unable to load images right now.";
		}

	} else {
		print "Unable to load images right now";
	}
	print '<hr/><a href="/browser/">View Geograph Browser</a>';
	exit;





function getGeographUrl($gridimage_id,$hash,$size ='small') { 
       $yz=sprintf("%02d", floor($gridimage_id/1000000)); 
       $ab=sprintf("%02d", floor(($gridimage_id%1000000)/10000)); 
       $cd=sprintf("%02d", floor(($gridimage_id%10000)/100)); 
       $abcdef=sprintf("%06d", $gridimage_id); 
	if ($yz == '00') {
		$fullpath="/photos/$ab/$cd/{$abcdef}_{$hash}"; 
	} else {
		$fullpath="/geophotos/$yz/$ab/$cd/{$abcdef}_{$hash}"; 
	}
       $server =  "https://s".($gridimage_id%4).".geograph.org.uk"; 
       switch($size) { 
               case 'full': return "https://s0.geograph.org.uk $fullpath.jpg"; break; 
               case 'med': return "$server{$fullpath}_213x160.jpg"; break; 
               case 'small': 
               default: return "$server{$fullpath}_120x120.jpg"; 
       } 

} 
