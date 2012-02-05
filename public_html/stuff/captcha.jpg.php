<?php
/**
 * $Project: GeoGraph $
 * $Id: captcha.php 2960 2007-01-15 14:33:27Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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

if (!defined('CHECK_CAPTCHA')) {

	require_once('geograph/global.inc.php');

	init_session();
	
}

$folder_root = $_SERVER['DOCUMENT_ROOT'].'/stuff/';

$CAPTCHA_CONFIG = array(
	//Folder Path (relative to this file) where image files can be stored, must be readable and writable by the web server
	//Don't forget the trailing slash
'tempfolder'=>$folder_root.'captcha_tmp/',
	//Folder Path (relative to this file) where your captcha font files are stored, must be readable by the web server
	//Don't forget the trailing slash
'TTF_folder'=>$folder_root.'captcha_fonts/',
	//The minimum number of characters to use for the captcha
	//Set to the same as maxchars to use fixed length captchas
'minchars'=>5,
	//The maximum number of characters to use for the captcha
	//Set to the same as minchars to use fixed length captchas
'maxchars'=>7,
	//The minimum character font size to use for the captcha
	//Set to the same as maxsize to use fixed font size
'minsize'=>20,
	//The maximum character font size to use for the captcha
	//Set to the same as minsize to use fixed font size
'maxsize'=>30,
	//The maximum rotation (in degrees) for each character
'maxrotation'=>25,
	//Use background noise instead of a grid
'noise'=>TRUE,
	//Use web safe colors (only 216 colors)
'websafecolors'=>FALSE,
	//Enable debug messages
'debug'=>FALSE,
	//Filename of garbage collector counter which is stored in the tempfolder
'counter_filename'=>'counter.txt',
	//Prefix of captcha image filenames
'filename_prefix'=>'img_',
	//Number of captchas to generate before garbage collection is done
'collect_garbage_after'=>50,
	//Maximum lifetime of a captcha (in seconds) before being deleted during garbage collection
'maxlifetime'=>600,
	//Make all letters uppercase (does not preclude symbols)
'case_sensitive'=>FALSE);

require_once('geograph/b2evo_captcha.class.php');

//Initialize the captcha object with our configuration options
$captcha =& new b2evo_captcha($CAPTCHA_CONFIG);
$captcha->validchars = preg_replace('/[^\w]+/i','',$captcha->validchars);

if (defined('CHECK_CAPTCHA')) {
	define('CAPTCHA_RESULT',$captcha->validate_submit($_SESSION['imgLoc'],$_POST['verify']));
} else {
	$imgLoc = $captcha->get_b2evo_captcha();

	$_SESSION['imgLoc'] = $imgLoc;


	$size=filesize($_SERVER['DOCUMENT_ROOT']. $imgLoc);
	header("Content-Type: image/jpeg");
	header("Content-Length: $size");
	readfile($_SERVER['DOCUMENT_ROOT']. $imgLoc);
}

?>