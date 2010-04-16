<?php
/**
 * $Project: GeoGraph $
 * $Id$
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
	'secret'=>'imagesitemap',		//secret - change this 
	'dir'=>'/var/www/geograph_live/',		//base installation dir
	'config'=>'www.geograph.org.uk', //effective config
	'per'=>50000, //number of lines per sitemap
	'normal'=>'1', //which sitemaps to produce
	'geo'=>'1', //which sitemaps to produce
	'image'=>'1', //which sitemaps to produce
	'ri'=>'1', //grid
	'suffix'=>'', //eg '.ie'
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
    --secret=<string>   : slug to use in image sitemap urls - to make it harder for leachers - pick something unique
    --dir=<dir>         : base directory (/var/www/geograph_live/)
    --config=<domain>   : effective domain config (www.geograph.org.uk)
    --ri=<number>       : which reference index to export (0=all)
    --suffix=<string>   : optional suffix, eg --suffix=.ie
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

$urls_per_sitemap=$param['per']; 

//how many sitemap files must we write?
printf("Counting images...\r");
$images=$db->GetOne("select count(*) from gridimage_search".($param['ri']?" where reference_index = {$param['ri']}":''));
$sitemaps=ceil($images / $urls_per_sitemap);

//go through each sitemap file...
$last_percent=0;
$count=0;
for ($sitemap=1; $sitemap<=$sitemaps; $sitemap++)
{
	//prepare output file and query
	printf("Preparing sitemap %d of %d, %d%% complete...\r", $sitemap, $sitemaps,$percent);
	
	if ($param['normal']) {
		$filename=sprintf('%s/public_html/sitemap/root/sitemap%04d%s.xml', $param['dir'], $sitemap, $param['suffix']); 
		$fh=fopen($filename, "w");
		if (!$fh) {
			die("unable to write $filename");
		}

		fprintf($fh, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
		fprintf($fh, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n");
	}
	
	if ($param['geo']) {
		$filename2=sprintf('%s/public_html/sitemap/root/sitemap-geo%04d%s.xml', $param['dir'], $sitemap, $param['suffix']); 
		$fh2=fopen($filename2, "w");
		if (!$fh2) {
			die("unable to write $filename2");
		}

		fprintf($fh2, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
		fprintf($fh2, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:geo="http://www.google.com/geo/schemas/sitemap/1.0">'."\n");
	}
	
	if ($param['images']) {
		$filename3=sprintf('%s/public_html/sitemap/root/sitemap-%s%04d%s.xml', $param['dir'], $param['secret'], $sitemap, $param['suffix']); 
		$fh3=fopen($filename3, "w");
		if (!$fh3) {
			die("unable to write $filename3");
		}

		fprintf($fh3, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
		fprintf($fh3, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'."\n");
	}
	
	$maxdate="";
	
	$offset=($sitemap-1)*$urls_per_sitemap;
	$recordSet = &$db->Execute(
		"select i.gridimage_id,date(upd_timestamp) as moddate,title,user_id ".
		"from gridimage_search as i ".
		($param['ri']?"where reference_index = {$param['ri']} ":'').
		"order by i.gridimage_id ".
		"limit $offset,$urls_per_sitemap");
	
	$image=new GridImage;
	
	//write one <url> line per result...
	while (!$recordSet->EOF) 
	{
		//figure out most recent update
		$date=$recordSet->fields['moddate'];
		
		if (strcmp($date,$maxdate)>0)
			$maxdate=$date;
		
		if ($param['normal']) {
			fprintf($fh,"<url>".
			"<loc>http://{$param['config']}/photo/%d</loc>".
			"<lastmod>%s</lastmod>".
			"<changefreq>yearly</changefreq><priority>0.8</priority>".
			"</url>\n",
			$recordSet->fields['gridimage_id'],
			$date
			);
		}
		if ($param['geo']) {
			fprintf($fh2,"<url>".
			"<loc>http://{$param['config']}/photo/%d.kml</loc>".
			"<lastmod>%s</lastmod>".
			"<changefreq>yearly</changefreq><priority>0.5</priority>".
			"<geo:geo><geo:format>kml</geo:format></geo:geo>".
			"</url>\n",
			$recordSet->fields['gridimage_id'],
			$date
			);
		}
		if ($param['images']) {
			$image->fastInit($recordSet->fields);
			fprintf($fh3,"<url>".
			"<loc>http://{$param['config']}/photo/%d</loc>".
			"<lastmod>%s</lastmod>".
			"<changefreq>yearly</changefreq><priority>0.8</priority>".
			"<image:image>\n".
			"<image:loc>%s</image:loc>".
			"<image:title>%s</image:title>".
			"<image:license>http://creativecommons.org/licenses/by-sa/2.0/</image:license>\n".
			"</image:image>".
			"</url>\n",
			$recordSet->fields['gridimage_id'],
			$date,
			$image->_getFullpath(false,true),
			utf8_encode(htmlnumericentities($image->title))
			);
		}
		
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
	//set datestamp on file
	//gzip it
	
	$unixtime=strtotime("$maxdate 00:00:00");
	
	if ($param['normal']) {
		fprintf($fh, '</urlset>');
		fclose($fh); 
		touch($filename,$unixtime);
		`gzip $filename -f`;
	}
	
	if ($param['geo']) {
		fprintf($fh2, '</urlset>');
		fclose($fh2); 
		touch($filename2,$unixtime);
		`gzip $filename2 -f`;
	}
	
	if ($param['images']) {
		fprintf($fh3, '</urlset>');
		fclose($fh3); 
		touch($filename3,$unixtime);
		`gzip $filename3 -f`;
	}
}

//now we write an index file pointing to our hand edited sitemap sitemap0000.xml)
//and our generated ones above
if ($param['normal']) {
	$filename=sprintf('%s/public_html/sitemap/root/sitemap%s.xml', $param['dir'], $param['suffix']); 
	$fh=fopen($filename, "w");

	fprintf($fh, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
	fprintf($fh, '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n");
}

if ($param['geo']) {
	$filename2=sprintf('%s/public_html/sitemap/root/sitemap-geo%s.xml', $param['dir'], $param['suffix']); 
	$fh2=fopen($filename2, "w");

	fprintf($fh2, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
	fprintf($fh2, '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n");
}

if ($param['images']) {
	$filename3=sprintf('%s/public_html/sitemap/root/sitemap-%s%s.xml', $param['dir'], $param['secret'], $param['suffix']); 
	$fh3=fopen($filename3, "w");

	fprintf($fh3, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
	fprintf($fh3, '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n");
}

for ($s=0; $s<=$sitemaps; $s++)
{
	//first file is not compressed...
	$fname=($s==0)?"sitemap0000{$param['suffix']}.xml":sprintf("sitemap%04d%s.xml.gz", $s, $param['suffix']);

	$mtime=filemtime($param['dir']."/public_html/sitemap/root/".$fname);
	$mtimestr=strftime("%Y-%m-%dT%H:%M:%S+00:00", $mtime);
		
	if ($param['normal']) {
		fprintf($fh, "<sitemap>");

		fprintf($fh, "<loc>http://{$param['config']}/%s</loc>", $fname);
		fprintf($fh, "<lastmod>%s</lastmod>", $mtimestr);
		fprintf($fh, "</sitemap>\n");
	}
	
	if ($param['geo'] && $s>0) {
		fprintf($fh2, "<sitemap>");
	
		$fname=sprintf("sitemap-geo%04d%s.xml.gz", $s, $param['suffix']);
		
		if (!$param['normal']) {
			$mtime=filemtime($param['dir']."/public_html/sitemap/root/".$fname);
			$mtimestr=strftime("%Y-%m-%dT%H:%M:%S+00:00", $mtime);
		}
		
		fprintf($fh2, "<loc>http://{$param['config']}/%s</loc>", $fname);
		fprintf($fh2, "<lastmod>%s</lastmod>", $mtimestr);
		fprintf($fh2, "</sitemap>\n");
	}
	
	if ($param['images']) {
		fprintf($fh3, "<sitemap>");

		$fname=($s==0)?"sitemap0000{$param['suffix']}.xml":sprintf("sitemap-%s%04d%s.xml.gz", $param['secret'], $s, $param['suffix']);

		if (!$param['normal']) {
			$mtime=filemtime($param['dir']."/public_html/sitemap/root/".$fname);
			$mtimestr=strftime("%Y-%m-%dT%H:%M:%S+00:00", $mtime);
		}
		
		fprintf($fh3, "<loc>http://{$param['config']}/%s</loc>", $fname);
		fprintf($fh3, "<lastmod>%s</lastmod>", $mtimestr);
		fprintf($fh3, "</sitemap>\n");
	}
}

if ($param['normal']) {
	fprintf($fh, '</sitemapindex>');
	fclose($fh); 
}
if ($param['geo'] && $s>0) {
	fprintf($fh2, '</sitemapindex>');
	fclose($fh2);
}
if ($param['images']) {
	fprintf($fh3, '</sitemapindex>');
	fclose($fh3);
}


