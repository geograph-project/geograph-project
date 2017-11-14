<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * http://geograph.sourceforge.net/
 *
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

/**
* Provides the ImageList class
*
* @package Geograph
* @author Paul Dixon <paul@elphin.com>
* @version $Revision$
*/

/**
* ImageList class
* Provides facilities for building a list of GridImage instances
* The resulting list is easily attached to Smarty for display
*/
class ImageList
{
	/**
	* internal db handle
	*/
	var $db;

	/**
	* array of GridImage instances
	*/
	var $images=array();


	/**
	* constructor - can be used to build a basic list (See getImages)
	*/
	function ImageList($statuses=null, $sort=null, $count=null,$advanced = false)
	{
		if (!is_null($statuses))
			$this->getImages($statuses, $sort, $count,$advanced);
	}
	
	/**
	* build a basic image list from basic criteria
	* @param sort - optional sort field and direction, e.g. submitted desc
	* @param count - optional upper limit on images returned
	* @param advanced - true to use real table, eg if need pending (default: false)
	*/
	function getPOTD($sort=null, $count=null,$advanced = false)
	{
		if (is_null($sort))
			$orderby="";
		else
			$orderby="order by $sort";
		
		if (is_null($count))
			$limit="";
		else
			$limit="limit $count";
		
		if ($advanced) {
			$sql = "select gi.*,grid_reference,gi.realname as credit_realname,if(gi.realname!='',gi.realname,user.realname) as realname,imagecount,gd.showday ".
				"from gridimage as gi ".
				"inner join gridsquare as gs using(gridsquare_id) ".
				"inner join user on(gi.user_id=user.user_id) ".
				"inner join gridimage_daily gd using(gridimage_id) ".
				"where gd.showday is not null and gd.showday < NOW() ".
				"$orderby $limit";
		} else {
			$sql = "select gi.*,gd.showday ".
				"from gridimage_search gi ".
				"inner join gridimage_daily gd using(gridimage_id) ".
				"where gd.showday is not null and gd.showday < NOW() ".
				"$orderby $limit";
		}
		
		return $this->_getImagesBySql($sql);
	}

	/**
	* build a basic image list from basic criteria
	* @param statuses - either an array of statuses or a single status (pending, rejected or accepted)
	* @param sort - optional sort field and direction, e.g. submitted desc
	* @param count - optional upper limit on images returned
	* @param advanced - true to use real table, eg if need pending (default: false)
	*/
	function getImages($statuses, $sort=null, $count=null,$advanced = false)
	{
		//we accept an array or a single status...
		if (is_array($statuses))
			$statuslist="where moderation_status in ('".implode("','", $statuses)."') ";
		elseif (is_int($statuses)) 
			$statuslist="where moderation_status = $statuses ";
		elseif ($statuses)
			$statuslist="where moderation_status = '$statuses' ";
		
		if (is_null($sort))
			$orderby="";
		else
			$orderby="order by $sort";
		
		if (is_null($count))
			$limit="";
		else
			$limit="limit $count";
		
		if ($advanced) {
			$sql = "select gi.*,grid_reference,gi.realname as credit_realname,if(gi.realname!='',gi.realname,user.realname) as realname,imagecount ".
				"from gridimage as gi ".
				"inner join gridsquare as gs using(gridsquare_id) ".
				"inner join user on(gi.user_id=user.user_id) ".
				" $statuslist ".
				"$orderby $limit";
		} else {
			if (strpos($statuslist,'geograph') !== FALSE && strpos($statuslist,'accepted') !== FALSE)
				$statuslist = '';
			$sql = "select * ".
				"from gridimage_search ".
				" $statuslist ".
				"$orderby $limit";
		}
		
		return $this->_getImagesBySql($sql);
	}

	/**
	* get image list for particular user
	*/
	function getImagesByUser($user_id, $statuses, $sort = 'submitted', $count=null,$advanced = false)
	{
		//we accept an array or a single status...
		if (is_array($statuses))
			$statuslist=" moderation_status in ('".implode("','", $statuses)."') and ";
		elseif (is_int($statuses)) 
			$statuslist=" moderation_status = $statuses and ";
		elseif ($statuses)
			$statuslist=" moderation_status = '$statuses' and ";
		else
			$statuslist='';

		$user_id=intval($user_id);

		if (is_null($sort))
			$orderby='';
		else
			$orderby="order by $sort";

		if (is_null($count))
			$limit='';
		else
			$limit="limit $count";

		if ($advanced || preg_match("/(pending|rejected)/",$statuslist)) {
			$sql="select gi.*,grid_reference,gi.realname as credit_realname,if(gi.realname!='',gi.realname,user.realname) as realname, t.topic_id,t.forum_id,t.last_post ";
			if ($advanced == 2)
				$sql.=", (select count(*) from gridimage_ticket where gridimage_id=gi.gridimage_id and status<3) as open_tickets ";
			$sql.="from gridimage as gi ".
				"inner join gridsquare as gs using(gridsquare_id) ".
				"inner join user on(gi.user_id=user.user_id) ".
				"left join gridsquare_topic as t on(gi.gridsquare_id=t.gridsquare_id and ".
				"t.last_post=(select max(last_post) from gridsquare_topic where gridsquare_id=gi.gridsquare_id)) ".
				"where $statuslist ".
				"gi.user_id='$user_id' ".
				"$orderby $limit";
		} else {
			if (strpos($statuslist,'geograph') !== FALSE && strpos($statuslist,'accepted') !== FALSE)
				$statuslist = '';
			$sql="select gi.* ".
				"from gridimage_search as gi ".
				"where $statuslist ".
				"gi.user_id='$user_id' ".
				"$orderby $limit";
		}

		return $this->_getImagesBySql($sql);
	}
	
