<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2006 Barry Hunter (geo@barryhunter.co.uk)
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

$smarty->caching = 0; //dont cache!

$cacheid = 0;

$template = 'gallery.tpl';

if (!$smarty->is_cached($template, $cacheid))
{
	
	$db=NewADOConnection($GLOBALS['DSN']);
	
	$prev_fetch_mode = $ADODB_FETCH_MODE;
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$list = $db->getAll("
	select topic_id,topic_title,topic_poster,topic_poster_name,topic_time,topic_views,posts_count,count(*) as images_count
	from geobb_topics
	left join gridimage_post using (topic_id)
	where forum_id = 6
	group by topic_id
	order by topic_last_post_id desc");
	
	foreach ($list as $i => $row) {
		$list[$i]['url'] = trim(strtolower(preg_replace('/[^\w]+/','_',html_entity_decode($row['topic_title']))),'_').'_'.$row['topic_id'];
	}
	
	$smarty->assign_by_ref('list', $list);

}

$smarty->display($template, $cacheid);

	
?>
