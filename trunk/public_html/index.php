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


$template='homepage.tpl';
$cacheid=rand(1,5); //so we get a selection of homepages

//regenerate?
if (!$smarty->is_cached($template, $cacheid))
{
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/imagelist.class.php');

	require_once('geograph/map.class.php');
	require_once('geograph/mapmosaic.class.php');

	$overview=new GeographMapMosaic('overview');
	$overview->assignToSmarty($smarty, 'overview');
	
	
	//lets find some recent photos
	new RecentImageList($smarty);
	
	//let's find recent posts in the announcements forum made by
	//administrators
	$db=NewADOConnection($GLOBALS['DSN']);
	$sql="select u.user_id,u.realname,t.topic_title,p.post_text,t.topic_id,t.topic_time, posts_count - 1 as comments 
		from geobb_topics as t
		inner join geobb_posts as p on(t.topic_id=p.topic_id)
		inner join user as u on (p.poster_id=u.user_id)
		where find_in_set('admin',u.rights)>0 and
		abs(unix_timestamp(t.topic_time) - unix_timestamp(p.post_time) ) < 10 and
		t.forum_id=1
		order by t.topic_time desc limit 3";
	$news=$db->GetAll($sql);
	if ($news) 
	{
		foreach($news as $idx=>$item)
		{
			$news[$idx]['post_text']=str_replace('<br>', '<br/>', GeographLinks($news[$idx]['post_text']));
		}
	}
	

	$smarty->assign_by_ref('news', $news);
	
	
	$stats= $db->cacheGetRow(3600,"select count(*) as images,count(distinct grid_reference) as squares,count(distinct user_id) as users ,sum(ftf=1 and moderation_status = 'geograph') as points from gridimage_search");
	$smarty->assign_by_ref('stats', $stats);
}


$smarty->display($template, $cacheid);

	
?>
