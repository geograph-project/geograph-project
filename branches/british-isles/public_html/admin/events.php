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
require_once('geograph/event.class.php');


init_session();
$smarty = new GeographPage;
$USER->mustHavePerm("admin");

$db=GeographDatabaseConnection(true);

if (isset($_GET['showlogs']))
{
	$logdb=NewADOConnection(!empty($GLOBALS['DSN2'])?$GLOBALS['DSN2']:$GLOBALS['DSN']);

	$event_id=intval($_GET['showlogs']);
	$smarty->assign('logs', $logdb->GetAll("select * from event_log where event_id=$event_id order by event_log_id"));
	$smarty->assign('handlers', $db->GetAll("select * from event_handled_by where event_id=$event_id"));

	$smarty->display('admin_eventlog.tpl');
	exit;
}

if (isset($_POST['fire']))
{
	Event::fire(stripslashes($_POST['event_name']), stripslashes($_POST['event_param']), stripslashes($_POST['event_priority']));
	$smarty->assign('event_name',stripslashes($_POST['event_name']));
	$smarty->assign('event_param',stripslashes($_POST['event_param']));
	$smarty->assign('event_priority',stripslashes($_POST['event_priority']));
	$smarty->assign('event_fired',1);
}

//gather some stats
$smarty->assign('count_pending', $db->GetOne("select count(*) from event where status='pending'"));
$smarty->assign('count_inprogress', $db->GetOne("select count(*) from event where status='in_progress'"));
$smarty->assign('count_recent', $db->GetOne("select count(*) from event where status='completed' and (unix_timestamp(now())-unix_timestamp(processed))<3600"));
$smarty->assign('stat_recent', $db->GetOne("select avg(unix_timestamp(processed)-unix_timestamp(posted)) from event where status='completed' and (unix_timestamp(now())-unix_timestamp(processed))<3600"));

//build list of events - gather search parameters
$search_name=isset($_GET['search_name'])?trim($_GET['search_name']):'';
$smarty->assign('search_name',$search_name);

$status=isset($_GET['status'])?$_GET['status']:'';
$smarty->assign('status',$status);


$statuses=array(""=>"Any", "pending"=>"Pending", "in_progress"=>"In Progress", "completed"=>"Completed");
$smarty->assign('statuses',$statuses);

$default_start=strftime("%Y-%m-%d %H:%M:%S", time()-3600*24);
$default_end=strftime("%Y-%m-%d %H:%M:%S", time());

$search_start=isset($_GET['search_start'])?trim($_GET['search_start']):$default_start;
$search_end=isset($_GET['search_end'])?trim($_GET['search_end']):$default_end;

$smarty->assign('search_start',$search_start);
$smarty->assign('search_end',$search_end);


//perform the search
$offset=isset($_GET['offset'])?trim($_GET['offset']):0;
$count=isset($_GET['count'])?trim($_GET['count']):50;



$filter="";
$sep="where ";

if (strlen($search_start))
{
	$filter.=$sep."posted >= '$search_start'";
	$sep=" and ";
}
if (strlen($search_end))
{
	$filter.=$sep."posted <= '$search_end'";
	$sep=" and ";
}
if (strlen($status))
{
	$filter.=$sep."status = '$status'";
	$sep=" and ";
}
if (strlen($search_name))
{
	$filter.=$sep."event_name like '%$search_name%'";
	$sep=" and ";
}

$sql="select count(*) from event $filter";
$total=$db->GetOne($sql);


if (isset($_GET['next']))
	$offset=$offset+$count;
if (isset($_GET['prev']))
	$offset=max(0, $offset-$count);


$sql="select * from event $filter order by event_id desc limit $offset,$count";
$events=$db->GetAll($sql);
$smarty->assign_by_ref("events", $events);
$smarty->assign("total", $total);
$smarty->assign("offset", $offset);
$smarty->assign("count", $count);


$pages=ceil($total/$count);
$smarty->assign("pages", $pages);

$page=floor($offset/$count)+1;
$smarty->assign("page", $page);


$smarty->display('admin_events.tpl');

