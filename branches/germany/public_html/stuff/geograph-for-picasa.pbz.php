<?php
/**
 * $Project: GeoGraph $
 * $Id: years.php 3514 2007-07-10 21:09:55Z barry $
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

include("geograph/zip.class.php");

$zipfile = new zipfile();

$xml = file_get_contents("../../modules/picasa/{1563f71e-b0c6-4ff5-a3e0-402a70bc357d}.pbf");

$xml = str_replace("domain.com",$_SERVER['HTTP_HOST'],$xml);

$zipfile->addFile($xml, "{1563f71e-b0c6-4ff5-a3e0-402a70bc357d}.pbf");


// add the binary data stored in the string 'filedata'
$zipfile->addFile(file_get_contents("../../modules/picasa/{1563f71e-b0c6-4ff5-a3e0-402a70bc357d}.psd"), "{1563f71e-b0c6-4ff5-a3e0-402a70bc357d}.psd");

header("Content-type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"geograph-for-picasa.pbz\"");
print $zipfile->file();

?>
