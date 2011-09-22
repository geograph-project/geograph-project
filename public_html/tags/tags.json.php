<?php
/**
 * $Project: GeoGraph $
 * $Id$
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

if (!empty($_GET['term'])) {
	$_GET['q'] = $_GET['term'];	
}

$db = GeographDatabaseConnection(true);

$sql = array();

$sql['tables'] = array();
$sql['tables']['t'] = 'tag';

if (!empty($_GET['term'])) {
	$_GET['q'] = $_GET['term'];	
	$sql['columns'] = "if (tag.prefix and not (tag.prefix='term' or tag.prefix='cluster' or tag.prefix='wiki'),concat(tag.prefix,':',tag.tag),tag.tag) as tag";
} else {
	$sql['columns'] = "tag.tag,if (tag.prefix='term' or tag.prefix='cluster' or tag.prefix='wiki','',tag.prefix) as prefix";
}

if (!empty($_GET['gridimage_id'])) {
	init_session();
	if (!$USER->registered) {
		die("{error: 'not logged in'}");
	}

	$sql['tables']['gt'] = 'INNER JOIN gridimage_tag gt USING (tag_id)';

	$sql['wheres'] = array();
	$sql['wheres'][] = "tag.status = 1";
	$sql['wheres'][] = "gt.user_id = ".$USER->user_id;
	$sql['wheres'][] = "gt.gridimage_id = ".intval($_GET['gridimage_id']);

	$sql['order'] = 'gt.created';

} elseif (!empty($_GET['q'])) {
	customExpiresHeader(3600);

	if (!empty($CONF['sphinx_host'])) {
		if (strpos($_REQUEST['q'],':') !== FALSE) {
			list($prefix,$_REQUEST['q']) = explode(':',$_REQUEST['q'],2);
		}
		
                $q = trim(preg_replace('/[^\w]+/',' ',str_replace("'",'',$_REQUEST['q'])));
		
		$sphinx = new sphinxwrapper($q);
		
		$sphinx->pageSize = $pgsize = 60; 
		
		$pg = (!empty($_REQUEST['page']))?intval(str_replace('/','',$_REQUEST['page'])):0;
		if (empty($pg) || $pg < 1) {$pg = 1;}
		
		$offset = (($pg -1)* $sphinx->pageSize)+1;
	
		if ($offset < (1000-$pgsize) ) { 
			$client = $sphinx->_getClient();

                        $client->SetRankingMode(SPH_RANK_SPH04);
			
			$sphinx->sort = "prefered DESC"; //within group order
			$client->SetGroupBy('grouping',SPH_GROUPBY_ATTR,"@relevance DESC, @id DESC"); //overall sort order
			
			if ($sphinx->q) {
				$sphinx->q = "\"^{$sphinx->q}$\" | (^$sphinx->q) | ($sphinx->q) | @tag (^$sphinx->q) | @tag \"^{$sphinx->q}$\"";
				if (!empty($prefix)) {
					$sphinx->q = "({$sphinx->q}) @prefix $prefix";
				}
			} elseif (!empty($prefix)) {
				$sphinx->q = "\"^{$prefix}$\" | (^$prefix) | ($prefix) | @tag (^$prefix) | @tag \"^{$prefix}$\"";
			}
	
			$ids = $sphinx->returnIds($pg,'tags');
			
			if (!empty($ids) && count($ids)) {
				$idstr = join(",",$ids);
				$where = "tag_id IN(".join(",",$ids).")";
	
				$sql['wheres'] = array("`tag_id` IN ($idstr)");
				$sql['order'] = "FIELD(`tag_id`,$idstr)";
				$sql['limit'] = count($ids);
			} else {
				$sql['wheres'] = array(0);
			}
		} else {
			$sql['wheres'] = array(0);
		}
	} else {
		$sql['tables']['gt'] = 'INNER JOIN gridimage_tag gt USING (tag_id)';

		$sql['wheres'] = array("`tag` LIKE ".$db->Quote($_GET['q'].'%'));
		$sql['wheres'][] = "t.status = 1";
		$sql['wheres'][] = "gt.status = 2";

		$sql['group'] = 'tag_id';

		$sql['order'] = '`tag`';

		$sql['limit'] = 100;
	}
	//todo sort by popularity?
} else {
	die("todo");
	
        $sql['tables']['gt'] = "INNER JOIN `gridimage_tag` USING (tag_id)";

        $sql['wheres'] = array('status = 1');

        if (!empty($_GET['user_id'])) {
                $sql['wheres'][] = "t user_id = ".intval($_GET['user_id']);
        }
        if (!empty($_GET['tag'])) {
                if ($row2 = getRow("SELECT tag_id FROM `tag` WHERE `tag` = ".dbQuote($_GET['tag']))) {
                        $sql['tables'][] = "INNER JOIN `link2tag` l2 USING (link_id)";
                        $sql['wheres'][] = "l2.tag_id = ".$row2['tag_id'];
                }
        }

        $sql['group'] = 'tag.tag_id';
}

$query = sqlBitsToSelect($sql);

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
if (!empty($_GET['term'])) {
	$data = $db->getCol($query);
} else {
	$data = $db->getAll($query);
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



