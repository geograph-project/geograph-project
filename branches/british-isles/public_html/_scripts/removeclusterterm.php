<?php
/**
 * $Project: GeoGraph $
 * $Id: buildgridimage_search.php 7099 2011-02-12 00:00:25Z barry $
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


print "<pre>";

$recordSet = &$db->Execute("SELECT tag_id,tag,prefix FROM tag WHERE prefix IN ('cluster','term') and tag = 'bridge' AND status = 1 LIMIT 10");
$count=0;
while (!$recordSet->EOF) 
{
	$tag = $recordSet->fields;

	$dest = $db->getRow($sql = "SELECT tag_id FROM tag WHERE prefix = '' AND tag = ".$db->Quote($tag['tag']));
	
	print "$sql\n";
	
	
	$s = array();
	if (empty($dest)) {
		//can just update the original tag!
		
			$s[] = "UPDATE tag SET prefix = '' WHERE tag_id = {$tag['tag_id']}";
		
		#Job done!
		
	} else {
		//move all images to dest...
	
		
		#Change all the gt's
		
			$s[] = "UPDATE IGNORE gridimage_tag SET tag_id = {$dest['tag_id']} WHERE tag_id = {$tag['tag_id']}"; 

			//this is trickly. Any of the above that failed (due to duplicate key), means the 'new' tag is already on the image, and so the old one can be zapped. 
			#  PRIMARY KEY  (`gridimage_id`,`tag_id`,`user_id`),
			
		#delete any duplicates left... 
			
			$s[] = "DELETE FROM gridimage_tag WHERE tag_id = {$tag['tag_id']}"; 

		#move any canonicals 
		
			$s[] = "UPDATE tag SET canonical = {$dest['tag_id']} WHERE canonical = {$tag['tag_id']}"; 
			
		#delete the old tag!
			
			$s[] = "UPDATE tag SET status = 0 WHERE tag_id = {$tag['tag_id']}"; 
	}
	
	
	
	print "{$tag['tag_id']} -- {$tag['tag']} -- {$tag['prefix']}\n";
	print_r($s);
	if (!empty($s)) { 
            foreach ($s as $q) { 
                print htmlentities($q).";"; 
                $db->Execute($q); 
                 
                print " #Rows = ".$db->Affected_Rows()."<hr/>"; 
            } 
        } 

	$count++;
	$recordSet->MoveNext();
}


print "</pre><hr/>Done $count;";