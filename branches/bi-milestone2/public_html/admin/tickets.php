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

	$hours = (isset($_GET['hours']) && is_numeric($_GET['hours']))?intval($_GET['hours']):24;

	$ticket=new GridImageTroubleTicket($gridimage_ticket_id);
	if ($ticket->isValid())
	{
		$ticket->setDefer("DATE_ADD(NOW(), INTERVAL $hours HOUR)");
		echo "Ticket Deferred for $hours hours";		
	}
	else
	{
		echo "FAIL";
	}
	
	exit;
}

if (!empty($_GET['Submit'])) {
	//if changing state, release locks
	
	$db->Execute("DELETE FROM gridimage_moderation_lock WHERE user_id = {$USER->user_id}");
	
	header("Location: /admin/tickets.php?".str_replace('Submit='.$_GET['Submit'],'',$_SERVER['QUERY_STRING']));
	exit;
}


#############################
# form input

$limit = (isset($_GET['limit']) && is_numeric($_GET['limit']))?min(100,intval($_GET['limit'])):50;
$page = (isset($_GET['page']) && is_numeric($_GET['page']))?min(100,intval($_GET['page'])):0;
if ($page) {
	$limit = sprintf("%d,%d",($page -1)* $limit,$limit);	
}

$rev = (isset($_GET['rev']))?'desc':'';

$type = (isset($_GET['type']) && preg_match('/^\w+$/' , $_GET['type']))?$_GET['type']:'pending';
$modifer = (isset($_GET['modifer']) && preg_match('/^\w+$/' , $_GET['modifer']))?$_GET['modifer']:'recent';
$theme = (isset($_GET['theme']) && preg_match('/^\w+$/' , $_GET['theme']))?$_GET['theme']:'any';
$variation = (isset($_GET['variation']) && preg_match('/^\w+$/' , $_GET['variation']))?$_GET['variation']:'any';

$major = (isset($_GET['a']))?1:0;
$minor = (isset($_GET['i']))?1:0;
if (empty($major) && empty($minor)) {
	$major = 1;$minor = 1;
}
$defer = (isset($_GET['defer']))?1:0;
$locked = (isset($_GET['locked']))?1:0;


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

#################
# setup type

if (isset($_GET['moderator']) && ($mid = intval($_GET['moderator']))) {
	$sql_where .= " and t.moderator_id=$mid";
	$smarty->assign('moderator', 1);

} elseif (isset($_GET['image_moderator']) && ($mid = intval($_GET['image_moderator']))) {
	$sql_where .= " and i.moderator_id=$mid";
	$smarty->assign('moderator', 1);

} elseif (isset($_GET['user_id']) && ($mid = intval($_GET['user_id']))) {
	$sql_where .= " and i.user_id=$mid";
	
} elseif (isset($_GET['suggestor']) && ($mid = intval($_GET['suggestor']))) {
	$sql_where .= " and t.user_id=$mid";
}

if (!empty($_GET['q'])) {
	if (isset($_GET['legacy'])) {
		if (strpos($_GET['q'],'!') === 0) {
			$q = $db->Quote("%".preg_replace('/^!/','',$_GET['q'])."%");
			$sql_where .= " and not (t.notes like $q or i.title like $q)";
		} else {
			$q = $db->Quote("%{$_GET['q']}%");
			$sql_where .= " and (t.notes like $q or i.title like $q)";
		}
	} else {
		$sphinx = new sphinxwrapper($_GET['q']);

		$sphinx->pageSize = $pgsize = 100;

		$ids = $sphinx->returnIds(1,'tickets');	
				
		if (!empty($ids) && count($ids)) {
			$sql_where .= " and t.gridimage_ticket_id IN(".join(",",$ids).")";
		} else {
			$sql_where .= " and 0";
		}
		$smarty->assign('q', $sphinx->qclean);

	}
}

#################
# available values

$types = array('pending'=>'New Tickets','open'=>"Open Tickets",'closed'=>"Closed Tickets",'ongoing'=>'New or Open');
$modifers = array('recent'=>'All','24'=>"over 24 hours old",'7'=>"over 7 days old");
$themes = array('any'=>'Any','tmod'=>"on ticket I moderating/ed",'mod'=>"on images I moderated",'comment'=>"tickets I have commented on",'suggest'=>"tickets I suggested",'all'=>'any involvement');
$variations = array('any'=>'Any','own'=>"suggested on own images",'comment'=>"has left comment");

#################
# setup type

if ($type != 'pending') {
	$columns .= ",moderator.realname as moderator";
	$tables .= " left join user as moderator on (moderator.user_id=t.moderator_id)";
		$locks[] = "user moderator READ";

	$smarty->assign('col_moderator', 1);

	if ($type == 'open') { 
		$where_crit = "t.moderator_id>0 and t.status in ('pending','open')";
	} elseif ($type == 'closed') {
		$where_crit = "t.status='closed'";
	} elseif ($type == 'all') {
		$where_crit = "1";
	} else {//ongoing
		$where_crit = "t.status in ('pending','open')";
	}
	
	$rev = ($rev)?'':'desc';
	
} else {
	$type = 'pending';
	
	$where_crit = " t.moderator_id=0 and t.status in ('pending','open')";

	
	$sql_where .= " and i.user_id != {$USER->user_id}";
}

