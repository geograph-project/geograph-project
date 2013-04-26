<?php
/**
 * $Project: GeoGraph $
 * $Id: tags.json.php 7882 2013-04-15 13:32:52Z barry $
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

if (!empty($_GET['callback'])) {
	header('Content-type: text/javascript');
} else {
	header('Content-type: application/json');
}

$db = GeographDatabaseConnection(true);

$sql = array();

$sql['tables'] = array();
$sql['tables']['t'] = 'tag';
$sql['wheres'] = array();

if (isset($_GET['term'])) {
	$_REQUEST['q'] = $_GET['q'] = $_GET['term'];
	$sql['columns'] = "if (tag.prefix != '' and not (tag.prefix='term' or tag.prefix='category' or tag.prefix='cluster' or tag.prefix='wiki'),concat(tag.prefix,':',tag.tag),tag.tag) as tag";
	if (empty($_GET['term']) && !empty($CONF['sphinx_host'])) {
		$_REQUEST['q'] = $_GET['q'] = '..'; //falls though as an empty to query, which sphinx now orders by images desc - so gives most popular tags!
	}
} else {
	$sql['columns'] = "tag.tag,if (tag.prefix='term' or tag.prefix='category' or tag.prefix='cluster' or tag.prefix='wiki','',tag.prefix) as prefix";
}

if (!empty($_GET['string'])) {

	if (!empty($CONF['sphinx_host'])) {

                $q = trim(preg_replace('/[^\w@!]+/',' ',str_replace("'",'',$_REQUEST['string'])));

		$sphinx = new sphinxwrapper($q);
		$sphinx->pageSize = $pgsize = 59;
		$pg = (!empty($_REQUEST['page']))?intval(str_replace('/','',$_REQUEST['page'])):0;
		if (empty($pg) || $pg < 1) {$pg = 1;}

		$offset = (($pg -1)* $sphinx->pageSize)+1;

		if ($offset < (1000-$pgsize) ) {
			$client = $sphinx->_getClient();

                        $client->SetSelect('grouping'); //using the grouping directly as the id, allows us to pick the canonical version.

			$sphinx->q = '@tag "'.$sphinx->q.'"/1';

			$client->SetRankingMode(SPH_RANK_EXPR,'if(sum(hit_count)>=tag_wc,10,1)*sum((word_count+(lcs-1)*max_lcs)*user_weight)');
			$client->setFieldWeights(array('tag'=>10));

			$client->SetGroupBy('grouping',SPH_GROUPBY_ATTR,"@relevance DESC, images DESC, @id DESC"); //overall sort order
			$sphinx->sort = "prefered DESC, images DESC"; //within group order

			$ids = $sphinx->returnIds($pg,'tagsstemmed');

//TODO - cacht ERROR 1064 (42000): index tagsstemmed: query too complex, not enough stack (thread_stack_size=89K or higher required)


			$ids = array();
			foreach ($sphinx->res['matches'] as $idx => $row) {
				if ($row['weight']>=100) {
					$ids[] = $row['attrs']['grouping'];
				}
	                }

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
		die("todo");
	}

	customExpiresHeader(3600*24,true);
} else {
	die("todo");
}

$query = sqlBitsToSelect($sql);
if (!empty($_GET['deb']))
        print_r($query);

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
if (isset($_GET['term'])) {

	if (isset($_GET['topics'])) {
	        ###################
        	# Yahoo Term Extraction API

	        $mkey = md5($string);
	        $value =& $memcache->name_get('term',$mkey);

        	if (empty($value)) {
	                $yahoo_appid = "R7drYPbV34FffYJ1XzR0uw2hACglcoZKtAALrgk3xShTg3M04lzPf9spFg_QEZh.xA--";

	                $value = termExtraction($string);

	                $memcache->name_set('term',$mkey,$value,$memcache->compress,$memcache->period_med);
	        }
		$topics = array();
	        if (!empty($value) && !empty($value['ResultSet']) && !empty($value['ResultSet']['Result'])) {
	                foreach ($value['ResultSet']['Result'] as $topic) {
	                        $topics[] = $topic;
	                }
	        }
		$data = array_merge($topics,$db->getCol($query));
	} else {
		$data = $db->getCol($query);
	}
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



