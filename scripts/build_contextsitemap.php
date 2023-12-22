<?php
/**
 * $Project: GeoGraph $
 * $Id: build_snippetsitemap.php 6622 2010-04-10 13:35:13Z barry $
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


chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = NewADOConnection($GLOBALS['DSN']);

$ri = 1;

//set this low - to try it out...
$urls_per_sitemap=10000;


//how many sitemap files must we write?
printf("Counting urls...\r");
$images=$db->GetOne("select count(*) from gridimage_search INNER JOIN sample_selection USING (gridimage_id) WHERE context IS NOT NULL AND context != '' AND reference_index = $ri");
$sitemaps=ceil($images / $urls_per_sitemap);

############################################

//go through each sitemap file...
$percent=$last_percent=0;
$count=0;
for ($sitemap=1; $sitemap<=$sitemaps; $sitemap++)
{
	//prepare output file and query
	printf("Preparing user sitemap %d of %d, %d%% complete...\r", $sitemap, $sitemaps,$percent);

	$filename=sprintf('%s/public_html/sitemap/root/sitemap-context%04d.xml', $param['dir'], $sitemap);
	$fh=fopen($filename, "w");

	fprintf($fh, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
	fprintf($fh, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n");

	$maxdate="";
	$offset=($sitemap-1)*$urls_per_sitemap;
	$recordSet = $db->Execute(
		"select gridimage_id,date(upd_timestamp) as updated ".
		"from gridimage_search INNER JOIN sample_selection USING (gridimage_id) WHERE context IS NOT NULL AND context != '' AND reference_index = $ri ".
		"limit $offset,$urls_per_sitemap");

	//write one <url> line per result...
	while (!$recordSet->EOF) {
		//figure out most recent update
		$date=$recordSet->fields['updated'];

		if (strcmp($date,$maxdate)>0)
			$maxdate=$date;

		fprintf($fh,"<url>".
			"<loc>https://{$param['config']}/photo/%d</loc>".
			"<lastmod>%s</lastmod>".
			"</url>\n",
			$recordSet->fields['gridimage_id'],
			$date
			);

		$count++;
		$percent=round(($count*100)/$images);
		if ($percent!=$last_percent) {
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
	$unixtime=strtotime($maxdate." 23:59:59");
	if ($unixtime>time()) //it could be updated TODAY, claiming end of day is wrong!
		$unixtime=time();
	touch($filename,$unixtime);

	//gzip it
	`gzip $filename -f`;
	touch("$filename.gz",$unixtime); //weird (bug? possibl in GeogridFS!), if gzip is overwriting a file, it doesn't perserve the timestamp!
}

//now we write an index file pointing to our generated ones above
$filename=sprintf('%s/public_html/sitemap/root/sitemap-context.xml', $param['dir']);
$fh=fopen($filename, "w");

fprintf($fh, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
fprintf($fh, '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n");

for ($s=1; $s<=$sitemaps; $s++) {
	fprintf($fh, "<sitemap>");

	$fname=sprintf("sitemap-context%04d.xml.gz", $s);

	$mtime=filemtime($param['dir']."/public_html/sitemap/root/".$fname);
	$mtimestr=strftime("%Y-%m-%dT%H:%M:%S+00:00", $mtime);

	fprintf($fh, "<loc>https://{$param['config']}/%s</loc>", $fname);
	fprintf($fh, "<lastmod>$mtimestr</lastmod>", $fname);
	fprintf($fh, "</sitemap>\n");
}

fprintf($fh, '</sitemapindex>');
fclose($fh);

