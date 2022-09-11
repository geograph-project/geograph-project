<?php
/**
 * $Project: GeoGraph $
 * $Id: recreate_maps.php 2996 2007-01-20 21:39:07Z barry $
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

############################################

//these are the arguments we expect
$param=array(
	'lock'=>'0',
);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

//want the primary. indexer_wrapper allwas connects to primary
$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


############################################

if ($param['lock']) {
	$locked = $db->getOne("SELECT IS_USED_LOCK('indexer_active')");

	if ($locked)
		die("locked by $locked\n");

	if ($db->getOne("SELECT GET_LOCK('indexer_active',60)")) {
		print "indexer locked\n";
	} else {
		die("failed to get lock!\n");
	}


	print "Now sleeping. press Ctrl-C to release lock.\n";
	sleep(6*3600); // sleep to keep the lock active.

	print "releaseing lock...\n";
	$db->Execute("DO RELEASE_LOCK('indexer_active')");
}

############################################

