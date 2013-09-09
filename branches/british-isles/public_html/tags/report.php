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

$USER->mustHavePerm("basic");




if (!empty($_GET['finder'])) {
	 $template = 'tags_report_finder.tpl';

} elseif (!empty($_GET['lookup'])) {

	$db = GeographDatabaseConnection(false);
        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	$data = $db->getAll("SELECT * FROM tag_report WHERE tag_id = ".intval($_GET['tag_id'])." AND tag2 != '' AND status != 'rejected' AND type != 'canonical' ORDER BY tag2");

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

	$smarty->assign_by_ref('report',$report);


} elseif (!empty($_GET['review'])) {
	$USER->mustHavePerm("tagsmod");

                $db = GeographDatabaseConnection(false);

        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$reports = $db->getAll("SELECT report_id,tag_id,tag2_id,tag,tag2,r.type,r.user_id,approver_id,COUNT(gridimage_id) as images FROM tag_report r LEFT JOIN gridimage_tag USING (tag_id) WHERE r.status IN ('approved','moved') AND type != 'split' AND type != 'canonical' group by tag_id ORDER BY tag_id");

	foreach ($reports as $idx => $report) {
		$reports[$idx]['levenshtein'] = levenshtein($report['tag'],$report['tag2']);
	}

?>
<script type="text/javascript" src="http://s1.geograph.org.uk/js/geograph.v7635.js"></script>
<script src="http://s1.geograph.org.uk/sorttable.v7274.js"></script>
<?


        print "<TABLE class=\"report sortable\" id=\"photolist\" cellspacing=0 cellpadding=3 border=1>";
	print "<thead><tr><th>".implode('</th><th>',array_keys($reports[0])).'</th></tr></htead>';

	print "<tbody>";
	foreach ($reports as $report) {
		print "<tr><td>".implode('</td><td>',$report).'</td></tr>';
	}
	print "</tbody></table>";
	exit;

} elseif (!empty($_GET['deal'])) {
	$USER->mustHavePerm("admin");

	$template = 'tags_report_deal.tpl';

	if (!empty($_POST)) {
		
		//dont want this to die!
		set_time_limit(3600*24);
		print str_repeat(' ',1000);
		flush();
		
		
		$db = GeographDatabaseConnection(false);

		$reports = $db->getAssoc("SELECT r.*, 
						t2.tag_id AS tag2_id,
						t2.canonical
					FROM tag_report r 
						INNER JOIN tag t1 USING (tag_id) 
						LEFT JOIN tag t2 ON ( t2.tag = SUBSTRING_INDEX(r.tag2,':',-1) AND t2.prefix = IF(r.tag2 LIKE '%:%',SUBSTRING_INDEX(r.tag2,':',1),'') )
					WHERE r.status = 'new' ");
		
		$s = array();
		
		foreach ($_POST['res'] as $report_id => $resolution) {
			if (!($row = $reports[$report_id]))
				die("unable to find $report_id");
				
			switch ($resolution) {
				case 'reject':   
					#$s[] = "UPDATE tag_report SET status = 'rejected' WHERE report_id = $report_id";
					$s[] = "UPDATE tag_report SET status = 'rejected' WHERE tag_id = {$row['tag_id']} AND tag2 = ".$db->Quote($row['tag2']);
					
					break;
					
				case 'canonical':
                                        if (empty($row['tag2_id'])) {
                                                die("UNKNOWN DESTINIATION TAG {$row['tag2']}!!!");
                                        }

                                        $s[] = "UPDATE tag SET canonical = {$row['tag_id']} WHERE tag_id = {$row['tag2_id']}";

					$s[] = "UPDATE tag_report SET status = 'moved' WHERE report_id = $report_id";

					break;

				case 'move':
					if (empty($row['tag2'])) {
						die("UNKNOWN DESTINIATION TAG {$row['tag2']}!!!");
					}
					
					if (empty($row['tag2_id'])) {
						$bits = explode(':',$row['tag2'],2);
						if (count($bits) > 1) {
							$values = array("prefix = ".$db->Quote($bits[0]),"tag = ".$db->Quote($bits[1]));
						} else {
							$values = array("tag = ".$db->Quote($row['tag2']));
						}
							
						if ($db->getOne("SELECT tag_id FROM tag WHERE ".implode(' AND ',$values))) {
							die("THERE IS ALREADY A TAG {$row['tag2']}!!!");
						}

						$values[] = "created = NOW()";
						$values[] = "user_id = {$USER->user_id}";
						$db->Execute("INSERT INTO tag SET ".implode(', ',$values));
						
						$row['tag2_id'] = $db->Insert_ID();
						
						print "# INSERT INTO tag SET ".implode(', ',$values)."; #{$row['tag2_id']}<hr/>";
					}
					
					if ($images = $db->getCol("SELECT gridimage_id FROM gridimage_tag WHERE tag_id = {$row['tag_id']} AND status = 2 AND gridimage_id < 4294967295")) {
					
						foreach ($images as $gridimage_id) {

							$s[] = "INSERT INTO gridimage_ticket SET 
								gridimage_id=$gridimage_id,
								suggested=NOW(),
								user_id={$USER->user_id},
								updated=NOW(),
								status='closed',
								notes='Applying a change to a tag',
								type='minor',
								notify='',
								public='everyone'";

							$s[] = "INSERT INTO gridimage_ticket_item SET
								gridimage_ticket_id = LAST_INSERT_ID(),
								approver_id = {$USER->user_id},
								field = 'tag',
								oldvalue = ".$db->Quote($row['tag']).",
								newvalue = ".$db->Quote($row['tag2']).",
								status = 'immediate'";
						}

						$s[] = "UPDATE IGNORE gridimage_tag SET tag_id = {$row['tag2_id']} WHERE tag_id = {$row['tag_id']} AND status = 2";
						
						//this is trickly. Any of the above that failed (due to duplicate key), means the 'new' tag is already on the image, and so the old one can be zapped. 
							
					}
					
					if ($_POST['canon'][$report_id]) {
						$s[] = "UPDATE tag SET canonical = {$row['tag2_id']} WHERE tag_id = {$row['tag_id']}";
					}

					#$s[] = "UPDATE tag_report SET status = 'moved' WHERE report_id = $report_id";
					$s[] = "UPDATE tag_report SET status = 'moved' WHERE tag_id = {$row['tag_id']} AND tag2 = ".$db->Quote($row['tag2']);
					
					break;
					
				case 'rename':
					if (empty($row['tag2'])) {
						die("UNKNOWN DESTINIATION TAG {$row['tag2']}!!!");
					}
					
					$bits = explode(':',$row['tag2'],2);
					if (count($bits) > 1) {
						$values = array("prefix = ".$db->Quote($bits[0]),"tag = ".$db->Quote($bits[1]));
					} else {
						$values = array("tag = ".$db->Quote($row['tag2']));
					}

					if ($db->getOne("SELECT tag_id FROM tag WHERE ".implode(' AND ',$values)." AND tag_id != {$row['tag_id']}")) {
						die("THERE IS ALREADY A TAG {$row['tag2']}!!!");
					}
					
					$s[] = "UPDATE tag SET ".implode(', ',$values)." WHERE tag_id = {$row['tag_id']}";
					
					#$s[] = "UPDATE tag_report SET status = 'renamed' WHERE report_id = $report_id";
					$s[] = "UPDATE tag_report SET status = 'renamed' WHERE tag_id = {$row['tag_id']} AND tag2 = ".$db->Quote($row['tag2']);
					
					break;
			
			}
		
		}

			
		if (!empty($s)) {
			foreach ($s as $q) {
				print htmlentities($q).";";
				$db->Execute($q);
				
				print " #Rows = ".$db->Affected_Rows()."<hr/>";
			}
		}
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

		$reports = $db->getAll("SELECT tag FROM tag_report WHERE status='new' AND type != 'canonical' GROUP BY tag_id ORDER BY tag");
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
