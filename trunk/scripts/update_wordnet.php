<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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


//needed to allow the config file to load - could be passed in as a argument??
$_SERVER['HTTP_HOST'] = "www.geograph.org.uk";

//not sure how to autodetect this?
$_SERVER['DOCUMENT_ROOT'] = "/var/www/geograph_live/"; 

//--------------------------------------------
// nothing below here should need changing

require_once('geograph/global.inc.php');
require_once('geograph/wordnet.inc.php');

$db = NewADOConnection($GLOBALS['DSN']);


	$tim = time();
	
	
	$recordSet = &$db->Execute("select gridimage_id,title from gridimage where moderation_status != 'rejected'");
	while (!$recordSet->EOF) 
	{
		updateWordnet($db,$recordSet->fields['title'],'title',$recordSet->fields['gridimage_id']);
		if ($recordSet->fields['gridimage_id']%5000==0)
			printf("done %d at %d seconds\n",$recordSet->fields['gridimage_id'],time()-$tim);
	
		$recordSet->MoveNext();
	}
	$recordSet->Close(); 

	
?>
