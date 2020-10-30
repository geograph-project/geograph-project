<?php
/**
 * $Project: GeoGraph $
 * $Id: accountmerge.php 3438 2007-06-18 18:36:08Z barry $
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
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
init_session();

$USER->mustHavePerm("admin");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$from=isset($_POST['from'])?intval($_POST['from']):'';
$to=isset($_POST['to'])?intval($_POST['to']):'';


//do some processing?
if (isset($_POST['go']) && !empty($from) && !empty($to))
{
	//this takes a long time, so we output a header first of all
	$smarty->display('_std_begin.tpl');
	echo "<h3><a href=\"accountmerge.php\">&lt;&lt;</a> Merging accounts... ($from >> $to)</h3>";
	flush();
	set_time_limit(3600*24);

	$dups = $db->getAssoc($sql = "select gridsquare_id,count(distinct user_id) as users from gridimage where user_id in ($from,$to) and ftf>0 group by gridsquare_id having users > 1");
	if (!empty($dups)) {
//		print "<h3>ERROR</h3>";
		print "<p>there are squares with images by BOTH profiles, moving images would upset points. the process doesnt current support this!</p>";
		print "<pre>";
		print_r($dups);
		print "</pre>";
//		exit;
	}

	if (!isset($_POST['real'])) {
		$filedb=NewADOConnection($CONF['filesystem_dsn']);
		$fileroot = "/geograph_live/public_html";
	}

	$realname = $db->Quote($db->GetOne("select realname from user where user_id=$to"));

	if (!empty($_POST['ids'])) {
		if (!preg_match('/^\s*\d+(,\s*\d+)*\s*$/',$_POST['ids']))
			die("invalid id format");
		$recordSet = &$db->Execute("select * from gridimage where user_id=$from AND gridimage_id IN ({$_POST['ids']})"); //validated above to avoid SQL injection
	} else {
		$limit = 1000;
		if (!empty($_POST['limit'])) {
			$limit = intval($_POST['limit']);
		}

		$recordSet = &$db->Execute("select * from gridimage where user_id=$from LIMIT $limit");
	}

	while (!$recordSet->EOF)
	{
		$image=new GridImage;
		$image->loadFromRecordset($recordSet);

		$oldfile=$image->_getFullpath();

		if (file_exists($_SERVER['DOCUMENT_ROOT'].$oldfile))
		{
			print "<li><b>{$image->gridimage_id}</b><br>";
			$image->user_id=$to;
			$newhash=$image->_getAntiLeechHash(false);//calling false will override the cache! (otherwise doesnt deal with chaning user_id!)
			$newfile=$image->_getFullpath(false);

			echo "$oldfile to $newfile<br>";
			if (isset($_POST['real'])) {
				//could just do something like, but need to deal with orginal too
				//$image->storeImage($_SERVER['DOCUMENT_ROOT'].$oldfile, true);

				//copy everything. Dont NEED to copy thumbs, they can be recreated, but doing so avoids issues with thumbnail cache
				//also COPY, rather then MOVE, as there might still be old pags with reference to old image, lets avoid breaking them
				$oldglob = str_replace('.jpg','*',$oldfile);
				foreach (glob($_SERVER['DOCUMENT_ROOT'].$oldglob) as $file) {
					$newfile = preg_replace('/(\/0*'.$image->gridimage_id.')_(\w{8})/','$1_'.$newhash,$file);
					if (file_exists($newfile)) {
						print "$newfile already exists!<br>\n";
					} else {
						print "copying ".basename($file)." to ".basename($newfile)."<br>";
						copy($file,$newfile);
					}
				}

			} else {
				//there is no NEED to check filesystem, but being paranoid!
				$oldlike = str_replace('.jpg','%',$oldfile);
				$dir = dirname($oldfile);
				$folder_id = $filedb->getOne("SELECT folder_id FROM folder WHERE folder = '$fileroot$dir'");
				$list = $filedb->getAssoc($sql = "SELECT filename,replica_count FROM file WHERE filename LIKE '$fileroot$oldlike' AND folder_id = $folder_id");

				$oldglob = str_replace('.jpg','*',$oldfile);
				foreach (glob($_SERVER['DOCUMENT_ROOT'].$oldglob) as $file) {
					$filepath = str_replace($_SERVER['DOCUMENT_ROOT'],$fileroot,$file);
					print "<tt>$file</tt>  [File: {$list[$filepath]} replicas]<br>";
					$newfile = preg_replace('/(\/0*'.$image->gridimage_id.')_(\w{8})/','$1_'.$newhash,$file);
					if (file_exists($newfile)) {
						print "$newfile already exists!<br>\n";
					} else {
						print "copying ".basename($file)." to ".basename($newfile)."<br>";
					}
					unset($list[$filepath]);
				}

				if (!empty($list)) {
					print "<span style=color:red><hr>remaining!<br>";
					foreach ($list as $filepath => $replicas)
						print "$filepath => $replicas<br>";
					print "</span>";
				}
			}

			$sqls = array();

			if ($image->ftf>0 && isset($dups[$image->gridsquare_id])) {
				//grab the second image in the square, it MIGHT not be THIS image.
				$rows = $db->getAll("SELECT gridimage_id,ftf,grid_reference FROM gridimage INNER JOIN gridsquare USING (gridsquare_id)
					WHERE gridsquare_id = {$image->gridsquare_id} AND user_id in ($from,$to) AND ftf>0 ORDER BY seq_no DESC");

				//check there really is a second to remove!
				if (count($rows) == 2) {
					$second = $rows[0];

					if (empty($second['ftf']))
						die("unable to get ftf");

					//take the point away
					$sqls[] = "update gridimage set ftf=0 where gridimage_id = {$second['gridimage_id']}";
					$sqls[] = "update gridimage_search set ftf=0 where gridimage_id = {$second['gridimage_id']}";

					//move any other personal points up one.
					$sqls[] = "update gridimage set ftf=ftf-1,upd_timestamp=upd_timestamp where gridsquare_id = {$image->gridsquare_id} AND ftf > {$second['ftf']}";
					$sqls[] = "update gridimage_search set ftf=ftf-1,upd_timestamp=upd_timestamp where grid_reference = '{$second['grid_reference']}' AND ftf > {$second['ftf']}";
				}
			}

			$sqls[] = "update gridimage set user_id=$to where gridimage_id={$image->gridimage_id}";
			$sqls[] = "update gridimage_search set user_id=$to,realname = $realname where gridimage_id={$image->gridimage_id} AND credit_realname=0"; //dont change credited ones
			$sqls[] = "update gridimage_search set user_id=$to where gridimage_id={$image->gridimage_id} AND credit_realname=1"; //these leave specific credit

			$sqls[] = "update gridimage_tag set user_id=$to where gridimage_id={$image->gridimage_id} AND user_id=$from";
			$sqls[] = "update gridimage_snippet set user_id=$to where gridimage_id={$image->gridimage_id} AND user_id=$from";

			foreach ($sqls as $sql) {
				print "$sql;";
				if (isset($_POST['real'])) {
					$db->Execute($sql);
					$rows = mysql_affected_rows($db->_connectionID);
					print " #$rows rows affected";
				}
				print "<br>";
			}

			print "</li>";
			flush();

		}
		else
		{
			echo "<li>skipping $oldfile (not found)</li>";
		}
		$recordSet->MoveNext();
	}
	$recordSet->Close();



	$sqls = array();

	if (!$db->getOne("SELECT COUNT(*) FROM gridimage WHERE user_id = $from")) {
		//if they have no images left, easy just delete their record.
		//the record of the $to user will be updated as gridiamge_search has updated timestamp

		 $sqls[] = "delete from user_stat where user_id = $from";

		//move any collections too - the old account is effectively 'inactive'
		 $sqls[] = "update tag set user_id=$to where user_id=$from";
	         $sqls[] = "update snippet set user_id=$to where user_id=$from";
	         $sqls[] = "update blog set user_id=$to where user_id=$from";
	         $sqls[] = "update article set user_id=$to where user_id=$from";

		//fix any tickets, keep them attached so they 'owner' tickets.
	         $sqls[] = "update gridimage_ticket t inner join gridimage g using (gridimage_id)
				set t.user_id=$to where t.user_id=$from and g.user_id=$to";
	         $sqls[] = "update gridimage_ticket_archive t inner join gridimage g using (gridimage_id)
				set t.user_id=$to where t.user_id=$from and g.user_id=$to";

	         $sqls[] = "update gridimage_ticket_comment c inner join gridimage_ticket t using (gridimage_ticket_id) inner join gridimage g using (gridimage_id)
				set c.user_id=$to where c.user_id=$from and g.user_id=$to";

	} else {
		//if there are images (eg we only moved some) - then much more tricky,
		//TODO - maybe could `touch` one of the images to trigger a recalculation??

	}

	print "<hr>";
	foreach ($sqls as $sql) {
		print "$sql;";
		if (isset($_POST['real'])) {
			$db->Execute($sql);
			$rows = mysql_affected_rows($db->_connectionID);
			print " #$rows rows affected";
		}
		print "<br>";
	}

	$smarty->display('_std_end.tpl');
	exit;
}


$smarty->assign('from', $from);
$smarty->assign('to', $to);
$smarty->display('accountmerge.tpl');


