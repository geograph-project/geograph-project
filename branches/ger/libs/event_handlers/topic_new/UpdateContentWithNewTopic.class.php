<?php
/**
 * $Project: GeoGraph $
 * $Id: UpdateDiscussionCrossReferencesWithNewTopic.class.php 4204 2008-03-04 23:33:06Z barry $
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

require_once("geograph/eventhandler.class.php");
require_once("geograph/content.topic.inc.php");

//filename of class file should correspond to class name, e.g.  myhandler.class.php
class UpdateContentWithNewTopic extends EventHandler
{
	function processEvent(&$event)
	{
		//perform actions
		$topic_id = $event['event_param'];

		$db=&$this->_getDB();

		add_topic_to_content($topic_id,$db);
	
		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
	
}
?>
