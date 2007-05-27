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
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
init_session();

$USER->mustHavePerm("admin");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);

$from=isset($_POST['from'])?$_POST['from']:$CONF['photo_hashing_secret'];
$to=isset($_POST['to'])?$_POST['to']:$CONF['photo_hashing_secret'];


//do some processing?
if (isset($_POST['go']))
{
	//this takes a long time, so we output a header first of all
	$smarty->display('_std_begin.tpl');
	echo "<h3><a href=\"hashchanger.php\">&lt;&lt;</a> Changing image hashes...</h3>";
	flush();
	set_time_limit(3600*24);
	
	$recordSet = &$db->Execute("select * from gridimage");
	while (!$recordSet->EOF) 
	{
		
		$image=new GridImage;
		$image->loadFromRecordset($recordSet);
		
		$CONF['photo_hashing_secret']=$from;
		$oldfile=$image->_getFullpath();
		
		if ($oldfile != '/photos/error.jpg' && file_exists($_SERVER['DOCUMENT_ROOT'].$oldfile))
		{
			$CONF['photo_hashing_secret']=$to;
			$image->storeImage($_SERVER['DOCUMENT_ROOT'].$oldfile, true);
		
			$newfile=$image->_getFullpath();
			echo "<li>renamed $oldfile<br>to $newfile</li>";
			flush();
		}
		else
		{
			echo "<li>skipping $oldfile (not found)</li>";
		
		}
	
		$recordSet->MoveNext();
	}
	$recordSet->Close(); 
	
	$smarty->display('_std_end.tpl');
	exit;
}


$smarty->assign('from', $from);
$smarty->assign('to', $to);
$smarty->display('hashchanger.tpl');

	
?>
