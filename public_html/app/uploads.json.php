<?php
/**
 * $Project: GeoGraph $
 * $Id: xmas.php 6235 2009-12-24 12:33:07Z barry $
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

require_once('geograph/global.inc.php');
init_session();

if (!$USER->hasPerm("basic")) {
        //return a nice JSON error!
	$data = array('error'=>'login required');
	outputJSON($data); //uses pass-by-ref;
	exit;
}

$uploadmanager=new UploadManager;


if ($_SERVER["REQUEST_METHOD"] === "POST" && empty($_POST['email'])) { //aovid a login request!
	//delete submisisons


        // Get the raw POST data
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

	if (is_array($data['ids'])) {
		sleep(3); //fake!
	}
	//noop!

} else {

	$data = $uploadmanager->getUploadedFiles();

}


outputJSON($data);


