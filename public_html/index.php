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

customGZipHandlerStart();

$template='homepage.tpl';
if ($CONF['template']!='charcoal') {
	$cacheid=rand(1,5); //so we get a selection of homepages
}

if (isset($_GET['potd'])) {
	$USER->mustHavePerm("moderator");
	$smarty->caching = 0;
}

#if (empty($_SERVER['HTTP_REFERER']) && $USER->registered && $CONF['template']=='basic') {
if ($CONF['template'] == 'basic') {
	$_GET['preview'] = 1;
}

if (isset($_GET['preview'])) {
	$template='homepage-new.tpl';
	$smarty->assign('maincontentclass', 'content2'); //we need this because it in a dynamic block in _std_begin
}

//regenerate?
if (!$smarty->is_cached($template, $cacheid))
{
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/imagelist.class.php');

	require_once('geograph/map.class.php');
	require_once('geograph/mapmosaic.class.php');

	switch($CONF['template']) {
		case 'charcoal': $preset = 'overview_charcoal'; break;
		case 'ireland': $preset = 'overview_ireland'; break;
		default: $preset = 'overview_large'; break;
	}
	if (isset($_GET['preview'])) {
		 $preset = 'overview_charcoal';
	}
	$overview=new GeographMapMosaic($preset);
	$overview->type_or_user = -1;
	if ($preset == 'overview_large' || isset($_GET['preview'])) {
		$overview->assignToSmarty($smarty, 'overview2');
	} else {
		$overview->assignToSmarty($smarty, 'overview');
	}
	
	
	require_once('geograph/pictureoftheday.class.php');
	$potd=new PictureOfTheDay;
	if (isset($_GET['potd'])) {
		$potd->assignToSmarty($smarty,intval($_GET['potd'])); 
	} else {
		$potd->assignToSmarty($smarty); 
	}
	
	
	//lets find some recent photos
	if ($CONF['template']=='ireland') {
		new RecentImageList($smarty,2);
		 $discuss_where = ' and t.topic_id not in (11663)';
	} else {
		$smarty->assign('marker', $overview->getSquarePoint($potd->image->grid_square));
		new RecentImageList($smarty);
		$discuss_where = '';
	}
	
	
	$db = GeographDatabaseConnection(true);
	
	if ($CONF['forums']) {
		//let's find recent posts in the announcements forum made by
		//administrators

		

		$sql="select u.user_id,u.realname,t.topic_title,p.post_text,t.topic_id,t.topic_time, posts_count - 1 as comments 
			from geobb_topics as t
			inner join geobb_posts as p on(t.topic_id=p.topic_id)
			inner join user as u on (p.poster_id=u.user_id)
			where find_in_set('admin',u.rights)>0 and
			abs(unix_timestamp(t.topic_time) - unix_timestamp(p.post_time) ) < 10 and
			t.forum_id=1
			$discuss_where
			group by t.topic_id
                        order by t.topic_time desc limit 3";
		$news=$db->GetAll($sql);
		if ($news) 
		{
			foreach($news as $idx=>$item)
			{
				$news[$idx]['post_text']=str_replace('<br>', '<br/>', GeographLinks($news[$idx]['post_text'],true));
			}
		}
	
		if (isset($_GET['preview'])) {
			$smarty->assign_by_ref('news2', $news);
		} else {
			$smarty->assign_by_ref('news', $news);
		}
		
		if ($rss = file_get_contents("http://twitter.com/statuses/user_timeline/251137848.rss")) {
			
			preg_match_all('/<title>(.*?)<\/title>/',$rss,$m);
			preg_match_all('/<pubDate>(.*?)<\/pubDate>/',$rss,$m2);
			
			array_shift($m[1]);
			$feed = array();
			foreach ($m[1] as $idx => $text) {
				if (strpos($text,'Picture of the Day: http://geograph') !== FALSE) {
					continue;
				}				
				$text = str_replace('geograph_bi: ','',$text);
				$text = preg_replace('/^([\w ]+):/','<b>$1</b>:',$text);
				$text = preg_replace('/\. ([\w \.,;]+\.\.\.)\s*$/','. <span style="color:gray">$1</span>...',$text);
				$text = str_replace('/geograph.org.uk/','/www.geograph.org.uk/',$text);
				$text = str_replace('org.uk/p/','org.uk/photo/',$text);
				$text = preg_replace("/(http:\/\/[\w\.\/]+)/",'<a href="$1">Link</a>',$text);
				
				$item = array();
				$item['text'] = $text;
				$item['time'] = strtotime($m2[1][$idx]);
				
				$feed[] = $item;
				if (count($feed) == 6) 
					break;
			}
			$smarty->assign_by_ref('feed', $feed);
			array_pop($news);
		}

	}
	
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$hectads= $db->getAll("SELECT * FROM hectad_stat WHERE geosquares >= landsquares ORDER BY last_submitted DESC LIMIT 5");
	$smarty->assign_by_ref('hectads', $hectads);
	
	$stats= $db->GetRow("select * from user_stat where user_id = 0");
	$stats += $db->GetRow("select count(*)-1 as users from user_stat");
	$stats += $db->cacheGetRow(3600,"select count(*) as total,sum(imagecount in (1,2,3)) as fewphotos from gridsquare where percent_land > 0");
	$stats['nophotos'] = $stats['total'] - $stats['squares'];
	$stats['percentage'] = sprintf("%.1f",$stats['squares']/$stats['total']*100);
	$smarty->assign_by_ref('stats', $stats);
	
	$smarty->assign('rss_url','/discuss/syndicator.php?forum=1&amp;first=1');
	
	$smarty->assign('messages', array(
		0=>'click map to zoom in',
		1=>'click me and explore!',
		2=>'I\'m zoomable - click me',
		4=>'',
		5=>'click to see bigger map',
		6=>'click for more detail'));
		
	$smarty->assign('m',rand(0,6));	
}


$smarty->display($template, $cacheid);



