<?php
/**
 * $Project: GeoGraph $
 * $Id: MaintainRecentlyModeratedList.class.php 3753 2007-09-05 19:34:21Z barry $
 * 
 * GeoGraph geographic photo archive project
 * http://geograph.sourceforge.net/
 *
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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


require_once("geograph/eventhandler.class.php");
require_once("geograph/gridsquare.class.php");


//filename of class file should correspond to class name, e.g.  myhandler.class.php
class SendtoAmazonS3 extends EventHandler
{
	function processEvent(&$event)
	{
		global $CONF;
		$ok = true;
		
		if (!empty($CONF['awsAccessKey'])) {
			require_once("3rdparty/S3.php");
			
			$s3 = new S3($CONF['awsAccessKey'], $CONF['awsSecretKey']);

			
			list($gridimage_id,$user_id,$storedoriginal) = explode(',',$event['event_param']);

			$image=new GridImage();
			$image->gridimage_id = $gridimage_id;
			$image->user_id = $user_id;

			//beware image is not a full image object. 


			$fullpath = $image->_getFullpath();

			if ($fullpath!="/photos/error.jpg") {
			
				$ok = $s3->putObjectFile($_SERVER['DOCUMENT_ROOT'].$fullpath, $CONF['awsS3Bucket'], preg_replace("/^\//",'',$fullpath), S3::ACL_PRIVATE);
			}

			if ($storedoriginal) {

				$originalpath = $image->_getOriginalpath();
				
				if ($originalpath!="/photos/error.jpg") {
				
					$ok = $ok && $s3->putObjectFile($_SERVER['DOCUMENT_ROOT'].$originalpath, $CONF['awsS3Bucket'], preg_replace("/^\//",'',$originalpath), S3::ACL_PRIVATE);
				}
			}
		}
		
		//return true to signal completed processing
		//return false to have another attempt later
		return $ok;
	}
	
}

