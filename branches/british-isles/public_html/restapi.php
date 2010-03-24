<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2006 Paul Dixon (lordelph@gmail.com)
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
require_once('geograph/gridimage.class.php');
require_once('geograph/imagelist.class.php');
require_once('geograph/gridsquare.class.php');


/*
This file is intended to provide a REST style API - at the moment it does not
require a key but we might need to alter that if usage of it takes off

A rewrite rule maps api requests to this script, the script must parse out any
additional parameters

API

Get photo metadata
-----------------------------------------------------
Example URL: www.geograph.org.uk/api/photo/123456
Result: XML file containing all metadata


TODO
- rewrite using DOM methods when we switch to php5
*/

class EmptyClass {} 

class RestAPI
{
	var $db=null;
	var $params=array();
	var $output='xml';
	
	function handleUser()
	{
		$user_id=intval($this->params[0]);
		
		$profile=new GeographUser($user_id);
		if ($profile->registered)
		{
			$profile->getStats();
			if (isset($profile->stats) && count($profile->stats))
			{
				$this->beginResponse();
		
				echo '<status state="ok"/>';
				echo '<realname>'.htmlentities2($profile->realname).'</realname>';
				echo '<nickname>'.htmlentities2($profile->nickname).'</nickname>';
				
				echo "<stats";
				foreach ($profile->stats as $key => $value) {
					if (!is_numeric($key))
						echo " $key=\"$value\"";
				}
				echo " />";
				
				$this->endResponse();
			}
			else
			{
				$this->error("User $user_id unavailable (or they have not contributed anything)");	
			}
		}
		else
		{
			$this->error("Invalid user id $user_id");	
		}
	}
	
	function handlePhoto()
	{
		$gridimage_id=intval($this->params[0]);
		
		$image=new GridImage;
		if ($image->loadFromId($gridimage_id,1))
		{
			if ($image->moderation_status=='geograph' || $image->moderation_status=='accepted')
			{
				$this->beginResponse();

				if ($this->output=='json') {
					
					require_once '3rdparty/JSON.php';
					$json = new Services_JSON();
					$obj = new EmptyClass;
					
					$obj->title = $image->title;
					$obj->grid_reference = $image->grid_reference;
					$obj->profile_link = $image->profile_link;
					$obj->realname = $image->realname;
					
					$details = $image->getThumbnail(120,120,2);
					$obj->imgserver = $details['server'];
					$obj->thumbnail = $details['url'];
					$obj->image = $image->_getFullpath();
					$obj->sizeinfo = $image->_getFullSize();
					$obj->sizeinfo[3] = "0";
					$obj->taken = $image->imagetaken;
					$obj->submitted = strtotime($image->submitted);
					$obj->category = $image->category;
					$obj->comment = $image->comment;
					
					print $json->encode($obj);
				} else {
					echo '<status state="ok"/>';

					echo '<title>'.utf8_encode(htmlentities2($image->title)).'</title>';
					echo '<gridref>'.htmlentities($image->grid_reference).'</gridref>';
					echo "<user profile=\"http://{$_SERVER['HTTP_HOST']}{$image->profile_link}\">".htmlentities($image->realname).'</user>';

					echo preg_replace('/alt=".*?" /','',$image->getFull());

					$details = $image->getThumbnail(120,120,2);
					echo '<thumbnail>'.$details['server'].$details['url'].'</thumbnail>';
					echo '<taken>'.htmlentities($image->imagetaken).'</taken>';
					echo '<submitted>'.htmlentities($image->submitted).'</submitted>';
					echo '<category>'.utf8_encode(htmlentities2($image->imageclass)).'</category>';
					echo '<comment><![CDATA['.utf8_encode(htmlentities2($image->comment)).']]></comment>';
					
					$size = $image->_getFullSize(); //uses cached_size
					if (!empty($size[4])) {
						echo "<original width=\"{$size[4]}\" height=\"{$size[5]}\"/>";
					}
				}
				
				$this->endResponse();
			}
			else
			{
				$this->error("Image $gridimage_id unavailable ({$image->moderation_status})");	
			}
		}
		else
		{
			$this->error("Invalid image id $gridimage_id");	
		}
	}
	
	function handleSnippet()
	{
		$ids = $this->params[0];
		
		if (preg_match('/^\d+(,\d+)*$/',$ids))
		{
			$db = GeographDatabaseConnection(true);

			//the param has already been validated to be save by the regexp
			
			$images = $db->getAssoc("SELECT gridimage_id,COUNT(*) AS count FROM gridimage_snippet WHERE gridimage_id IN ($ids) GROUP BY gridimage_id"); 
			
			$this->beginResponse();

			if ($this->output=='json') {

				require_once '3rdparty/JSON.php';
				$json = new Services_JSON();

				print $json->encode($images);
			} else {
				echo '<status state="ok"/>';

				foreach ($images as $id => $count) {
					echo "<image id=\"$id\" snippets=\"$count\"/>";
				}
			}

			$this->endResponse();
		}
		else
		{
			$this->error("Invalid Request");
		}
		
	}
	
