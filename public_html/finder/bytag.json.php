<?php
/**
 * $Project: GeoGraph $
 * $Id: clusters.php 5786 2009-09-12 10:18:04Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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

customExpiresHeader(3600);


$db = GeographDatabaseConnection(true);

$sql = array();

$sql['tables'] = array();
$sql['tables']['t'] = 'tag';

$sql['columns'] = "tag_id,tag.tag,if (tag.prefix='term' or tag.prefix='cluster' or tag.prefix='wiki','',tag.prefix) as prefix";

$sql['wheres'] = array();

if (!empty($_GET['q'])) {
	$q=trim($_GET['q']);

	$sphinx = new sphinxwrapper($q);
	$sphinx->pageSize = $pgsize = 60;

	
	$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
	if (empty($pg) || $pg < 1) {$pg = 1;}
	

	
	$sphinx->processQuery();


	$sphinx->sort = "@weight DESC, @id ASC"; //this is the WITHIN GROUP ordering 

	$client = $sphinx->_getClient();
	$client->SetArrayResult(true);

	$sphinx->SetGroupBy('all_tag_id', SPH_GROUPBY_ATTR, '@count DESC');
	$res = $sphinx->groupByQuery($pg,'tagsoup');

	$tagids = array();
	if (!empty($res['matches'])) {
		foreach ($res['matches'] as $idx => $row) {
			$tagids[] = $row['attrs']['@groupby'];
			$count[$row['attrs']['@groupby']] = $row['attrs']['@count'];
		}
	
		if (!empty($tagids)) {
			$idstr = join(",",$tagids);
			$sql['wheres'][] = "tag_id IN($idstr)";
			$sql['order'] = "FIELD(`tag_id`,$idstr)";
			$sql['limit'] = count($tagids);
		} else {
			$sql['wheres'] = array(0);
		}
	} else {
		$sql['wheres'] = array(0);
	}

} else {
	die('todo');
	//popular tags?
}

$query = sqlBitsToSelect($sql);

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
if (!empty($_GET['term'])) {
	$data = $db->getCol($query);
} else {
	$data = $db->getAll($query);

	foreach ($data as $idx => $row) {
		$data[$idx]['count'] = $count[$row['tag_id']];
		unset($data[$idx]['tag_id']);
	}
}

if (!empty($_GET['callback'])) {
        $callback = preg_replace('/[^\w\.-]+/','',$_GET['callback']);
        echo "{$callback}(";
}

require_once '3rdparty/JSON.php';
$json = new Services_JSON();
print $json->encode($data);

if (!empty($_GET['callback'])) {
        echo ");";
}



