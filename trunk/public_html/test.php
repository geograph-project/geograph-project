<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
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


/**
 * This file should test the local environment to ensure the geograph
 * application can run successfully
 */
$ok=true;

function fail($msg)
{
	global $ok;
	$ok=false;
	echo "<li style=\"color:red;\">FAIL: $msg</li>";
}

function warn($msg)
{
	echo "<li style=\"color:orange;\">WARN: $msg</li>";
}


function check_include($file)
{
	$ok=false;
	$f=@fopen ($file, 'r', true);	
	if ($f)
	{
		fclose($f);
		$ok=true;
	}
	return $ok;
}

echo "<h1>Geograph System Test...</h1>";


//////////////////////////////////////////////////////////////////
// general php configuration

if (!extension_loaded('gd'))
	fail('PHP GD extension not available - REQUIRED');

if (!extension_loaded('exif'))
	fail('PHP EXIF extension not available - REQUIRED');

//check for a recent browscap.ini
$browscap=get_cfg_var("browscap");
if (strlen($browscap) && @file_exists($browscap))
{
	$ageDays=(time() - filemtime($browscap))/86400;
	if ($ageDays > 180)

	{
		warn("browscap.ini more than six months old - check for updates at http://www.garykeith.com/browsers/downloads.asp");
	}
	
	
}
else
{
	fail('browscap file not configured in php.ini - REQUIRED');

}

//////////////////////////////////////////////////////////////////
// include files

$inc=realpath($_SERVER['DOCUMENT_ROOT'].'/../libs');
if (check_include('geograph/global.inc.php'))
{
	//include path is ok - let see if it contains the other stuff we need
	if (!check_include('conf/'.$_SERVER['HTTP_HOST'].'.conf.php'))
		fail('conf/'.$_SERVER['HTTP_HOST'].'conf.php not found - copy and adapt the www.example.com.conf.php file');
	if (!check_include('adodb/adodb.inc.php'))
		fail("ADOdb not found in $inc/adodb - download and install it there");
	if (!check_include('smarty/libs/Smarty.class.php'))
		fail("Smarty not found in $inc/smarty - download and install it there");
}
else
{
	fail($inc.' should be on include path');
}

//pull in the exampledomain, it contains all configuration settings
include('conf/www.exampledomain.com.conf.php');
$example=$CONF;
unset($CONF);

//try and include the real configuration
include('conf/'.$_SERVER['HTTP_HOST'].'.conf.php');

//check everything is set
foreach($example as $name=>$value)
{
	if (!isset($CONF[$name]))
	{
		fail("Your domain configuration file has no \$CONF['$name'] entry - see www.exampledomain.com.conf.php for an example");
	}
}

//////////////////////////////////////////////////////////////////
// directory permissions

if (!is_writable($_SERVER['DOCUMENT_ROOT'].'/maps'))
	fail('public_html/maps not writable - REQUIRED');

if (!is_writable($_SERVER['DOCUMENT_ROOT'].'/photos'))
	fail('public_html/photos not writable - REQUIRED');

if (!is_writable($_SERVER['DOCUMENT_ROOT'].'/rss'))
	fail('public_html/rss not writable - REQUIRED');

if (!is_writable($_SERVER['DOCUMENT_ROOT'].'/templates/basic/compiled'))
	fail('public_html/templates/basic/compiled not writable - REQUIRED');

if (!is_writable($_SERVER['DOCUMENT_ROOT'].'/templates/basic/cache'))
	fail('public_html/templates/basic/cache not writable - REQUIRED');

if (!is_writable($CONF['adodb_cache_dir']))
	fail('$CONF[\'adodb_cache_dir\'] ('.$CONF['adodb_cache_dir'].') not writable - REQUIRED');


//show some diagnostics if not ok...
if (!$ok)
{
	echo "<br><br><br><br>";
	phpinfo();
}
else
{
	echo "<li style=\"color:green;font-weight:bold;\>Server is correctly configured to run Geograph!</li>";
}
//just adding this comment to test if can now commit... (Barry) another commit try!
?>
