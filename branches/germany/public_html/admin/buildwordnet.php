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
require_once('geograph/wordnet.inc.php');
init_session();

$USER->mustHavePerm("admin");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);




	//this takes a long time, so we output a header first of all
	$smarty->display('_std_begin.tpl');
	echo "<h3> ReBuilding Wordnet...</h3>";
	flush();
	set_time_limit(3600*24);
	


	$tim = time();
	
	$recordSet = &$db->Execute("select gridimage_id,title from gridimage where moderation_status != 'rejected'");
	
	$db->Execute("truncate wordnet1");
	$db->Execute("truncate wordnet2");
	$db->Execute("truncate wordnet3");
	$db->Execute("LOCK TABLES wordnet1 WRITE,wordnet2 WRITE,wordnet3 WRITE");
	$db->Execute("ALTER TABLE wordnet1 DISABLE KEYS");
	$db->Execute("ALTER TABLE wordnet2 DISABLE KEYS");
	$db->Execute("ALTER TABLE wordnet3 DISABLE KEYS");
	
	 
	
	while (!$recordSet->EOF) 
	{
		updateWordnet($db,$recordSet->fields['title'],'title',$recordSet->fields['gridimage_id']);
		//the comments arent searched yet anyway...
		//if ($_GET['comments']) 
		//	updateWordnet($db,$recordSet->fields['comment'],'comment',$recordSet->fields['gridimage_id']);
		if ($recordSet->fields['gridimage_id']%5000==0) {
			printf("done %d at <b>%d</b> seconds<BR>",$recordSet->fields['gridimage_id'],time()-$tim);
			flush();
		}
	
		$recordSet->MoveNext();
	}
	$recordSet->Close(); 

	$db->Execute("ALTER TABLE wordnet1 ENABLE KEYS ");
	$db->Execute("ALTER TABLE wordnet2 ENABLE KEYS ");
	$db->Execute("ALTER TABLE wordnet3 ENABLE KEYS ");
	$db->Execute("UNLOCK TABLES");
	$smarty->display('_std_end.tpl');
	exit;
	


	
?>
