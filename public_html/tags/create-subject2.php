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

$USER->mustHavePerm("basic");

	$USER->mustHavePerm("tagsmod");


$smarty->display('_std_begin.tpl');

$db = GeographDatabaseConnection(false);
$sph = GeographSphinxConnection('sphinxql',true);

###################################

if (!empty($_POST['select'])) {
	foreach ($_POST['select'] as $tag_id => $value) {
		if (empty($value)) //only process ones, with something selected in dropdown
			continue;

		$type = 'offical';
		if ($value != 'NONE' && !empty($_POST['checked'][$tag_id]))
			$type = 'split';

                $u = $db->getRow("SELECT tag_id,prefix,tag FROM tag WHERE tag_id = ".intval($tag_id));
                $u['user_id'] = $USER->user_id;
                if (!empty($u['prefix']))
                        $u['tag'] = $u['prefix'].":".$u['tag'];
                unset($u['prefix']);
                $u['type'] = $type;
                $u['status'] = 'approved';

		if ($value != 'NONE') {
                        $u['tag2'] = trim($value); //already has the subject prefix
			//print_r($u); print "<hr>";
			$db->Execute('INSERT INTO tag_report SET created=NOW(),`'.implode('` = ?, `',array_keys($u)).'` = ?',array_values($u));
		}

		if (!empty($_POST['checked'][$tag_id])) {
                        $u['tag2'] = trim($_POST['checked'][$tag_id]); //deliberately unprefixed
			//print_r($u); print "<hr>";
			$db->Execute('INSERT INTO tag_report SET created=NOW(),`'.implode('` = ?, `',array_keys($u)).'` = ?',array_values($u));
		}
	}
}

###################################

	$recordSet = $db->Execute("select tag_id,t.tag,t.user_id,t.created,sum(gt.status = 2) as images from tag t left join subjects s on (t.tag = s.subject) left join tag_report using (tag_id) left join gridimage_tag gt using (tag_id) where t.prefix = 'subject' and t.status = 1 and s.subject is NULL and report_id IS NULL GROUP BY tag_id LIMIT 50");

	$row = $recordSet->fields;
	$idx = 1;

		print "<script src=\"".smarty_modifier_revision("/sorttable.js")."\"></script>";

	print "<form method=post>";
        print "<TABLE border='1' cellspacing='0' cellpadding='2' class=\"report sortable\" id=\"list$idx\"><THEAD><TR>";
        foreach ($row as $key => $value) {
                print "<TH>$key</TH>";
        }
	print "<th>Convert to";
	print "<th>unprefix";
        print "</TR></THEAD><TBODY>";
        $keys = array_keys($row);
        $first = $keys[0];
	while (!$recordSet->EOF) {
		$row = $recordSet->fields;
                print "<TR>";
                $align = "left";
                foreach ($row as $key => $value) {
                        $align = is_numeric($value)?"right":'left';
			if ($key == 'tag') {
	                        print "<TD><a href=\"/tagged/subject:".urlencode2($value)."\">".htmlentities($value)."</a></TD>";
			} else
	                        print "<TD ALIGN=$align>".htmlentities($value)."</TD>";
                }

		print "<td><select name=\"select[{$row['tag_id']}]\" style=max-width:120px>";
		print "<option></option>";

		$q = $sph->Quote('"'.$row['tag'].'"');
		$list = $sph->getAll("SELECT subjects,count(*) AS cnt from sample8 where match($q) group by subject_ids order by cnt desc");
		foreach ($list as $r) {
			foreach (explode('_SEP_', $r['subjects']) as $tag) {
				$v = trim($tag);
				if (empty($v) || strtolower($v) == strtolower(trim($row['tag'])))
					continue;
				printf('<option value="%s"%s>%s</option>',$v = htmlentities("subject:$v"), '', $v);
			}
		}
		print "<option value=\"NONE\">NONE - delete the subject tag, but still create unprefixed</option>";
		print "</select>";
		print "</td>";
		print "<td><input type=checkbox name=\"checked[{$row['tag_id']}]\" value=\"".htmlentities($row['tag'])."\" checked>Add unprefixed '".htmlentities($row['tag'])."' as tag";


                print "</TR>";
		$recordSet->MoveNext();
	}
        print "</TR></TBODY></TABLE>";
	print "<input type=submit>";
	print "</form>";

	$idx++;


$smarty->display('_std_end.tpl');
