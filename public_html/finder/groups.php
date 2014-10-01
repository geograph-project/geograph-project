<?php
/**
 * $Project: GeoGraph $
 * $Id: contributors.php 6407 2010-03-03 20:44:37Z barry $
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
init_session();

if (isset($_GET['mine'])) {
	$_GET['q'] .= " user:user{$USER->user_id} by:{$USER->realname}";
}

$smarty = new GeographPage;
$template = 'finder_groups.tpl';

$cacheid = md5(serialize($_GET));
$extra = array();

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hr cache


if (!empty($_GET['query']) && !empty($_GET['skip'])) {

	$template = 'finder_groups_inner.tpl';

	if (!$smarty->is_cached($template, $cacheid)) {

		$query = $_GET['query'];
		$idsused = array(intval($_GET['skip']));
		$results = array();
		$row = array('id' => intval($_GET['skip']));


                $prev_fetch_mode = $ADODB_FETCH_MODE;
                $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

                $sph = NewADOConnection($CONF['sphinxql_dsn']) or die("unable to connect to sphinx. ".mysql_error());


                                                $where = "match(".$sph->Quote($query).")";
                                                $rows2 = $sph->getAll($sql = "
                                                select id,realname,user_id,title,grid_reference, in(id,".implode(',',$idsused).") as used
                                                from sample8
                                                where $where
                                                order by used asc, score desc
                                                limit 6");

                                                $d = 1;
                                                foreach ($rows2 as $idx2 => $row2) {
                                                        if ($row2['id'] == $row['id'] || $d==5)
                                                                continue;
                                                        $row2['gridimage_id'] = $row2['id'];
                                                        //$row2['group'] = $row['group']; //we should copy 'images' too, but its only read on the first
                                                        $gridimage = new GridImage;
                                                        $gridimage->fastInit($row2);
                                                        $results[] = $gridimage;
                                                        $d++;
                                                        $idsused[] = $row2['id'];
                                                }

        	$smarty->assign('thumbw',120);
	        $smarty->assign('thumbh',120);
		$smarty->assign_by_ref('results', $results);
	}

	$smarty->display($template,$cacheid);
	exit;
}


if (true) { //actully we can run the code, even in the case of an empty query...

	if (!empty($_GET['q'])) {
		$q=trim($_GET['q']);
	} else {
		$q = '';
		$smarty->cache_lifetime = 3600*24*3; //3 day
	}

	$sphinx = new sphinxwrapper($q);
	if (!empty($sphinx->q))
		$extra[] = "q=".urlencode($sphinx->q);

	$sphinx->pageSize = $pgsize = 100;

	$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
	if (empty($pg) || $pg < 1) {$pg = 1;}

	if (isset($_REQUEST['inner'])) {
		$smarty->assign('inner',1);
		$extra[] = "inner";
	}
	if (!$smarty->is_cached($template, $cacheid)) {


		$groupings = array(
':1' => '',
'context_ids' => 'Geographical Contexts',
'subject_ids' => 'Subject',
'tag_ids' => 'Tags',
'snippet_ids' => 'Shared Descriptions',
'bucket_ids' => 'Buckets',
'group_ids' => 'Automatic Clusters',
'term_ids' => 'Extracted Terms',
'imageclass' => 'Image Category',
'wiki_ids' => 'WikiMedia Categories',
':2' => '',
'myriad' => 'Myriad Square',
'hectad' => 'Hectad Square',
'grid_reference' => 'Grid Square',
':3' => '',
'user_id' => 'Contributor',
':8' => '',
'country' => 'Country',
'county' => 'County',
'place' => 'Placename',
':4' => '',
'decade' => 'Decade Taken',
'takenyear' => 'Year Taken',
'takenmonth' => 'Month Taken',
'takenday' => 'Day Taken',
':5' => '',
'segment' => 'When Submitted',
':6' => '',
'direction' => 'View Direction',
'distance' => 'Subject Distance',
':7' => '',
'format' => 'Image Format',
'status' => 'Moderation Status',
		);

				$segs = array(
					360*86400 => 'Last 360 days',
					180*86400 => 'Last 180 days',
					90*86400 => 'Last 90 days',
					60*86400 => 'Last 60 days',
					30*86400 => 'Last 30 days',
					7*86400 => 'Last 7 days',
					2*86400 => 'Last 2 days',
					86400 => 'Last 1 day',
				);


		$sphinx->processQuery();

		if (preg_match('/@grid_reference \(/',$sphinx->q) && preg_match('/^\w{1,2}\d{4}$/',$sphinx->qclean)) {
			$smarty->assign('gridref',$sphinx->qclean);
		}

		$prev_fetch_mode = $ADODB_FETCH_MODE;
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

		$sph = NewADOConnection($CONF['sphinxql_dsn']) or die("unable to connect to sphinx. ".mysql_error());

		$where = "match(".$sph->Quote($sphinx->q).")";


                //convert gi_stemmed -> sample8 format.
                $where = preg_replace('/@by/','@realname',$where);
                $where = preg_replace('/__TAG__/i','_SEP_',$where);

		$groupn = 5;

		if (!empty($_GET['group']) && preg_match('/^\w+$/',$_GET['group']) && isset($groupings[$_GET['group']])) {
			$group = $_GET['group'];
			$column = ", `$group` as `group`";
			$order = "images desc,`$group` asc";
			if ($group == 'segment') {
				$column =', INTERVAL(submitted, NOW()-'.implode(', NOW()-',array_keys($segs)).') AS segment';
				$names = array_merge(array('Older-Images'),array_values($segs));

				$order = "`$group` desc";
			} elseif (in_array($_GET['group'],array('realname','user_id','title','grid_reference'))) {
				$copyfrom = ($group == 'user_id')?'realname':$group;
				$column ='';
			} elseif (preg_match('/(\w+)_ids/',$_GET['group'],$m)) {
				$extractfrom = $m[1];
				$column = ", {$m[0]}, {$m[1]}s, GROUPBY() as `group`";
				$groupn = ''; //temp, as group N by not worky with MVA.
				//todo, set within group order by!!
			}
		} else {
			$group = "decade";
			$column = ", `$group` as `group`";
			$order = "`$group` desc";
		}
		if (!empty($_GET['sort']) && preg_match('/^\w+$/',$_GET['sort'])) {
			$order = "`{$_GET['sort']}` desc";
		}

		$rows = $sph->getAll($sql = "
			select id,realname,user_id,title,grid_reference, count(*) as images, weight() as w $column
			from sample8
			where $where
			group $groupn by `$group`
			order by $order, score desc, w desc
			limit {$sphinx->pageSize}");

		if (!empty($_GET['p']))
			print "$sql;";

		$idsused = array();

		if (!empty($rows) && count($rows)) {

			$meta = $sph->getAssoc("SHOW META");

			if ($group == 'distance') {
				//do it in php because sphinx cant do natsort on strings
				function cmp($a, $b) {
				    if ($a['group'] == $b['group'] || !is_numeric($a['group']) || !is_numeric($b['group'])) {
				        return strcmp($a['group'],$b['group']);
				    }
				    return (intval($a['group']) < intval($b['group'])) ? -1 : 1;
				}

				usort($rows, "cmp");
			}

			$results = array();
			$groups = array();
			foreach ($rows as $idx => $row) {
				$row['gridimage_id'] = $row['id'];
				if (!empty($copyfrom)) {
					$row['group'] = $row[$copyfrom];
				} elseif ($group == 'segment' && !empty($names)) {
					$row['group'] = $names[$row['segment']];
				} elseif (!empty($extractfrom)) {
					$ids = explode(',',$row[$extractfrom.'_ids']);
					$names = explode('_SEP_',$row[$extractfrom.'s']);array_shift($names); //the first is always blank!
					$row['group'] = trim($names[array_search($row['group'],$ids)]);
				} else
					$row['group'] = preg_replace('/(?<=\d{3})tt/','0s',$row['group']);

				if (!empty($_GET['filter']) && $_GET['filter'] == 'place' && preg_match('/^\s*(place|countr?y):/i',$row['group']))
					continue;

				$groups[$row['group']]=1;
				$gridimage = new GridImage;
                                $gridimage->fastInit($row);
				$results[] = $gridimage;
				$idsused[] = $row['id'];

				####
				if (empty($groupn) && $row['images'] > 1) {
					$query = "({$sphinx->q}) @{$extractfrom}s {$row['group']}";
			                $query = preg_replace('/@by/','@realname',$query);
               				$query = preg_replace('/__TAG__/i','_SEP_',$query);
					if ($idx < 4) {
						$where = "match(".$sph->Quote($query).")";
						$rows2 = $sph->getAll($sql = "
                	        		select id,realname,user_id,title,grid_reference, in(id,".implode(',',$idsused).") as used
                        			from sample8
                        			where $where
                        			order by used asc, score desc
	                        		limit 6");

						$d = 1;
			                        foreach ($rows2 as $idx2 => $row2) {
							if ($row2['id'] == $row['id'] || $d==5)
								continue;
		                	                $row2['gridimage_id'] = $row2['id'];
							$row2['group'] = $row['group']; //we should copy 'images' too, but its only read on the first
		                                	$gridimage = new GridImage;
	                		                $gridimage->fastInit($row2);
        	                        		$results[] = $gridimage;
							$d++;
							$idsused[] = $row2['id'];
						}
					} else {
						$results[count($results)-1]->query = $query;
					}
				}
				####

			}

			$smarty->assign_by_ref('results', $results);
			$query_info = count($rows)." of {$meta['total_found']} ";
			if (empty($groupn)) {
				//one image per group - images loaded on demand.
				$query_info .= "groups";
			} else {
				$query_info .= "images, in ".count($groups);
				if ( count($rows) < $meta['total_found'])
					$query_info .= " of ??";
				$query_info .= " groups, ";
			}
			$query_info .= " found in {$meta['time']} seconds";
			$smarty->assign_by_ref('query_info', $query_info);
		}

		if (preg_match('/\b(\w{1,2}\d{4})\b/',$sphinx->qclean,$m)) {
                        $db = GeographDatabaseConnection(true);
			$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

			$rows = $db->getAll("SELECT label,images FROM gridimage_group_stat WHERE grid_reference = ".$db->Quote($m[1])." AND decades > 2 ORDER BY label DESC"); //todo, add score to stat table, so can sort by it!
			if (!empty($rows)) {
				$queries = array();
				foreach ($rows as $row) {
					$queries["{$m[1]} \"{$row['label']}\""] = "{$row['label']} [{$row['images']}]";
				}
				$smarty->assign_by_ref('queries', $queries);
			}
		}

		$smarty->assign("group",$group);
		$smarty->assign_by_ref("groupings",$groupings);
		$smarty->assign("groupname",$groupings[$group]);
		$smarty->assign("groupn",$groupn);
		$ADODB_FETCH_MODE = $prev_fetch_mode;
	}

	$smarty->assign('thumbw',120);
        $smarty->assign('thumbh',120);
	$smarty->assign("q",$sphinx->qclean);
}


$smarty->display($template,$cacheid);

