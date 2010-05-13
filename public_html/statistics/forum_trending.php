<?php
/**
 * $Project: GeoGraph $
 * $Id: forum_image_breakdown.php 5967 2009-10-31 12:31:43Z geograph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2010 Barry Hunter (geo@barryhunter.co.uk)
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

$template='statistics_forum_trending.tpl';

$s = (isset($_GET['s']) && is_numeric($_GET['s']))?intval($_GET['s']):1;

$h = (isset($_GET['h']) && is_numeric($_GET['h']))?intval($_GET['h']):3;

$types = array(
	1=>"Most Viewed",
	2=>"Most Commented",
);
if (empty($types[$s])) {
	$s = 1;
}

$hours = array(
	1=>"1 Hour",
	3=>"3 Hours",
	6=>"6 Hours",
	24=>"24 Hours",
	48=>"2 Days",
);
if (empty($hours[$h])) {
	$h = 3;
}


$cacheid='statistics|'.$template.'.'.$s.'.'.$h;

if (!$smarty->is_cached($template, $cacheid))
{
	$db = GeographDatabaseConnection(false); //the forum uses the master directly, so more likly to have the tables cached

	if ($s == 2) {
		$table = "geobb_posts";
		$date_column = "post_time";
	} else {
		$table = "geobb_lastviewed";
		$date_column = "ts";
	}	
		
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$table=$db->GetAll(" 
	select topic_id,count(*) c,t.forum_id,topic_title,poster_name,topic_time
	from $table
	inner join geobb_topics t using (topic_id) 
	where $date_column > date_sub(now(),interval $h hour) 
	group by topic_id 
	order by c desc,topic_id desc 
	limit 40" );
	
	$smarty->assign_by_ref('table', $table);
	
	$smarty->assign("s",$s);
	$smarty->assign("h",$h);

	$smarty->assign_by_ref('types', $types);
	$smarty->assign_by_ref('hours', $hours);
	
} 

$smarty->display($template, $cacheid);

