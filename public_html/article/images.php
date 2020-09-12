<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2006 Barry Hunter (geo@barryhunter.co.uk)
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



$db = GeographDatabaseConnection(true);


$match = empty($_GET['s'])?'http':'http://';

$data = $db->getAll("SELECT url,title,content,user_id,realname FROM article INNER JOIN user USING (user_id) WHERE content LIKE '%[img=$match%'");


foreach ($data as $idx => $row) {
	preg_match_all('/\[img=(http.*?)\]/',$row['content'],$m);
	if (!empty($m[1])) {
		print "<h3><a href=\"/article/{$row['url']}\">{$row['title']}</a></h3>\n";
		print "<h4><a href=\"/profile/{$row['user_id']}\">{$row['realname']}</a></h4>\n";

		foreach ($m[1] as $image) {
			if (empty($_GET['a']) && preg_match('/^https?:\/\/(t0|s\d|media)\.geograph\.org\.uk\//',$image))
				continue;
			print "* $image<br/>\n";
		}
		print "<br/>";
	}

}

print "<hr/>done";
