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




if (!empty($_GET['deal'])) {
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
						LEFT JOIN tag t2 ON (r.tag2_id = t2.tag_id OR IF(t2.prefix='',t2.tag,CONCAT(t2.prefix,':',t2.tag)) = r.tag2 )
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
					
					if ($images = $db->getCol("SELECT gridimage_id FROM gridimage_tag WHERE tag_id = {$row['tag_id']} AND status = 2")) {
					
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

						$s[] = "UPDATE gridimage_tag SET tag_id = {$row['tag2_id']} WHERE tag_id = {$row['tag_id']} AND status = 2";
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

					if ($db->getOne("SELECT tag_id FROM tag WHERE ".implode(' AND ',$values))) {
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
				print htmlentities($q).";<hr/>";
				$db->Execute($q);
			}
		}
	}

	if (empty($db))
		$db = GeographDatabaseConnection(true);

	$reports = $db->getAll("SELECT r.*, 
					COUNT(DISTINCT gridimage_id) AS images,
					t2.tag_id AS tag2_id,
					t2.canonical
				FROM tag_report r 
					INNER JOIN tag t1 USING (tag_id) 
					LEFT JOIN gridimage_tag gt USING (tag_id)
					LEFT JOIN tag t2 ON (r.tag2_id = t2.tag_id OR IF(t2.prefix='',t2.tag,CONCAT(t2.prefix,':',t2.tag)) = r.tag2 )
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

			$db->Execute('INSERT INTO tag_report SET created=NOW(),`'.implode('` = ?, `',array_keys($u)).'` = ?',array_values($u));

			$smarty->assign("message",'Report saved at '.date('r'));
		}

	}

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

	$smarty->assign_by_ref('types',$types);

	if (empty($db))
		$db = GeographDatabaseConnection(true);

	$reports = $db->getAll("SELECT tag FROM tag_report WHERE status='new' GROUP BY tag_id ORDER BY tag");
	$smarty->assign_by_ref('reports',$reports);
	
	$recent = $db->getAll("SELECT tag FROM tag_report WHERE status!='new' GROUP BY tag_id ORDER BY updated DESC LIMIT 50");
	$smarty->assign_by_ref('recent',$recent);

	if (!empty($_GET['tag'])) {
		$smarty->assign_by_ref('tag',$_GET['tag']);
	}
}


$smarty->display($template,$cacheid);
