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

//////////////////////////////////////////////////////////////////
// helper functions

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

function status($msg)
{
	echo "<script langauge=\"javascript\">\n";
	echo "info.innerHTML='$msg';\n";
	echo "</script>\n";
	flush();
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

function check_http($page, $pattern, &$errstr, $host = '', $status_expected = 200)
{
	$ok=false;

	$errstr='';
	if (empty($host)) {
		$host=$_SERVER['HTTP_HOST'];
	} 

	$fp = fsockopen($host, 80, $errno, $errstr, 30);
	if (!$fp) 
	{
		$errstr="Unable to Connect";
		return $ok;
	} 
	else 
	{
		$out = "GET $page HTTP/1.0\r\n";
		$out .= "Host: $host\r\n";
		$out .= "User-Agent: Geograph System Tester\r\n";
		$out .= "Connection: Close\r\n\r\n";

		$headers=array();
		$body="";
		$in_headers=true;

		fwrite($fp, $out);
		while (!feof($fp)) 
		{
			$line=fgets($fp, 1024);
			if ($in_headers)
			{
				$line=trim($line);
				if (strlen($line))
				{
					$headers[]=$line;
				}
				else
				{
					$in_headers=false;
				}
			}
			else
			{
				$body.=$line;
			}
		}
		fclose($fp);
	}
	

	//HTTP/1.1 404 Not Found
	if (preg_match('{HTTP/\d+\.\d+ (\d+) (.*)}', $headers[0], $matches))
	{

		$status=$matches[1];
		if ($status==$status_expected)
		{
			//hurrah - lets check the content
			$ok=preg_match($pattern, $body);
			if (!$ok)
			{
				$errstr="Page did not contain expected text";
			}
		}
		else
		{
			//server failed us
			$errstr="Server returned $status {$matches[2]}";
		}
	}
	else
	{
		$errstr="Server returned unexpected response to HTTP request ({$headers[0]})";
	}
	

	return $ok;
}


//////////////////////////////////////////////////////////////////
// BEGIN TESTING


?><h1>Geograph System Test...</h1>
<li id="info"></li>
<script language="javascript">
info=document.getElementById('info');
</script>

<?php
flush();

//////////////////////////////////////////////////////////////////
// got the right php version?
status("checking php version...");

$version=phpversion();
$v=explode('.', $version);
if ($v[0]<5)
{
	fail('You need PHP 5 or higher, you have '.$version);
}
elseif ($v[0]==5)
{
	//hurrah, php5, that's the ticket

	//no sub-version test necessary at the moment
	/*
	if ($v[1]<3)
	{
		fail('You need PHP 4.3 or higher, you have '.$version);
	}
	*/
	
}
else
{
	//6 might work...?

	warn("Software is untested on php $version");
}

//////////////////////////////////////////////////////////////////
// general php configuration
status("checking php configuration...");

if (!extension_loaded('gd'))
	fail('PHP GD extension not available - REQUIRED');

if (!extension_loaded('exif'))
	fail('PHP EXIF extension not available - REQUIRED');

$register_globals=strtolower(ini_get('register_globals'));
if($register_globals=='on' || $register_globals=='1')
	fail('register_globals should be turned OFF - REQUIRED');

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
status("checking php include files...");

$inc=realpath($_SERVER['DOCUMENT_ROOT'].'/../libs');
if (check_include('geograph/global.inc.php'))
{
	//include path is ok - let see if it contains the other stuff we need
	if (!check_include('conf/'.$_SERVER['HTTP_HOST'].'.conf.php'))
		fail('conf/'.$_SERVER['HTTP_HOST'].'.conf.php not found - copy and adapt the www.example.com.conf.php file');
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
		if ($name == 'db_persist') {
			warn("Your domain configuration file has no \$CONF['$name'] entry - Recommended, but is not required");
		} else {
			fail("Your domain configuration file has no \$CONF['$name'] entry - see www.exampledomain.com.conf.php for an example");
		}
	}
}


//the following is only useful on develoment domains
if ($CONF['adodb_debugging'] || $CONF['smarty_debugging'] || !$CONF['smarty_caching'] || $CONF['fetch_on_demand'])
	foreach($CONF as $name=>$value)
	{
		if (!isset($example[$name]))
		{
			warn("The Example configuration file has no \$example['$name'] entry - see please define its use");
		}
	}

