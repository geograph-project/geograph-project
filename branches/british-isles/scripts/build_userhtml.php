<?php
/**
 * $Project: GeoGraph $
 * $Id: build_usersitemap.php 6622 2010-04-10 13:35:13Z barry $
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


    

//these are the arguments we expect
$param=array(
	'dir'=>'/var/www/geograph_live/',		//base installation dir
	'config'=>'www.geograph.org.uk', //effective config
	'help'=>0,		//show script help?
);

//very simple argument parser
for($i=1; $i<count($_SERVER['argv']); $i++)
{
	$arg=$_SERVER['argv'][$i];

	if (substr($arg,0,2)=='--')

	{
		$arg=substr($arg,2);
		$bits=explode('=', $arg,2);
		if (isset($param[$bits[0]]))
		{
			//if we have a value, use it, else just flag as true
			$param[$bits[0]]=isset($bits[1])?$bits[1]:true;
		}
		else die("unknown argument --$arg\nTry --help\n");
	}
	else die("unexpected argument $arg - try --help\n");
	
}


if ($param['help'])
{
	echo <<<ENDHELP
---------------------------------------------------------------------
build_sitemap.php 
---------------------------------------------------------------------
    --dir=<dir>         : base directory (/var/www/geograph_live/)
    --config=<domain>   : effective domain config (www.geograph.org.uk)
    --help              : show this message	
---------------------------------------------------------------------
	
ENDHELP;
exit;
}
	
//set up  suitable environment
ini_set('include_path', $param['dir'].'/libs/');
$_SERVER['DOCUMENT_ROOT'] = $param['dir'].'/public_html/'; 
$_SERVER['HTTP_HOST'] = $param['config'];


//--------------------------------------------
// nothing below here should need changing

require_once('geograph/global.inc.php');

$db = NewADOConnection($GLOBALS['DSN']);

$filename=sprintf('%s/public_html/sitemap/credits.html', $param['dir']); 
$fh=fopen($filename, "w");

fprintf($fh, "<html><head><title>Contributor List for Geograph Britain and Ireland</title>\n".
		"<style>body { font-family: georgia; line-height:1.4em; } a { text-decoration: none; } </style>\n".
		"</head>\n".
		"<body>".
		"<h2>Complete Contributor List for Geograph Britain and Ireland</h2>".
		"<p>As at ".date('r')." <small>(page updated once a day)</small></p>".
		"<p><a href=\"/\">back to Homepage</a> | <a href=\"/credits/\">back to Credits page</a></p><hr/>".
		"<p align=\"center\">");
		

$recordSet = &$db->Execute("SELECT user_id,realname,nickname,images FROM user INNER JOIN user_stat USING (user_id) ORDER BY realname");
while (!$recordSet->EOF) 
{
	$r = $recordSet->fields;
	
	$r['realname'] = preg_replace('/ +/','&middot;',trim($r['realname']));
	
	fprintf($fh,'&nbsp;<a href="/profile/%s" title="%s, %s images">%s</a><small>&nbsp;[%s]</small> &nbsp;',
		$r['user_id'],
		htmlentities2($r['nickname']),
		$r['images'],
		($r['images'] > 100)?("<b>".htmlentities2($r['realname'])."</b>"):htmlentities2($r['realname']),
		number_format($r['images'],0));

	$recordSet->MoveNext();
}

$recordSet->Close();

fprintf($fh, "</p>\n<hr/><p><a href=\"/\">back to Homepage</a> | <a href=\"/credits/\">back to Credits page</a></p>");


//finalise file
fprintf($fh, '</body></html>');
fclose($fh); 
