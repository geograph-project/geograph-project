<?php
/**
 * $Project: GeoGraph $
 * $Id: viewps.php 2302 2006-07-05 12:15:49Z barryhunter $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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
init_session();

$USER->mustHavePerm("admin");

print "<script src=\"".smarty_modifier_revision("/js/geograph.js")."\"></script>";
print "<script src=\"".smarty_modifier_revision("/sorttable.js")."\"></script>";

if (!empty($CONF['redis_host']))
{
        if (empty($redis)) {
                $redis = new Redis();
                $redis->connect($CONF['redis_host'], $CONF['redis_port']);
        }
        if (!empty($CONF['redis_api_db']))
                $redis->select($CONF['redis_api_db']);

	$mainkey = 'restapi';
	$prefix = 'r';

	if (!empty($_GET['s'])) {
		$mainkey = 'syndicator';
		$prefix = 's';
	}

	$data = $redis->hGetAll($mainkey);
	$stat = $s2 = array();

	if (!empty($_GET['key'])) {
	        $keys = array();
		//get all the full keys, starting with prefix
        	foreach ($data as $key => $value) {
                	$bits = explode('|',$key);
			if ($bits[0] == $_GET['key'])
		                @$keys[$key]+=$value;
        	}
print_r($keys);
		//then get the by date stat per full kull
		foreach ($keys as $key => $value) {
			$data = $redis->hGetAll($prefix.'|'.$key);

			foreach ($data as $date => $count) {
				@$stat[$date]+=$count;
			}
		}
	} else {
		//create a stat of JUST the first bit of key

		foreach ($data as $key => $value) {
			$bits = explode('|',$key);
			@$stat[$bits[0]]+=$value;
			@$s2[$bits[0]][$key]=1;
		}
	}

	ksort($stat);

	print "<h3>$mainkey</h3>";
	print '<table class="report sortable" id="photolist" style="font-size:8pt;">';
	print "<thead><tr><th>Key</th><th>Count</th>";
	if (!empty($_GET['year']))
		print "<th>year</th>";
	if (!empty($_GET['lookup'])) {
		print "<th>lookup</th>";
		$db = GeographDatabaseConnection(true);
	}
	print "</tr></thead>";
	print "<tbody>";
	foreach ($stat as $key => $count) {
		print "<tr><td><a href=\"?key=$key\">$key</a></td><td align=right>$count</td>";
		$key0 = $key;
		if (!empty($_GET['year']) && $count > 10 && !empty($key)) {
			$total = 0;
			//loop though all the full keys
	                foreach ($s2[$key0] as $key => $value) {
				//dont care about $value here, as its not filtered by date!

	                        $data = $redis->hGetAll($prefix.'|'.$key);

				//loop though all the matching days!
                        	foreach ($data as $date => $count) {
					if (strpos($date,$_GET['year']) ===0)
	                	                $total+=$count;
        	                }
	                }
			print "<td align=right>$total</td>";
		} elseif (!empty($_GET['year'])) {
			print "<td></td>";

		}
		if (!empty($_GET['lookup']) && preg_match('/^\w+$/',$key0)) {
			print "<td>";
			$row = $db->getRow("SELECT * FROM apikeys WHERE apikey = ".$db->Quote($key0));
			foreach (array('label','homepage_url','email','type') as $k)
				if (!empty($row[$k]))
					print "<span class=nowrap>".htmlentities($row[$k])."</span> ";
			print "</td>";
		}
		print "</tr>";
	}
	print "</tbody></table>";



#	print "<pre>";
#	print_r($data);



} else {
	print "NO redis!";
}
