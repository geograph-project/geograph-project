<?php
/**
 * $Project: GeoGraph $
 * $Id: syndicator.php 3052 2007-02-08 13:57:25Z barry $
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
require_once('geograph/feedcreator.class.php');


$format="RSS0.91";


$rss = new UniversalFeedCreator();


$rss->title = 'Geograph Featured Searches'; 
$rss->link = "http://{$_SERVER['HTTP_HOST']}/explore/searches.php";


$db = GeographDatabaseConnection(true);
	

$where = array();
if (isset($_GET['admin'])) {
	$where[] = 'approved > -1';
} else {
	$where[] = 'approved = 1';
}

if (count($where))
	$where_sql = " where ".join(' AND ',$where);
		

$sql = "select
		id,searchdesc,`count`,comment,created,approved,orderby
	from
		queries_featured
		inner join queries using (id)
		left join queries_count using (id)
	$where_sql
	order by 
		created desc";

$recordSet = &$db->Execute($sql);
while (!$recordSet->EOF)
{
	$item = new FeedItem();
	
	$item->link = "http://{$_SERVER['HTTP_HOST']}/results/".$recordSet->fields['id'];
	
	$item->title = "images".$recordSet->fields['searchdesc'];
	
	$item->date = strtotime($recordSet->fields['created']);

	$rss->addItem($item);

	$recordSet->MoveNext();
}


print $rss->createFeed($format);

?>