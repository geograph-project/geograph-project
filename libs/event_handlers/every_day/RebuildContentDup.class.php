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

//filename of class file should correspond to class name, e.g.  myhandler.class.php
class RebuildContentDup extends EventHandler
{
	function processEvent(&$event)
	{
		//perform actions
		
		$db=&$this->_getDB();
		
		$db->Execute("CREATE TABLE `content_tmp` LIKE `content`");
		
		//we dont want the auto_increment, otherwise it will populate on the temp table, and mess up 'ON DUPLICATE KEY'
		$db->Execute("ALTER TABLE `content_tmp` CHANGE `content_id` `content_id` INT(10) UNSIGNED NULL, DROP PRIMARY KEY");

		
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
WHERE `content`.source IN ('category','snippet','user')
	AND `content_tmp`.`foreign_id` IS NULL;
	
		");
		
		$db->Execute("DROP TABLE `content_tmp`");
		
		
		//return true to signal completed processing
		//return false to have another attempt later
		return true;		
	}
	
}
