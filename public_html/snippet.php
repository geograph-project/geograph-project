<?php
/**
 * $Project: GeoGraph $
 * $Id: snippet.php 9134 2020-08-19 15:34:50Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2009 Barry Hunter (geo@barryhunter.co.uk)
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

if ((strpos($_SERVER["REQUEST_URI"],'/snippet/') === FALSE && isset($_GET['id'])) || strlen($_GET['id']) !== strlen(intval($_GET['id']))) {
	//keep urls nice and clean - esp. for search engines!
	header("HTTP/1.0 301 Moved Permanently");
	header("Status: 301 Moved Permanently");
	header("Location: /snippet/".intval($_GET['id']));
	print "<a href=\"/snippet/".intval($_GET['id'])."\">View shared description page</a>";
	exit;
}


require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

pageMustBeHTTPS();

$template='snippet.tpl';

$snippet_id = intval($_REQUEST['id']);

$cacheid = $snippet_id;

//what style should we use?
$style = $USER->getStyle();

$smarty->assign('maincontentclass', 'content_photo'.$style.'" style="padding:10px');


if (!$smarty->is_cached($template, $cacheid)) {


	$db = GeographDatabaseConnection(true);


	$data = $db->getRow("SELECT s.*,realname FROM snippet s LEFT JOIN user USING (user_id) WHERE snippet_id = $snippet_id AND enabled = 1");

	if (!empty($data['snippet_id'])) {

		$smarty->assign('extra_meta', "<link rel=\"canonical\" href=\"{$CONF['SELF_HOST']}/snippet/{$data['snippet_id']}\"/>");

		$data['images'] = $db->getOne("SELECT COUNT(*) FROM gridimage_snippet gs INNER JOIN gridimage_search USING (gridimage_id) WHERE snippet_id = $snippet_id");

		if ($data['images']) {
			$imagelist = new ImageList();

			$sql = "SELECT gridimage_id,gi.user_id,realname,credit_realname,gi.title,imageclass,grid_reference FROM gridimage_snippet gs INNER JOIN gridimage_search gi USING (gridimage_id) WHERE snippet_id = $snippet_id AND gridimage_id < 4294967296 ORDER BY crc32(concat(gridimage_id,yearweek(now()))) LIMIT 25";

			$imagelist->_getImagesBySql($sql);
			$smarty->assign_by_ref('results', $imagelist->images);

			if ($data['images'] <= 10) {
				$smarty->assign('thumbw',213);
				$smarty->assign('thumbh',160);
			} else {
				$smarty->assign('thumbw',120);
				$smarty->assign('thumbh',120);
			}
		}


		if ($data['nateastings'] && intval($data['natgrlen']) > 4) {
			require_once('geograph/conversions.class.php');
			$conv = new Conversions;

			list($gr,$len) = $conv->national_to_gridref(
				$data['nateastings'],
				$data['natnorthings'],
				max(4,$data['natgrlen']),
				$data['reference_index'],false);

			$data['grid_reference'] = $gr;
		}

                if (empty($CONF['forums']) && !empty($data['comment']) && preg_match('/geograph\.(org\.uk|uk|ie)\/discuss\//',$data['comment'])) {
                        //todo, heavy handled, but editing the description could be tricky!
                        $data['comment'] = '';
                }

		if (!empty($data['comment'])) {
			require_once("smarty/libs/plugins/modifier.truncate.php");
			$smarty->assign('meta_description', smarty_modifier_truncate(preg_replace('/[\s\n]+/',' ',$data['comment']), 255) );

			$rawlen = strlen($data['comment']);

			//we do this here first, rather than in smarty - so we can attach html.
			$data['comment'] = htmlentities2($data['comment']);
			$data['comment'] = GeographLinks($data['comment']);
			$data['comment'] = preg_replace('/(^|[\n\r\s]+)(Keywords?[\s:][^\n\r>]+)$/i','<span class="keywords">$2</span>',$data['comment']);

			// http://en.wikipedia.org/wiki/T.J._Maxx
			if ($rawlen <= 150 && preg_match('/\/(\w+).wikipedia.org\/wiki\/([\w\.,:\(\)-]+)/',$data['comment'],$m)) {

				//https://en.wikipedia.org/w/api.php?format=json&action=query&prop=extracts&exintro=&explaintext=&titles=T.J._Maxx

				ini_set('user_agent', 'Geograph Britain and Ireland - http://www.geograph.org.uk/snippet/'.$data['snippet_id']);

				$rawtext = file_get_contents("https://{$m[1]}.wikipedia.org/w/api.php?format=json&action=query&prop=extracts&exintro=&explaintext=&redirects=1&titles=".urlencode($m[2]));

				if ($extract = json_decode($rawtext,true)) {
					if (count($extract['query']['pages']) == 1) {
						foreach ($extract['query']['pages'] as $key => $value) {
							if (!empty($value['extract'])) {
								$data['comment'] .= "<blockquote><i>".nl2br($value['extract'])."</i><br><small>This extract uses material from the Wikipedia article <a href=\"http://{$m[1]}.wikipedia.org/wiki/{$m[2]}\">\"{$m[2]}\"</a>, which is released under the <a href=\"http://creativecommons.org/licenses/by-sa/3.0/\">Creative Commons Attribution-Share-Alike License 3.0</a>.</small></blockquote>";
							}
						}
					}
				}
			}

		} else {
			$smarty->assign('meta_description', "Shared description for ".$data['title'].', featuring '.$data['images'].' images');
		}

		if ($CONF['sphinx_host'] && $data['grid_reference']) {

			$sphinx = new sphinxwrapper();
			$sphinx->pageSize = $pgsize = 25;
			$pg = 1;

			if ($data['nateastings']) {
				require_once('geograph/conversions.class.php');
				$conv = new Conversions;

				$geodata = array();

				list($geodata['x'],$geodata['y']) = $conv->national_to_internal($data['nateastings'],$data['natnorthings'],$data['reference_index']);

				if ($data['natgrlen'] > 4) {
					list($geodata['lat'],$geodata['long']) = array($data['wgs84_lat'],$data['wgs84_long']);
				}
				$geodata['d'] = 2;
				$geodata['sort'] = "@geodist ASC, @relevance DESC, @id DESC";

				$sphinx->setSort($geodata['sort']);
				$sphinx->setSpatial($geodata);
			} else {
				$sphinx->prepareQuery($data['grid_reference']);
			}
$client = $sphinx->_getClient();
$client->setFilter('images',array(0),true);

			if (empty($_GET['skipp']))
				$ids = $sphinx->returnIds($pg,'snippet');

			if (!empty($ids) && count($ids) > 0) {
				$where = array();

				$id_list = implode(',',$ids);
				$where[] = "s.snippet_id IN($id_list)";
				$orderby = "ORDER BY FIELD(s.snippet_id,$id_list)";

				$where[] = "enabled = 1";
				$where[] = "s.snippet_id != {$data['snippet_id']}";

				$where= implode(' AND ',$where);

				$others = $db->getAll($sql="SELECT snippet_id,title,comment FROM snippet s WHERE $where $orderby");

				$smarty->assign_by_ref('others',$others);

				//we only replace links, if they appears to be in bits not affected by markup
				foreach ($others as $id => $row) {
					if (strlen($row['title']) > 3 && stripos($data['comment'],$row['title']) !== FALSE)
						$data['comment'] = preg_replace("/\b(".preg_quote($row['title'],'/').")\b(?![^<]*>)/i",'<a href="/snippet/'.$row['snippet_id'].'">$1</a>',$data['comment']);
				}

			}
		}
		if (!empty($data['comment']) && $CONF['sphinx_host']) {
			$sphinx = new sphinxwrapper();
			$sphinx->pageSize = $pgsize = 8;
			$pg = 1;

			$crit = '';
			if (strlen($data['comment']) > 100) {

				preg_match_all('/\b([A-Z]\w{3,})\b/',str_replace('Link','',$data['comment']),$m);

				if (count($m[1]) > 3) {
					$words = array_unique($m[1]);

					$quorum = min(2,count($words) -2);

					$crit = ' | (@(title,comment) "'.implode(' ',$words).'"/'.$quorum.')';
				}
			}

			if (!empty($geodata)) {
				$sphinx->setSpatial($geodata); //does not set sort order in itseel
				$sphinx->setSelect("*,if(@geodist < 20000 OR @geodist > 150000,1,0) as f"); //nearby, or non-located
				$sphinx->addFilters(array('f'=>array(1)));
				$cl = $sphinx->_getClient();
				foreach ($cl->_filters as $key => $value) {
					if ($value['attr'] == '@geodist' && $key == 0)
						array_shift($cl->_filters); //use shift, so that it reindexes the keys.
				}
			}

			$sphinx->prepareQuery("(@title {$data['title']}) | (@comment \"{$data['title']}\") ".$crit);
##			$sphinx->setGroupBy('titlecrc',SPH_GROUPBY_ATTR,"@relevance DESC, @id DESC");

$client = $sphinx->_getClient();
$client->setFilter('images',array(0),true);

			$ids = $sphinx->returnIds($pg,'snippet');

			if (!empty($ids) && count($ids) > 0) {
				$where = array();

				$id_list = implode(',',$ids);
				$where[] = "s.snippet_id IN($id_list)";
				$orderby = "ORDER BY FIELD(s.snippet_id,$id_list)";

				$where[] = "enabled = 1";
				$where[] = "s.snippet_id != {$data['snippet_id']}";

				$where= implode(' AND ',$where);

				$related = $db->getAll($sql="SELECT s.snippet_id,title,comment,realname,s.user_id,COUNT(gs.snippet_id) AS images FROM snippet s LEFT JOIN user u USING (user_id) LEFT JOIN gridimage_snippet gs ON (s.snippet_id = gs.snippet_id AND gridimage_id < 4294967296)  WHERE $where  GROUP BY s.snippet_id $orderby"); 

				$smarty->assign_by_ref('related',$related);

				//we only replace links, if they appears to be in bits not affected by markup - should help prevent replaces in what is already links, or titles of images etc
				$nohtml = strip_tags(preg_replace('/<a\s.+?>.*?<\/a>/','', $data['comment']));
				$hassame = 0;
				foreach ($related as $id => $row) {
                                        if (strlen($row['title']) > 3 && stripos($nohtml,$row['title']) !== FALSE)
                                               $data['comment'] = preg_replace("/(?<!!)\b(".preg_quote($row['title'],'/').")\b/",'<a href="/snippet/'.$row['snippet_id'].'">$1</a>',$data['comment']);

					//if (strlen($row['title']) > 3)
					//	//only replace text NOT already inside <a> tag, image thumbs etc, will always be in a <a>
                                        //        $data['comment'] = preg_replace("/(^|\/a>)([^!<\"]*)\b(".preg_quote($row['title'],'/').")\b/m",'$1$2<a href="/snippet/'.$row['snippet_id'].'">$3</a>',$data['comment']);
					if ($row['title'] == $data['title']) {
						$hassame++;
					}
				}
				$data['comment'] = preg_replace("/(^|\s)!([A-Z]\w+)/",'$1$2',$data['comment']);

				$smarty->assign('hassame',$hassame);
			}
		}

		if (!empty($data['comment']))
			$data['comment'] = nl2br($data['comment']);

		$smarty->assign($data);
		$t2 = ($data['grid_reference'])?" in {$data['grid_reference']}":'';
		if ($data['images']) {
			$smarty->assign('page_title',"{$data['title']} [{$data['images']} photos]$t2");
		} else {
			$smarty->assign('page_title',$data['title'].$t2);
		}

		if (empty($data['images']) || empty($imagelist->images) ) {
			//for bots, send a status to prevent page being indexed.
			header("HTTP/1.0 404 Not Found");
			header("Status: 404 Not Found");
		}

	} else {
		header("HTTP/1.0 404 Not Found");
		header("Status: 404 Not Found");

		$template = 'static_404.tpl';
	}
} else {
	$db = GeographDatabaseConnection(true);
	$images = $db->getOne("select images from content where foreign_id = $snippet_id AND source = 'snippet'"); //this table only has a row for snippets WITH images!
	if (!$images) {
	        header("HTTP/1.0 404 Not Found");
                header("Status: 404 Not Found");
	}

	$smarty->assign('snippet_id',$snippet_id);
}


$smarty->display($template, $cacheid);


