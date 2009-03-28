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
* Provides the GridImage class
*
* @package Geograph
* @author Paul Dixon <paul@elphin.com>
* @version $Revision$
*/

/**
* GridImage class
* Provides an abstraction of a grid image, providing all the
* obvious functions you'd expect
*/
class GridImage
{
	/**
	* internal db handle
	*/
	var $db;

	/**
	* image id
	*/
	var $gridimage_id;

	/**
	* the 'host' grid square
	*/
	var $grid_square;

	/**
	* image sequence number for associated square
	*/
	var $seq_no;
		
	/**
	* user id of submitter
	*/
	var $user_id;
	
	/**
	* first to find?
	*/
	var $ftf;

	/**
	* moderation status - 'pending', 'accepted', 'rejected' or 'geograph'
	*/
	var $moderation_status;

	/**
	* image title
	*/
	var $title;

	/**
	* image comment
	*/
	var $comment;

	/**
	* serialize exif data
	*/
	var $exif;

	/**
	* submission date
	*/
	var $submitted;

	/**
	* user real name
	*/
	var $realname;
	
	/**
	* photographer grid reference
	*/
	var $photographer_gridref;
	
	/**
	* photographer grid reference precision (in metres)
	*/
	var $photographer_gridref_precision;
	
	/**
	* subject grid reference
	*/
	var $subject_gridref;
	
	/**
	* subject grid reference precision (in metres)
	*/
	var $subject_gridref_precision;
	
	/**
	* external image?
	 */
	var $ext;
	var $ext_server;
	var $ext_thumb_url;
	var $ext_img_url;
	var $ext_profile_url;
	var $ext_gridimage_id;

