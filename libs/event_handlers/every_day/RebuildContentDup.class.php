<?php
/**
 * $Project: GeoGraph $
 * $Id: RebuildUserStats.class.php 3288 2007-04-20 11:32:27Z barry $
 * 
 * GeoGraph geographic photo archive project
 * http://geograph.sourceforge.net/
 *
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
*
* @package Geograph
* @author Barry Hunter <geo@barryhunter.co.uk>
* @version $Revision: 3288 $
*/

require_once("geograph/eventhandler.class.php");
require_once('geograph/searchcriteria.class.php');
require_once('geograph/searchengine.class.php');

//filename of class file should correspond to class name, e.g.  myhandler.class.php
class RebuildContentDup extends EventHandler
{
	function processEvent(&$event)
	{
		global $memcache;
		//perform actions
		
		$db=&$this->_getDB();
		
		$db->Execute("DROP TABLE IF EXISTS `content_tmp`");
		$db->Execute("CREATE TABLE `content_tmp` LIKE `content`");
		
		//we dont want the auto_increment, otherwise it will populate on the temp table, and mess up 'ON DUPLICATE KEY'
		$db->Execute("ALTER TABLE `content_tmp` CHANGE `content_id` `content_id` INT(10) UNSIGNED NULL, DROP PRIMARY KEY");

#######################
#BLOG
		$db->Execute("
		
INSERT INTO `content_tmp`
SELECT 
	NULL AS content_id, 
	blog_id AS foreign_id, 
	TRIM(title) AS title, 
	CONCAT('/blog/',blog_id) AS url, 
	user_id AS user_id, 
	gridimage_id, 
	gridsquare_id, 
	'' AS extract, 
	0 AS images, 
	0 AS wordcount, 
	0 AS views, 
	0 AS titles, 
	0 AS tags, 
	content AS words, 
	'blog' AS source, 
	'info' AS type, 
	updated, 
	created 
FROM blog
WHERE approved = 1

		");

#######################
#Category		
		$db->Execute("

INSERT INTO `content_tmp`
SELECT 
	NULL AS content_id, 
	CRC32(LOWER(gi.imageclass)) AS foreign_id, 
	TRIM(gi.imageclass) AS title, 
	CONCAT('/search.php?imageclass=',REPLACE(gi.imageclass,' ','+')) AS url, 
	0 AS user_id, 
	gridimage_id, 
	0 AS gridsquare_id, 
	IF(canonical IS NULL,'',CONCAT('Parent: ',canonical)) AS extract, 
	COUNT(*) AS images, 
	0 AS wordcount, 
	0 AS views, 
	0 AS titles, 
	0 AS tags, 
	0 AS words, 
	'category' AS source, 
	'info' AS type, 
	MAX(submitted) AS updated, 
	MIN(submitted) AS created 
FROM gridimage_search gi
LEFT JOIN category_canonical USING (imageclass)  
GROUP BY gi.imageclass;

		");

#######################
#CONTEXT (NOT ALL TAGS!)

		$db->Execute("

INSERT INTO `content_tmp`
SELECT 
	NULL AS content_id, 
	gt.tag_id AS foreign_id, 
	TRIM(tag) AS title, 
	CONCAT('/search.php?tag=',REPLACE(IF(prefix!='',CONCAT(prefix,':',tag),tag),' ','+')) AS url, 
	0 AS user_id, 
	gi.gridimage_id, 
	0 AS gridsquare_id, 
	'' AS extract, 
	COUNT(*) AS images, 
	0 AS wordcount, 
	0 AS views, 
	0 AS titles, 
	0 AS tags, 
	0 AS words, 
	'context' AS source, 
	'info' AS type, 
	MAX(gt.created) AS updated, 
	t.created AS created 
FROM gridimage_search gi
INNER JOIN gridimage_tag gt USING (gridimage_id)
INNER JOIN tag t USING (tag_id)
WHERE gt.status = 2 AND t.status = 1 AND prefix = 'top'
GROUP BY gt.tag_id;
		
		");

#######################
#SNIPPET
		$db->Execute("

INSERT INTO `content_tmp`
SELECT 
	NULL AS content_id, 
	s.snippet_id AS foreign_id, 
	TRIM(title) AS title, 
	CONCAT('/snippet/',s.snippet_id) AS url, 
	s.user_id, 
	gridimage_id, 
	0 AS gridsquare_id, 
	'' AS extract, 
	COUNT(gridimage_id) AS images, 
	0 AS wordcount, 
	0 AS views, 
	0 AS titles, 
	0 AS tags, 
	comment AS words, 
	'snippet' AS source, 
	'info' AS type, 
	MAX(gs.created) AS updated, 
	s.created 
FROM snippet s
INNER JOIN gridimage_snippet gs ON (s.snippet_id = gs.snippet_id AND gridimage_id < 4294967296)
GROUP BY s.snippet_id;

		");

#######################
#USER
		$db->Execute("
		
INSERT INTO `content_tmp`
SELECT 
	NULL AS content_id, 
	u.user_id AS foreign_id, 
	TRIM(u.realname) AS title, 
	CONCAT('/profile/',u.user_id) AS url, 
	0 AS user_id, 
	gridimage_id, 
	0 AS gridsquare_id, 
	IF(nickname='','',CONCAT('Nickname: ',nickname)) AS extract, 
	images, 
	0 AS wordcount, 
	0 AS views, 
	0 AS titles, 
	0 AS tags, 
	'' AS words, 
	'user' AS source, 
	'info' AS type, 
	submitted AS updated, 
	DATE(signup_date) AS created 
FROM user u
INNER JOIN user_stat USING (user_id)
INNER JOIN gridimage_search ON (last=gridimage_id);

		");

#  UNIQUE KEY `foreign_id` (`foreign_id`,`source`)

#####################################
# GEO-TRIPS

if ($h = fopen('http://users.aber.ac.uk/ruw/misc/geotrip_csv.php', 'r')) {
	$c = 0;
	while (($data = fgetcsv($h, 65536, ',', '"' )) !== FALSE) {
		if (!$c) {
			$headings = $data;
		} else {
			$row = array_combine($headings,$data);
/*    [1] => Array
        (
            [TripID] => 47
            [Title] => A walk around the block
            [SubTitle] => Hardly an epic walk - just experimenting with the new facility. A need to get out and to record how the weather was
affetcing our local traffic after nearly a week of snow and sub-zero temperatures.
            [UserID] => 322
            [GridimageID] => 2187811
            [GridReference] => SG4729
            [TripDate] => 2005-08-03
            [Updated] => 1292095230
            [Content] => Hardly an epic walk - just experimenting with the new facility. A need to get out and to record how the weather was a
ffetcing our local traffic after nearly a week of snow and sub-zero temperatures.
            [SearchID] => 2187811
        )
*/
			$updates = array();
			$updates['foreign_id'] = $row['TripID'];
			$updates['title'] = stripslashes($row['Title']);
			$updates['url'] = "http://users.aber.ac.uk/ruw/misc/geotrip_show.php?osos&trip=".$row['TripID'];
			$updates['user_id'] = $row['UserID'];
			$updates['gridimage_id'] = $row['GridimageID'];
			
			$gs=new GridSquare();
			if (!empty($row['GridReference']) && $gs->setByFullGridRef($row['GridReference'])) {
				$updates['gridsquare_id'] = $gs->gridsquare_id;
			}
			
			$updates['created'] = $row['TripDate'];
			$updates['updated'] = date('Y-m-d H:i:s',$row['Updated']);
			
			if (!empty($row['SubTitle'])) {
				$lines = explode("\n",wordwrap($row['SubTitle'],250,"\n")); 

				$updates['extract'] = stripslashes($lines[0]).(count($lines)>1?'...':'');
			}
			$updates['words'] = stripslashes($row['Content']);
			
			$mkey = $row['SearchID'];
			$images =& $memcache->name_get('fse',$mkey);
			if (empty($images)) {

				$engine = new SearchEngine($row['SearchID']);
				if ($engine->criteria) {
					$engine->criteria->resultsperpage = 1; //override it
					$engine->Execute($pg);
					if ($engine->resultCount && $engine->results) {
						$images = $engine->resultCount;

						$memcache->name_set('fse',$mkey,$images,$memcache->compress,3600*6*rand(3,10));
					}
				}
			}
			if (!empty($images)) {
				$updates['images'] = $images;
			}
			$updates['source'] = 'trip';
			$updates['type'] = 'info';
			
			$db->Execute('INSERT INTO content_tmp SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
		}
		$c++;
	}

}

#####################################

#Tidy up....

		$db->Execute("
		
INSERT INTO `content`
SELECT * FROM `content_tmp` ct
ON DUPLICATE KEY UPDATE
	title = ct.title,
	url = ct.url,
	user_id = ct.user_id,
	gridimage_id = ct.gridimage_id,
	gridsquare_id = ct.gridsquare_id,
	extract = ct.extract,
	images = ct.images,
	words = ct.words,
	updated = ct.updated,
	created = ct.created;
	
		");
	
		$db->Execute("
		
DELETE `content`.* 
FROM `content` 
	LEFT JOIN `content_tmp` USING (`foreign_id`,`source`) 
WHERE `content`.source IN ('category','context','snippet','user','trip','blog')
	AND `content_tmp`.`foreign_id` IS NULL;
	
		");
		
		$db->Execute("DROP TABLE `content_tmp`");
		
		
		//return true to signal completed processing
		//return false to have another attempt later
		return true;		
	}
	
}
