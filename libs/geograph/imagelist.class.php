<?php
/**
 * $Project: GeoGraph $
 * $Id: imagelist.class.php 8954 2019-05-22 13:35:03Z barry $
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
* @version $Revision: 8954 $
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
	* standard list of cols, to use with getbySql
	*/
	var $cols = "gridimage_id,title,user_id,realname,grid_reference,credit_realname";

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
	* @param statuses - either an array of statuses or a single status (pending, rejected or accepted)
	* @param sort - optional sort field and direction, e.g. submitted desc
	* @param count - optional upper limit on images returned
	* @param advanced - true to use real table, eg if need pending (default: false)
	* @param includeUserStatus - include any that have been self moderated (default: false)
	*/
	function getImages($statuses, $sort=null, $count=null,$advanced = false)
	{

split_timer('imagelist'); //starts the timer

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
		
		$c = $this->_getImagesBySql($sql);
		
split_timer('imagelist','getImages',$statuslist); //logs the wall time

		return $c;
	}

	/**
	* get image list for particular user
	*/
	function getImagesByUser($user_id, $statuses, $sort = 'gridimage_id', $count=null,$advanced = false)
	{
	
split_timer('imagelist'); //starts the timer

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
			$sql="select gi.*,grid_reference,gi.realname as credit_realname,if(gi.realname!='',gi.realname,user.realname) as realname ";
			$sql.=" , group_concat(distinct if(prefix='',tag,concat(prefix,':',tag)) separator '?') as tags ";
#			if ($advanced == 2)
#				$sql.=", (select count(*) from gridimage_ticket where gridimage_id=gi.gridimage_id and status<3) as open_tickets ";
			$sql.="from gridimage as gi ".
				"inner join gridsquare as gs using(gridsquare_id) ".
				"inner join user on(gi.user_id=user.user_id) ".
				"left join tag_public tp on(tp.gridimage_id = gi.gridimage_id) ".
				"where $statuslist ".
				"gi.user_id='$user_id' ".
				"group by gi.gridimage_id ".
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

	if (!empty($_GET['sql'])) {
		header('X-SQL: '.preg_replace('/\s+/',' ',$sql));
	}

		$c = $this->_getImagesBySql($sql);

split_timer('imagelist','getImagesByUser',"$user_id,$statuslist,$sort,$count,$advanced"); //logs the wall time

		return $c;
	}
	
	/**
	* get image list for particular query
	*/
	function getImagesBySphinx($q,$pgsize=15,$pg = 1, $new = false) {

split_timer('imagelist'); //starts the timer

		$sphinx = new sphinxwrapper($q, $new);

		$sphinx->pageSize = $pgsize;

		$sphinx->processQuery();

		if ($new) {
			//TODO, use $this->colsQL or something?, so can add more columns like place,county etc
			$sql = "SELECT id,title,realname,user_id,takendays,tags,grid_reference,hash FROM sample8 WHERE MATCH(?)";
			if ($pg > 1)
				$sql .= sprintf(" LIMIT %d,%d", ($pg -1)*$pgsize, $pgsize);
			else
				$sql .= " LIMIT $pgsize";

			return $this->getImagesBySphinxQL($sql, $new, $sphinx->q); // getImagesBySphinxQL has a basic implementation of prepared query!
		}

		$ids = $sphinx->returnIds($pg,'_images');
		
		if (!empty($ids)) {
			$this->resultCount = $sphinx->resultCount;
			
			$c = $this->getImagesByIdList($ids);
			
split_timer('imagelist','getImagesBySphinx',"$q"); //logs the wall time

			return $c;
		} else {
			return 0;
		}
	}

	/**
	* get image list for particular list
	*/
	function getImagesByIdList($ids,$columnlist = "*",$advanced=false) {

split_timer('imagelist'); //starts the timer

		if (empty($ids))
			return false;

                if ($advanced) {
			if (empty($columnlist) || $columnlist == '*')
				$columnlist = "gi.*";
			$where = array();
			$where[] = "gridimage_id IN(".join(",",$ids).")";
			$where[] = "moderation_status> 2";
			$where = implode(' AND ',$where);
                        $sql = "select $columnlist,grid_reference,gi.realname as credit_realname,if(gi.realname!='',gi.realname,user.realname) as realname,imagecount ".
                                "from gridimage as gi ".
                                "inner join gridsquare as gs using(gridsquare_id) ".
                                "inner join user on(gi.user_id=user.user_id) ".
                                "where $where ".
                                "LIMIT ".count($ids);
			//no order needed, as reorder below!

		} else {
			$sql = "SELECT $columnlist FROM gridimage_search WHERE gridimage_id IN(".join(",",$ids).") LIMIT ".count($ids);
		}

		$i=0;
		if ($sql) {
			$db=$this->_getDB(true);

			global $ADODB_FETCH_MODE;
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

split_timer('imagelist','getImagesByIdList',count($ids)); //logs the wall time

		return $i;
	}


	function getImagesBySphinxQL($sql,$new = true, $query = null, $tags_as_array = true) {
		$sph = GeographSphinxConnection('sphinxql', $new);

		if (!is_null($query) && stripos($sql,'MATCH(?)') !== FALSE) // we offer to do the quoting, as we have the sphinxQL connection!
			$sql = str_ireplace('MATCH(?)', 'MATCH('.$sph->Quote($query).')', $sql);

		$this->images=array();
		$i=0;
		$recordSet = $sph->Execute($sql);
		if ($recordSet && $recordSet->numRows()) {
		while (!$recordSet->EOF)
		{
			$this->images[$i]=new GridImage;
			$row = $recordSet->fields;

			$row['gridimage_id'] = $row['id'];
			$row['title'] = utf8_to_latin1($row['title']);
			if (!empty($row['takenday'])) //20040629
				$row['imagetaken'] = preg_replace('/(\d{4})(\d{2})(\d{2})/','$1-$2-$3',$row['takenday']);
			if (!empty($row['wgs84_lat'])) {
				$row['wgs84_lat'] = rad2deg($row['wgs84_lat']);
				$row['wgs84_long'] = rad2deg($row['wgs84_long']);
			}
			if (!empty($row['submitted']))
				$row['submitted'] = date('Y-m-d H:i:s',$row['submitted']);
			if (!empty($row['scenti']))
				$row['reference_index'] = substr($row['scenti'],0,1);
			if (!empty($row['status']))
				$row['moderation_status'] = str_replace('supplemental','accepted', $row['status']);

			$this->images[$i]->fastInit($row);

			if (!empty($this->images[$i]->tags) && is_string($this->images[$i]->tags))
		                $this->images[$i]->tags = array_filter(array_map('trim',explode("_SEP_",$this->images[$i]->tags)));
			else
				$this->images[$i]->tags = array();
			if (!empty($this->images[$i]->subjects) && is_string($this->images[$i]->subjects))
				foreach (array_filter(array_map('trim',explode("_SEP_",$this->images[$i]->subjects))) as $tag)
					$this->images[$i]->tags[] = 'subject:'.$tag;
			if (!empty($this->images[$i]->contexts) && is_string($this->images[$i]->contexts))
				foreach (array_filter(array_map('trim',explode("_SEP_",$this->images[$i]->contexts))) as $tag)
					$this->images[$i]->tags[] = 'top:'.$tag;
			if (!$tags_as_array)
				$this->images[$i]->tags = implode('?',$this->images[$i]->tags);

			if (!empty($row['geodist']) && $row['geodist'] > 100)
				$this->images[$i]->dist_string = sprintf("Dist:%.1fkm", $row['geodist']/1000); //provided in meters

			$recordSet->MoveNext();
			$i++;
		}
		$recordSet->Close();
		}
		$this->meta = $sph->getAssoc("SHOW META");
		return $i;
	}


	/**
	* this is a designed to emulate searchenginebuilder->buildSimpleQuery(), mainly for use by API functions, which run a build a search and immdeaially run it!
	* althoough ntoably it only lat/long queries at moment - todo!
	* .. so its like Execute has been run!
	*/
	function buildSimpleQuery($q = '',$distance = 10,$autoredirect='auto',$userlimit = 0) {

		$limit = 15;
		if (!empty($_GET['perpage']))
			$limit = min(100,intval($_GET['perpage']));

		//these are all the columns getImagesBySphinxQL needs to emulate gridimage_search rows
		// notably credit_realname is missing, so we gridimage.profile_link wont include the credit!
		$cols = "id,title,realname,user_id,grid_reference,takenday,submitted,imageclass,scenti,tags,subjects,contexts,status,wgs84_lat,wgs84_long";

		if (preg_match("/\b(-?\d+\.?\d*)[, ]+(-?\d+\.?\d*)\b/",$q,$ll)) {

			$lat = $ll[1];
			$long = $ll[2];

			require_once('3rdparty/facet-functions.php');
			require_once('geograph/conversions.class.php');

       			$where  = array();
		        if (!empty($userlimit))
       			        $where[] = "user_id = ".intval($userlimit);

			if ($distance == 1) { //searchengine has a weird legacy feature if distance=1, then just do in 'in same square'. So emulate that!
				$where[] = "MATCH(?)"; $query = geotiles($lat,$long,0.1); //quoted automatically later - just gets a 'grid_reference' filter

	        		$sql = "SELECT $cols
                		FROM sample8
		                WHERE ".implode(" AND ",$where)."
        		        ORDER BY id DESC
                		LIMIT $limit
	                	OPTION ranker=none";
			} else {
			        $prefix = 'wgs84_';
	        		$where[] = "MATCH(?)"; $query = geotiles($lat,$long,$distance*1000); //quoted automatically later
	        		$where[] = "geodist < ".floatval($distance)*1000;

	        		$sql = "SELECT $cols, geodist({$prefix}lat,{$prefix}long,".deg2rad($lat).','.deg2rad($long).") as geodist
                		FROM sample8
		                WHERE ".implode(" AND ",$where)."
        		        ORDER BY geodist ASC, id ASC
                		LIMIT $limit
	                	OPTION max_query_time = 10000";
			}
		} else {
			$sql = "SELECT $cols
			FROM sample8
        	        WHERE id = 5403392 ORDER BY takenday DESC, realname ASC, id DESC LIMIT $limit";
			$query = 'test';
		}

		if (!empty($_GET['groupby']) && preg_match('/^\w+$/',$_GET['groupby'])) {
			 // only cope with 'groupby=scenti' for now - which is same in both legacy and new engine!
			$sql = str_replace('ORDER BY ',"GROUP BY {$_GET['groupby']} ORDER BY ",$sql);
		}

if (!empty($_GET['debug'])) {
	print "$sql;<hr>";
	print "$query<hr>";
}

		$this->getImagesBySphinxQL($sql, true, $query, false); //tags_as_array=false, because most imagelist functions returns tags as array, but serach engine still provided string

		//the image description is only thing cant get from search index, need to lookup in database
		if (!empty($_GET['desc']) && !empty($this->images)) {
			$ids = array();
			foreach ($this->images as $image)
				$ids[] = $image->gridimage_id;
			$sql = "SELECT gridimage_id,comment FROM gridimage_search WHERE gridimage_id IN(".join(",",$ids).") LIMIT ".count($ids);
			$db=$this->_getDB(true);
			$comments = $db->getAssoc($sql);
			foreach ($this->images as &$image)
				$image->comment = @$comments[$image->gridimage_id];
		}

		//provide the same API that SearchEngine has after calling Execute
		$this->resultCount = $this->meta['total_found'];
		$this->numberOfPages = ceil($this->meta['total']/$limit);
		$this->criteria = new ImageList(); //we just want a fake class we can add some members to!
		$this->criteria->resultsperpage = $limit;
		$this->criteria->searchdesc = ", matching query";
		$this->results = &$this->images;
	}

	/**
	* get image list based on supplied sql...
	* @access private
	*/
	function _getImagesBySql($sql,$cache = 0) {
		$db=$this->_getDB(true);
		$this->images=array();
		$i=0;
		if ($cache > 0) {
			$recordSet = $db->CacheExecute($cache,$sql);
		} else {
			$recordSet = $db->Execute($sql);
		}
		if ($recordSet && $recordSet->numRows()) {
		while (!$recordSet->EOF)
		{
			$this->images[$i]=new GridImage;
			$this->images[$i]->fastInit($recordSet->fields);
			if (!empty($this->images[$i]->tags) && is_string($this->images[$i]->tags))
		                $this->images[$i]->tags = explode("?",$this->images[$i]->tags);

			$recordSet->MoveNext();
			$i++;
		}
		$recordSet->Close();
		}
		return $i;
	}

	/**
	* get image list or count for particular area...
	* @access private
	*/
	function _getImagesByArea($left,$right,$top,$bottom,$reference_index=null, $count_only=true)
	{
		$limit="";

split_timer('imagelist'); //starts the timer
		
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
			$db=$this->_getDB(true);
			
			$count=$db->GetOne($sql);
		}
		else
		{
			$count= $this->_getImagesBySql($sql);
		}
		
split_timer('imagelist','_getImagesByArea',"$left,$right,$top,$bottom,$reference_index"); //logs the wall time
		
		return $count;
	}

	/**
	* get database recordset for an area ... (only returns geograph(ftf)
	* @access public
	*/
	function getRecordSetByArea($left,$right,$top,$bottom,$reference_index=null, $count_only=true)
	{
		$db=$this->_getDB(true);

split_timer('imagelist'); //starts the timer

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

		$recordSet = $db->Execute($sql);

split_timer('imagelist','getRecordSetByArea',"$left,$right,$top,$bottom,$reference_index"); //logs the wall time

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

		$db=$this->_getDB(true);

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
		$smarty->assign_by_ref($basename, $this->images);
		$smarty->assign($basename.'count', count($this->images));
	}


	/**
	 * output images as thumbs (intended as a basic testing function, for quick prototypes, rather than full feature)
	 */
	function outputThumbs($thumbw = 120,$thumbh = 120, $clear = true)
	{
       		if (count($this->images)) {
	                foreach ($this->images as $idx => $row) {

				if (is_array($row)) {
	                	        $image = new GridImage();
        	                	$image->fastInit($row);
				} else {
					$image = $row;
				}

				print '<div style="float:left;position:relative; width:'.($thumbw+10).'px; height:'.($thumbh+10).'px">';
				print '<div align="center">';
				print '<a title="'.$image->grid_reference.' : '.htmlentities($image->title).' by '.htmlentities($image->realname).' - click to view full size image"';
				print ' href="/photo/'.$image->gridimage_id.'">'.$image->getThumbnail($thumbw,$thumbh,false,true).'</a>';
				print '</div></div>';
        	        }
			if ($clear)
		                print "<br style=\"clear:both\"/>";
       		}
	}


	/**
	 * get stored db object, creating if necessary
	 * @access private
	 */
	function &_getDB($allow_readonly = false)
	{

///$allow_readonly = false; //todo, temp overright as slave non-functional.


		//check we have a db object or if we need to 'upgrade' it
		if (!is_object($this->db) || ($this->db->readonly && !$allow_readonly) ) {
			$this->db=GeographDatabaseConnection($allow_readonly);
		}
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
		global $memcache;

		$mkey = rand(1,10).'.'.$reference_index;
		//fails quickly if not using memcached!
		$this->images = $memcache->name_get('ril',$mkey);
		if ($this->images) {
			$this->assignSmarty($smarty, 'recent');
			return;
		}

		$db=$this->_getDB(true);

split_timer('imagelist'); //starts the timer

		//carefully construct a query, that 1) uses indexes (ie primary key) and 2) avoids temporaly table, and/or filesort
		if ($reference_index == 2) {
			$crit = "- 2500 and reference_index = $reference_index and rand()>0.9";
		} elseif ($reference_index) {
			$crit = "- 500 and reference_index = $reference_index and rand()>0.9";
		} else {
			$crit = "- 250 and rand()>0.96";
		}

		$recordSet = $db->Execute("select {$this->cols} from gridimage_search
			where moderation_status = 'geograph' and gridimage_id > (select max(gridimage_id) from gridimage_search) $crit limit 5");

		$this->images=array();
		$i=0;
		if ($recordSet && $recordSet->numRows()) {
		while (!$recordSet->EOF) {
			$this->images[$i]=new GridImage;
			$this->images[$i]->fastInit($recordSet->fields);
			$recordSet->MoveNext();
			$i++;
		}
		$recordSet->Close();
		}

		shuffle($this->images);

split_timer('imagelist','RecentImageList',$reference_index); //logs the wall time

		$this->assignSmarty($smarty, 'recent');

		//fails quickly if not using memcached!
		$memcache->name_set('ril',$mkey,$this->images,$memcache->compress,$memcache->period_short);

		return $i;
	}

}
