<?php
/**
 * $Project: GeoGraph $
 * $Id: index.php 9086 2020-03-18 14:49:09Z barry $
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

if ($_SERVER['HTTP_HOST'] == 'www.geograph.org.uk') {
	if (!empty($_GET['lang']))
		$mobile_url = "https://m.geograph.org.uk/?lang=cy";
	else
		$mobile_url = "https://m.geograph.org.uk/";
}

if (empty($smarty)) {

require_once('geograph/global.inc.php');
init_session();


$smarty = new GeographPage;
}

if ($CONF['template']!='ireland') {
	$smarty->assign('welsh_url',"/?lang=cy"); //needed by the english template!
	$smarty->assign('english_url',"/"); //needed by the welsh template!
}


customGZipHandlerStart();

$template='homepage.tpl';
if ($CONF['template']!='charcoal' && $CONF['template']!='charcoal_cy') {
	$cacheid=rand(1,5); //so we get a selection of homepages
}

if (isset($_GET['potd'])) {
	$USER->mustHavePerm("moderator");
	$smarty->caching = 0;
}

if (isset($_GET['preview'])) {
	$template='homepage-new.tpl';
}

//regenerate?
if (!$smarty->is_cached($template, $cacheid))
{
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/imagelist.class.php');

	require_once('geograph/map.class.php');
	require_once('geograph/mapmosaic.class.php');

/////////////////////////////
// overview map

	if ($CONF['template'] == 'ireland' && !isset($_GET['preview']))
		$preset = 'overview_ireland';
	else
		$preset = 'overview_charcoal';

	$overview=new GeographMapMosaic($preset);
	$overview->type_or_user = -1;
	if ($preset != 'overview_ireland') {
		$overview->assignToSmarty($smarty, 'overview2');
	} else {
		$overview->assignToSmarty($smarty, 'overview');
	}

/////////////////////////////
// photo of the day

	require_once('geograph/pictureoftheday.class.php');
	$potd=new PictureOfTheDay;
	if (isset($_GET['potd'])) {
		$_GET['potd'] = preg_replace('/[^\d]+/',' ',$_GET['potd']);
		$potd->assignToSmarty($smarty,intval($_GET['potd']));
	} else {
		$potd->assignToSmarty($smarty);
	}

/////////////////////////////
// lets find some recent photos
	if ($CONF['template']=='ireland') {
		new RecentImageList($smarty,2);
		 $discuss_where = ' and t.topic_id not in (11663)';
	} else {
		$smarty->assign('marker', $overview->getSquarePoint($potd->image->grid_square));
		new RecentImageList($smarty);
		$discuss_where = '';
	}

/////////////////////////////
// annonucements
	$db = GeographDatabaseConnection(true);

	if (0 && $CONF['forums']) {
		//let's find recent posts in the announcements forum made by
		//administrators

		$sql="select u.user_id,u.realname,t.topic_title,p.post_text,t.topic_id,t.topic_time, posts_count - 1 as comments
			from geobb_topics as t
			inner join geobb_posts as p on(t.topic_id=p.topic_id)
			inner join user as u on (p.poster_id=u.user_id)
			where (find_in_set('admin',u.rights)>0 or p.poster_id IN (3,560)) and
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
				$text = preg_replace("/(http:\/\/[\w\.\/\?=\&-]+)/",'<a href="$1">Link</a>',$text);

				$text = str_replace("http://t.co/rIJ8W3P",'/stuff/thisday.php',$text);

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

/////////////////////////////
// statistics
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	if ($CONF['template']=='ireland') {
		$hectads= $db->getAll("SELECT * FROM hectad_stat WHERE geosquares >= landsquares AND reference_index=2 ORDER BY last_submitted DESC LIMIT 5");
	} else {
		$hectads= $db->getAll("SELECT * FROM hectad_stat WHERE geosquares >= landsquares ORDER BY last_submitted DESC LIMIT 5");
	}
	$smarty->assign_by_ref('hectads', $hectads);

	if ($CONF['template']=='ireland') {
		$stats= $db->GetRow("SELECT SUM(imagecount) AS images, SUM(has_geographs>0) AS squares,
				COUNT(*) AS total,SUM(imagecount in (1,2,3)) AS fewphotos FROM gridsquare WHERE reference_index = 2 AND percent_land > 0");
		$stats += $db->cacheGetRow(3600*24, "SELECT COUNT(distinct user_id) as users from gridimage_search where x<410 and y<648 and reference_index = 2");

	} else {
		$stats= $db->GetRow("select * from user_stat where user_id = 0");
		$stats += $db->GetRow("select count(*)-1 as users from user_stat");
		$stats += $db->cacheGetRow(3600,"select SUM(imagecount) AS images, count(*) as total,sum(imagecount in (1,2,3)) as fewphotos from gridsquare where percent_land > 0");
	}

	$stats['nophotos'] = $stats['total'] - $stats['squares'];
	$stats['percentage'] = sprintf("%.1f",$stats['squares']/$stats['total']*100);
	$smarty->assign_by_ref('stats', $stats);

/////////////////////////////
// misc
	$smarty->assign('rss_url','/discuss/syndicator.php?forum=1&amp;first=1');

	$smarty->assign('messages', array(
		0=>'click map to zoom in',
		1=>'click me and explore!',
		2=>'I\'m zoomable - click me',
		3=>'click to explore map',
		4=>'click to see bigger map',
		5=>'click for more detail'));
	$smarty->assign('m',rand(0,5));

	$smarty->assign('ptitles', array(
		0=>'Photograph of the day',
		1=>'Photograph for today',
		2=>'Featured photograph',
		3=>"Today's photo",
		4=>'One photo',
		5=>'Selected photograph'));
	$smarty->assign('ptitle',rand(0,5));

/////////////////////////////
// featured collection
	$datecolumn = 'updated';
	$where = "content.content_id = (select content_id from content_featured where showday <= date(now()) order by showday desc limit 1)";
	$limit = 1;

	$list = $db->getAll($sql = "
	select content.content_id,content.user_id,url,title,extract,unix_timestamp(replace(content.$datecolumn,'-00','-01')) as $datecolumn,realname,content.source,content.gridimage_id,
		(content.views+coalesce(article_stat.views,0)+coalesce(topic_views,0)) as views,
		(content.images+coalesce(article_stat.images,0)+coalesce(count(gridimage_post.seq_id),0)) as images,
		article_stat.words,coalesce(posts_count,0) as posts_count,coalesce(count(distinct gridimage_post.post_id),0) as posts_with_images
	from content
		left join user using (user_id)
		left join article_stat on (content.source = 'article' and foreign_id = article_id)
		left join geobb_topics on (content.source IN ('gallery','themed') and foreign_id = topic_id)
		left join gridimage_post using (topic_id)
	where $where
	group by content_id
	having (posts_with_images >= posts_count/2) OR (content.source = 'gallery' AND posts_with_images>1)
	limit $limit");

	if (empty($_GET['lang'])) $_GET['lang'] = ''; //avoids notice below!
	foreach ($list as $i => $row) {
		if ($row['gridimage_id']) {
			$list[$i]['image'] = new GridImage;
			$g_ok = $list[$i]['image']->loadFromId($row['gridimage_id'],true);
			if ($g_ok && $list[$i]['image']->moderation_status == 'rejected')
				$g_ok = false;
			if (!$g_ok) {
				unset($list[$i]['image']);
			}
		}
		$diff = time() - $row[$datecolumn];
		if ($diff > (3600*24*31)) {
			$list[$i][$datecolumn] = sprintf(($_GET['lang']=='cy')?"%d misoedd yn &ocirc;l":"%d months ago",$diff/(3600*24*31));
		} elseif ($diff > (3600*24)) {
			$list[$i][$datecolumn] = sprintf(($_GET['lang']=='cy')?"%d dyddiau yn &ocirc;l":"%d days ago",$diff/(3600*24));
		} elseif ($diff > 3600) {
			$list[$i][$datecolumn] = sprintf(($_GET['lang']=='cy')?"%d oriau yn &ocirc;l":"%d hours ago",$diff/3600);
		} else {
			$list[$i][$datecolumn] = sprintf(($_GET['lang']=='cy')?"%d munudau yn &ocirc;l":"%d minutes ago",$diff/60);
		}
	}

	$sources = $CONF['content_sources'];
	unset($sources['themed']);
	unset($sources['portal']);

	$smarty->assign_by_ref('collections', $list);
	$smarty->assign_by_ref("sources",$sources);

	#pallet by http://jiminy.medialab.sciences-po.fr/tools/palettes/index.php
	$colours = array('E1BBDA','DDEA8E','83E7E1','D5CEA9','E6B875','A7CEE5','E9B1A5','A6E09A','7DE0B8','CED4CF','B2DAAD','C5C474','A0DACA');

	$keys = array_keys($sources);
	foreach ($keys as $idx => $key) {
		$colours[$key] = $colours[$idx];
	}
	$smarty->assign_by_ref("colours",$colours);


	//$job = $db->getRow("select blog_id,title from blog where tags like 'job posting' and approved = 1 order by rand() limit 1");
	//$smarty->assign_by_ref("job",$job);

	$extra_meta = array();

	if (!empty($_GET['lang']))
		$extra_meta[] = "<link rel=\"canonical\" href=\"{$CONF['SELF_HOST']}/?lang=cy\"/>";
	else
		$extra_meta[] = "<link rel=\"canonical\" href=\"{$CONF['SELF_HOST']}/\"/>";

	//hard to do this dynamically
	if ($CONF['template']=='charcoal' ||  $CONF['template']=='charcoal_cy') {
		$extra_meta[] = "<link rel=\"alternate\" hreflang=\"en\" href=\"https://schools.geograph.org.uk/\"/>";
		$extra_meta[] = "<link rel=\"alternate\" hreflang=\"cy\" href=\"https://schools.geograph.org.uk/?lang=cy\"/>";
	} else {
		$extra_meta[] = "<link rel=\"alternate\" hreflang=\"en\" href=\"https://www.geograph.org.uk/\"/>";
		$extra_meta[] = "<link rel=\"alternate\" hreflang=\"cy\" href=\"https://www.geograph.org.uk/?lang=cy\"/>";
		$extra_meta[] = "<link rel=\"alternate\" hreflang=\"en-ie\" href=\"https://www.geograph.ie/\"/>";
	}

	if (!empty($mobile_url))
		$extra_meta[] = "<link rel=\"alternate\" media=\"only screen and (max-width: 640px)\" href=\"$mobile_url\"/>";

	$smarty->assign('extra_meta',implode('',$extra_meta));
}


$smarty->display($template, $cacheid);



