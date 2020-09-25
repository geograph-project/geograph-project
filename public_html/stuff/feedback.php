<?php
/**
 * $Project: GeoGraph $
 * $Id: feedback.php 8519 2017-08-13 18:59:40Z barry $
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

require_once('geograph/global.inc.php');
init_session();


$smarty = new GeographPage;

$template='stuff_feedback.tpl';

$cacheid='';



$db = NewADOConnection($GLOBALS['DSN']);

if (!empty($_POST['submit'])) {
	foreach ($_POST as $key => $value) {
		if (preg_match('/radio(\d+)/',$key,$m)) {
			$id = intval($m[1]);
			
			$ins = "INSERT INTO vote_log SET
			type = 'f',
			id = $id,
			vote = ".intval($value).",
			ipaddr = INET6_ATON('".getRemoteIP()."'),
			user_id = ".intval($USER->user_id);
			$db->Execute($ins);
		}
	}

	$subject = "Feedback Form";
	$msg=stripslashes(trim($_POST['comments']));
	if (!empty($msg)) {
		if (!empty($_POST['name'])) {
			die("Spam, Spam, Eggs, Spam, Cheese and Spam!");
		}
		
		$msg.="\n\n-------------------------------\n";
		if (!empty($_POST['template'])) {
			if (preg_match('/(search.php\?i=|results\/)(\d+)/',$_SERVER['HTTP_REFERER'],$m)) {
				require_once('geograph/searchcriteria.class.php');
				require_once('geograph/searchengine.class.php');
				$engine = new SearchEngine($m[2]);
				if ($engine->criteria && $engine->criteria->searchdesc) {
					$msg.="Search: {$engine->criteria->searchdesc}\n";
				}
			}
			$msg.="Template: {$_POST['template']}\n";
			$subject.=" ({$_POST['template']})";
			if (!empty($_POST['referring_page'])) {
				$msg.="Referring page: ".$_POST['referring_page']."\n";
			}
			$msg.="Page: {$_SERVER['HTTP_REFERER']}\n";
		}
		if (!empty($_POST['nonanon']) && $_SESSION['user']->user_id) {
			$msg.="User profile: {$CONF['SELF_HOST']}/profile/{$_SESSION['user']->user_id}\n";
			$from = $_SESSION['user']->email;
		} else {
			$from = "anon@geograph.org.uk";
		}
		$msg.="Browser: ".$_SERVER['HTTP_USER_AGENT']."\n";

		mail($CONF['contact_email'], 
			'[Geograph] '.$subject,
			$msg,
			'From:'.$from);	
	}
	
	$smarty->assign('thanks', 1);
} else {


	$prev_fetch_mode = $ADODB_FETCH_MODE;
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	$exp = $db->getAll($sql = "
	select *
	from feedback
	where category = 'Experience'
	order by question
	");
	$smarty->assign_by_ref('exp', $exp);


	$list = $db->getAll($sql = "
	select *
	from feedback
	where category != 'Experience'
	order by FIELD(category,'Experience','Site Features','Searching for Photos','Viewing Maps','Exploring Images','Advanced Features','Profile','Submission','Editing','Contacting'),question
	");
	$smarty->assign_by_ref('list', $list);

}

$smarty->display($template,$cacheid);

	
?>
