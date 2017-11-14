<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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

$amount=isset($_POST['amount'])?$_POST['amount']:100;
$radius=isset($_POST['radius'])?$_POST['radius']:0.5;
$threshold=isset($_POST['threshold'])?$_POST['threshold']:3;
$w=isset($_POST['w'])?$_POST['w']:120;
$h=isset($_POST['h'])?$_POST['h']:80;

	function makeTimeStamp($name)
	{
	   return mktime(0, 0, 0, $_POST[$name.'Month'], $_POST[$name.'Day'], $_POST[$name.'Year']);
	}

//do some processing?
if (isset($_POST['go']))
{
	//this takes a long time, so we output a header first of all
	$smarty->display('_std_begin.tpl');
	echo "<h3><a href=\"recreatethumbs.php\">&lt;&lt;</a> Creating Thumbnails...</h3>";
	flush();
	set_time_limit(3600*24);

		$datefrom = makeTimeStamp('datefrom');
		$dateto = makeTimeStamp('dateto');
		$funct = $_GET['function'];
		
		
print "<PRE>"; print_r($_POST); print "</PRE>";		
		print "$datefrom - $dateto<BR>";
	
	$recordSet = &$db->Execute("select * from gridimage");
	while (!$recordSet->EOF) 
	{
		
		$image=new GridImage;
		$image->loadFromRecordset($recordSet);
		

		
		$oldfile=$image->_getFullpath();
		
		if (file_exists($_SERVER['DOCUMENT_ROOT'].$oldfile))
		{
			$filedate = filemtime( $_SERVER['DOCUMENT_ROOT'].$oldfile);
			printf("%c = %d %d %d<BR>",$oldfile,$filedate,$filedate - $datefrom,$dateto -$filedate);
			if ($filedate > $datefrom && $filedate <= $dateto) {
			
				
			
	##			$CONF['photo_hashing_secret']=$to;
	#			$image->storeImage($_SERVER['DOCUMENT_ROOT'].$oldfile, true);
	#		
	#			$newfile=$image->_getFullpath();
				echo "<li>renamed $oldfile<br>to $newfile</li>";
				flush();
			}
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

$smarty->assign('amount', $amount);
$smarty->assign('radius', $radius);
$smarty->assign('threshold', $threshold);
$smarty->assign('w', $w);
$smarty->assign('h', $h);

$smarty->display('admin_recreatethumbs.tpl');

	
?>