#################

if (!$defer) {
	//exclude deferred
	$sql_where .= " and deferred < date_sub(NOW(),INTERVAL 24 HOUR)";
}

#################

if ($modifer == '24') {
	$sql_where .= " and suggested < date_sub(NOW(),INTERVAL 24 HOUR)";

} elseif ($modifer == '7') {
	$sql_where .= " and suggested < date_sub(NOW(),INTERVAL 7 DAY)";

} else {
	$modifer = 'recent';
}

#################

if ($theme == 'all') {
	$sql_where .= " and {$USER->user_id} in (i.moderator_id,t.moderator_id,c.user_id,t.user_id)";

} elseif ($theme == 'mod') {
	$sql_where .= " and i.moderator_id = {$USER->user_id}";

} elseif ($theme == 'tmod') {
	$sql_where .= " and t.moderator_id = {$USER->user_id}";

} elseif ($theme == 'comment') {
	$sql_where .= " and c.user_id = {$USER->user_id}";

} elseif ($theme == 'suggest') {
	$sql_where .= " and t.user_id = {$USER->user_id}";

} else {
	$theme = 'any';
}

#################

if ($variation == 'own') {
	$sql_where .= " and t.user_id = i.user_id";

} elseif ($variation == 'comment') {
	$sql_where .= " and c.user_id = i.user_id";

} else {
	$variation = 'any';
}

if (empty($major)) {
	$sql_where .= " and type = 'minor'";
} elseif(empty($minor)) {
	$sql_where .= " and type = 'normal'";
}

#################

$title = "Showing: ".$modifers[$modifer].", ".$types[$type];
if ($defer) {		
	$title .= ", including Deferred";
}
if ($locked) {		
	$title = "<span style='color:red'>$title, including Locked</span>";
}

#################
# put it all together...

$smarty->assign('title', $title);

$info = $db->getAssoc("select moderator_id>0,count(*) as c from gridimage_ticket where status in ('pending','open') and deferred < NOW() group by moderator_id=0");

$types['pending'] .= " [~ {$info['0']}]";
$types['open'] .= " [~ {$info['1']}]";



$smarty->assign('type', $type);
$smarty->assign('modifer', $modifer);
$smarty->assign('theme', $theme);
$smarty->assign('variation', $variation);
$smarty->assign('minor', $minor);
$smarty->assign('major', $major);
$smarty->assign('defer', $defer);
$smarty->assign('locked', $locked);

$smarty->assign_by_ref('types', $types);
$smarty->assign_by_ref('modifers', $modifers);
$smarty->assign_by_ref('themes', $themes);
$smarty->assign_by_ref('variations', $variations);

$smarty->assign('query_string', $_SERVER['QUERY_STRING']);

#################
# put it all together...

$available = "(l.gridimage_id is null OR 
				(l.user_id = {$USER->user_id} AND lock_type = 'modding') OR
				(l.user_id != {$USER->user_id} AND lock_type = 'cantmod')
		)";

if (empty($_GET['locked'])) {

	$db->Execute("LOCK TABLES ".implode(',',$locks));
	
	$sql_where .= " and $available";
	
	$columns .= ", 1 as available";
} else {
	$columns .= ", $available as available";
}

$newtickets=$db->GetAll($sql = 
	"select t.*,suggester.realname as suggester, (i.user_id = t.user_id) as ownimage,
		submitter.realname as submitter, submitter.ticket_option as submitter_ticket_option, (submitter.rights LIKE '%dormant%') as submitter_dormant,
		i.title, DATEDIFF(NOW(),t.updated) as days,
		group_concat(if(c.user_id=i.user_id,c.comment,null)) as submitter_comment,
		group_concat(if(c.user_id=t.user_id,c.comment,null)) as suggester_comment
		$columns
	from gridimage_ticket as t
	inner join user as suggester on (suggester.user_id=t.user_id)
	inner join gridimage as i on (t.gridimage_id=i.gridimage_id)
	inner join user as submitter on (submitter.user_id=i.user_id)
	$tables
	left join gridimage_moderation_lock as l
		on(i.gridimage_id=l.gridimage_id and lock_obtained > date_sub(NOW(),INTERVAL 1 HOUR) )
	left join gridimage_ticket_comment as c
		on(c.gridimage_ticket_id=t.gridimage_ticket_id)
	where $where_crit $sql_where
		
	group by t.gridimage_ticket_id
	order by t.suggested $rev
	limit $limit");
if (!empty($_GET['debug']))
	print $sql;

$smarty->assign_by_ref('newtickets', $newtickets);


if (empty($_GET['locked'])) {
	#################
	# lock images

	foreach ($newtickets as $i => $row) {
		$db->Execute("REPLACE INTO gridimage_moderation_lock SET user_id = {$USER->user_id}, gridimage_id = {$row['gridimage_id']}");
	}


	#############################

	$db->Execute("UNLOCK TABLES");

}
#############################

$template = (!empty($_GET['sidebar']))?'admin_tickets_sidebar.tpl':'admin_tickets.tpl';
$smarty->display($template);

	
?>
