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
init_session();

$USER->mustHavePerm("admin");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);




	//this takes a long time, so we output a header first of all
	$smarty->display('_std_begin.tpl');
	echo "<h3> RBuilding Wordnet...</h3>";
	flush();
	
function doaddw2($phrase) {
	global $w2;
	$w2[$phrase]++; 
}

function doaddw3($phrase) {
	global $w3;
	$w3[$phrase]++; 
}
	
function docount($text,$field,$id) {
	global $db,$w1,$w2,$w3;
	
	$alltext = strtolower(preg_replace('/[^a-zA-Z0-9]+/',' ',str_replace("'",'',$text)));
	

	if (strlen($text)< 1)
		return;

		
	$words = preg_split('/ /',$alltext);
	
	$w1 = array();
	$w2 = array();
	$w3 = array();
	
	//build a list of one word phrases
	foreach ($words as $word) {
		$w1[$word]++;
	}
	
	//build a list of two word phrases
		$text = $alltext;
	$text = preg_replace('/(\w+) (\w+)/e','doaddw2("$1 $2")',$text);	
		$text = $alltext;
		$text = preg_replace('/(\w+)/','',$text,1);
	$text = preg_replace('/(\w+) (\w+)/e','doaddw2("$1 $2")',$text);
	
	//build a list of three word phrases
		$text = $alltext;
	$text = preg_replace('/(\w+) (\w+) (\w+)/e','doaddw3("$1 $2 $3")',$text);	
		$text = $alltext;
		$text = preg_replace('/(\w+)/','',$text,1);
	$text = preg_replace('/(\w+) (\w+) (\w+)/e','doaddw3("$1 $2 $3")',$text);	
		$text = $alltext;
		$text = preg_replace('/(\w+) (\w+)/','',$text,1);
	$text = preg_replace('/(\w+) (\w+) (\w+)/e','doaddw3("$1 $2 $3")',$text);
		
	
	
	foreach ($w1 as $word=>$count) {
		$db->Execute("insert into wordnet set gid = $id,`len` = 1,words = '$word',$field = $count");// ON DUPLICATE KEY UPDATE $field=$field+$count");
	}
	foreach ($w2 as $word=>$count) {
		$db->Execute("insert into wordnet set gid = $id,`len` = 2,words = '$word',$field = $count");
	}	
	foreach ($w3 as $word=>$count) {
		$db->Execute("insert into wordnet set gid = $id,`len` = 3,words = '$word',$field = $count");
	}	
}

	$tim = time();
	$db->Execute("delete from wordnet");
	#$db->Execute("LOCK TABLES wordnet WRITE");
	#$db->Execute("ALTER TABLE wordnet DISABLE KEYS");
	
	 
	
	$recordSet = &$db->Execute("select gridimage_id,title,comment from gridimage where moderation_status != 'rejected'");
	while (!$recordSet->EOF) 
	{
		docount($recordSet->fields['title'],'title',$recordSet->fields['gridimage_id']);
		docount($recordSet->fields['comment'],'comment',$recordSet->fields['gridimage_id']);
		if ($recordSet->fields['gridimage_id']%10==0)
			printf("done %d at <b>%d</b> seconds<BR>",$recordSet->fields['gridimage_id'],time()-$tim);
	
		$recordSet->MoveNext();
	}
	$recordSet->Close(); 

	#$db->Execute("ALTER TABLE wordnet ENABLE KEYS ");
	#$db->Execute("UNLOCK TABLES");
	$smarty->display('_std_end.tpl');
	exit;
	


	
?>