//////////////////////////////////////////////////////////////////
// directory permissions
status("checking file permissions...");

if (!is_writable($_SERVER['DOCUMENT_ROOT'].'/maps'))
	fail('public_html/maps not writable - REQUIRED');

if (!is_writable($_SERVER['DOCUMENT_ROOT'].'/photos'))
	fail('public_html/photos not writable - REQUIRED');

if (!is_writable($_SERVER['DOCUMENT_ROOT'].'/geophotos'))
	fail('public_html/geophotos not writable - REQUIRED');

if (!is_writable($_SERVER['DOCUMENT_ROOT'].'/rss'))
	fail('public_html/rss not writable - REQUIRED');

if (!is_dir($_SERVER['DOCUMENT_ROOT'].'/rss/'.$CONF['template']))
	fail('public_html/rss/'.$CONF['template'].' doesn\'t exist - REQUIRED');

if (!is_writable($_SERVER['DOCUMENT_ROOT'].'/rss/'.$CONF['template']))
	fail('public_html/rss/'.$CONF['template'].' not writable - REQUIRED');

if (!is_writable($_SERVER['DOCUMENT_ROOT'].'/sitemap'))
	fail('public_html/sitemap not writable - REQUIRED');

if (!is_writable($_SERVER['DOCUMENT_ROOT'].'/sitemap/root'))
	fail('public_html/sitemap/root not writable - REQUIRED');

if (!is_writable($_SERVER['DOCUMENT_ROOT'].'/kml'))
	fail('public_html/sitemap/kml not writable - REQUIRED');

if (!is_writable($_SERVER['DOCUMENT_ROOT'].'/memorymap'))
	fail('public_html/memorymap not writable - REQUIRED');

if (!file_exists($_SERVER['DOCUMENT_ROOT'].'/memorymap/geograph.bmp'))
	fail('public_html/memorymap/geograph.bmp missing - REQUIRED');

if (!is_writable($_SERVER['DOCUMENT_ROOT'].'/templates/basic/compiled'))
	fail('public_html/templates/basic/compiled not writable - REQUIRED');

if (!is_writable($_SERVER['DOCUMENT_ROOT'].'/templates/basic/cache'))
	fail('public_html/templates/basic/cache not writable - REQUIRED');


if (!is_writable($_SERVER['DOCUMENT_ROOT'].'/templates/'.$CONF['template'].'/compiled'))
	fail('public_html/templates/'.$CONF['template'].'/compiled not writable - REQUIRED');

if (!is_writable($_SERVER['DOCUMENT_ROOT'].'/templates/'.$CONF['template'].'/cache'))
	fail('public_html/templates/'.$CONF['template'].'/cache not writable - REQUIRED');


if (!is_writable($CONF['adodb_cache_dir']))
	fail('$CONF[\'adodb_cache_dir\'] ('.$CONF['adodb_cache_dir'].') not writable - REQUIRED');

if (!is_writable($CONF['photo_upload_dir']))
	fail('$CONF[\'photo_upload_dir\'] ('.$CONF['photo_upload_dir'].') not writable - REQUIRED');

if (!empty($CONF['sphinx_host'])) {
	if (!is_writable($CONF['sphinx_cache']))
		fail('$CONF[\'sphinx_cache\'] ('.$CONF['sphinx_cache'].') not writable - REQUIRED');
}

if ($CONF['log_script_timing']=='file') {
	if (!is_writable($CONF['log_script_folder']))
		fail('$CONF[\'log_script_folder\'] ('.$CONF['log_script_folder'].') not writable - REQUIRED or disable Script Timing Logging');
}

/////////////////////////////////////////////////////////////
// other required software

if (strlen($CONF['imagemagick_path']))
{
	if (!file_exists($CONF['imagemagick_path'].'mogrify') && !file_exists(str_replace('"','',$CONF['imagemagick_path']).'mogrify.exe'))
		fail('$CONF[\'imagemagick_path\'] ('.$CONF['imagemagick_path'].') not valid (mogrify not found) - clear this configuration variable if ImageMagick is not available');
}
else
{
	warn('imagemagick_path not set - resize operations will be slower, lower quality');
}


if (empty($CONF['sphinx_host'])) {
	warn('sphinx does not appear to be enabled - HIGHLY RECOMMENDED');
}

