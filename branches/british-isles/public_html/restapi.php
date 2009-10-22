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

class RestAPI
{
	var $db=null;
	var $params=array();
	
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
		
				echo '<status state="ok"/>';
				
				echo '<title>'.htmlentities2($image->title).'</title>';
				echo '<gridref>'.htmlentities($image->grid_reference).'</gridref>';
				echo "<user profile=\"http://{$_SERVER['HTTP_HOST']}{$image->profile_link}\">".htmlentities($image->realname).'</user>';
				
				echo preg_replace('/alt=".*?" /','',$image->getFull());
				
				$details = $image->getThumbnail(120,120,2);
				echo '<thumbnail>'.$details['server'].$details['url'].'</thumbnail>';
				echo '<taken>'.htmlentities($image->imagetaken).'</taken>';
				echo '<submitted>'.htmlentities($image->submitted).'</submitted>';
				echo '<category>'.htmlentities2($image->imageclass).'</category>';
				echo '<comment><![CDATA['.htmlentities2($image->comment).']]></comment>';
				
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
				
				if (isset($_GET['output']) && $_GET['output']=='json') {
					require_once '3rdparty/JSON.php';
					$json = new Services_JSON();
					$whitelist = array();
					$whitelist['gridimage_id'] = 1;
					$whitelist['seq_no'] = 1;
					$whitelist['user_id'] = 1;
					$whitelist['ftf'] = 1;
					$whitelist['moderation_status'] = 1;
					$whitelist['title'] = 1;
					$whitelist['comment'] = 1;
					$whitelist['submitted'] = 1;
					$whitelist['realname'] = 1;
					$whitelist['nateastings'] = 1;
					$whitelist['natnorthings'] = 1;
					$whitelist['natgrlen'] = 1;
					$whitelist['imageclass'] = 1;
					$whitelist['imagetaken'] = 1;
					$whitelist['upd_timestamp'] = 1;
					$whitelist['viewpoint_eastings'] = 1;
					$whitelist['viewpoint_northings'] = 1;
					$whitelist['viewpoint_grlen'] = 1;
					$whitelist['view_direction'] = 1;
					$whitelist['use6fig'] = 1;
					$whitelist['credit_realname'] = 1;
					$whitelist['profile_link'] = 1;
					
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

							echo ' <title>'.htmlentities($image->title).'</title>';
							echo " <user profile=\"http://{$_SERVER['HTTP_HOST']}{$image->profile_link}\">".htmlentities($image->realname).'</user>';

							echo ' '.preg_replace('/alt=".*?" /','',$image->getThumbnail(120,120));

							if ($more) {
								echo '<taken>'.htmlentities($image->imagetaken).'</taken>';
								echo '<submitted>'.htmlentities($image->submitted).'</submitted>';
								echo '<category>'.htmlentities2($image->imageclass).'</category>';
								echo '<comment><![CDATA['.htmlentities2($image->comment).']]></comment>';
							}

							echo ' <location grid="'.($square->reference_index).'" eastings="'.($image->nateastings).'" northings="'.($image->natnorthings).'" figures="'.($image->natgrlen).'"/>';
							echo '</image>';
						}
					}
				}
			} 
			else 
			{
				if (isset($_GET['output']) && $_GET['output']=='json') {
					die("{error: '{$square->errormsg}'}");
				} else {
					echo '<status state="ok" count="0"/>';
				}
			}
			$this->endResponse();
		}
		else
		{
			if (isset($_GET['output']) && $_GET['output']=='json') {
				die("{error: 'Invalid grid reference ".$this->params[0]."'}");
			} else {
				$this->error("Invalid grid reference ".$this->params[0]);
			}
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
		if (isset($_GET['output']) && $_GET['output']=='json') {
		
		} else {
			header("Content-Type:text/xml");
			echo '<?xml version="1.0" encoding="UTF-8"?>';
			echo '<geograph>';
		}
	}
	
	function endResponse()
	{
		if (isset($_GET['output']) && $_GET['output']=='json') {
		
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
		
		echo '<status state="failed">';
		echo '<error code="400">';
		echo '<message>'.htmlentities($msg).'</message>';
		echo '</error>';
		echo '</status>';
		
		$this->endResponse();
		
	}
	
	/**
	* dispatch request to appropriate handler
	*/
	function dispatch()
	{
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
