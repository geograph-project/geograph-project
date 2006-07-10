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
	
	function handlePhoto()
	{
		$db=NewADOConnection($GLOBALS['DSN']);
		
		$gridimage_id=intval($this->params[0]);
		
		$image=new GridImage;
		if ($image->loadFromId($gridimage_id))
		{
			if ($image->moderation_status=='geograph' || $image->moderation_status=='accepted')
			{
				$this->beginResponse();
		
				echo '<status state="ok"/>';
				
				echo '<title>'.htmlentities($image->title).'</title>';
				echo '<gridref>'.htmlentities($image->grid_reference).'</gridref>';
				echo "<user profile=\"http://{$_SERVER['HTTP_HOST']}/profile.php?u={$image->user_id}\">".htmlentities($image->realname).'</user>';
				
				$url=$image->_getFullpath();
				$size=getimagesize($_SERVER['DOCUMENT_ROOT'].$url);
				echo "<img src=\"http://{$_SERVER['HTTP_HOST']}{$url}\" width=\"{$size[0]}\" height=\"{$size[1]}\" />";
				
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
	
	function handleUserTimeline()
	{
		$db=NewADOConnection($GLOBALS['DSN']);

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
						htmlentities($image->title),
						htmlentities("<a href=\"/photo/{$image->gridimage_id}\">".$image->getThumbnail(120,120))."</a>",
						$image->grid_reference,
						htmlentities(GeographLinks($image->comment))
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
		header("Content-Type:text/xml");
		echo '<?xml version="1.0" encoding="UTF-8"?>';
		echo '<geograph>';
	}
	
	function endResponse()
	{
		echo '</geograph>';
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