if (!function_exists('memcache_pconnect')) {
	warn('memcache PHP extension does not appear to be installed - RECOMMENDED');
}

/////////////////////////////////////////////////////////////
// rewrite rules

$httperr='';
status("checking /gridref rewrite rules...");
if (!check_http('/gridref/HP0000', '/HP0000 seems to be all at sea/',$httperr))
	fail("mod_rewrite rule for /gridref/<em>xxnnnn</em> failed ($httperr) - REQUIRED");

status("checking /help rewrite rules...");
if (!check_http('/help/credits', '/This project relies on the following open-source technologies/',$httperr))
	fail("mod_rewrite rule for /help/<em>page</em> failed ($httperr) - REQUIRED");
	
if (!check_http('/help/credits/', '/This project relies on the following open-source technologies/',$httperr))
	fail("mod_rewrite rule for /help/<em>page</em>/ doesn't cope with trailing slash on request ($httperr) - REQUIRED");

status("checking /reg rewrite rules...");
if (!check_http('/reg/123/abcdef1234567890', '/there was a problem confirming your registration/',$httperr))
	fail("mod_rewrite rule for /reg/<em>uid</em>/<em>hash</em> failed ($httperr) - REQUIRED");

status("checking /photo rewrite rules...");
if (!check_http('/photo/9999999', '/image not available/',$httperr,'',404))
	fail("mod_rewrite rule for /photo/<em>id</em> failed ($httperr) - REQUIRED");

status("checking /feed/recent rewrite rules...");
if (!check_http('/feed/recent', '/http:\/\/purl\.org\/rss\/1\.0\//',$httperr))
	fail("mod_rewrite rule for /feed/recent failed ($httperr) - REQUIRED");
	
if (!check_http('/feed/recent/GeoRSS/', '/http:\/\/www\.georss\.org\/georss/',$httperr))
	fail("mod_rewrite rule for /feed/recent/<em>format</em> doesn't cope with bad clients ($httperr) - REQUIRED");


/////////////////////////////////////////////////////////////
// hostname setups

if (!empty($CONF['KML_HOST'])) {
	status("checking ".$CONF['KML_HOST']);
	if (!check_http('/kml/images/cam1.png', '/.+/',$httperr,$CONF['KML_HOST'],200))
		fail('$CONF[\'KML_HOST\'] ('.$CONF['KML_HOST'].") - does not seem to work ($httperr) - REQUIRED");
} else {
	fail('$CONF[\'KML_HOST\'] not defined - REQUIRED');
}

if (!empty($CONF['TILE_HOST'])) {
	status("checking ".$CONF['TILE_HOST']);
	if (!check_http('/tile.php', '/no action specified/',$httperr,$CONF['TILE_HOST'],200))
		fail('$CONF[\'TILE_HOST\'] ('.$CONF['TILE_HOST'].") - does not seem to work ($httperr) - REQUIRED");
} else {
	fail('$CONF[\'TILE_HOST\'] not defined - REQUIRED');
}

if (!empty($CONF['CONTENT_HOST'])) {
	status("checking ".$CONF['CONTENT_HOST']);
	if (!check_http('/img/adodb.gif', '/.+/',$httperr,$CONF['CONTENT_HOST'],200))
		fail('$CONF[\'CONTENT_HOST\'] ('.$CONF['CONTENT_HOST'].") - does not seem to work ($httperr) - REQUIRED");
} else {
	fail('$CONF[\'CONTENT_HOST\'] not defined - REQUIRED');
}

if (!empty($CONF['STATIC_HOST'])) {
	status("checking ".$CONF['STATIC_HOST']);
	if (!check_http('/img/adodb.gif', '/.+/',$httperr,$CONF['STATIC_HOST'],200))
		fail('$CONF[\'STATIC_HOST\'] ('.$CONF['STATIC_HOST'].") - does not seem to work ($httperr) - REQUIRED");
	if ($CONF['enable_cluster']) {
		if (strpos($CONF['STATIC_HOST'],'0') !== FALSE) {
			for($q = 0; $q < $CONF['enable_cluster']; $q++ ) {
				$host = str_replace('0',($q%$CONF['enable_cluster']),$CONF['STATIC_HOST']);
				status("checking ".$host);
				if (!check_http('/img/adodb.gif', '/.+/',$httperr,$host,200))
					fail("$host - does not seem to work ($httperr) - REQUIRED (or disable \$CONF['enable_cluster'])");
			}
		} else {
			fail('$CONF[\'STATIC_HOST\'] doesn\'t contain "0" - REQUIRED');
		}
	}
} else {
	fail('$CONF[\'STATIC_HOST\'] not defined - REQUIRED');
}

