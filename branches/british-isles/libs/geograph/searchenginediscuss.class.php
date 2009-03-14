<?php
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
* Provides the SearchEngineDiscuss class
*
* @package Geograph
* @author Barry Hunter <geo@barryhunter.co.uk>
* @version $Revision$
*/


/**
* SearchEngineDiscuss
*
* 
* @package Geograph
*/
class SearchEngineDiscuss extends SearchEngineBuilder
{
	var $page = "discuss/search.php";
	var $searchuse = "discuss";
	
	function Execute($pg) 
	{
		$db=$this->_getDB();
		
		$this->criteria->getSQLParts();
		extract($this->criteria->sql,EXTR_PREFIX_ALL^EXTR_REFS,'sql');
		
		$this->currentPage = $pg;
	
		$pgsize = $this->criteria->resultsperpage;
	
		if (!$pgsize) {$pgsize = 15;}
		if ($pg == '' or $pg < 1) {$pg = 1;}
	
		$page = ($pg -1)* $pgsize;
	
		if (empty($sql_where)) {
			$sql_where = "1";
		} else {
			$this->islimited = true;
		}
		if (!$sql_order) {$sql_order = 'gs.grid_reference';}
	
	// construct the count query sql
$sql = <<<END
	   SELECT count(*)
		FROM geobb_topics AS gi INNER JOIN gridsquare AS gs ON(forum_id = 5 AND topic_title = grid_reference)
			 $sql_from
		WHERE 
			$sql_where 
END;
		$this->resultCount = $db->GetOne($sql);
		$this->numberOfPages = ceil($this->resultCount/$pgsize);
	
		if ($sql_order == 'post_time desc') {
			$sql_from .= " LEFT JOIN geobb_posts ON (`topic_last_post_id` = geobb_posts.post_id)";
		
		}
	
	// construct the query sql
$sql = <<<END
	   SELECT distinct gi.*,x,y,nickname,realname,grid_reference,user_id,topic_time
			$sql_fields
		FROM geobb_topics AS gi INNER JOIN gridsquare AS gs ON(gi.forum_id = 5 AND topic_title = grid_reference)
		INNER JOIN user ON(gi.topic_poster=user.user_id)
			 $sql_from
		WHERE 
			$sql_where
		ORDER BY $sql_order
		LIMIT $page,$pgsize
END;
if (!empty($_GET['debug']))
	print "<BR><BR>$sql";
		//lets find some photos
		$this->results=array();
		$i=0;
		$recordSet = &$db->Execute($sql);
		while (!$recordSet->EOF) 
		{
			$this->results[$i] = $recordSet->fields;
			if ($d = $recordSet->fields['dist_sqd']) {
				$angle = rad2deg(atan2( $recordSet->fields['x']-$this->criteria->x, $recordSet->fields['y']-$this->criteria->y ));
				$this->results[$i]['dist_string'] = sprintf("Dist:%.1fkm %s",sqrt($d),heading_string($angle));
			
			}
			
			//temporary nickname fix for beta accounts
			if (strlen($this->results[$i]['nickname'])==0)
				$this->results[$i]['nickname']=str_replace(" ", "", $this->results[$i]['realname']);

			
			$recordSet->MoveNext();
			$i++;
		}
		$recordSet->Close(); 
if (!empty($_GET['debug']))
	print_r($this->results);
	}
}



?>