	function handleLatLong()
	{
		
		if (preg_match("/\b(-?\d+\.?\d*)[, ]+(-?\d+\.?\d*)\b/",$this->params[1],$ll)) {
			$this->beginResponse();
			
			//todo - we could do this directly rather than via the search engine
			require_once('geograph/searchcriteria.class.php');
			require_once('geograph/searchengine.class.php');
	
			$engine = new SearchEngineBuilder('#'); 
			$engine->searchuse = "syndicator";
			$_GET['i'] = $engine->buildSimpleQuery($this->params[1],intval($this->params[0]),false,isset($_GET['u'])?$_GET['u']:0);
			
			$images = new SearchEngine($_GET['i']);
				
			$images->Execute($pg);
			
			if (!empty($images->results))
			{
				$images =& $images->results;
				$count = count($images);
				
				if ($this->output=='json') {
					require_once '3rdparty/JSON.php';
					$json = new Services_JSON();
					$whitelist = array('gridimage_id'=>1, 'seq_no'=>1, 'user_id'=>1, 'ftf'=>1, 'moderation_status'=>1, 'title'=>1, 'comment'=>1, 'submitted'=>1, 'realname'=>1, 'nateastings'=>1, 'natnorthings'=>1, 'natgrlen'=>1, 'imageclass'=>1, 'imagetaken'=>1, 'upd_timestamp'=>1, 'viewpoint_eastings'=>1, 'viewpoint_northings'=>1, 'viewpoint_grlen'=>1, 'view_direction'=>1, 'use6fig'=>1, 'credit_realname'=>1, 'profile_link'=>1,'wgs84_lat'=>1,'wgs84_long'=>1);
					
					foreach ($images as $i => $image) {
						foreach ($image as $k => $v) {
							if (empty($v) || empty($whitelist[$k])) {
								unset($images[$i]->$k);
							}
						}
						$images[$i]->image = $image->_getFullpath(true,true);
						$images[$i]->thumbnail = $image->getThumbnail(120,120,true);
					}
					print $json->encode($images);
				} else {
			
					echo '<status state="ok" count="'.$count.'" total="'.$images->resultCount.'"/>';

					foreach ($images as $i => $image) {
						if ($image->moderation_status=='geograph' || $image->moderation_status=='accepted')
						{
							echo " <image url=\"http://{$_SERVER['HTTP_HOST']}/photo/{$image->gridimage_id}\">";

							echo ' <title>'.utf8_encode(htmlentities($image->title)).'</title>';
							echo " <user profile=\"http://{$_SERVER['HTTP_HOST']}{$image->profile_link}\">".utf8_encode(htmlentities($image->realname)).'</user>';

							echo ' '.preg_replace('/alt=".*?" /','',$image->getThumbnail(120,120));
							
							if (!empty($image->nateastings))
								echo ' <location grid="'.($image->reference_index).'" eastings="'.($image->nateastings).'" northings="'.($image->natnorthings).'" figures="'.($image->natgrlen).'"/>';
							if (!empty($image->wgs84_lat))
								echo ' <location grid="'.($image->reference_index).'" lat="'.($image->wgs84_lat).'" long="'.($image->wgs84_long).'"/>';
								
							echo '</image>';
						}
					}
				}
			} 
			else 
			{
				if ($this->output=='json') {
					print "{error: '0 results'}";
				} else {
					echo '<status state="ok" count="0"/>';
				}
			}
			$this->endResponse();
		}
		else
		{
			$this->error("Invalid grid reference ".$this->params[0]);
		}
		
	}
	
	function handleGridrefMore()
	{
		return $this->handleGridref(true);
	}
	