if (empty($CONF['server_ip']) || strpos($_SERVER['SERVER_ADDR'],$CONF['server_ip']) !== 0) {
	fail('$CONF[\'server_ip\'] ('.$CONF['server_ip'].') does not match $_SERVER[\'SERVER_ADDR\'] ('.$_SERVER['SERVER_ADDR'].') - HIGHLY RECOMMENDED');
}

/////////////////////////////////////////////////////////////
// few sanity checks

if (empty($CONF['register_confirmation_secret']) || $CONF['register_confirmation_secret']=='CHANGETHIS') {
	fail('$CONF[\'register_confirmation_secret\'] does not appear to have been configured. RECOMMEND CHANGING NOW');
}

if (empty($CONF['photo_hashing_secret']) || $CONF['photo_hashing_secret']=='CHANGETHISTOO') {
	fail('$CONF[\'photo_hashing_secret\'] does not appear to have been configured. RECOMMEND CHANGING NOW');
}

if (empty($CONF['token_secret']) || $CONF['token_secret']=='CHANGETHIS') {
	fail('$CONF[\'token_secret\'] does not appear to have been configured. RECOMMEND CHANGING NOW');
}


if (empty($CONF['google_maps_api_key']) || $CONF['google_maps_api_key'] == 'XXXXXXX') {
	fail('$CONF[\'google_maps_api_key\'] does not appear to have been configured. HIGHLY RECOMMENDED');
}

if (empty($CONF['OS_licence']) || $CONF['OS_licence'] == 'XXXXXXXX') {
	warn('$CONF[\'OS_licence\'] does not appear to have been configured. Only a problem if Geograph British Isles');
}

if (empty($CONF['metacarta_auth']) || $CONF['metacarta_auth'] == '') {
	warn('$CONF[\'metacarta_auth\'] does not appear to have been configured. OK, experimental anyway');
}

if (empty($CONF['GEOCUBES_API_KEY'])) {
	warn('$CONF[\'GEOCUBES_API_KEY\'] does not appear to have been configured.');
} elseif (empty($CONF['GEOCUBES_API_TOKEN'])) {
	warn('$CONF[\'GEOCUBES_API_TOKEN\'] does not appear to have been configured. NEEDED IF HAVE GEOCUBES_API_KEY');
}

if (empty($CONF['flickr_api_key'])) {
	warn('$CONF[\'flickr_api_key\'] does not appear to have been configured. OK, not really used anyway');
}

if (empty($CONF['picnik_api_key'])) {
	warn('$CONF[\'picnik_api_key\'] does not appear to have been configured. RECOMMENDED but not required (code may need changing to take account of this)');
}

/////////////////////////////////////////////////////////////
// server setup

if (strpos($_ENV["OS"],'Windows') === FALSE) {
	status("checking server setup...");
	$f = fopen("/proc/loadavg","r");
	if ($f)
	{
		$buffer = '';
		if (!feof($f)) {
			$buffer = fgets($f, 1024);
		}
		fclose($f);
		$loads = explode(" ",$buffer);
		if (strlen($buffer) == 0 || $loads <= 0) //how likly is it to be 0
			fail("unable to read loadavg value - REQUIRED on non windows systems");
	} else {
		fail("loadavg check failed - REQUIRED on non windows systems");
	}
} else {
	warn("possible windows system detected: site will skip loadavg check - NOT RECOMMENDED");
}

//////////////////////////////////////////////////////////////////

#todo? Database check...

#todo? API key checks - flickr, GMaps, Geocubes, metacarta etc... 

//////////////////////////////////////////////////////////////////
// END OF TESTING
// We show some diagnostics if any tests failed...
status("completed");

if (!$ok)
{
	echo "<br><br><br><br>";
	##phpinfo();
}
else
{
	echo "<li style=\"color:green;font-weight:bold;\">Server is correctly configured to run Geograph!</li>";
}
?>
