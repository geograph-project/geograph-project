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

$template = 'discuss_report.tpl';

$USER->mustHavePerm("basic");

if (!empty($_POST)) {
	
	$db = GeographDatabaseConnection(false);

	$u = array();
	foreach (array('post_id','topic_id','type','comment') as $key) {
		if (!empty($_POST[$key])) {
			$u[$key] = trim($_POST[$key]);
		}
	}

	if (!empty($u)) {
		
		$u['user_id'] = $USER->user_id;

		$db->Execute('INSERT INTO discuss_report SET created=NOW(),`'.implode('` = ?, `',array_keys($u)).'` = ?',array_values($u));
		
		$smarty->assign("message",'Report saved at '.date('r').', thank you!');
		
		
		ob_start();
		print "http://{$_SERVER['HTTP_HOST']}/admin/discuss_reports.php\n\nHost: ".`hostname`."\n\n";
		print_r($_POST);
		$con = ob_get_clean();
		mail('geograph@barryhunter.co.uk','[Forum Report] for thread #'.$_POST['topic_id'],$con);
	}

}


$types = array(
	'post'=>'This single Post',
	'thread'=>'The whole Thread',
	'onwards'=>'The general Discussion from this point forward');

$smarty->assign_by_ref('types',$types);

$db = GeographDatabaseConnection(true);

$sql = array();

$sql['columns'] = "t.topic_id,topic_title AS thread";

$sql['tables'] = array();
$sql['tables']['t'] = 'geobb_topics t';

$sql['wheres'] = array();

if (!empty($_GET['post'])) {
	$sql['columns'] .= ",CONCAT(post_time,' by ',poster_name) AS post,post_id";
	$sql['tables']['p'] = 'INNER JOIN geobb_posts p USING (topic_id)';
	$sql['wheres'][] = "`post_id` = ".$db->Quote(trim($_GET['post']));
}
$sql['wheres'][] = "`topic_id` = ".$db->Quote(trim($_GET['topic']));


$sql['group'] = 'topic_id';

$sql['order'] = 'null';

$sql['limit'] = 1;




$query = sqlBitsToSelect($sql);

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
$data = $db->getRow($query);

$smarty->assign($data);

$smarty->display($template,$cacheid);
