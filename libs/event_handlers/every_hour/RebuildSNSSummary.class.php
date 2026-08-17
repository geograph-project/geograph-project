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
class RebuildSNSSummary extends EventHandler
{
	function processEvent(&$event)
	{
		$db=&$this->_getDB();

		// Extract the core query definition so it remains DRY across INSERT and CREATE paths
		$selectFields = "
		    md5(LOWER(TRIM(JSON_VALUE(Message,'$.mail.destination[0]')))) as email_md5,
		    TimeStamp,
		    CONCAT_WS(', ',
		        JSON_VALUE(Message,'$.notificationType'),
		        JSON_VALUE(Message,'$.bounce.bounceType'),
		        NULLIF(JSON_VALUE(Message,'$.bounce.bounceSubType'),'General'),
		        JSON_VALUE(Message,'$.complaint.complaintType'),
		        NULLIF(JSON_VALUE(Message,'$.complaint.complaintSubType'),'null'),
		        JSON_VALUE(Message,'$.complaint.complaintFeedbackType')) as type,
		    JSON_VALUE(Message,'$.mail.destination[0]') as `email`,
		    JSON_VALUE(Message,'$.mail.commonHeaders.subject') as `subject`,
		    JSON_VALUE(Message,'$.mail.source') as `sender`,
		    REGEXP_REPLACE( REGEXP_REPLACE( REGEXP_REPLACE( JSON_VALUE(Message,'$.mail.commonHeaders.subject'),
		        '(Suggestion for|Forum topic updated:|Copy of message sent to) .*', '\\\\1 ...'),
		        '.* (contacting you via|is sending you an e-Card)', '... \\\\1'),
			'.+ #\\\\d+$', '... #ticket-id'
		    ) AS normalized";

		$columns = "email_md5, TimeStamp, type, email, subject, sender, normalized";

		$whereClause = "
		    Type = 'Notification'
		    AND JSON_VALUE(Message,'$.mail.destination[0]') IS NOT NULL
		    AND block_cleared = 0";

		// Check if sns_summary already exists in the current schema
		$tableExists = $db->getOne("SHOW TABLES LIKE 'sns_summary'");

		if ($tableExists) {
			$describe = $db->getAssoc("DESCRIBE sns_summary");
			if (implode(', ',array_keys($describe)) != $columns) {
			    // need to replace the table. Use REPLACE TABLE to get a nice atomic replace!
			    $tableExists = false;
			}
		}

		if ($tableExists) {
		    // Fetch the latest timestamp to run an incremental delta insert
		    $maxTimestamp = $db->GetOne("SELECT MAX(TimeStamp) FROM sns_summary");

	    	    if ($maxTimestamp) {
	        	$db->Execute("
		            INSERT IGNORE INTO sns_summary ($columns)
		            SELECT {$selectFields}
		            FROM sns_message
	        	    WHERE {$whereClause}
		              AND TimeStamp > " . $db->Quote($maxTimestamp)
		        );
		    }
		} else {
		    // Initial run: Create table with unique key constraint and populate full set
		    $db->Execute("
		        CREATE OR REPLACE TABLE sns_summary (
		            UNIQUE KEY (email_md5, TimeStamp)
		        ) IGNORE SELECT {$selectFields}
		        FROM sns_message
		        WHERE {$whereClause}"
		    );
		}

		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
}
