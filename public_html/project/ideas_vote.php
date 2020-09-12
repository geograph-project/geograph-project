<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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

if (empty($CONF['forums'])) {
	$smarty = new GeographPage;
        $smarty->display('static_404.tpl');
        exit;
}

init_session();

$smarty = new GeographPage;
$USER->mustHavePerm('basic');

$cacheid = $USER->hasPerm('basic')?$USER->user_id:0;
$cacheid .= '|'.md5(implode(',',$_GET));

$isadmin=$USER->hasPerm('moderator')?1:0;
$smarty->assign_by_ref('isadmin', $isadmin);

$template = 'project_ideas_vote.tpl';

if (!empty($_POST['results'])) {
	$db=GeographDatabaseConnection(false);

	$group = 0;
	$vote = 1;
        foreach (explode("\n",str_replace("\r",'',$_POST['results'])) as $line) {
                if (empty($line)) {
			$group++;
			$vote=0;
		} else {
			list($name,$id,$canon) = explode(' | ',$line);

			$updates = array();
			$updates[] = "project_idea_id = ".intval($id);
			$updates[] = "user_id = {$USER->user_id}";
			$updates[] = "vote = $vote";

			//we need to insert even vote=0 so that it can be used to move items from left to right (to v=0)
			$db->Execute($sql = 'INSERT INTO project_idea_vote SET created=NOW(),'.implode(',',$updates).' ON DUPLICATE KEY UPDATE '.implode(',',$updates));

			if ($group==0)
				$vote++;
                }
	}


}


if (true)
{
	if (empty($db))
		$db=GeographDatabaseConnection(false);
	$prev_fetch_mode = $ADODB_FETCH_MODE;
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	if (!empty($_GET['add'])) {
		$auto = intval($_GET['add']);
		$smarty->assign("autoadd",auto);
	} else
		$auto = 0;


	$list = $db->getAll($sql = "
	select i.*,realname,
		sum(item_type='pledge') as pledges,sum(item_type='reason') as reasons,max(t.created) as activity,
		IF(i.project_idea_id = $auto,0.5,v.vote) as mine
	from project_idea i
		left join user using (user_id)
		left join project_idea_item t on (t.project_idea_id = i.project_idea_id and t.approved=1)
		left join project_idea_vote v on (v.project_idea_id = i.project_idea_id and v.user_id = {$USER->user_id})
	where ((i.approved = 1 and i.published < now())
		or i.user_id = {$USER->user_id}
		or ($isadmin and i.approved != -1)
		)
		and status NOT IN('invalid','complete')
	group by i.project_idea_id
	order by IF(mine>0,mine,9999999),created");

	$smarty->assign_by_ref('ideas', $list);
}

$smarty->display($template, $cacheid);

