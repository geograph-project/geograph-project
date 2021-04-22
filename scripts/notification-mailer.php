<?php
/**
 * $Project: GeoGraph $
 * $Id: notification-mailer.php 8717 2018-02-21 19:13:16Z barry $
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2013 Barry Hunter (geo@barryhunter.co.uk)
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

//these are the arguments we expect
$param=array(
        'schedule'=>'weekly',
        'action'=>'dummy',
);

$HELP = <<<ENDHELP
    --schedule=<event>   : which event to run (weekly/daily/hourly)
    --help              : show this message
ENDHELP;


chdir(__DIR__);
require "./_scripts.inc.php";

############################################

	require_once "3rdparty/class.phpmailer.php";
	require_once "3rdparty/class.smtp.php";

	$mail = new PHPMailer;

	#########################
	if ($param['action'] == 'fake')
		$mail->SMTPDebug = 3;                               // Enable verbose debug output

	$mail->XMailer = 'x'; //used to SKIP the header

	if (!empty($CONF['smtp_host'])) {
		$mail->isSMTP();
		$mail->Host = $CONF['smtp_host'];
		if (!empty($CONF['smtp_user'])) {
			$mail->SMTPAuth = true;
			$mail->Username = $CONF['smtp_user'];
			$mail->Password = $CONF['smtp_pass'];
		}
		if ($CONF['smtp_port']> 25)
			$mail->SMTPSecure = 'tls';                    // Enable TLS encryption, `ssl` also accepted
		$mail->Port = $CONF['smtp_port'];                     // TCP port to connect to

		#########################

		$mail->setFrom($CONF['smtp_from'],'',true);//set sender too
	} else {
		$mail->setFrom($CONF['minibb_admin_email'],'',true);
	}

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

############################################

$schedule = $param['schedule'];

if ($schedule == 'weekly') {
	$crit = $db->Quote($db->getOne("SELECT DATE_SUB(NOW(),INTERVAL 7 DAY)"));
} elseif ($schedule == 'daily') {
        $crit = $db->Quote($db->getOne("SELECT DATE_SUB(NOW(),INTERVAL 24 HOUR)"));
} elseif ($schedule == 'monthly') {
        $crit = $db->Quote($db->getOne("SELECT DATE_SUB(NOW(),INTERVAL 1 MONTH)"));
}

//--------------------------------------------
// get a list of items to search and which users are interested

$map = array();
foreach ($db->getAll("select user_id,value from user_preference where pkey = 'notification.myphotos' and value LIKE '$schedule|%'") as $row) {
	@list($sch,$items,$date) = explode('|',$row['value']);
	foreach (explode(',',$items) as $item) {
		$map[$item][$row['user_id']] = true;
	}
}

//--------------------------------------------
//build the list of SQL queries to actully lookup the data - taking into account opted into users

$todo = array();
$columns = "gi.gridimage_id,gi.user_id,gi.grid_reference,gi.title";
if (!empty($map['featured'])) {
	$ids = implode(',',array_keys($map['featured']));
	$todo[] = array(
		'title' => 'Featured Images',
		'when' => 'featured',
		'sql' => "SELECT $columns,showday AS date FROM gridimage_search gi INNER JOIN gridimage_daily USING (gridimage_id)
				WHERE showday IS NOT NULL AND showday > $crit AND showday < NOW() AND user_id IN ($ids) ORDER BY showday DESC"
	);
}
if (!empty($map['gallery'])) {
        $ids = implode(',',array_keys($map['gallery']));
        $todo[] = array(
                'title' => 'Showcase Gallery',
		'when' => 'featured',
                'sql' => "SELECT $columns,showday AS date FROM gridimage_search gi INNER JOIN gallery_ids ON (id = gridimage_id)
				WHERE user_id IN ($ids) AND showday > $crit ORDER BY showday DESC"
        );
        $todo[] = array(
                'title' => 'Showcase Gallery',
		'when' => 'added',
                'sql' => "SELECT $columns,fetched AS date FROM gridimage_search gi INNER JOIN gallery_ids ON (id = gridimage_id)
				WHERE gallery_ids.baysian > 3.5 AND user_id IN ($ids) AND fetched > $crit AND showday IS NULL ORDER BY fetched DESC"
        );
}

if (!empty($map['collection'])) {
        $ids = implode(',',array_keys($map['collection']));
        $todo[] = array(
                'title' => 'Used in Collection',
		'when' => 'added',
                'sql' => "SELECT $columns,gc.created AS date,c.title AS special_title,url as special_url FROM gridimage_search gi INNER JOIN gridimage_content gc USING (gridimage_id) INNER JOIN content c USING (content_id)
				WHERE gc.created > $crit AND gi.user_id IN ($ids) ORDER BY content_id,gridimage_id"
        );
}

if (!empty($map['forum'])) {
        $ids = implode(',',array_keys($map['forum']));
        $todo[] = array(
                'title' => 'Used in Forum Threads/Galleries',
                'when' => 'posted',
		'sql' => "SELECT $columns,post_time AS date,topic_title AS special_title,CONCAT('/discuss/?action=vpost&amp;forum=',p.forum_id,'&amp;topic=',t.topic_id,'&amp;post=',post_id) as special_url
				FROM gridimage_search gi INNER JOIN gridimage_post gp USING (gridimage_id) INNER JOIN geobb_topics t USING (topic_id) INNER JOIN geobb_posts p USING (post_id)
				WHERE p.post_time > $crit AND  gi.user_id IN ($ids) ORDER BY t.topic_id,post_id"
	);
	//todo, split by forum_id?
}

if (!empty($map['thumbed'])) {
        $ids = implode(',',array_keys($map['thumbed']));
        $todo[] = array(
                'title' => 'Thumbed',
                'when' => 'last voted',
                'sql' => "SELECT $columns,last_vote AS date FROM gridimage_search gi INNER JOIN vote_stat ON (gridimage_id=id)
				WHERE last_vote > $crit AND gi.user_id IN ($ids) AND type in ('img','desc') ORDER BY last_vote"
	);
}

if (!empty($map['squares'])) {
        $ids = implode(',',array_keys($map['squares']));
        $todo[] = array(
                'title' => 'Images in squares you\'ve submitted to',
                'when' => 'submitted',
                'sql' => "SELECT $columns,gi.submitted AS date,CONCAT(gi.title,' by ',gi.realname) AS title,CONCAT(gi.grid_reference,IF(name is not null,concat(' near ',name,', ',localities),'')) AS special_title, CONCAT('/gridref/',gi.grid_reference) AS special_url, ug.user_id as user_id
                        FROM user_gridsquare ug INNER JOIN gridimage_search gi USING (grid_reference) INNER JOIN gridimage g2 USING (gridimage_id) LEFT JOIN placename_index ON (gr=grid_reference)
                        WHERE gi.submitted > DATE_SUB($crit, INTERVAL 3 DAY) AND g2.moderated > $crit AND ug.user_id IN ($ids) AND gi.user_id != ug.user_id GROUP BY ug.user_id,gridimage_id ORDER BY gi.grid_reference,gridimage_id"
			//used moderated as the filter, but include submitted in there too, as it has an index. (because when a mod backlog, images would be missed on daily schedule)
        );
}

if (!empty($map['snippets'])) {
        $ids = implode(',',array_keys($map['snippets']));
        $todo[] = array(
                'title' => 'Images using your Shared Descriptions',
                'when' => 'added',
                'sql' => "SELECT $columns,gs.created AS date,CONCAT(gi.title,' by ',gi.realname) AS title,s.title AS special_title, CONCAT('/snippet/',snippet_id) AS special_url, s.user_id as user_id
                        FROM snippet s INNER JOIN gridimage_snippet gs USING (snippet_id) INNER JOIN gridimage_search gi USING (gridimage_id)
                        WHERE gs.created > $crit AND s.user_id IN ($ids) AND gi.user_id != s.user_id GROUP BY snippet_id,gridimage_id"
        );
}

if (!empty($map['photos'])) {
	$ids = implode(',',array_keys($map['photos']));
        $todo[] = array(
                'title' => 'Linked from other images',
                'when' => 'found',
		'sql' => "SELECT $columns,b.created AS date,CONCAT(f.title,' by ',f.realname) AS special_title, CONCAT('/photo/',f.gridimage_id) AS special_url
			FROM gridimage_backlink b INNER JOIN gridimage_search gi ON (gi.gridimage_id = b.gridimage_id) INNER JOIN gridimage_search f ON (f.gridimage_id = b.from_gridimage_id)
			WHERE b.created > $crit AND gi.user_id IN ($ids) AND f.user_id NOT in ($ids) ORDER BY f.gridimage_id,b.created"
	);
}


unset($map);

//--------------------------------------------
// run the actual queries and compile list of notifications sorted by user_id

$results = array();
foreach ($todo as $row) {
	if ($param['action'] != 'send')
		print "{$row['sql']}\n";
	if ($images = $db->getAll($row['sql'])) {
		if ($param['action'] != 'send')
			print "found ".count($images)." images\n";
		foreach ($images as $image) {
			$image['when'] = $row['when'];
			$image['date'] = substr($image['date'],0,10);
			$results[$image['user_id']][$row['title']][] = $image;
		}
	}
	if ($param['action'] != 'send')
		print "\n\n";
}

unset($todo);

//--------------------------------------------
// actully send some emails!

$subject = "[Geograph] Your ".ucfirst($schedule)." Photo Usage Notification";

if ($param['action'] == 'fake') {
	$to = "geo@barryhunter.co.uk";
	$subject .= " ".date('r');
}

if (!empty($result))
foreach ($results as $user_id => $collections) {
	$html = $body = '';
	foreach ($collections as $title => $images) {
		$html .= "<h3>$title</h3>\n";
		$body .= str_repeat('=',70)."\n$title\n\n";
		$last = '';
		foreach ($images as $idx => $image) {
			if (!empty($image['special_title']) && $last != $image['special_title']) {
				$body .= html_entity_decode("{$image['special_title']}, http://{$param['config']}{$image['special_url']}\n\n");
				if ($image['when'] != 'posted') {
					//ugly hack because the forum is already entity encoding
					$image['special_title'] = htmlentities2($image['special_title']);
				}
				$html .= "<b><a href=\"http://{$param['config']}{$image['special_url']}\">{$image['special_title']}</a></b><br/>\n";
				$last = $image['special_title'];
			}
			$body .= "* {$image['title']}, http://{$param['config']}/photo/{$image['gridimage_id']} {$image['when']} {$image['date']}\n\n";
			$html .= "&middot; <a href=\"http://{$param['config']}/photo/{$image['gridimage_id']}\">".htmlentities2($image['title'])."</a> {$image['when']} {$image['date']}<br/>\n";
		}
	}
	if ($param['action'] == 'send') {
		$to = $db->getOne("SELECT email FROM user WHERE user_id = ".intval($user_id));
		if (empty($to)) {
			continue; //skips this iteration
		}
	} else {
		$realname = $db->getOne("SELECT realname FROM user WHERE user_id = ".intval($user_id));

		$html = "to $realname<br>".$html;

		print "To: $user_id $to\n\n";
		print "$body\n";
		print "-------------------\n";
	}

	$body = "To change your notification preferences, visit http://{$param['config']}/profile.php?notifications=1\n\n".$body;
	$html = "To change your notification preferences, visit <a href=\"http://{$param['config']}/profile.php?notifications=1\">this page</a><hr/>\n".

	"<br>This is still a experimental feature, to provide feedback, please used <a href=\"http://www.geograph.org.uk/discuss/index.php?&action=vthread&forum=12&topic=21685\">This thread</a><hr>".$html;

	if ($param['action'] == 'send' || $param['action'] == 'fake') {

		$mail->addAddress($to);

		$mail->Subject = $subject;

		$mail->IsHTML(true);
		$mail->Body = $html; //if using isHTML will be the HTML verson, AltBody, will be plain text!
		$mail->AltBody = $body; //plain text version

		$mail->send();

		$mail->clearAllRecipients(); //because more added next time
	}
}

if ($param['action'] == 'send' && count($results) > 2) {
	file_get_contents("http://www.geograph.org.uk/project/systemtask.php?id[]=45&spotcheck=1&api=1&method=POST");
}
