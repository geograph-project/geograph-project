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

ini_set("memory_limit","64M");

chdir(__DIR__);
require "./_scripts.inc.php";

#######################

//todo, only a placeholder for now.

/*
This script WILL...

1. Scan 'resources' (CSS/JS etc)    $exts = 'js,css,png,jpg,gif,ico,txt,xml,html,htm,htc,json'
     find /var/www/geograph/public_html/ -xdev -name "*.js" -or -name "*.css" -or ....
    ... update revisions.conf.php
    ... deploy the latest version to S3
	(remembering special case of deploying robots-archive.txt to S3 as robots.txt!)

3. Make sure there are symlinks to the EFS mount as needed

4. Create needed folders in EFS

*/
