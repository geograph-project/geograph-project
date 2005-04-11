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

function check_http($page, $pattern, &$errstr)
{
	$ok=false;

	$errstr='';
	$host=$_SERVER['HTTP_HOST'];


	$fp = fsockopen($host, 80, $errno, $errstr, 30);
	if (!$fp) 
	{
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
		if ($status==200)
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
if ($v[0]<4)
{
	fail('You need PHP 4.3 or higher, you have '.$version);
}
elseif ($v[0]==4)
{
	//hurrah, php4, that's the ticket

	if ($v[1]<3)
	{
		fail('You need PHP 4.3 or higher, you have '.$version);
	}
	
}
else
{
	//5 might work...

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
		fail("Your domain configuration file has no \$CONF['$name'] entry - see www.exampledomain.com.conf.php for an example");
	}
}



//////////////////////////////////////////////////////////////////
// directory permissions
status("checking file permissions...");

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



/////////////////////////////////////////////////////////////
// rewrite rules

$httperr='';
status("checking /gridref rewrite rules...");
if (!check_http('/gridref/HP0000', '/HP0000 seems to be all at sea/',$httperr))
	fail("mod_rewrite rule for /gridref/<em>xxnnnn</em> failed ($httperr) - REQUIRED");

status("checking /help rewrite rules...");
if (!check_http('/help/credits', '/This project relies on the following open-source technologies/',$httperr))
	fail("mod_rewrite rule for /help/<em>page</em> failed ($httperr) - REQUIRED");

status("checking /reg rewrite rules...");
if (!check_http('/reg/123/abcdef1234567890', '/there was a problem confirming your registration/',$httperr))
	fail("mod_rewrite rule for /reg/<em>uid</em>/<em>hash</em> failed ($httperr) - REQUIRED");

status("checking /photo rewrite rules...");
if (!check_http('/photo/999999', '/image not available/',$httperr))
	fail("mod_rewrite rule for /photo/<em>id</em> failed ($httperr) - REQUIRED");

status("checking /mapbrowse.php rewrite rules...");
if (!check_http('/mapbrowse.php?t=dummy&i=2&j=2&zoomin=1?43,72', '/TM0000/',$httperr))
	fail("mod_rewrite rule for mapbrowse.php image maps failed ($httperr) - REQUIRED");



//////////////////////////////////////////////////////////////////
// END OF TESTING
// We show some diagnostics if any tests failed...
status("completed");

if (!$ok)
{
	echo "<br><br><br><br>";
	phpinfo();
}
else
{
	echo "<li style=\"color:green;font-weight:bold;\">Server is correctly configured to run Geograph!</li>";
}
?>
