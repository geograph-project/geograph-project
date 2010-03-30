<?php
/**
 * $Project: GeoGraph $
 * $Id: recreate_maps.php 2578 2006-09-27 20:58:54Z barry $
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

//this upper limit is set by google
$urls_per_sitemap=50000;

//how many sitemap files must we write?
printf("Counting images...\r");
$images=$db->GetOne("select count(*) from kmlcache where rendered = 1 and filename != '' and level in (1,5,6,7)");
$sitemaps=ceil($images / $urls_per_sitemap);

//go through each sitemap file...
$last_percent=0;
$count=0;
for ($sitemap=1; $sitemap<=$sitemaps; $sitemap++)
{
	//prepare output file and query
	printf("Preparing sitemap %d of %d, %d%% complete...\r", $sitemap, $sitemaps,$percent);
		
	$filename=sprintf('%s/public_html/kml/sitemap%04d.xml', $param['dir'], $sitemap); 
	$fh=fopen($filename, "w");
	
	fprintf($fh, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
	fprintf($fh, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:geo="http://www.google.com/geo/schemas/sitemap/1.0">'."\n");
	
	
	$maxdate="";
	
	$offset=($sitemap-1)*$urls_per_sitemap;
	$recordSet = &$db->Execute(
		"select filename,date(ts) as ts_date ".
		"from kmlcache ".
		"where rendered = 1 and filename != '' and level in (1,5,6,7) ".
		"order by level ".
		"limit $offset,$urls_per_sitemap");
	
	//write one <url> line per result...
	while (!$recordSet->EOF) 
	{
		//figure out most recent update
		$date=$recordSet->fields['ts_date'];
		
		if (strcmp($date,$maxdate)>0)
			$maxdate=$date;
		
		fprintf($fh,"<url>".
			"<loc>%s</loc>".
			"<lastmod>%s</lastmod>".
			"<changefreq>monthly</changefreq>".
			"<geo:geo><geo:format>kml</geo:format></geo:geo>".
			"</url>\n",
			"http://".$CONF['KML_HOST'].$recordSet->fields['filename'],
			$date
			);
			
		$count++;	
		$percent=round(($count*100)/$images);
		if ($percent!=$last_percent)
		{
			$last_percent=$percent;
			printf("Writing sitemap %d of %d, %d%% complete...\r", $sitemap, $sitemaps,$percent);
		}	
	
		
		$recordSet->MoveNext();
	}
			
	$recordSet->Close();
	
	//finalise file
	fprintf($fh, '</urlset>');
	fclose($fh); 
	
	//set datestamp on file
	$unixtime=strtotime($maxdate);
	touch($filename,$unixtime);
	
	//gzip it
	`gzip $filename`;
}

//now we write an index file pointing to our generated ones above
$filename=sprintf('%s/public_html/kml/sitemap.xml', $param['dir']); 
$fh=fopen($filename, "w");

fprintf($fh, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
fprintf($fh, '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n");

for ($s=1; $s<=$sitemaps; $s++)
{
	fprintf($fh, "<sitemap>");
	
	$fname=sprintf("sitemap%04d.xml.gz", $s);
	
	$mtime=filemtime($param['dir']."/public_html/kml/".$fname);
	$mtimestr=strftime("%Y-%m-%dT%H:%M:%S+00:00", $mtime);
	
	fprintf($fh, "<loc>http://{$CONF['KML_HOST']}/kml/%s</loc>", $fname);
	fprintf($fh, "<lastmod>$mtimestr</lastmod>", $fname);
	fprintf($fh, "</sitemap>\n");
}

fprintf($fh, '</sitemapindex>');
	


	
?>
