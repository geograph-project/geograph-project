<?php
/**
 * $Project: GeoGraph $
 * $Id: build_gridsitemap.ie.php 6622 2010-04-10 13:35:13Z barry $
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
	'config'=>'www.geograph.ie', //effective config
);

chdir(__DIR__);
require "./_scripts.inc.php";

//--------------------------------------------
// nothing below here should need changing

$db = GeographDatabaseConnection(true);

//set this low - to try it out...
$urls_per_sitemap=50000;

//how many sitemap files must we write?
printf("Counting square...\r");
$images=$db->GetOne(" select count(*) from gridsquare where reference_index = 2 and imagecount > 0");
$sitemaps=ceil($images / $urls_per_sitemap);

//go through each sitemap file...
$percent=$last_percent=0;
$count=0;
for ($sitemap=1; $sitemap<=$sitemaps; $sitemap++)
{
	//prepare output file and query
	printf("Preparing grid sitemap %d of %d, %d%% complete...\r", $sitemap, $sitemaps,$percent);

	$filename=sprintf('%s/public_html/sitemap/root/sitemap-grid%04d.ie.xml', $param['dir'], $sitemap);
	$fh=fopen($filename, "w");

	fprintf($fh, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
	fprintf($fh, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n");

	$maxdate="";

	$offset=($sitemap-1)*$urls_per_sitemap;
	$recordSet = $db->Execute(
		"select grid_reference,date(last_timestamp) as moddate ".
		"from gridsquare ".
		"where reference_index = 2 and imagecount > 0 ".
		"order by grid_reference ".
		"limit $offset,$urls_per_sitemap");

	//write one <url> line per result...
	while (!$recordSet->EOF)
	{
		//figure out most recent update
		$date=$recordSet->fields['moddate'];

		if (strcmp($date,$maxdate)>0)
			$maxdate=$date;

		fprintf($fh,"<url>".
			"<loc>https://{$param['config']}/gridref/%s</loc>".
			"<lastmod>%s</lastmod>".
			"</url>\n",
			$recordSet->fields['grid_reference'],
			$date
			);

		$count++;
		$percent=round(($count*100)/$images);
		if ($percent!=$last_percent)
		{
			$last_percent=$percent;
			printf("Writing grid sitemap %d of %d, %d%% complete...\r", $sitemap, $sitemaps,$percent);
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
	`gzip $filename -f`;
	touch("$filename.gz",$unixtime); //weird (bug? possibl in GeogridFS!), if gzip is overwriting a file, it doesn't perserve the timestamp!
}

//now we write an index file pointing to our generated ones above
$filename=sprintf('%s/public_html/sitemap/root/sitemap-grid.ie.xml', $param['dir']);
$fh=fopen($filename, "w");

fprintf($fh, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
fprintf($fh, '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n");

for ($s=1; $s<=$sitemaps; $s++)
{
	fprintf($fh, "<sitemap>");

	$fname=sprintf("sitemap-grid%04d.ie.xml.gz", $s);

	$mtime=filemtime($param['dir']."/public_html/sitemap/root/".$fname);
	$mtimestr=strftime("%Y-%m-%dT%H:%M:%S+00:00", $mtime);

	fprintf($fh, "<loc>https://{$param['config']}/%s</loc>", $fname);
	fprintf($fh, "<lastmod>$mtimestr</lastmod>", $fname);
	fprintf($fh, "</sitemap>\n");
}

fprintf($fh, '</sitemapindex>');
fclose($fh);

