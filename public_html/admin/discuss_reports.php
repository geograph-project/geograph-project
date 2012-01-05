<?php
/**
 * $Project: GeoGraph $
 * $Id: suggestions.php 6586 2010-04-02 20:10:46Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
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

$USER->mustHavePerm("admin");

$smarty = new GeographPage;

$db = GeographDatabaseConnection(false);

#############################

$sql = array();

$sql['columns'] = "r.*,realname,t.topic_id,t.forum_id,topic_title AS thread";

$sql['tables'] = array();
$sql['tables']['r'] = 'discuss_report r';
$sql['tables']['u'] = 'INNER JOIN user u USING (user_id)';
$sql['tables']['t'] = 'INNER JOIN geobb_topics t USING (topic_id)';

$sql['wheres'] = array();


	$sql['columns'] .= ",CONCAT(post_time,' by ',poster_name) AS post,post_id";
	$sql['tables']['p'] = 'LEFT JOIN geobb_posts p USING (post_id)';


$sql['wheres'][] = "`resolution` in ('new','open')";


#$sql['group'] = 'r.topic_id';

$sql['order'] = 'r.updated desc';

$sql['limit'] = 100;




$query = sqlBitsToSelect($sql);

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
$data = $db->getAll($query);

$smarty->assign_by_ref('data',$data);

#############################

$smarty->display('admin_discuss_reports.tpl');


