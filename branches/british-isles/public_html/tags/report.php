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
	'bad'=>'Bad Term (abusive/foul language etc)',
	'unknown'=>'Unknown term - its not clear what this tag refers to',
	'split'=>'Needs splitting - refers to multiple distinct topics',
	'other'=>'Other... (anything else not covered above)');

$smarty->assign_by_ref('types',$types);

$smarty->display($template,$cacheid);
