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

$template = 'tags_report.tpl';
$cacheid = '';

$USER->mustHavePerm("basic");

if (!empty($_GET['review'])) {
	$_GET['report'] = 'review';
}


if (!empty($_GET['finder'])) {
	 $template = 'tags_report_finder.tpl';

} elseif (!empty($_GET['lookup'])) {

	$db = GeographDatabaseConnection(false);
        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	$data = $db->getAll("SELECT * FROM tag_report WHERE tag_id = ".intval($_GET['tag_id'])." AND tag2 != '' AND status != 'rejected' AND type != 'canonical' ORDER BY tag2");

	outputJSON($data);
	exit;

} elseif (!empty($_GET['approver'])) {
	$USER->mustHavePerm("tagsmod");

	$template = 'tags_report_approver.tpl';

        $db = GeographDatabaseConnection(false);
        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	if (!empty($_POST['report_id'])) {
		$report_id = intval($_POST['report_id']);
		if (!empty($_POST['skip'])) {
			$db->Execute("INSERT INTO tag_report_skip SET report_id = $report_id, user_id = {$USER->user_id}, created = NOW()");
		} elseif (!empty($_POST['approve'])) {
			$db->Execute("UPDATE tag_report SET status = 'approved',approver_id = {$USER->user_id} WHERE report_id = $report_id");
		} elseif (!empty($_POST['reject'])) {
			$db->Execute("UPDATE tag_report SET status = 'rejected',approver_id = {$USER->user_id} WHERE report_id = $report_id");


			//if the tag is already empty, might as well deactivate it. (will automatically be activated if someone uses it again!)
			$row = $db->getOne("SELECT tag_id,tag2_id FROM tag_report WHERE report_id = $report_id");
			if (!empty($row['tag_id'])) {
				if (!$db->getOne("SELECT tag_id FROM gridimage_tag WHERE tag_id = {$row['tag_id']} LIMIT 1")) {
					if (empty($row['tag2_id'])) {
						$db->Execute("UPDATE tag SET status=0 WHERE tag_id = {$row['tag_id']}");
					} else {
						$db->Execute("UPDATE tag SET status=0,canonical={$row['tag2_id']} WHERE tag_id = {$row['tag_id']}");
					}
				}
			}
		}
	}
	$status = (empty($_GET['status']) || !ctype_alpha($_GET['status']))?'new':$_GET['status'];
	$report = $db->getRow("SELECT r.*,u.realname FROM tag_report r
		INNER JOIN tag t USING (tag_id)
		INNER JOIN user u ON (u.user_id = r.user_id)
		LEFT JOIN tag_report_skip s ON (s.report_id = r.report_id AND s.user_id = {$USER->user_id})
		WHERE r.user_id != {$USER->user_id}
		AND s.user_id IS NULL
		AND r.status = '$status'
		AND t.status = 1
		AND r.type != 'canonical'
		ORDER BY r.report_id
		LIMIT 1");

	if ($report['type'] == 'split') {
		$report['tag2'] = ''; //the old style suggestions wont work anymore, so let the admin create a new one!
	}

	$report['levenshtein'] = levenshtein($report['tag'],$report['tag2']);

	$best = 99; $top = null; $none = preg_replace('/^\w+:\s*/','',$report['tag']);
	foreach ($db->getCol("SELECT top FROM category_primary") as $test) {
		$guess = levenshtein($none,$test);
		if ($guess < $best) {
			$best = $guess;
			$top = $test;
		}
	}
	if ($best < strlen($none))
		$smarty->assign('top',$top);

	$best = 99; $subject = null;
	foreach ($db->getCol("SELECT subject FROM subjects") as $test) {
		$guess = levenshtein($none,$test);
		if ($guess < $best) {
			$best = $guess;
			$subject = $test;
		}
	}
	if ($best < strlen($none))
		$smarty->assign('subject',$subject);

	$smarty->assign_by_ref('report',$report);


} elseif (!empty($_GET['report'])) {

	$smarty->display('_std_begin.tpl');

        $db = GeographDatabaseConnection(false);

	if (!empty($_GET['stop'])) {
		$USER->mustHavePerm("tagsmod");

		$db->Execute("UPDATE tag_report SET status = 'new' WHERE report_id = ".intval($_GET['stop']));
	}

	if ($_GET['report'] == 'mine') {
		$USER->mustHavePerm("basic");

	        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
        	$reports = $db->getAll("select gridimage_id,title,r.tag as old,tag2 as new
		from tag_report r inner join gridimage_tag gt using (tag_id) inner join gridimage_search gi using (gridimage_id)
		where r.status in ('approved','moved') and type != 'canonical' and gi.user_id = {$USER->user_id}");


	} elseif ($_GET['report'] == 'subjects') {

		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
                $reports = $db->getAll(" select report_id,tag,tag2,type,user_id,created,updated,status,approver_id from tag_report where tag like 'subject:%' order by tag,status+0 desc");

		print "<b>new</b> - suggestion made but not approved yet<br>";
		print "<b>approved</b> - suggestion approved but not yet actioned<br>";
		print "<b>moved</b> - the images have been moved from one tag to other (and new images will continue to be moved!)<br>";
		print "<b>renamed</b> - the actual tag has been edited to rename it (doesn't stop the same tag being recreated later)<br>";
		print "<b>rejected</b> - the suggestion has been actively rejected and will have no effect<br>";


	} elseif ($_GET['report'] == 'loops') {

		$reports=$db->getAll("select one.tag_id,one.canonical,one.prefix,one.tag,one.status, two.tag_id,two.canonical,two.prefix,two.tag,two.status
		 from tag one inner join tag two on (two.tag_id = one.canonical and one.tag_id != one.canonical) where two.canonical = one.tag_id");


		/*
		select one.tag_id,one.tag,one.status, two.tag_id,two.tag,two.status
		 from tag_report one inner join tag_report two on (two.tag_id = one.tag2_id and one.tag_id != one.tag2_id)
		 where two.tag2_id = one.tag_id and one.status in ('approved','moved','renamed') and two.status in ('approved','moved','renamed')
		*/

	} elseif ($_GET['report'] == 'disabled') {

                $reports=$db->getAll("select one.tag_id,one.canonical,one.prefix,one.tag,one.status, two.tag_id,two.canonical,two.prefix,two.tag,two.status
                 from tag one inner join tag two on (two.tag_id = one.canonical and one.tag_id != one.canonical) where one.status = 1 and two.status = 0");


	} elseif ($_GET['report'] == 'duplicates') {
		$USER->mustHavePerm("basic");

		print "<h3>These are duplicates. Can't be processed until duplicates removed</h3>";

		$ids = $db->getCol("select tag_id,count(distinct tag2) as dist from
			 tag_report where status in ('approved','moved') and type != 'split' and type != 'canonical' group by tag having dist > 1");
		if (empty($ids))
			die("none! Good.\n");

		$ids = implode(",",$ids);

	        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
        	$reports = $db->getAll("select report_id,r.updated,r.tag as old,tag2 as new,count(*) as images,r.status
		from tag_report r inner join gridimage_tag gt using (tag_id) inner join gridimage_search gi using (gridimage_id)
		where r.status in ('approved','moved') and type != 'canonical' and tag_id in ($ids)
		group by tag_id,tag2");


	} elseif ($_GET['report'] == 'outstanding') {
		$USER->mustHavePerm("basic");
	        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$check = $db->getAll("select tag_id,tag,group_concat(tag2),group_concat(status),count(distinct tag2) as dist from
 tag_report where status != 'rejected' and type != 'split' and type != 'canonical' group by tag having dist > 1");

if (!empty($check)) {
 $con = print_r($check,TRUE);
 print "<h3>FAILED MULTI CHECK</h3>";
 print "<p>The following tags have multiple suggestions, processing is halted until resolved</p>";
 print "<pre>$con</pre>";
}


$check = $db->getAll("select report_id,tag_id,tag,tag2_id,tag2 from
 tag_report where status in ('approved','moved') and (tag_id = 0)"); //we dont check tag2 here, as that might be zero as the new tag is to be craeted!

if (!empty($check)) {
 $con = print_r($check,TRUE);
 print "<h3>FAILED ID CHECK</h3>";
 print "<p>The following reports where not linked to tags, processing is halted until resolved</p>";
 print "<pre>$con</pre>";
}

		print "<h3>The following changes will soon be made automatically. You do not need to make any changes yourself (it's best that you dont)</h3>";

        	$reports = $db->getAll("select report_id,r.updated,r.tag as old,tag2 as new,count(*) as images,r.status
		from tag_report r inner join gridimage_tag gt using (tag_id) inner join gridimage_search gi using (gridimage_id)
		where r.status in ('approved','moved') and type != 'canonical' and tag_id > 0 and gt.status > 0
		group by tag_id,tag2");


        } elseif ($_GET['report'] == 'new') {
                $USER->mustHavePerm("tagsmod");

	        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		$reports = $db->getAll("select r.created,r.tag,tag2,type,r.user_id,count as images
		from tag_report r inner join tag t using (tag_id) left join tag_stat s using (tag_id)
		where r.status='new' AND t.status = 1 AND type != 'canonical'");


	} else if ($_GET['report'] == 'split') {
		$USER->mustHavePerm("basic");

	        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		$reports = $db->getAll("SELECT COUNT(DISTINCT report_id) as reports,tag,GROUP_CONCAT(tag2 SEPARATOR ', ') AS tag2s,r.status,r.user_id,approver_id,
			COUNT(DISTINCT gi.gridimage_id) as images,count(distinct gt.user_id) as users
		FROM tag_report r LEFT JOIN gridimage_tag gt USING (tag_id) LEFT JOIN gridimage_search gi USING (gridimage_id)
		WHERE r.status IN ('approved','moved') AND type = 'split'
		group by tag_id ORDER BY tag_id");


	} else if ($_GET['report'] == 'review') {
		$USER->mustHavePerm("tagsmod");

	        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		$reports = $db->getAll("SELECT report_id,tag_id,tag2_id,tag,tag2,r.type,r.status,r.user_id,approver_id,COUNT(gi.gridimage_id) as images,count(distinct gt.user_id) as users
		FROM tag_report r LEFT JOIN gridimage_tag gt USING (tag_id) LEFT JOIN gridimage_search gi USING (gridimage_id)
		WHERE r.status IN ('approved','moved') AND type != 'split' AND type != 'canonical'
		group by tag_id,tag2 ORDER BY tag_id");

		foreach ($reports as $idx => $report) {
			$reports[$idx]['levenshtein'] = levenshtein($report['tag'],$report['tag2']);
		}


	} else if ($_GET['report'] == 'reject') {
		$USER->mustHavePerm("tagsmod");

	        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		$reports = $db->getAll("SELECT report_id,tag_id,tag2_id,tag,tag2,r.type,r.user_id,approver_id,COUNT(gridimage_id) as images,count(distinct gt.user_id) as users
		FROM tag_report r LEFT JOIN gridimage_tag gt USING (tag_id)
		WHERE r.status IN ('rejected') AND type != 'canonical'
		group by tag_id ORDER BY tag_id");

		foreach ($reports as $idx => $report) {
			$reports[$idx]['levenshtein'] = levenshtein($report['tag'],$report['tag2']);
		}


	} elseif ($_GET['report'] == 'list') {
		$USER->mustHavePerm("tagsmod");

		$limit = empty($_GET['limit'])?30:intval($_GET['limit']);
		$status = (empty($_GET['status']) || !ctype_alpha($_GET['status'])?'rejected':$_GET['status']);

		$ADODB_FETCH_MODE = ADODB_FETCH_NUM;
		$reports = $db->getAll($sql = "select r.*,t.*
		from tag_report r left join tag_report t on (t.tag_id = r.tag_id and t.report_id != r.report_id)
		where r.status = '$status' order by r.report_id desc limit $limit");


        } elseif ($_GET['report'] == 'potential2') {

		if (!empty($_GET['r'])) {
			foreach(explode("\n",trim("
update tag_suggest set score = 0;
update tag_suggest inner join tag using (tag_id) set score=score+80 where tag.tag = replace(replace(tag_suggest.tag,'&amp;','&'),'&#39;',\"'\");
update tag_suggest inner join tag using (tag_id) set score=score+1 where replace(replace(replace(replace(tag.tag,' ',''),'-',''),'&amp;','&'),'&#39;',\"'\") = replace(replace(replace(replace(tag_suggest.tag,' ',''),'-',''),'&amp;','&'),'&#39;',\"'\");
update tag_suggest inner join tag using (tag_id) set score=score+1 where tag_suggest.tag = concat(tag.tag,'s');
update tag_suggest inner join tag using (tag_id) set score=score+1 where tag_suggest.tag like concat(tag.tag,'%');
update tag_suggest inner join tag using (tag_id) set score=score+1 where tag_suggest.others like concat(tag.tag,'%');
update tag_suggest inner join tag using (tag_id) set score=score+1 where tag_suggest.others like concat('%',tag.tag,'%');
update tag_suggest set score=score+1 where tag != '' && tag != substring_index(others,'|',1);
update tag_suggest set score=score+1 where tag != '';
			")) as $sql) {
				$db->Execute(trim($sql,'; '));
			}
		}


		@$s = intval($_GET['s']);
		@$g = intval($_GET['g']);
		$size = 10000;

		print "<form target=_self><input type=hidden name=report value=potential2 >";
		$data = $db->getAll("SELECT tag_id DIV $size AS g,COUNT(*) as tags FROM tag_suggest WHERE score = $s GROUP BY tag_id DIV $size");
		print "Set:<select name=g onchange=\"this.form.submit()\">";
		foreach ($data as $row) {
			printf("<option value=\"%d\"%s>%d [%d tags]</option>",$row['g'],$row['g']==$g?' selected':'',$row['g'],$row['tags']);
		}
		print "</select> ";
		$data = $db->getAll("SELECT score as s,COUNT(*) as tags FROM tag_suggest WHERE tag_id DIV $size = $g AND score < 50 GROUP BY score");
		print "Level:<select name=s onchange=\"this.form.submit()\">";
		foreach ($data as $row) {
			printf("<option value=\"%d\"%s>%d [%d tags]</option>",$row['s'],$row['s']==$s?' selected':'',$row['s'],$row['tags']);
		}
		print "</select> ";
		print "</form>";

                $reports = $db->getAll($sql = "select tag_id,prefix,t.tag,s.tag as suggest,others from tag t inner join tag_suggest s using (tag_id)
                                inner join tag_stat using (tag_id)
                                left join tag_report r using (tag_id)
                        where tag_id DIV $size = $g AND score = $s
			and t.tag not like '%)'
			and t.status = 1 and r.tag_id is null and `count` > 0
			and prefix not in ('wiki','top')
			order by t.tag,prefix
                        limit 5000");

                print "<base target=_blank>";
                print "<ol>";
                @$l = intval($_GET['l']); //specify &l=1 to get reports what has the supplied tag 'somewhere' in the suggestions, suggests its ok, just not as popular as suggestion
                @$c = intval($_GET['c']); //specify &c=1 to get reports that only differ in Special Chars
                foreach ($reports as $row) {
                        print "<li><a href=\"?admin=1#t=".rawurlencode($row['prefix']?"{$row['prefix']}:{$row['tag']}":$row['tag'])."\"";
                        print ">".htmlentities($row['tag'])."</a>";
                        print " &gt; ".str_replace('&#39;','',$row['suggest'])."</li>";
                }
                print "</ol>";

		$smarty->display('_std_end.tpl');

                exit;



        } elseif ($_GET['report'] == 'potential') {

		$reports = $db->getAll($sql = "select tag_id,t.tag,s.tag as suggest,others from tag t inner join tag_suggest s using (tag_id)
				inner join tag_stat using (tag_id)
				left join tag_report r using (tag_id)
			where t.tag != s.tag and s.tag != CONCAT(t.tag,'s')
			and t.tag not like '%)' and s.others not like concat(t.tag,' %')
			and t.status = 1 and r.tag_id is null and status2 > 0
			 limit 1000");

		print "<base target=_blank>";
		print "<ol>";
		@$l = intval($_GET['l']); //specify &l=1 to get reports what has the supplied tag 'somewhere' in the suggestions, suggests its ok, just not as popular as suggestion
		@$c = intval($_GET['c']); //specify &c=1 to get reports that only differ in Special Chars
		foreach ($reports as $row) {
			if (preg_match('/^'.preg_quote(str_replace('&','&amp;',$row['tag']),'/').'s?\b/i',$row['others'])) {
				continue;
			} elseif ($c xor strtolower(preg_replace('/[^\w ]+/','',$row['tag'])) == preg_replace('/[^\w ]+/','',$row['suggest'])) {
				continue;
			} elseif ($l xor preg_match('/\b'.preg_quote($row['tag'],'/').'\b/i',$row['others'])) {
				continue;
			}
			print "<li><a href=\"?admin=1#t=".rawurlencode($row['tag'])."\"";
			#if (preg_match('/\b'.preg_quote($row['tag'],'/').'\b/i',$row['others'])) {
			#	print " style=\"color:gray\"";
			#}
			if (preg_match('/^'.preg_quote($row['tag'],'/').'/i',$row['others'])) {
				print " style=\"color:gray\"";
			}
			print ">".htmlentities($row['tag'])."</a>";
			print " &gt; ".str_replace('&#39;','',$row['suggest'])."</li>";
		}
		print "</ol>";
		print $db->getOne("select count(*) from tag_suggest"). " of ";
		print $db->getOne("select count(*) from tag where status = 1")." tags processed so far";

		$smarty->display('_std_end.tpl');

		exit;

	} else {
		die(":(");
	}

	if (empty($reports)) {
		die("nothing to show. Hopefully that is a good thing!");
	}

?>

<script src="<? echo smarty_modifier_revision("/sorttable.js"); ?>"></script>
<p>Click a column header to resort the table</p>
<TABLE class="report sortable" id="photolist" cellspacing=0 cellpadding=3 border=1>
<?

        print "<thead><tr><th>".implode('</th><th>',array_keys($reports[0])).'</th>';
        if (!empty($reports[0]['report_id'])) {
                print '<th>Stop</td>';
        }
        print '</tr></htead>';

        print "<tbody>";
        foreach ($reports as $report) {
                print "<tr><td>".implode('</td><td>',$report).'</td>';
		if (!empty($report['report_id']) && ($report['status'] == 'approved' || $report['status'] == 'moved')) {
			print "<td><a href=\"?report={$_GET['report']}&stop={$report['report_id']}\">Stop</a></td>";
		}
		print '</tr>';
        }
        print "</tbody></table>";


	$smarty->display('_std_end.tpl');

        exit;


} elseif (!empty($_GET['deal'])) {
	$USER->mustHavePerm("admin");

	$template = 'tags_report_deal.tpl';

	if (!empty($_POST)) {

		die("Currently disabled. Suggestions are now processed by an automated system. (see scripts/process-tag-typos.php)");
	}

	if (empty($db))
		$db = GeographDatabaseConnection(true);


	//TODO - add AND r.user_id != $USER->user_id
	$reports = $db->getAll("SELECT r.*, 
					COUNT(DISTINCT gridimage_id) AS images,
					t2.tag_id AS tag2_id,
					t2.canonical
				FROM tag_report r 
					INNER JOIN tag t1 USING (tag_id) 
					LEFT JOIN gridimage_tag gt USING (tag_id)
					LEFT JOIN tag t2 ON ( t2.tag = SUBSTRING_INDEX(r.tag2,':',-1) AND t2.prefix = IF(r.tag2 LIKE '%:%',SUBSTRING_INDEX(r.tag2,':',1),'') )
				WHERE r.status = 'new' 
				GROUP BY tag_id,tag2");

	$smarty->assign_by_ref('reports',$reports);


} else {

	if (!empty($_POST)) {

		$db = GeographDatabaseConnection(false);

		$u = array();
		foreach (array('tag','tag_id','tag2','tag2_id','type') as $key) {
			if (!empty($_POST[$key])) {
				$u[$key] = trim($_POST[$key]);
			}
		}

		if (!empty($u)) {
			$u['tag2'] = str_replace('\\','',$u['tag2']);
			$u['tag2'] = str_replace("'",'',$u['tag2']);
			$u['user_id'] = $USER->user_id;

			if (!empty($_GET['admin']) && $USER->hasPerm("tagsmod") && !empty($_POST['tags'])) {
				$u['status'] = 'approved';
				foreach (explode("\n",trim(str_replace("\r",'',$_POST['tags']))) as $tag2) {
					$tag2 = trim($tag2);
					if (empty($tag2))
						continue;
					$u['tag2'] = $tag2;
					$prefix2 = '';
					$bits = explode(':',$tag2,2);
					if (count($bits) > 1) {
						list($prefix2,$tag2) = $bits;
					}
					$u['tag2_id'] = $db->getOne("SELECT tag_id FROM tag WHERE prefix = ".$db->Quote($prefix2)." AND tag = ".$db->Quote($tag2));
					$db->Execute('INSERT INTO tag_report SET created=NOW(),`'.implode('` = ?, `',array_keys($u)).'` = ?',array_values($u));
				}
			} else {
				if (!empty($_GET['admin']) && $USER->hasPerm("tagsmod") && !empty($_POST['tag2']) && levenshtein($_POST['tag'],$_POST['tag2']) < 3) {
					$u['status'] = 'approved';
				}

				$db->Execute('INSERT INTO tag_report SET created=NOW(),`'.implode('` = ?, `',array_keys($u)).'` = ?',array_values($u));
			}

			if (!empty($_GET['close'])) {
				print 'Report Saved. <a href="javascript:window.close()">Close Window</a>';
				exit;
			}

			$smarty->assign("message",'Report saved at '.date('r'));
		}
	}

	if (!empty($_GET['admin'])) {
        	$USER->mustHavePerm("tagsmod");

	        $template = 'tags_report_admin.tpl';

        	$types = array(
                'spelling'=>'Spelling',
                'grammer'=>'Grammer',
                'punctuation'=>'Punctuation',
                'caps'=>'Capitalization',
                //'prefix'=>'Change the prefix/namespace used',
                'split'=>'Needs splitting - refers to multiple distinct topics');
	        //todo, synonums

	} else {
		$types = array(
		'spelling'=>'Spelling',
		'grammer'=>'Grammer',
		'punctuation'=>'Punctuation',
		'caps'=>'Capitalization',
		'prefix'=>'Change the prefix/namespace used',
		'bad'=>'Bad Term (abusive/foul language etc)',
		'unknown'=>'Unknown term - its not clear what this tag refers to',
		'split'=>'Needs splitting - refers to multiple distinct topics',
		'other'=>'Other... (anything else not covered above)');

		if (empty($db))
			$db = GeographDatabaseConnection(true);

		$reports = $db->getAll("SELECT r.tag FROM tag_report r INNER JOIN tag t USING (tag_id) WHERE r.status='new' AND type != 'canonical' AND t.status = 1 GROUP BY tag_id ORDER BY r.tag");
		$smarty->assign_by_ref('reports',$reports);

		$recent = $db->getAll("SELECT tag FROM tag_report WHERE status!='new' AND type != 'canonical' GROUP BY tag_id ORDER BY updated DESC LIMIT 50");
		$smarty->assign_by_ref('recent',$recent);
	}
	$smarty->assign_by_ref('types',$types);

	if (!empty($_GET['tag'])) {
		$smarty->assign_by_ref('tag',$_GET['tag']);
	}
}


$smarty->display($template,$cacheid);
