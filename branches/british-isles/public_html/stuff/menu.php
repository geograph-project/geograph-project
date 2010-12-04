<?php
/**
 * $Project: GeoGraph $
 * $Id: places.php 5786 2009-09-12 10:18:04Z barry $
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
init_session();




$smarty = new GeographPage;
$template = 'stuff_menu.tpl';



$html = $smarty->fetch($template,$cacheid);


$db=NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');


$data = $db->getAll("select round(avg(vote)*10) as score,value,count(distinct l.user_id) c from vote_log l inner join vote_string using (id) where type = 'menu' group by id having c > 3 order by null");


foreach ($data as $idx => $row) {
	$link = htmlentities($row['value']);
	$html = str_replace('href="'.$link.'"','href="'.$link.'" class="score'.$row['score'].'"',$html);
}

$score = $db->getOne("select round(avg(vote)*10) as score from vote_log inner join vote_string using (id) where type = 'menu' and value like '/profile/%/map' group by '1'");
$html = preg_replace('/href="(\/profile\/\d+\/map)"/','href="$1" class="score'.$score.'"',$html);

$score = $db->getOne("select round(avg(vote)*10) as score from vote_log inner join vote_string using (id) where type = 'menu' and value like '/export.csv.%' group by '1'");
$html = preg_replace('/href="(\/export.csv.php.*?)"/','href="$1" class="score'.$score.'"',$html);

$html = preg_replace('/ class="score\d+">GeoGraph/','>GeoGraph',$html);

print $html;