	/**
	* get image list for particular query
	*/
	function getImagesBySphinx($q,$pgsize=15,$pg = 1) {
		$sphinx = new sphinxwrapper($q);

		$sphinx->pageSize = $pgsize;

		$sphinx->processQuery();

		$ids = $sphinx->returnIds($pg,'_images');
		
		if (count($ids)) {
			$this->resultCount = $sphinx->resultCount;
		
			return $this->getImagesByIdList($ids);
		} else {
			return 0;
		}
	}
	
	/**
	* get image list for particular list
	*/
	function getImagesByIdList($ids) {
		$sql = "SELECT * FROM gridimage_search WHERE gridimage_id IN(".join(",",$ids).") LIMIT ".count($ids);
				
		$i=0;
		if ($sql) {
			$db=&$this->_getDB();
	
			$prev_fetch_mode = $ADODB_FETCH_MODE;
			$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;	
			$rows = $db->getAssoc($sql);
			$ADODB_FETCH_MODE = $prev_fetch_mode;
		
			if (count($rows)) {
				
				$this->images = array();
				foreach ($ids as $c => $id) {
					if (!empty($rows[$id])) {
						$gridimage = new GridImage;
						$row = array('gridimage_id'=>$id)+$rows[$id];
						$gridimage->fastInit($row);
	
						$this->images[] = $gridimage;
						$i++;
					}
				}
			}
		}
		return $i;
	}


	/**
	* get image list based on supplied sql...
	* @access private
	*/
	function _getImagesBySql($sql,$cache = 0) {
		$db=&$this->_getDB();
		if (!empty($_GET['debug']))
			print $sql;
		$this->images=array();
		$i=0;
		if ($cache > 0) {
			$recordSet = $db->CacheExecute($cache,$sql);
		} else {
			$recordSet = $db->Execute($sql);
		}
		while (!$recordSet->EOF) 
		{
			$this->images[$i]=new GridImage;
			$this->images[$i]->fastInit($recordSet->fields);
			$recordSet->MoveNext();
			$i++;
		}
		$recordSet->Close(); 
		return $i;
	}
	
	
	/**
	* get image list or count for particular area...
	* @access private
	*/
	function _getImagesByArea($left,$right,$top,$bottom,$reference_index=null, $count_only=true)
	{
		$limit="";
		
		//ensure correct order
		$l=min($left,$right);
		$r=max($left,$right);
		$t=min($top,$bottom);
		$b=max($top,$bottom);
		
		//figure for particular grid system?
		$rfilter="";
		if (!is_null($reference_index))
			$rfilter="and reference_index=$reference_index";
		
		$cols=$count_only?"count(*) as cnt":"*";
		
		$rectangle = "'POLYGON(($l $b,$r $b,$r $t,$l $t,$l $b))'";
		
		$sql="select $cols 
			from gridimage_search 
			where 
			CONTAINS( 	
				GeomFromText($rectangle),
				point_xy)
			$rfilter $limit";
		
		$this->images=array();
		if ($count_only)
		{
			$db=&$this->_getDB();
			
			$count=$db->GetOne($sql);
		}
		else
		{
			$count= $this->_getImagesBySql($sql);
		}
		
		return $count;
	}

	/**
	* get database recordset for an area ... (only returns geograph(ftf)
	* @access public
	*/
	function getRecordSetByArea($left,$right,$top,$bottom,$reference_index=null, $count_only=true)
	{
		$db=&$this->_getDB();

		$orderby="";
		$limit="";
		
		//ensure correct order
		$l=min($left,$right);
		$r=max($left,$right);
		$t=min($top,$bottom);
		$b=max($top,$bottom);
		
		//figure for particular grid system?
		$rfilter="";
		if (!is_null($reference_index))
			$rfilter="and reference_index=$reference_index";
		
		$cols=$count_only?"count(*) as cnt":"*";
		
		$rectangle = "'POLYGON(($l $b,$r $b,$r $t,$l $t,$l $b))'";
		
		$sql="select $cols 
			from gridimage_search 
			where moderation_status = 'geograph' and ftf = 1 $rfilter and 
			CONTAINS( 	
				GeomFromText($rectangle),
				point_xy)
			$orderby $limit";

		$recordSet = &$db->Execute($sql);

		return $recordSet;
	}
	