	/**
	* constructor
	*/
	function GridImage($id = null)
	{
		$this->ext = false;
		if (!empty($id)) {
			$this->loadFromId($id);
		}
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
	
	/**
	* Get an array of current image classes
	*/
	function& getImageClasses()
	{
		$db=&$this->_getDB();
		
		$arr = $db->CacheGetAssoc(24*3600,"select imageclass,imageclass from gridimage ".
			"where length(imageclass)>0 and moderation_status in ('accepted','geograph') ".
			"group by imageclass");
		
		//temp 'defaults' until the group by will pick them up!
		foreach(array('Urban Landscape',
		'Urban Landmark',
		'Open Countryside',
		'Farmland',
		'Woodland',
		'Water Bodies - Lakes and Rivers',
		'Mountains',
		'Marshland',
		'Coastline/Beaches') as $val) {
			if(!$arr[$val]) 
				$arr[$val]=$val;
		}
		natcasesort($arr);
		
		return $arr;
	}
	
	
	/**
	* Returns grid reference of photographer if available
	* Data is additionally stored as member data
	*/
	function getPhotographerGridref($spaced = false)
	{
		//already calculated?
		if (strlen($this->photographer_gridref))	
			return $this->photographer_gridref;

		$this->photographer_gridref='';
		if ($this->viewpoint_northings) 
		{
			require_once('geograph/conversions.class.php');
			$conv = new Conversions;
			
			list($posgr,$len) = $conv->national_to_gridref(
				$this->viewpoint_eastings,
				$this->viewpoint_northings,
				($this->use6fig && $spaced)?min(6,$this->viewpoint_grlen):max(2,$this->viewpoint_grlen),
				$this->grid_square->reference_index,$spaced);
			
			$this->photographer_gridref=$posgr;
			$this->photographer_gridref_precision=pow(10,6-$len)/10;
		}	
		
		return $this->photographer_gridref;
	}
	
	function getSubjectGridref($spaced = false)
	{
		//already calculated?
		if (strlen($this->subject_gridref))	
			return $this->subject_gridref;

		require_once('geograph/conversions.class.php');
		$conv = new Conversions;
	
		if (empty($this->grid_square) && $this->gridsquare_id) {
			$this->grid_square=new GridSquare;
			if (is_object($this->db))
				$this->grid_square->_setDB($this->db);
			$this->grid_square->loadFromId($this->gridsquare_id);
			$this->grid_reference=$this->grid_square->grid_reference;
			if ($this->nateastings) {
				$this->natspecified = 1;
				$this->grid_square->natspecified = 1;
				$this->grid_square->natgrlen=$this->natgrlen;
				$this->grid_square->nateastings=$this->nateastings;
				$this->grid_square->natnorthings=$this->natnorthings;
			}	
		}
	
		//if this image doesnt have an exact position then we need to remove 
		//the move to the center of the square
		//must be before getNatEastings is called
		$correction = ($this->natgrlen > 4)?0:500;
		
		list($gr,$len) = $conv->national_to_gridref(
			$this->grid_square->getNatEastings()-$correction,
			$this->grid_square->getNatNorthings()-$correction,
			($this->use6fig && $spaced)?min(6,$this->natgrlen):max(4,$this->natgrlen),
			$this->grid_square->reference_index,$spaced);
		
		$this->subject_gridref=$gr;
		$this->subject_gridref_precision=pow(10,6-$len)/10;
		
		return $this->subject_gridref;
	}
	
	/**
	 * clear all member vars
	 * @access private
	 */
	function _clear()
	{
		$vars=get_object_vars($this);
		foreach($vars as $name=>$val)
		{
			if ($name!="db")
				unset($this->$name);
		}
	}
	
	/**
	* assign members from array containing required members
	*/
	function _initFromArray(&$arr)
	{
		foreach($arr as $name=>$value)
		{
			if (!is_numeric($name))
				$this->$name=$value;
		}
		$this->ext = false;
		if (!empty($this->gridsquare_id)) {
			$this->grid_square=new GridSquare;
			if (is_object($this->db))
				$this->grid_square->_setDB($this->db);
			$this->grid_square->loadFromId($this->gridsquare_id);
			$this->grid_reference=$this->grid_square->grid_reference;
			if ($this->nateastings) {
				$this->natspecified = 1;
				$this->grid_square->natspecified = 1;
				$this->grid_square->natgrlen=$this->natgrlen;
				$this->grid_square->nateastings=$this->nateastings;
				$this->grid_square->natnorthings=$this->natnorthings;
			}
		}
		
		$this->profile_link = "/profile/{$this->user_id}";
		
		if (!empty($this->credit_realname))
			$this->profile_link .= "?a=".urlencode($this->realname);
		
		if (empty($this->title))
			$this->title="Untitled photograph for {$this->grid_reference}";
	}
	
	/**
	* advanced method which sets up a gridimage without a gridsquare instance
	* only use this method if you know what you are doing
	* if need a grid_reference it should be supplied in the array
	*/
	function fastInit(&$arr)
	{
		$this->ext = false;
		$this->grid_square=null;
		$this->grid_reference='';
		foreach($arr as $name=>$value)
		{
			if (!is_numeric($name))
				$this->$name=$value;
		}
		
		$this->profile_link = "/profile/{$this->user_id}";
		
		if (!empty($this->credit_realname))
			$this->profile_link .= "?a=".urlencode($this->realname);
	}
	
	/**
	* return true if instance references a valid grid image
	*/
	function isValid()
	{
		return isset($this->gridimage_id) && ($this->gridimage_id>0);
	}
	
	/**
	* assign members from recordset containing required members
	*/
	function loadFromRecordset(&$rs)
	{
		$this->_clear();
		$this->_initFromArray($rs->fields);
		return $this->isValid();
	}
	
	/**
	* trim members to make object as small as possible
	*/
	function compact()
	{
		unset($this->db);
		unset($this->exif);
		if (is_object($this->grid_square))
		{
			unset($this->grid_square->db);
		}
	}
	
	/**
	* assign members from gridimage_id
	*/
	function loadFromId($gridimage_id,$usesearch = false)
	{
		//todo memcache
		
		$db=&$this->_getDB();
		
		$this->_clear();
		if (preg_match('/^\d+$/', $gridimage_id))
		{
			if ($usesearch) {
				$row = &$db->GetRow("select * from gridimage_search where gridimage_id={$gridimage_id} limit 1");
			} else {
				$row = &$db->GetRow("select gi.*,gi.realname as credit_realname,if(gi.realname!='',gi.realname,user.realname) as realname,user.realname as user_realname,user.nickname from gridimage gi inner join user using(user_id) where gridimage_id={$gridimage_id} limit 1");
			}
			if (is_array($row))
			{
				$this->_initFromArray($row);
			}
		}
		//todo memcache (probably make sure dont serialise the dbs!) 
		
		return $this->isValid();
	}

	/**
	* assign members from gridimage_id and server (use api)
	*/
	function loadFromServer($server, $gridimage_id)
	{
		//todo memcache
		
		$this->_clear();
		if (preg_match('/^\d+$/', $gridimage_id))
		{
			# http://www.geograph.org.uk/api/Photo/419440
			$url = "http://$server/api/Photo/$gridimage_id";
#			$xml_str=<<<EOF
#< ?xml version="1.0" encoding="UTF-8"? ><geograph><status state="ok"/><title>Marble Arch</title><gridref>TQ2780</gridref><user profile="http://www.geograph.org.uk/profile/1621">Stephen McKay</user><img src="http://www.geograph.org.uk/photos/41/94/419440_df5f4e31.jpg" width="640" height="479"/><thumbnail>http://s0.geograph.org.uk/photos/41/94/419440_df5f4e31_120x120.jpg</thumbnail><taken>2007-04-30</taken><submitted>2007-05-01 09:55:32</submitted><category>Arch</category><comment><![CDATA[This grand edifice stands in the centre of one of London's busiest road junctions, where Oxford Street, Park Lane, Bayswater Road and Edgware Road meet. It was designed by John Nash in 1828 as a gateway to Buckingham Palace, but was removed to its present site in 1851. It is built of white Carrara marble, the design taken from the triumphal arch of Constantine in Rome.]]></comment></geograph>
#EOF;
			#$xml = new SimpleXMLElement($xml_str);
			$xml = simplexml_load_file($url);
			if ($xml !== false && $xml->status['state'] == 'ok') {
				$this->grid_reference    = (string)$xml->gridref;
				$this->title             = (string)$xml->title;
				$this->realname          = (string)$xml->user;
				$this->ext_img_url       = (string)$xml->img['src'];
				$this->ext_profile_url   = (string)$xml->user['profile'];
				$this->ext_thumb_url     = (string)$xml->thumbnail;
				$this->ext               = true;
				$this->ext_server        = $server;
				$this->moderation_status = 'geograph';
				$this->submitted         = (string)$xml->submitted;
				$this->imagetaken        = (string)$xml->taken;
				$this->imageclass        = (string)$xml->category;
				$this->comment           = (string)$xml->comment;
				$this->gridimage_id      = 0;
				$this->ext_gridimage_id  = $gridimage_id;
				$this->grid_square       = null;
				# getThumbnail(120,120,false,true);
				#
				# photographer_gridref_precision
				# photographer_gridref
				# subject_gridref
				# $gridimage_id

				$this->profile_link = $this->ext_profile_url;
				
				#if (!empty($this->credit_realname))
				#	$this->profile_link .= "?a=".urlencode($this->realname);

				if (empty($this->title))
					$this->title="Untitled photograph for {$this->grid_reference}";
				return true;
			}


		}
		//todo memcache (probably make sure dont serialise the dbs!) 
		
		return false;
	}
	
	/**
	* calculate a hash to prevent easy downloading of every image in sequence
	*/
	function _getAntiLeechHash()
	{
		global $CONF;
		return substr(md5($this->gridimage_id.$this->user_id.$CONF['photo_hashing_secret']), 0, 8);
	}
	
	function assignToSmarty($smarty) {
		global $CONF;
		
		$taken=$this->getFormattedTakenDate();

		//get the grid references
		$this->getSubjectGridref(true);
		$this->getPhotographerGridref(true);



		//remove grid reference from title
		$this->bigtitle=trim(preg_replace("/^{$this->grid_reference}/", '', $this->title));
		$this->bigtitle=preg_replace('/(?<![\.])\.$/', '', $this->bigtitle);

		$smarty->assign('page_title', $this->bigtitle.":: OS grid {$this->grid_reference}");

		$smarty->assign('image_taken', $taken);
		$smarty->assign('ismoderator', $ismoderator);
		$smarty->assign_by_ref('image', $this);

		//get a token to show a suroudding geograph map
		$mosaic=new GeographMapMosaic;
		$smarty->assign('map_token', $mosaic->getGridSquareToken($this->grid_square));


		//find a possible place within 25km
		$place = $this->grid_square->findNearestPlace(75000);
		$smarty->assign_by_ref('place', $place);

		if (empty($this->comment)) {
			$smarty->assign('meta_description', "{$this->grid_reference} :: {$this->bigtitle}, ".strip_tags(smarty_function_place(array('place'=>$place))) );
		} else {
			$smarty->assign('meta_description', $this->comment);
		}

		if (!empty($CONF['forums'])) {
			//let's find posts in the gridref discussion forum
			$this->grid_square->assignDiscussionToSmarty($smarty);
		}

		//count the number of photos in this square
		$smarty->assign('square_count', $this->grid_square->imagecount);

		//lets add an overview map too
		$overview=new GeographMapMosaic('largeoverview');
		$overview->setCentre($this->grid_square->x,$this->grid_square->y); //does call setAlignedOrigin
		$overview->assignToSmarty($smarty, 'overview');
		$smarty->assign('marker', $overview->getSquarePoint($this->grid_square));


		require_once('geograph/conversions.class.php');
		$conv = new Conversions;

		list($lat,$long) = $conv->gridsquare_to_wgs84($this->grid_square);
		$smarty->assign('lat', $lat);
		$smarty->assign('long', $long);

		list($latdm,$longdm) = $conv->wgs84_to_friendly($lat,$long);
		$smarty->assign('latdm', $latdm);
		$smarty->assign('longdm', $longdm);

		//lets add an rastermap too
		$rastermap = new RasterMap($this->grid_square,false);
		$rastermap->addLatLong($lat,$long);
		if (!empty($this->viewpoint_northings)) {
			$rastermap->addViewpoint($this->viewpoint_eastings,$this->viewpoint_northings,$this->viewpoint_grlen,$this->view_direction);
		} elseif (isset($this->view_direction) && strlen($this->view_direction) && $this->view_direction != -1) {
			$rastermap->addViewDirection($this->view_direction);
		}
		$smarty->assign_by_ref('rastermap', $rastermap);


		$smarty->assign('x', $this->grid_square->x);
		$smarty->assign('y', $this->grid_square->y);

		if ($this->view_direction > -1) {
			$smarty->assign('view_direction', ($this->view_direction%90==0)?strtoupper(heading_string($this->view_direction)):ucwords(heading_string($this->view_direction)) );
		}

		$level = ($this->grid_square->imagecount > 1)?6:5;
		$smarty->assign('sitemap',getSitemapFilepath($level,$this->grid_square)); 
	}
	
	
	/**
	* get a list of tickers for this image
	*/
	function& getTroubleTickets($aStatus)
	{
		if (!is_array($aStatus))
			die("GridImage::getTroubleTickets expects array param");
			
		$db=&$this->_getDB();
		
		$statuses="'".implode("','", $aStatus)."'";
	

		$tickets=array();
		
		$recordSet = &$db->Execute("select t.*,u.realname as suggester_name,DATEDIFF(NOW(),t.updated) as days from gridimage_ticket as t ".
			"inner join user as u using(user_id) ".
			"where t.gridimage_id={$this->gridimage_id} and t.status in ($statuses) order by t.updated desc");
		while (!$recordSet->EOF) 
		{
			//create new ticket object
			$t=new GridImageTroubleTicket;
			$t->loadFromRecordset($recordSet);
			
			if ($t->days > 365) {
				$t->days = 'over a year';
			} elseif ($t->days > 30) {
				$t->days = 'over '.intval($t->days/30).' months';
			} elseif ($t->days > 14) {
				$t->days = 'over '.intval($t->days/7).' weeks';
			} elseif ($t->days > 7) {
				$t->days = 'over a week';
			} elseif ($t->days > 1) {
				$t->days = $t->days.' days';
			} elseif ($t->days < 1) {
				$t->days = 'less than a day';
			} else {
				$t->days = '1 day';
			}
			
			//load its ticket items (should this be part of load from Recordset?
			$t->loadItems();
			$t->loadComments();
			
			$tickets[]=$t;
			$recordSet->MoveNext();
		}
		$recordSet->Close(); 
	
	
		return $tickets;
	}
	
	
	/**
	* given a temporary file, transfer to final destination for the image
	*/
	function storeImage($srcfile, $movefile=false)
	{
		$yz=sprintf("%02d", floor($this->gridimage_id/1000000));
		$ab=sprintf("%02d", floor(($this->gridimage_id%1000000)/10000));
		$cd=sprintf("%02d", floor(($this->gridimage_id%10000)/100));
		$abcdef=sprintf("%06d", $this->gridimage_id);
		$hash=$this->_getAntiLeechHash();

		$base=$_SERVER['DOCUMENT_ROOT'].'/geophotos';
		if (!is_dir("$base/$yz"))
			mkdir("$base/$yz");
		if (!is_dir("$base/$yz/$ab"))
			mkdir("$base/$yz/$ab");
		if (!is_dir("$base/$yz/$ab/$cd"))
			mkdir("$base/$yz/$ab/$cd");

		$dest="$base/$yz/$ab/$cd/{$abcdef}_{$hash}.jpg";
		if ($movefile)
			return @rename($srcfile, $dest);
		else
			return @copy($srcfile, $dest);
	}
	
	/**
	* calculate the path to the full size photo image
	* if you specify true for check_exists parameter (the default), the
	* function will verify the file exists and returnt he path to an
	* error image if not found. If you specify false, the function will
	* always return the image page whether it exists or not
	*
	* if $CONF['fetch_on_demand'] is set, this will try to fetch missing
	* images from the fetch_on_demand server, which must use the same
	* hash secret
	*/
	function _getFullpath($check_exists=true,$returntotalpath = false)
	{
		global $CONF;
		
		if (!empty($this->fullpath)) {
			return $this->fullpath;
		}
		
		$yz=sprintf("%02d", floor($this->gridimage_id/1000000));
		$ab=sprintf("%02d", floor(($this->gridimage_id%1000000)/10000));
		$cd=sprintf("%02d", floor(($this->gridimage_id%10000)/100));
		$abcdef=sprintf("%06d", $this->gridimage_id);
		$hash=$this->_getAntiLeechHash();
		if ($this->gridimage_id<1000000) {
			$fullpath="/photos/$ab/$cd/{$abcdef}_{$hash}.jpg";
		} else {
			$fullpath="/geophotos/$yz/$ab/$cd/{$abcdef}_{$hash}.jpg";
		}
		$ok=file_exists($_SERVER['DOCUMENT_ROOT'].$fullpath);
		
		if (!$ok)
		{
			
			//can we fetch it from elsewhere?
			if (isset($CONF['fetch_on_demand']) && ($_SERVER['HTTP_HOST']!=$CONF['fetch_on_demand']))
			{
				$url='http://'.$CONF['fetch_on_demand'].$fullpath;
				$fin=fopen($url, 'rb');
				
				if ($fin)
				{
					$target=$_SERVER['DOCUMENT_ROOT'].$fullpath;
					
					//create target dir
					$base=$_SERVER['DOCUMENT_ROOT'].'/geophotos';
					if (!is_dir("$base/$yz"))
						mkdir("$base/$yz");
					if (!is_dir("$base/$yz/$ab"))
						mkdir("$base/$yz/$ab");
					if (!is_dir("$base/$yz/$ab/$cd"))
						mkdir("$base/$yz/$ab/$cd");
			
					$fout=fopen($target, 'wb');
					if ($fout)
					{
						while (!feof($fin))
						{
							 $chunk = fread($fin, 8192);
							 fwrite($fout,$chunk);
	
						}	
						fclose($fout);
					}
						
					fclose($fin);
					
					$ok=file_exists($_SERVER['DOCUMENT_ROOT'].$fullpath);
		
				}
			}
		}

		if (!$ok)
			$fullpath="/photos/error.jpg";

		if ($returntotalpath)
			$fullpath="http://".$CONF['CONTENT_HOST'].$fullpath;

		return $fullpath;
	}
	
	/**
	* returns HTML img tag to display this image at full size
	*/
	function getFull($returntotalpath = true)
	{
		global $CONF;
		$fullpath=$this->_getFullpath();
		
		if (isset($this->cached_size)) {
			$size = $this->cached_size;
		} elseif ($this->gridimage_id) {
			global $memcache;
			$mkey = "{$this->gridimage_id}:F";
			//fails quickly if not using memcached!
			$size =& $memcache->name_get('is',$mkey);
			if (!$size) {
				$size=getimagesize($_SERVER['DOCUMENT_ROOT'].$fullpath);
			
				//fails quickly if not using memcached!
				$memcache->name_set('is',$mkey,$places,$memcache->compress,$memcache->period_long);
			}
			$this->cached_size = $size;
		} else {
			$size = array();
			$size[3] = '';
		}
		
		$title=htmlentities2($this->title);
		
		if (!empty($CONF['curtail_level']) && empty($GLOBALS['USER']->user_id) && isset($GLOBALS['smarty'])) {
			$fullpath = cachize_url("http://".$CONF['CONTENT_HOST'].$fullpath);
		} elseif ($returntotalpath)
			$fullpath="http://".$CONF['CONTENT_HOST'].$fullpath;
		
		$html="<img alt=\"$title\" src=\"$fullpath\" {$size[3]}/>";
		
		return $html;
	}
	
	/**
	* returns true if picture is wider than it is tall
	*/
	function isLandscape()
	{
		if (!$this->gridimage_id) {
			return 1;
		} 
		if (isset($this->cached_size)) {
			$result = $this->cached_size[0]>$this->cached_size[1];
			return $result;
		} 
		global $memcache;
		
		$mkey = "{$this->gridimage_id}:F";
		//fails quickly if not using memcached!
		$size =& $memcache->name_get('is',$mkey);
		if (!$size) {
			$fullpath=$this->_getFullpath();
			$size=getimagesize($_SERVER['DOCUMENT_ROOT'].$fullpath);
			
			//fails quickly if not using memcached!
			$memcache->name_set('is',$mkey,$size,$memcache->compress,$memcache->period_long);
		}
		$this->cached_size = $size;
		$result = $size[0]>$size[1];
		return $result;
	}
	
	/**
	* returns HTML img tag to display a square thumbnail that would fit the given dimensions
	* If the required thumbnail doesn't exist, it is created. This method is really
	* handy helper for Smarty templates, for instance, given an instance of this
	* class, you can use this {$image->getSquareThumbnail(100,100)} to show a thumbnail
	*/
	function getSquareThumbnail($maxw, $maxh)
	{
		
		global $CONF;
		//establish whether we have a cached thumbnail
		$ab=sprintf("%02d", floor(($this->gridimage_id%1000000)/10000));
		$cd=sprintf("%02d", floor(($this->gridimage_id%10000)/100));
		$abcdef=sprintf("%06d", $this->gridimage_id);
		$hash=$this->_getAntiLeechHash();

		$base=$_SERVER['DOCUMENT_ROOT'].'/photos';
		if ($this->gridimage_id<1000000) {
			$thumbpath="/photos/$ab/$cd/{$abcdef}_{$hash}_{$maxw}XX{$maxh}.jpg"; ##two XX's as windows isnt case sensitive!
		} else {
			$yz=sprintf("%02d", floor($this->gridimage_id/1000000));
			$thumbpath="/geophotos/$yz/$ab/$cd/{$abcdef}_{$hash}_{$maxw}XX{$maxh}.jpg"; ##two XX's as windows isnt case sensitive!
		}
		if (!file_exists($_SERVER['DOCUMENT_ROOT'].$thumbpath))
		{
			//get path to fullsize image, but don't fallback to error image..
			$fullpath=$this->_getFullpath(false);
			if ($fullpath != '/photos/error.jpg' && file_exists($_SERVER['DOCUMENT_ROOT'].$fullpath))
			{
				//generate resized image
				$fullimg = @imagecreatefromjpeg($_SERVER['DOCUMENT_ROOT'].$fullpath); 
				if ($fullimg)
				{
					$srcw=imagesx($fullimg);
					$srch=imagesy($fullimg);

					if (($srcw>$maxw) || ($srch>$maxh))
					{
						//figure out size of image we'll keep
						if ($srcw>$srch)
						{
							//landscape
							
							$srcx = round(($srcw - $srch)/2);
							$srcy = 0;
							
							$srcw = $srch;
						}
						else
						{
							//portrait
							
							$srcx = 0;
							$srcy = round(($srch - $srcw)/2);
							
							$srch = $srcw;
						}


						$resized = imagecreatetruecolor($maxw, $maxh);
						imagecopyresampled($resized, $fullimg, 0, 0, $srcx, $srcy, 
									$maxw,$maxh, $srcw, $srch);

						
						require_once('geograph/image.inc.php');
						UnsharpMask($resized,100,0.5,3);
						
							
						imagedestroy($fullimg);

						//save the thumbnail
						imagejpeg ($resized, $_SERVER['DOCUMENT_ROOT'].$thumbpath);
						imagedestroy($resized);
					}
					elseif ($srcw == 0 && $srch == 0)
					{
						//couldn't read image!
						$thumbpath="/photos/error.jpg";

						imagedestroy($fullimg);
					} 
					else
					{
						//requested thumb is larger than original - stick with original
						copy($_SERVER['DOCUMENT_ROOT'].$fullpath, $_SERVER['DOCUMENT_ROOT'].$thumbpath);
					}
				}
				else
				{
					//couldn't load full jpeg
					$thumbpath="/photos/error.jpg";
				}
			}
			else
			{
				//no original image! - return link to error image
				$thumbpath="/photos/error.jpg";
		
			}
		}
		
		
		if ($thumbpath=='/photos/error.jpg')
		{
			$html="<img src=\"$thumbpath\" width=\"$maxw\" height=\"$maxh\"/>";
		}
		else
		{
			$title=htmlentities2($this->title);
			
			$size=getimagesize($_SERVER['DOCUMENT_ROOT'].$thumbpath);
			if (!empty($CONF['enable_cluster'])) {
				$return['server']= str_replace('0',($this->gridimage_id%$CONF['enable_cluster']),"http://{$CONF['STATIC_HOST']}");
			} else {
				$return['server']= "http://".$CONF['CONTENT_HOST'];
			}
			$thumbpath = $return['server'].$thumbpath;
			
			if (isset($CONF['curtail_level']) && $CONF['curtail_level'] > 1 && empty($GLOBALS['USER']->user_id) && isset($GLOBALS['smarty'])) {
				$thumbpath = cachize_url($thumbpath);
			}
			
			$html="<img alt=\"$title\" src=\"$thumbpath\" {$size[3]}/>";
		}
		
		
		
		return $html;
	}

	
	/**
	* returns a GD image instance for a square thumbnail of the image
	*/
	function getSquareThumb($size)
	{
		$ab=sprintf("%02d", floor(($this->gridimage_id%1000000)/10000));
		$cd=sprintf("%02d", floor(($this->gridimage_id%10000)/100));
		$abcdef=sprintf("%06d", $this->gridimage_id);
		$hash=$this->_getAntiLeechHash();
		$img=null;
		
		
		$base=&$_SERVER['DOCUMENT_ROOT'];
		if ($this->gridimage_id<1000000) {
			$thumbpath="/photos/$ab/$cd/{$abcdef}_{$hash}_{$size}x{$size}.gd";
		} else {
			$yz=sprintf("%02d", floor($this->gridimage_id/1000000));
			$thumbpath="/geophotos/$yz/$ab/$cd/{$abcdef}_{$hash}_{$size}x{$size}.gd";
		}
		if (!file_exists($base.$thumbpath))
		{
			//get path to fullsize image, but don't fallback to error image..
			$fullpath=$this->_getFullpath(false);
			
			if ($fullpath != '/photos/error.jpg' && file_exists($base.$fullpath))
			{
				
		
				//generate resized image
				$fullimg = @imagecreatefromjpeg($base.$fullpath); 
				if ($fullimg)
				{
					$srcw=imagesx($fullimg);
					$srch=imagesy($fullimg);
					
					if ($srcw == 0 && $srch == 0)
					{
						//couldn't read image!
						$img=null;

						imagedestroy($fullimg);
					} else {
						//crop percentage is how much of the
						//image to keep in the thumbnail
						$crop=0.75;

						//figure out size of image we'll keep
						if ($srcw>$srch)
						{
							//landscape
							$s=$srch*$crop;


						}
						else
						{
							//portrait
							$s=$srcw*$crop;
						}

						$srcx = round(($srcw-$s)/2);
						$srcy = round(($srch-$s)/2);
						$srcw = $s;
						$srch=$s;

						$img = imagecreatetruecolor($size, $size);
						imagecopyresampled($img, $fullimg, 0, 0, $srcx, $srcy, 
									$size,$size, $srcw, $srch);

						require_once('geograph/image.inc.php');
						UnsharpMask($img,200,0.5,3);

						imagedestroy($fullimg);

						//save the thumbnail
						imagegd($img, $base.$thumbpath);
					}
					
				}
				else
				{
					//couldn't load full jpeg
					$img=null;
				}
			}
			else
			{
				//no original image!
				$img=null;
		
			}
			
		}
		else
		{
			$img=imagecreatefromgd($base.$thumbpath);
		}
		return $img;
	}
	
	/**
	* general purpose internal method for creating resized images - accepts
	* a variety of options. Use this to build specific methods for public
	* consumption
	* 
	* maxw : maximum width of image (default '100')
	* maxh : maximum height of image (default '100')
	* bestfit : show entire image inside max width/height. If false
	*           then the image is cropped to match the aspect ratio of
	*           of the target area first (default 'true')
	* attribname : attribute name of img tag which holds url (default 'src')
	* bevel : give image a raised edge (default true)
	* unsharp : do an unsharp mask on the image
	* 
	* returns an association array containing 'html' element, which contains
	* a fragment to load the image, and 'path' containg relative url to image
	*/
	function _getResized($params)
	{
		global $memcache,$CONF;
		$mkey = "{$this->gridimage_id}:".md5(serialize($params));
		//fails quickly if not using memcached!
		$result =& $memcache->name_get('ir',$mkey);
		if ($result && $result['url'] !='/photos/error.jpg')
			return $result;
	
		//unpack known params and set defaults
		$maxw=isset($params['maxw'])?$params['maxw']:100;
		$maxh=isset($params['maxh'])?$params['maxh']:100;
		$attribname=isset($params['attribname'])?$params['attribname']:'src';
		$bestfit=isset($params['bestfit'])?$params['bestfit']:true;
		$bevel=isset($params['bevel'])?$params['bevel']:true;
		$unsharp=isset($params['unsharp'])?$params['unsharp']:true;
		
		
		
		
		global $CONF;
		//establish whether we have a cached thumbnail
		$ab=sprintf("%02d", floor(($this->gridimage_id%1000000)/10000));
		$cd=sprintf("%02d", floor(($this->gridimage_id%10000)/100));
		$abcdef=sprintf("%06d", $this->gridimage_id);
		$hash=$this->_getAntiLeechHash();

		$base=$_SERVER['DOCUMENT_ROOT'].'/photos';
		
		if ($this->gridimage_id<1000000) {
			$thumbpath="/photos/$ab/$cd/{$abcdef}_{$hash}_{$maxw}x{$maxh}.jpg";
		} else {
			$yz=sprintf("%02d", floor($this->gridimage_id/1000000));
			$thumbpath="/geophotos/$yz/$ab/$cd/{$abcdef}_{$hash}_{$maxw}x{$maxh}.jpg";
		}

		$mkey = "{$this->gridimage_id}:{$maxw}x{$maxh}";
		//fails quickly if not using memcached!
		$size =& $memcache->name_get('is',$mkey);
		if ($size) {
			$return=array();
			$return['url']=$thumbpath;

			$title=$this->grid_reference.' : '.htmlentities2($this->title).' by '.$this->realname;
			if (!empty($CONF['enable_cluster'])) {
				$return['server']= str_replace('0',($this->gridimage_id%$CONF['enable_cluster']),"http://{$CONF['STATIC_HOST']}");
			} else {
				$return['server']= "http://".$CONF['CONTENT_HOST'];
			}
			$thumbpath = $return['server'].$thumbpath;
			
			if (isset($CONF['curtail_level']) && $CONF['curtail_level'] > 1 && empty($GLOBALS['USER']->user_id) && isset($GLOBALS['smarty'])) {
				$thumbpath = cachize_url($thumbpath);
			}
			
			$html="<img alt=\"$title\" $attribname=\"$thumbpath\" {$size[3]} />";
			
			$return['html']=$html;
					
			return $return;
		}

		if (!file_exists($_SERVER['DOCUMENT_ROOT'].$thumbpath))
		{
			//get path to fullsize image, but don't fallback to error image..
			$fullpath=$this->_getFullpath(false);
			
			if ($fullpath != '/photos/error.jpg' && file_exists($_SERVER['DOCUMENT_ROOT'].$fullpath))
			{
				if (strlen($CONF['imagemagick_path'])) {
					
					if (($info = getimagesize($_SERVER['DOCUMENT_ROOT'].$fullpath)) === FALSE) {
						//couldn't read image!
						$thumbpath="/photos/error.jpg";
					} else {
						list($width, $height, $type, $attr) = $info;
						
						if (($width>$maxw) || ($height>$maxh)) {
							$operation = ($maxw+$maxh < 400)?'thumbnail':'resize';
						} elseif (!$bestfit) {
							$operation = 'adaptive-resize';
						}
						
						if (isset($operation)) {
							$unsharpen=$unsharp?"-unsharp 0x1+0.8+0.1":"";
							
							$raised=$bevel?"-raise 2x2":"";
							
							$operation = ($maxw+$maxh < 400)?'thumbnail':'resize';
							
							if ($bestfit)
							{
								$cmd = sprintf ("\"%sconvert\" -$operation %ldx%ld  $unsharpen $raised -quality 87 jpg:%s jpg:%s", 
								$CONF['imagemagick_path'],
								$maxw, $maxh, 
								$_SERVER['DOCUMENT_ROOT'].$fullpath,
								$_SERVER['DOCUMENT_ROOT'].$thumbpath);
								
								
								passthru ($cmd);
							}
							else
							{
								$aspect_src=$width/$height;
								$aspect_dest=$maxw/$maxh;
								
								if ($aspect_src > $aspect_dest)
								{
									//src image is relatively wider - we'll trim the sides
									$optimum_width=round($height*$aspect_dest);
									$offset=round(($width-$optimum_width)/2);
									
									$crop="-crop {$optimum_width}x{$height}+$offset+0";
									
								}
								else
								{
									//src image is relatively taller - we'll trim the top/bottom
									$optimum_height=round($width/$aspect_dest);
									$offset=round(($height-$optimum_height)/2);
									
									$crop="-crop {$width}x{$optimum_height}+0+$offset";
								
								}
								
								$cmd = sprintf ("\"%sconvert\" $crop -quality 87 jpg:%s jpg:%s", 
								$CONF['imagemagick_path'],
								$_SERVER['DOCUMENT_ROOT'].$fullpath,
								$_SERVER['DOCUMENT_ROOT'].$thumbpath);
								
								
								passthru ($cmd);
								
								//now resize
								$cmd = sprintf ("\"%smogrify\" -$operation %ldx%ld $unsharpen $raised -quality 87 jpg:%s", 
								$CONF['imagemagick_path'],
								$maxw, $maxh, 
								$_SERVER['DOCUMENT_ROOT'].$thumbpath);
								
								
								passthru ($cmd);
							}
							
							

						} else {
							//requested thumb is larger than original - stick with original
							copy($_SERVER['DOCUMENT_ROOT'].$fullpath, $_SERVER['DOCUMENT_ROOT'].$thumbpath);
						}	
					}
				} else {
					//generate resized image
					$fullimg = @imagecreatefromjpeg($_SERVER['DOCUMENT_ROOT'].$fullpath); 
					if ($fullimg)
					{
						$srcw=imagesx($fullimg);
						$srch=imagesy($fullimg);

						if (($srcw>$maxw) || ($srch>$maxh))
						{
							//figure out size of image we'll keep
							if ($srcw>$srch)
							{
								//landscape
								$destw=$maxw;
								$desth=round(($destw * $srch)/$srcw);
							}
							else
							{
								//portrait
								$desth=$maxh;
								$destw=round(($desth * $srcw)/$srch);
							}


							$resized = imagecreatetruecolor($destw, $desth);
							imagecopyresampled($resized, $fullimg, 0, 0, 0, 0, 
										$destw,$desth, $srcw, $srch);
	
							if ($unsharp) {
								require_once('geograph/image.inc.php');
								UnsharpMask($resized,100,0.5,3);
							}
							imagedestroy($fullimg);

							//save the thumbnail
							imagejpeg ($resized, $_SERVER['DOCUMENT_ROOT'].$thumbpath,85);
							imagedestroy($resized);
						}
						elseif ($srcw == 0 && $srch == 0)
						{
							//couldn't read image!
							$thumbpath="/photos/error.jpg";
							
							imagedestroy($fullimg);
						} 
						else
						{
							//requested thumb is larger than original - stick with original
							copy($_SERVER['DOCUMENT_ROOT'].$fullpath, $_SERVER['DOCUMENT_ROOT'].$thumbpath);
						}
					}
					else
					{
						//couldn't load full jpeg
						$thumbpath="/photos/error.jpg";
					}
				}
			}
			else
			{
				//no original image! - return link to error image
				$thumbpath="/photos/error.jpg";
		
			}
		}
		
		$return=array();
		$return['url']=$thumbpath;
		
		if ($thumbpath=='/photos/error.jpg')
		{
			$html="<img $attribname=\"$thumbpath\" width=\"$maxw\" height=\"$maxh\" />";
		}
		else
		{
			$title=$this->grid_reference.' : '.htmlentities2($this->title).' by '.$this->realname;
			$size=getimagesize($_SERVER['DOCUMENT_ROOT'].$thumbpath);
			if (!empty($CONF['enable_cluster'])) {
				$return['server']= str_replace('0',($this->gridimage_id%$CONF['enable_cluster']),"http://{$CONF['STATIC_HOST']}");
			} else {
				$return['server']= "http://".$CONF['CONTENT_HOST'];
			}
			$thumbpath = $return['server'].$thumbpath;
			
			if (isset($CONF['curtail_level']) && $CONF['curtail_level'] > 1 && empty($GLOBALS['USER']->user_id) && isset($GLOBALS['smarty'])) {
				$thumbpath = cachize_url($thumbpath);
			}
			
			$html="<img alt=\"$title\" $attribname=\"$thumbpath\" {$size[3]} />";
			
			//fails quickly if not using memcached!
			$memcache->name_set('is',$mkey,$size,$memcache->compress,$memcache->period_med);
		}
		
		$return['html']=$html;
		
		return $return;
	}
	
	/**
	* returns HTML img tag to display a thumbnail that would fit the given dimensions
	* If the required thumbnail doesn't exist, it is created. This method is really
	* handy helper for Smarty templates, for instance, given an instance of this
	* class, you can use this {$image->getThumbnail(213,160)} to show a thumbnail
	*/
	function getThumbnail($maxw, $maxh,$urlonly = false,$fullalttag = false,$attribname = 'src')
	{
		if ($this->ext) {
			# (120,120,false,true);
			# $resized['html'];
			$title=$this->grid_reference.' : '.htmlentities2($this->title).' by '.$this->realname;
			#$html="<img alt=\"$title\" $attribname=\"$thumbpath\" {$size[3]} />";
			# width="120" height="90"
			$html="<img alt=\"$title\" $attribname=\"{$this->ext_thumb_url}\" />";
			return $html;
		}
		$params['maxw']=$maxw;
		$params['maxh']=$maxh;
		$params['attribname']=$attribname;
		$resized=$this->_getResized($params);
		
		if ($urlonly) {
			if ($urlonly === 2) 
				return $resized;
			else 
				return $resized['server'].$resized['url'];
		} else
			return $resized['html'];
	}	
	
	/**
	* returns HTML img tag to display a thumbnail that would EXACTLY fit the given dimensions
	* If the required thumbnail doesn't exist, it is created. This method is really
	* handy helper for Smarty templates, for instance, given an instance of this
	* class, you can use this {$image->getFixedThumbnail(213,160)} to show a thumbnail
	* 
	* Compare with getThumbnail, which is for getting a "best fit"
	*/
	function getFixedThumbnail($maxw, $maxh)
	{
		$params['maxw']=$maxw;
		$params['maxh']=$maxh;
		$params['bestfit']=false;
		$params['bevel']=false;
		$params['unsharp']=false;
		$resized=$this->_getResized($params);
		
		return $resized['html'];
	}	
	
	/**
	* Locks this image so its not shown to other moderators
	*/
	function lockThisImage($mid)
	{	
		$db=&$this->_getDB();
		
		$db->Execute("REPLACE INTO gridimage_moderation_lock SET user_id = $mid, gridimage_id = {$this->gridimage_id}");
	}
	
	/**
	* UnLocks this image so its now shown to other moderators
	*/
	function unlockThisImage($mid)
	{	
		$db=&$this->_getDB();
		
		$db->Execute("DELETE FROM gridimage_moderation_lock WHERE user_id = $mid AND gridimage_id = {$this->gridimage_id}");
	}
	
	/**
	* Check if this image is locked by another moderator
	*/
	function isImageLocked($mid = 0)
	{	
		$db=&$this->_getDB();

		return $db->getOne("
			select 
				m.realname
			from
				gridimage_moderation_lock as l
				inner join user as m on (m.user_id=l.user_id)
			where
				gridimage_id = {$this->gridimage_id}
				and m.user_id != $mid
				and lock_obtained > date_sub(NOW(),INTERVAL 1 HOUR)");
	}
	
	/**
	* find the moderator for the image
	*/
	function lookupModerator() 
	{
		$db=&$this->_getDB();
		if (empty($this->moderator_id))
			return;
		return $this->mod_realname = $db->getOne("select realname from user where user_id = {$this->moderator_id}");	
	}
	
	function setCredit($realname) {
		global $USER;

		if (!$this->isValid())
			return "Invalid image";

		$db = $this->_getDB();

		$db->Execute(sprintf("update gridimage set realname = %s where gridimage_id=%d",$db->Quote($realname),$this->gridimage_id));

		$ticket=new GridImageTroubleTicket();
		$ticket->setSuggester($USER->user_id);
		$ticket->setPublic('everyone'); ## dont thing any case for this to be anon, its either a mod or the owner
		$ticket->setImage($this->gridimage_id);
		#$ticket->setNotes("Credit changed to '$realname'");
		$ticket->updateField("realname", $this->realname, $realname, false);
		$status=$ticket->commit('closed');

		$this->realname = $realname;

		$this->updateCachedTables();
	}
	
	/**
	* Sets the moderation status for the image, intelligently updating user stats appropriately
	* status must either 'accepted' or 'rejected'
	* returns a textual describing the action taken
	*
	* This is all quite hairy stuff, as we need to maintain a number of 
	* counts and status fields in the database
	*/
	function setModerationStatus($status, $moderator_id)
	{
		$valid_status=array('accepted', 'rejected', 'geograph');
		
		if (!$this->isValid())
			return "Invalid image";
		
		$db=&$this->_getDB();
		
		if ($status==$this->moderation_status) {
			return "No change, still {$this->moderation_status}";
		}
		if (!in_array($status, $valid_status))
			return "Bad classification $status";
		
		//to get this far, the image is valid, the status
		//is valid, and it is a definite change of status
		
		
		//we want to detect changes in ftf status...a pending image is always ftf 0
		$original_ftf=$this->ftf;
		
		//todo: lock tables
		
		//you only get ftf if new status is 'geograph' and there are no other 
		//first geograph images
		$geographs= $db->GetOne("select count(*) from gridimage ".
					"where gridsquare_id={$this->gridsquare_id} and moderation_status='geograph' and ftf = 1");
		$this->ftf=0;
		if (($status=='geograph') && ($geographs==0))
		{
			$this->ftf=1;
			$geographs=1;
		}
					
		//update image status and ftf flag
		$sql="update gridimage set ".
			"moderation_status='$status',".
			"moderator_id='$moderator_id',".
			"moderated=now(),".
			"ftf={$this->ftf},".
			"seq_no={$this->seq_no} ".
			"where gridimage_id={$this->gridimage_id}";
		$db->query($sql);
		
		//if we've just cleared the ftf flag, we should check to see
		//the square contains other geographs, in which case, we award ftf to the
		//first one submitted
		if ($original_ftf && !$this->ftf)
		{
			$next_geograph= $db->GetOne("select gridimage_id from gridimage ".
				"where gridsquare_id={$this->gridsquare_id} and moderation_status='geograph' ".
				"order by gridimage_id");
			if ($next_geograph)
			{
				$db->Query("update gridimage set ftf=1 where gridimage_id={$next_geograph}");
				$db->Query("update gridimage_search set ftf=1 where gridimage_id={$next_geograph}");
			}

		}
		
		//todo: unlock tables. 
		
		//update maps on moderation if:
			//was pending
				//not now rejected
				//is now ftf or supp (cos might not be any ftf)
					//or was ftf (cos new ftf should take place
			//now rejected
				//was ftf or supp (cos might not be any ftf)
				
		
		
		//invalidate any cached maps (on anything except rejecting a pending image)
		$updatemaps = ( !($status == 'rejected' && $this->moderation_status == 'pending') );
	
		//fire an event (a lot of the stuff that follows should 
		//really be done asynchronously by an event handler
		require_once('geograph/event.class.php');
		new Event(EVENT_MODERATEDPHOTO, "{$this->gridimage_id},$updatemaps");
		
		
		//ok, update the image
		$this->moderation_status=$status;
	
		//updated cached tables
		$this->updateCachedTables();	
		
		//finally, we update status information for the gridsquare
		$this->grid_square->updateCounts();
		
	
	
		
		return "Classification is now $status";	
			
		
	}

	/**
	* Reassigns the reference of this image - callers of this are responsible for ensuring
	* only authorized calls can be made, but the method performs full error checking of 
	* the supplied reference
	*/
	function reassignGridsquare($grid_reference, &$error)
	{
		$ok=false;
		
		//is the reference valid?
		//old one is in $this->grid_square
		$newsq=new GridSquare;
		if (is_object($this->db))
			$newsq->_setDB($this->db);
		if ($newsq->setByFullGridRef($grid_reference,false,true))
		{
			$db=&$this->_getDB();
			
			//ensure this is a real change
			if ($newsq->gridsquare_id != $this->gridsquare_id) {
			
				//get sequence number of target square - for a rejected image
				//we use a negative sequence number
				if ($this->moderation_status!='rejected')
				{
					$seq_no = $this->db->GetOne("select max(seq_no) from gridimage ".
						"where gridsquare_id={$newsq->gridsquare_id}");
					$seq_no=max($seq_no+1, 0);
				}
				else
				{
					$seq_no = $this->db->GetOne("select min(seq_no) from gridimage ".
						"where gridsquare_id={$newsq->gridsquare_id}");
					$seq_no=min($seq_no-1, -1);
				}

				//was this image ftf? 
				if ($this->ftf)
				{
					//reset the ftf flag
					$this->ftf=0;

					//need to assign ftf to another image in the square if possible
					$next_geograph= $db->GetOne("select gridimage_id from gridimage ".
						"where gridsquare_id={$this->gridsquare_id} and moderation_status='geograph' ".
						"and gridimage_id<>{$this->gridimage_id} ".
						"order by gridimage_id");
					if ($next_geograph)
					{
						$db->Query("update gridimage set ftf=1 where gridimage_id={$next_geograph}");
						$db->Query("update gridimage_search set ftf=1 where gridimage_id={$next_geograph}");
					}

				}

				//does the image get ftf in the target square?
				if ($this->moderation_status=='geograph')
				{
					$geographs= $db->GetOne("select count(*) from gridimage ".
						"where gridsquare_id={$newsq->gridsquare_id} and moderation_status='geograph' and ftf = 1");
					if ($geographs==0)
						$this->ftf=1;
				}
				
				$sql_set = "gridsquare_id='{$newsq->gridsquare_id}',".
							"seq_no=$seq_no,ftf=$this->ftf, ";
			}
				//if not a new square only update nateastings and natnorthings
			
			//we DONT use getNatEastings here because only want them if it more than 4 figure
			$east=$newsq->nateastings+0;
			$north=$newsq->natnorthings+0;

			//reassign image
			$db->Execute("update gridimage set $sql_set ".
				"nateastings=$east,natnorthings=$north,natgrlen='{$newsq->natgrlen}' ".
				"where gridimage_id='$this->gridimage_id'");
			
			//ensure this is a real change
			if ($newsq->gridsquare_id != $this->gridsquare_id) 
			{
				//fire an event (some of the stuff that follows 
				//might be better as an event handler
				require_once('geograph/event.class.php');
				new Event(EVENT_MOVEDPHOTO, "{$this->gridimage_id},{$this->grid_square->grid_reference},{$newsq->grid_reference}");
							
				//update cached data for old square and new square
				$this->grid_square->updateCounts();
				$newsq->updateCounts();


				//invalidate any cached maps
					//handled by the event above 
			
				//update placename cached column
					//handled by the event above 
			
				//updated cached tables
					//this isnt needed as reassignGridsquare is only called before commitChanges
				//$this->updateCachedTables();
				
				//updateCachedTables needs to know the new gridref for the lat/long calc!
				$this->newsq =& $newsq;
			}
			
			
			$ok=true;
		}
		else
		{
			//bad grid reference
			$ok=false;
			$error=$newsq->errormsg;
		}
		return $ok;			
	}
	
	
	/**
	* gets a human readable version of the potentially part date
	*/
	function getFormattedTakenDate()
	{
		return getFormattedDate($this->imagetaken);
	}
	
	/**
	* Saves selected members to the gridimage record
	*/
	function commitChanges()
	{
		$db=&$this->_getDB();
		
		$sql="update gridimage set title=".$db->Quote($this->title).
			", comment=".$db->Quote($this->comment).
			", imageclass=".$db->Quote($this->imageclass).
			", imagetaken=".$db->Quote($this->imagetaken).
			", viewpoint_eastings=".$db->Quote($this->viewpoint_eastings).
			", viewpoint_northings=".$db->Quote($this->viewpoint_northings).
			", viewpoint_grlen='{$this->viewpoint_grlen}'".					
			", view_direction=".$db->Quote($this->view_direction).
			", use6fig=".$db->Quote($this->use6fig).
			" where gridimage_id = '{$this->gridimage_id}'";
		$db->Execute($sql);
		
		//fire an event 
		require_once('geograph/event.class.php');
		new Event(EVENT_UPDATEDPHOTO, "{$this->gridimage_id}");
		
		//updated cached tables
		$this->updateCachedTables();
	}
	
	
	/**
	* Saves update tables based on gridimage
	*/
	function updateCachedTables()
	{
		$db=&$this->_getDB();
		//quick sanity check
		if (!$this->gridimage_id) 
			die("no gridimage_id supplied to updateCachedTables");	
	
		if ($this->moderation_status == 'rejected' || $this->moderation_status == 'pending') {
			$sql="DELETE FROM gridimage_search WHERE gridimage_id = '{$this->gridimage_id}'";
			$db->Execute($sql);
			
			$db->Execute("DELETE FROM wordnet1 WHERE gid = {$this->gridimage_id}");
			$db->Execute("DELETE FROM wordnet2 WHERE gid = {$this->gridimage_id}");
			$db->Execute("DELETE FROM wordnet3 WHERE gid = {$this->gridimage_id}");
		
		} elseif ($this->moderation_status) {
			require_once('geograph/conversions.class.php');
			$conv = new Conversions;
			if (isset($this->newsq)) {
				$square = $this->newsq;
			} else {
				$square = $this->grid_square;
			}
			if (!$square) 
				die("ERROR: no square known in updateCachedTables");
			if ($square->nateastings) {
				list($lat,$long) = $conv->national_to_wgs84($square->nateastings,$square->natnorthings,$square->reference_index);
			} else {
				list($lat,$long) = $conv->internal_to_wgs84($square->x,$square->y,$square->reference_index);
			}
	
			$sql="REPLACE INTO gridimage_search
			SELECT gridimage_id,gi.user_id,moderation_status,title,submitted,imageclass,imagetaken,upd_timestamp,x,y,gs.grid_reference,gi.realname!='' as credit_realname,if(gi.realname!='',gi.realname,user.realname) as realname,reference_index,comment,$lat,$long,ftf,seq_no,point_xy,GeomFromText('POINT($long $lat)')
			FROM gridimage AS gi INNER JOIN gridsquare AS gs USING(gridsquare_id)
			INNER JOIN user ON(gi.user_id=user.user_id)
			WHERE gridimage_id = '{$this->gridimage_id}'";
			$db->Execute($sql);		
		} else {
			//fall back if we dont know the moduration status then lets load it and start again!
			$this->loadFromId($this->gridimage_id);	
			return $this->updateCachedTables();
		}
		
		
	}
	
	/**
	* Saves update tables based on gridimage
	*/
	function updatePlaceNameId($gridsquare = null)
	{
		global $CONF;
		$db=&$this->_getDB();
		
		if (!$gridsquare) 
			$gridsquare = $this->grid_square;
		
		if (!isset($gridsquare->nateastings))
			$gridsquare->getNatEastings();

		$gaz = new Gazetteer();
		
		//to optimise the query, we scan a square centred on the
		//the required point
		$radius = 30000;

		$places['pid'] = $gaz->findBySquare($gridsquare,$radius,array('C','T'));	
		
		$db->Execute("update gridimage set placename_id = '{$places['pid']}',upd_timestamp = '{$this->upd_timestamp}' where gridimage_id = {$this->gridimage_id}");
	}	
	
}
?>
