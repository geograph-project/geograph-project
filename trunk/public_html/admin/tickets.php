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

$USER->mustHavePerm("ticketmod");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);

if (!empty($_GET['relinqush'])) {
	$db->Execute("UPDATE user SET rights = REPLACE(rights,'ticketmod','') WHERE user_id = {$USER->user_id}");
	
	//reload the user object
	$_SESSION['user'] =& new GeographUser($USER->user_id);
	
	header("Location: /profile.php?edit=1");

} 

if (isset($_GET['gridimage_ticket_id']))
{
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/gridimagetroubleticket.class.php');

	//user may have an expired session, or playing silly buggers,
	//either way, we want to check for admin status on the session
	$gridimage_ticket_id=intval($_GET['gridimage_ticket_id']);

	$ticket=new GridImageTroubleTicket($gridimage_ticket_id);
	if ($ticket->isValid())
	{
		$ticket->setDefer('NOW()');
		echo "Ticket Deferred for 24 hours";		
	}
	else
	{
		echo "FAIL";
	}
	
	exit;
}


#############################
# form input

$limit = (isset($_GET['limit']) && is_numeric($_GET['limit']))?min(100,intval($_GET['limit'])):50;

$rev = (isset($_GET['rev']))?'desc':'';

$type = (isset($_GET['type']) && preg_match('/^\w+$/' , $_GET['type']))?$_GET['type']:'pending';
$modifer = (isset($_GET['modifer']) && preg_match('/^\w+$/' , $_GET['modifer']))?$_GET['modifer']:'recent';


#############################
# defaults

$locks = array();
$locks[] = "gridimage_moderation_lock WRITE";
$locks[] = "gridimage_moderation_lock l WRITE";
$locks[] = "gridimage_ticket_comment as c WRITE";
$locks[] = "gridimage_ticket t READ"; 
$locks[] = "user suggester READ";
$locks[] = "gridimage i READ";
$locks[] = "user as submitter READ";


$columns = ''; 
$tables = '';
$sql_where = '';

$where_crit = " t.moderator_id=0 and t.status<>'closed'";

#################
# setup type

if (isset($_GET['moderator'])) {
	$USER->mustHavePerm('admin');
	
	$mid = intval($_GET['moderator']);
	
	if ($mid != 0) {
		$sql_where .= " and t.moderator_id=$mid";
	}
	
	$smarty->assign('moderator', 1);

} elseif (isset($_GET['user_id'])) {
	$USER->mustHavePerm('admin');
	
	$mid = intval($_GET['user_id']);
	
	if ($mid != 0) {
		$sql_where .= " and i.user_id=$mid";
	}
} 

#################
# available values

$types = array('pending'=>'New Tickets','open'=>"Open Tickets",'closed'=>"Closed Tickets");
$modifers = array('recent'=>'Recent','24'=>"over 24 hours old",'7'=>"over 7 days old");

#################
# setup type

if ($type == 'open') {
	$columns .= ",moderator.realname as moderator";
	$tables .= " left join user as moderator on (moderator.user_id=t.moderator_id)";
		$locks[] = "user moderator READ";

	$smarty->assign('col_moderator', 1);

	$where_crit = "t.moderator_id>0 and t.status<>'closed'";
	
	if (!empty($_GET['defer'])) {		
		$smarty->assign('defer', 1);
	} else {
		//exclude deferred
		$sql_where .= " and deferred < date_sub(NOW(),INTERVAL 24 HOUR)";
	}

} elseif ($type == 'closed') {
	$columns .= ",moderator.realname as moderator";
	$tables .= " left join user as moderator on (moderator.user_id=t.moderator_id)";
		$locks[] = "user moderator READ";
		
	$smarty->assign('col_moderator', 1);

	$where_crit = "t.status='closed'";
	
} else {
	$type = 'pending';
	
	if (!empty($_GET['defer'])) {		
		$smarty->assign('defer', 1);
	} else {
		//exclude deferred
		$sql_where .= " and deferred < date_sub(NOW(),INTERVAL 24 HOUR)";
	}
	
}

if ($modifer == '24') {
	$sql_where .= " and suggested < date_sub(NOW(),INTERVAL 24 HOUR)";

} elseif ($modifer == '7') {
	$sql_where .= " and suggested < date_sub(NOW(),INTERVAL 7 DAY)";

} else {
	$modifer = 'recent';
}

$title = "Showing: ".$modifers[$modifer].", ".$types[$type];
if (!empty($_GET['defer'])) {		
	$title .= ", included Deferred";
}

#################
# put it all together...

if (!empty($_GET['debug']))
	print "$where_crit $sql_where";

$smarty->assign('type', $type);
$smarty->assign('modifer', $modifer);
$smarty->assign('title', $title);

$smarty->assign_by_ref('modifers', $modifers);
$smarty->assign_by_ref('types', $types);

$smarty->assign('query_string', $_SERVER['QUERY_STRING']);

#################
# put it all together...

$db->Execute("LOCK TABLES ".implode(',',$locks));

$newtickets=$db->GetAll(
	"select t.*,suggester.realname as suggester,
		submitter.realname as submitter, i.title, 
		count(c.gridimage_ticket_comment_id) as submitter_comments,
		c.comment as submitter_comment
		$columns
	from gridimage_ticket as t
	inner join user as suggester on (suggester.user_id=t.user_id)
	inner join gridimage as i on (t.gridimage_id=i.gridimage_id)
	inner join user as submitter on (submitter.user_id=i.user_id)
	$tables
	left join gridimage_moderation_lock as l
		on(i.gridimage_id=l.gridimage_id and lock_obtained > date_sub(NOW(),INTERVAL 1 HOUR) )
	left join gridimage_ticket_comment as c
		on(c.gridimage_ticket_id=t.gridimage_ticket_id and c.user_id=i.user_id )
	where $where_crit $sql_where
		and (l.gridimage_id is null OR 
				(l.user_id = {$USER->user_id} AND lock_type = 'modding') OR
				(l.user_id != {$USER->user_id} AND lock_type = 'cantmod')
		)
	group by t.gridimage_ticket_id
	order by t.suggested $rev
	limit $limit");

$smarty->assign_by_ref('newtickets', $newtickets);

#################
# lock images

foreach ($newtickets as $i => $row) {
	$db->Execute("REPLACE INTO gridimage_moderation_lock SET user_id = {$USER->user_id}, gridimage_id = {$row['gridimage_id']}");
}


#############################

$db->Execute("UNLOCK TABLES");


#############################

$template = (!empty($_GET['sidebar']))?'admin_tickets_sidebar.tpl':'admin_tickets.tpl';
$smarty->display($template);

	
?>