	/**
	* get image list for particular area...
	* @access public
	*/
	function getImagesByArea($left,$right,$top,$bottom,$reference_index=null)
	{
		return $this->_getImagesByArea($left,$right,$top,$bottom,$reference_index, false);
	}
	
	
	/**
	* get image count for particular area...
	* @access public
	*/
	function countImagesByArea($left,$right,$top,$bottom,$reference_index=null)
	{
		return $this->_getImagesByArea($left,$right,$top,$bottom,$reference_index, true);
	}

	function getRecordSetByPrefix($prefix) {

		$db=&$this->_getDB();

		$data=$db->GetRow("select * from gridprefix where prefix='".$prefix."' limit 1");

		return $this->getRecordSetByArea($data['origin_x'],$data['origin_x']+$data['width']-1,
			$data['origin_y']+$data['height']-1,$data['origin_y'], $data['reference_index'], false);

	}
	
	/**
	 * store image list as $basename in $smarty instance
	 * a $basenamecount field is also stored
	 */
	function assignSmarty(&$smarty, $basename)
	{
		global $CONF;
		$smarty->assign_by_ref($basename, $this->images);		
		$smarty->assign($basename.'count', count($this->images));
		$smarty->assign($basename.'search', $CONF['searchid_recent']);
		$smarty->assign($basename.'searchcur', $CONF['searchid_current']);
	}
	
	/**
	 * get stored db object, creating if necessary
	 * @access private
	 */
	function &_getDB()
	{
		if (!is_object($this->db))
			$this->db=NewADOConnection($GLOBALS['DSN']);
		if (!$this->db) die('Database connection failed');  
		return $this->db;
	}

	/**
	 * set stored db object
	 * @access private
	 */
	function _setDB(&$db)
	{
		$this->db=$db;
	}
	
	
}


/**
* RecentImageList class
* Provides basic (to be extended in future) functionality 
* for showing some recent images
*/
class RecentImageList extends ImageList {

	/**
	* constructor - used to build a basic list (See getImages)
	*/
	function RecentImageList(&$smarty,$reference_index = 0) {
		global $memcache, $ADODB_FETCH_MODE;
		
		$mkey = rand(1,10).'.'.$reference_index;
		//fails quickly if not using memcached!
		$this->images = $memcache->name_get('ril',$mkey);
		if ($this->images) {
			$this->assignSmarty($smarty, 'recent');
			return;
		}
		
		$db=&$this->_getDB();
		$prev_fetch_mode = $ADODB_FETCH_MODE;

		# get list of all images and list of the images by contributor
		$imagesbyuser = array();
		$images = array();
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		$recordSet = $db->Execute("select gridimage_id, user_id from gridimage_recent limit 100");
		$ADODB_FETCH_MODE = $prev_fetch_mode;
		while (!$recordSet->EOF) {
			$uid = $recordSet->fields['user_id'];       #$recordSet->fields[1];
			$giid = $recordSet->fields['gridimage_id']; #$recordSet->fields[0];
			if (array_key_exists($uid, $imagesbyuser)) {
				$imagesbyuser[$uid][] = $giid;
			} else {
				$imagesbyuser[$uid] = array($giid);
			}
			$images[$giid] = $giid;
			$recordSet->MoveNext();
		}
		$recordSet->Close();

		# get random list of image ids
		$reslist = array();
		$size = min(count($images), 5);
		if ($size >= 1) {
			# try to select a random set of contributors and get one random picture submitted by each of those
			$users = min(count($imagesbyuser), $size);
			if ($users == 1) { # thanks, array_rand()...
				reset($imagesbyuser);
				$selectedusers = array(key($imagesbyuser));
			} else {
				$selectedusers = array_rand($imagesbyuser, $users);
			}
			foreach ($selectedusers as $uid) {
				$giid = $imagesbyuser[$uid][array_rand($imagesbyuser[$uid])];
				$reslist[] = $giid;
				unset($images[$giid]);
			}
			$size -= count($reslist);
			if ($size) { # not enough contributors, get rest from $images
				if ($size == 1) { # thanks, array_rand()...
					$reslist[] = array_rand($images);
				} else {
					$reslist = array_merge($reslist, array_rand($images, $size));
				}
			}
		}

		$this->images=array();
		$i=0;
		if (count($reslist)) {
			$id_string = join(',',$reslist);
			$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
			$recordSet = $db->Execute("select * from gridimage_search where gridimage_id in ($id_string) order by RAND()");
			$ADODB_FETCH_MODE = $prev_fetch_mode;
			while (!$recordSet->EOF) {
				$this->images[$i] = new GridImage;
				$this->images[$i]->fastInit($recordSet->fields);
				$recordSet->MoveNext();
				$i++;
			}
			$recordSet->Close();
		}
		$this->assignSmarty($smarty, 'recent');
		
		//fails quickly if not using memcached!
		$memcache->name_set('ril',$mkey,$this->images,$memcache->compress,$memcache->period_short);
		
		return $i;
	}

}

?>
