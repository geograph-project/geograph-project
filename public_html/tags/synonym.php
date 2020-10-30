<?php
/**
 * $Project: GeoGraph $
 * $Id: places.php 5786 2009-09-12 10:18:04Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 barry hunter (geo@barryhunter.co.uk)
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

$template = 'tags_synonym.tpl';

if (!empty($_GET['new'])) {
	$template = 'tags_synonym_new.tpl';
}

$USER->mustHavePerm("basic");




if (!empty($_GET['deal'])) {
	$USER->mustHavePerm("admin");

} elseif (!empty($_GET['review'])) {

        $db = GeographDatabaseConnection(false);

	$data = $db->getAll("select o.tag_id,o.prefix,o.tag,o.canonical,
t.tag_id as tag_id2,t.prefix as prefix2,t.tag as tag2
from tag o inner join tag t on (o.canonical = t.tag_id)
inner join gridimage_tag go on (go.tag_id = o.tag_id)
inner join gridimage_tag gt on (gt.tag_id = t.tag_id)
where o.canonical != 0 and o.canonical != o.tag_id
group by t.tag_id
order by t.tag_id");

	$list = array();
	foreach ($data as $idx => $row) {
		$list[$row['canonical']][] = $row['tag_id'];
		$lookup[$row['canonical']] = empty($row['prefix2'])?$row['tag2']:"{$row['prefix2']}:{$row['tag2']}";
		$lookup[$row['tag_id']] = empty($row['prefix'])?$row['tag']:"{$row['prefix']}:{$row['tag']}";
	}
	if (empty($_GET['group'])) {
		foreach ($list as $canonical => $row) {
			print "<b>".htmlentities($lookup[$canonical])."</b>; ";
			foreach ($row as $tag_id) {
				print "<a>".htmlentities($lookup[$tag_id])."</a>; ";
			}
			print "<hr/>";
		}
	} else {
		$prefixes = array();
		foreach ($list as $canonical => $row) {
			$str = $lookup[$canonical];
                        foreach ($row as $tag_id) {
				$str .= "; ".$lookup[$tag_id];
                        }
			$prefix = '';
			if (preg_match('/([\w ]+):/',$str,$m)) {
				$prefix = trim(strtolower($m[1]));
			}
			$prefixes[$prefix][] = $canonical;
                }
		ksort($prefixes);
		foreach ($prefixes as $prefix => $rows) {
			if (empty($prefix) || $prefix == 'category' || $prefix == 'term')
				continue;
			print "<h3>$prefix</h3>";
			foreach ($rows as $canonical) {
				print "".htmlentities($lookup[$canonical])."<br/> ";
			}
		}
	}

	exit;

} else {
	if (!empty($_POST)) {

		$db = GeographDatabaseConnection(false);

		$u = array();
		foreach (array('tag','tag_id','tag2','tag2_id') as $key) {
			if (!empty($_POST[$key])) {
				$u[$key] = trim($_POST[$key]);
			}
		}

		if (!empty($u)) {
			$u['type'] = 'canonical';
			
			$u['user_id'] = $USER->user_id;

			$db->Execute('INSERT INTO tag_report SET created=NOW(),`'.implode('` = ?, `',array_keys($u)).'` = ?',array_values($u));

			$smarty->assign("message",'Suggestion saved at '.date('r'));
		}

	}



	if (empty($db))
		$db = GeographDatabaseConnection(true);

	if (!empty($_GET['tag'])) {
	
		$where = array();
		$where['prefix'] = "prefix = ''";
		
		if (isset($_GET['prefix'])) {
			$where['prefix'] = "prefix = ".$db->Quote($_GET['prefix']);
			$smarty->assign('theprefix', $_GET['prefix']);
			
		} elseif (strpos($_GET['tag'],':') !== FALSE) {
			list($prefix,$_GET['tag']) = explode(':',$_GET['tag'],2);

			$where['prefix'] = "prefix = ".$db->Quote($prefix);
			$smarty->assign('theprefix', $prefix);
		}
		$where['tag'] = "tag = ".$db->Quote($_GET['tag']);
		$smarty->assign('tag',$_GET['tag']);
		
		$row= $db->getRow("SELECT tag_id,prefix,tag,description,canonical FROM tag WHERE status = 1 AND ".implode(' AND ',$where));
		
		if (!empty($row)) {


			if (!empty($row['canonical'])) {
				//.. is part of a canonical set

				//find the definitive tag
				$canonical = $db->getRow("SELECT tag_id,prefix,tag,canonical FROM tag WHERE status = 1 AND tag_id = ".intval($row['canonical']));
				$smarty->assign_by_ref('canonical', $canonical);

				//find the siblings
				$synonyms = $db->getAll("SELECT tag_id,prefix,tag,canonical FROM tag WHERE status = 1 AND canonical = ".intval($row['canonical']));
				$smarty->assign_by_ref('synonyms', $synonyms);

			} else {
				//is not a synonum of another

				//check in case it as any children
				$synonyms = $db->getAll("SELECT tag_id,prefix,tag,canonical FROM tag WHERE status = 1 AND canonical = ".intval($row['tag_id']));
				$smarty->assign_by_ref('synonyms', $synonyms);

				if (!empty($synonyms)) {
					//it is the parent
					$smarty->assign_by_ref('canonical', $row);
					$smarty->assign('found', 1);
				}

			}
		}
	}
}


$smarty->display($template,$cacheid);
