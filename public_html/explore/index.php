<?php
/**
 * $Project: GeoGraph $
 * $Id$
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




$smarty = new GeographPage;

//regenerate?
if (!$smarty->is_cached('explore.tpl'))
{
	if (!$db) {
		$db=NewADOConnection($GLOBALS['DSN']);
		if (!$db) die('Database connection failed');
	}

	$countylist = array();
	$recordSet = &$db->Execute("SELECT reference_index,county_id,name FROM loc_counties WHERE n > 0"); 
	while (!$recordSet->EOF) 
	{
		$countylist[$CONF['references'][$recordSet->fields[0]]][$recordSet->fields[1]] = $recordSet->fields[2];
		$recordSet->MoveNext();
	}
	$recordSet->Close(); 
	$smarty->assign_by_ref('countylist', $countylist);

	$topicsraw = $db->GetAssoc("select gp.topic_id,concat(topic_title,' [',count(*),']') as title,forum_name from gridimage_post gp
		inner join geobb_topics using (topic_id)
		inner join geobb_forums using (forum_id)
		group by gp.topic_id 
		having count(*) > 4
		order by geobb_topics.forum_id desc,topic_title");

	$topics=array("1"=>"Any Topic"); 
	
	$options = array();
	foreach ($topicsraw as $topic_id => $row) {
		if ($last != $row['forum_name'] && $last) {
			$topics[$last] = $options;
			$options = array();
		}
		$last = $row['forum_name'];
	
		$options[$topic_id] = $row['title'];
	}
	$topics[$last] = $options;
	
	$smarty->assign_by_ref('topiclist',$topics);	

	$smarty->assign('histsearch',$CONF['searchid_historical']);
	if (count($CONF['hier_statlevels'])) {
		$smarty->assign('hasregions',true);
		$smarty->assign('regionlistlevel',$CONF['hier_listlevel']);
	}
}




$smarty->display('explore.tpl');

	
?>
