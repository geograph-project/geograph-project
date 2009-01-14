<?
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005  Barry Hunter (geo@barryhunter.co.uk)
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



/**
* Provides the methods for updating the worknet tables
*
* @package Geograph
* @author Barry Hunter <geo@barryhunter.co.uk>
* @version $Revision$
*/

function addTwoLetterPhrase($phrase) {
	global $w2;
	$w2[$phrase] = (isset($w2[$phrase]))?($w2[$phrase]+1):1; 
}

function addThreeLetterPhrase($phrase) {
	global $w3;
	$w3[$phrase] = (isset($w3[$phrase]))?($w3[$phrase]+1):1; 
}
	
function updateWordnet(&$db,$text,$field,$id) {
	global $w1,$w2,$w3;
	
	$alltext = strtolower(preg_replace('/[^a-zA-Z0-9]+/',' ',str_replace("'",'',$text)));
	

	if (strlen($text)< 1)
		return;

		
	$words = preg_split('/ /',$alltext);
	
	$w1 = array();
	$w2 = array();
	$w3 = array();
	
	//build a list of one word phrases
	foreach ($words as $word) {
		$w1[$word] = (isset($w1[$word]))?($w1[$word]+1):1; 
	}
	
	//build a list of two word phrases
		$text = $alltext;
	$text = preg_replace('/(\w+) (\w+)/e','addTwoLetterPhrase("$1 $2")',$text);	
		$text = $alltext;
		$text = preg_replace('/(\w+)/','',$text,1);
	$text = preg_replace('/(\w+) (\w+)/e','addTwoLetterPhrase("$1 $2")',$text);
	
	//build a list of three word phrases
		$text = $alltext;
	$text = preg_replace('/(\w+) (\w+) (\w+)/e','addThreeLetterPhrase("$1 $2 $3")',$text);	
		$text = $alltext;
		$text = preg_replace('/(\w+)/','',$text,1);
	$text = preg_replace('/(\w+) (\w+) (\w+)/e','addThreeLetterPhrase("$1 $2 $3")',$text);	
		$text = $alltext;
		$text = preg_replace('/(\w+) (\w+)/','',$text,1);
	$text = preg_replace('/(\w+) (\w+) (\w+)/e','addThreeLetterPhrase("$1 $2 $3")',$text);
		
	
	
	foreach ($w1 as $word=>$count) {
		$db->Execute("insert into wordnet1 set gid = $id,words = '$word',$field = $count");// ON DUPLICATE KEY UPDATE $field=$field+$count");
	}
	foreach ($w2 as $word=>$count) {
		$db->Execute("insert into wordnet2 set gid = $id,words = '$word',$field = $count");
	}	
	foreach ($w3 as $word=>$count) {
		$db->Execute("insert into wordnet3 set gid = $id,words = '$word',$field = $count");
	}	
}


?>