	function handleGridref($more = false)
	{
		$square=new GridSquare;
		$grid_given=true;
		$grid_ok=$square->setByFullGridRef($this->params[0]);
		
		$image=new GridImage;
		if ($grid_ok)
		{
			$this->beginResponse();

			if ($square->imagecount)
			{
				$images=$square->getImages(false,'',"order by null");
				$count = count($images);
				
				if ($this->output=='json') {
					require_once '3rdparty/JSON.php';
					$json = new Services_JSON();
					$whitelist = array('gridimage_id'=>1, 'seq_no'=>1, 'user_id'=>1, 'ftf'=>1, 'moderation_status'=>1, 'title'=>1, 'comment'=>1, 'submitted'=>1, 'realname'=>1, 'nateastings'=>1, 'natnorthings'=>1, 'natgrlen'=>1, 'imageclass'=>1, 'imagetaken'=>1, 'upd_timestamp'=>1, 'viewpoint_eastings'=>1, 'viewpoint_northings'=>1, 'viewpoint_grlen'=>1, 'view_direction'=>1, 'use6fig'=>1, 'credit_realname'=>1, 'profile_link'=>1);
					
					foreach ($images as $i => $image) {
						foreach ($image as $k => $v) {
							if (empty($v) || empty($whitelist[$k])) {
								unset($images[$i]->$k);
							}
						}
						$images[$i]->thumbnail = $image->getThumbnail(120,120,true);
					}
					print $json->encode($images);
				} else {
			
					echo '<status state="ok" count="'.$count.'"/>';

					foreach ($images as $i => $image) {
						if ($image->moderation_status=='geograph' || $image->moderation_status=='accepted')
						{
							echo " <image url=\"http://{$_SERVER['HTTP_HOST']}/photo/{$image->gridimage_id}\">";

							echo ' <title>'.utf8_encode(htmlentities($image->title)).'</title>';
							echo " <user profile=\"http://{$_SERVER['HTTP_HOST']}{$image->profile_link}\">".utf8_encode(htmlentities($image->realname)).'</user>';

							echo ' '.preg_replace('/alt=".*?" /','',$image->getThumbnail(120,120));

							if ($more) {
								echo '<taken>'.htmlentities($image->imagetaken).'</taken>';
								echo '<submitted>'.htmlentities($image->submitted).'</submitted>';
								echo '<category>'.utf8_encode(htmlentities2($image->imageclass)).'</category>';
								echo '<comment><![CDATA['.utf8_encode(htmlentities2($image->comment)).']]></comment>';
							}

							echo ' <location grid="'.($square->reference_index).'" eastings="'.($image->nateastings).'" northings="'.($image->natnorthings).'" figures="'.($image->natgrlen).'"/>';
							echo '</image>';
						}
					}
				}
			} 
			else 
			{
				if ($this->output=='json') {
					print "{error: '0 results'}";
				} else {
					echo '<status state="ok" count="0"/>';
				}
			}
			$this->endResponse();
		}
		else
		{
			$this->error("Invalid grid reference ".$this->params[0]);
		}
		
	}
	
	function handleUserTimeline()
	{
		$uid=intval($this->params[0]);

		$profile=new GeographUser($uid);
		if ($profile->realname)
		{
			header("Content-Type:text/xml");
					
			echo "<data>\n";
			$images=new ImageList;
					
			$images->getImagesByUser($uid, array('accepted', 'geograph'),'RAND()',200);
		
			foreach ($images->images as $i => $image) {
				if (!preg_match('/00(-|$)/',$image->imagetaken)) {
					$bits = explode('-',$image->imagetaken);
					$date = mktime(0,0,0,$bits[1],$bits[2],$bits[0]);
					printf("	<event start=\"%s\" title=\"%s\">%s &lt;b&gt;%s&lt;/b&gt;&lt;br/&gt; %s</event>\n",
						date('M d Y 00:00:00',$date).' GMT',
						htmlentities2($image->title),
						htmlentities("<a href=\"/photo/{$image->gridimage_id}\">".$image->getThumbnail(120,120)."</a>"),
						$image->grid_reference,
						htmlentities2(GeographLinks($image->comment))
					);
				}
			}
			
			echo "</data>\n";
		}
		else
		{
			$this->error("Invalid User id $uid");	
		}
	}
	
	function beginResponse()
	{
		customExpiresHeader(360,true,true);
		if ($this->output=='json') {
			if (!empty($this->callback)) {
				echo "{$this->callback}(";
			}
		} else {
			header("Content-Type:text/xml");
			echo '<?xml version="1.0" encoding="UTF-8"?>';
			echo '<geograph>';
		}
	}
	
	function endResponse()
	{
		if ($this->output=='json') {
			if (!empty($this->callback)) {
				echo ");";
			}
		} else {
			echo '</geograph>';
		}
	}
	
	/**
	* display appropriate error
	*/
	function error($msg)
	{
		header("HTTP/1.0 400 Bad Request");

		$this->beginResponse();
		
		if ($this->output=='json') {
			print("{error: '".addslashes($msg)."'}");
		} else {
			echo '<status state="failed">';
			echo '<error code="400">';
			echo '<message>'.htmlentities($msg).'</message>';
			echo '</error>';
			echo '</status>';
		}

		$this->endResponse();
		
	}
	
	/**
	* dispatch request to appropriate handler
	*/
	function dispatch()
	{
		if (isset($_GET['output']) && $_GET['output']=='json') {
			$this->output='json';
			if (isset($_GET['callback'])) {
				$this->callback=preg_replace('/[^\w$]+/','',$_GET['callback']);
				if (empty($this->callback)) {
					$this->callback = "geograph_callback";
				}
			} elseif (isset($_GET['_callback'])) {
				$this->callback=preg_replace('/[^\w$]+/','',$_GET['_callback']);
			}
		}
	
		if ($_SERVER["PATH_INFO"]) {
			$this->params=explode('/', $_SERVER["PATH_INFO"]);
		} else {
			$this->params=explode('/', $_SERVER["SCRIPT_NAME"]);
		}
		//eat params we don't need - empty initial param and 'api'
		if (strlen($this->params[0])==0)
			array_shift($this->params);
		array_shift($this->params);
		
		$method=array_shift($this->params);
		$method=preg_replace('/[^a-z_]/i', '', $method);
		
		$handler="handle".ucfirst($method);
		
		if (method_exists($this,$handler))
		{
			$this->$handler();	
		}
		else
		{
			$this->error("Unknown method $method");	
		}
	}	
}

$api=new RestAPI();
$api->dispatch();



?>
