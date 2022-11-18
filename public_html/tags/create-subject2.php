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

	$recordSet = $db->Execute("select tag_id,t.tag,t.user_id,t.created,sum(gt.status = 2) as images from tag t left join subjects s on (t.tag = s.subject) left join tag_report using (tag_id) left join gridimage_tag gt using (tag_id) where t.prefix = 'subject' and t.status = 1 and s.subject is NULL and report_id IS NULL GROUP BY tag_id LIMIT 50");


ini_set("display_errors",1);

	$row = $recordSet->fields;
	$idx = 1;

		print "<script src=\"".smarty_modifier_revision("/sorttable.js")."\"></script>";

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
                        print "<TD ALIGN=$align>".htmlentities($value)."</TD>";
                }

		print "<td><select>";
		print "<option></option>";

		$q = $sph->Quote('"'.$row['tag'].'"');
		$list = $sph->getAll("SELECT subjects,count(*) AS cnt from sample8 where match($q) group by subject_ids order by cnt desc");
		foreach ($list as $r) {
			$v = trim(str_replace('_SEP_','',$r['subjects']));
			if (strtolower($v) == strtolower(trim($row['tag'])))
				continue;
			printf('<option value=\"%s\"%s>subject:%s</option>',$v = htmlentities($v), '', $v);
		}
		print "</td>";
		print "<td><input type=checkbox checked>Add unprefixed '".htmlentities($row['tag'])."' as tag";


                print "</TR>";
		$recordSet->MoveNext();
	}
        print "</TR></TBODY></TABLE>";

	$idx++;


$smarty->display('_std_end.tpl');
