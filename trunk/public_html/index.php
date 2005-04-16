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
$cacheid='';

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
	$images=new ImageList(array('pending', 'accepted', 'geograph'), 'submitted desc', 5);
	$images->assignSmarty($smarty, 'recent');
	
	//let's find recent posts in the announcements forum made by
	//administrators
	$db=NewADOConnection($GLOBALS['DSN']);
	$sql='select u.user_id,u.realname,t.topic_title,p.post_text,t.topic_id,t.topic_time '.
		'from geobb_topics as t '.
		'inner join geobb_posts as p on(t.topic_id=p.topic_id) '.
		'inner join user as u on (p.poster_id=u.user_id) '.
		'where find_in_set(\'admin\',u.rights)>0 and '.
		't.topic_time=p.post_time and '.
		't.forum_id=1 '.
		'order by t.topic_time desc limit 3';
	$news=$db->GetAll($sql);
	if ($news) 
	{
		foreach($news as $idx=>$item)
		{
			$news[$idx]['post_text']=str_replace('<br>', '<br/>', $news[$idx]['post_text']);
			$news[$idx]['comments']=$db->GetOne('select count(*)-1 as comments from geobb_posts where topic_id='.$item['topic_id']);
		}
	}
	

	$smarty->assign_by_ref('news', $news);
	
}


$smarty->display($template, $cacheid);

	
?>
