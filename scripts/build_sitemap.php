<?php
/**
 * $Project: GeoGraph $
 * $Id: build_sitemap.php 8868 2018-10-18 11:07:09Z barry $
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
	'protocol'=>'https',
	'per'=>50000, //number of lines per sitemap
	'normal'=>'1', //which sitemaps to produce
	'images'=>'0', //which sitemaps to produce
	'ri'=>'1', //grid
	'suffix'=>'', //eg '.ie'
);

$HELP = <<<ENDHELP
    --secret=<string>   : slug to use in image sitemap urls - to make it harder for leachers - pick something unique
    --ri=<number>       : which reference index to export (0=all)
    --suffix=<string>   : optional suffix, eg --suffix=.ie
ENDHELP;

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

if (!empty($CONF['db_read_connect2'])) {
        if (!empty($DSN_READ))
                $DSN_READ = str_replace($CONF['db_read_connect'],$CONF['db_read_connect2'],$DSN_READ);
        if (!empty($CONF['db_read_connect']))
                $CONF['db_read_connect'] = $CONF['db_read_connect2'];
}

$db = GeographDatabaseConnection(true);

$urls_per_sitemap=$param['per'];

//how many sitemap files must we write?
printf("Counting images...\r");
$images=$db->GetOne("select count(*) from gridimage_search where user_id != 1695 ".($param['ri']?" and reference_index = {$param['ri']}":''));
$sitemaps=ceil($images / $urls_per_sitemap);

//go through each sitemap file...
$percent=$last_percent=0;
$count=0;
$last_id=0;
$stat=array();
$datecrit = date('Y-m-d', time()-86400*3);

for ($sitemap=1; $sitemap<=$sitemaps; $sitemap++)
{
	//prepare output file and query
	printf("Preparing sitemap %d of %d, %d%% complete...\r", $sitemap, $sitemaps, $percent);

	if ($param['normal']) {
		$filename=sprintf('%s/public_html/sitemap/root/sitemap%04d%s.xml', $param['dir'], $sitemap, $param['suffix']);
		$fh=fopen($filename, "w");
		if (!$fh) {
			die("unable to write $filename");
		}

		fprintf($fh, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
		fprintf($fh, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n");
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

	$where = array();
	$where[] = "user_id != 1695";
	if ($param['ri'])
		$where[] = "reference_index = {$param['ri']}";
	if ($last_id)
		$where[] = "gridimage_id > $last_id"; //still fast, as ordered by id too, it can use it as a index.

	$recordSet = &$db->Execute(
		"select i.gridimage_id,date(upd_timestamp) as moddate,title,user_id,realname ".
		"from gridimage_search as i ".
		"where ".implode(" and ",$where)." ".
		"order by i.gridimage_id ".
		"limit $urls_per_sitemap");

	while (!$recordSet->EOF) {
		//store the id from the LAST row (need to do this now, because may abort running the full build)
		$last_id = $recordSet->fields['gridimage_id'];

		$date=$recordSet->fields['moddate'];

                if (strcmp($date,$maxdate)>0)
                        $maxdate=$date;

                $recordSet->MoveNext();
        }

	$image=new GridImage;

	if ($maxdate < $datecrit) {
		//abort early
		printf("Aborting sitemap %d of %d - %s < %s\n", $sitemap, $sitemaps, $maxdate, $datecrit);

                if ($last_id >= $image->enforce_https) //temporally hotwire
                        $param['protocol'] = 'https';
		@$stat[$sitemap][$param['protocol']]++;

		if ($param['normal'])
			unlink($filename); //we only deleting the uncompressed file, any existing .gz file untouched!
		if ($param['images'])
			unlink($filename3);

                $recordSet->Close();
                continue;
	}

        $recordSet->moveFirst();

	//write one <url> line per result...
	while (!$recordSet->EOF)
	{
		$last_id = $recordSet->fields['gridimage_id'];

		//figure out most recent update
		$date=$recordSet->fields['moddate'];

		if (strcmp($date,$maxdate)>0)
			$maxdate=$date;

		if ($param['normal']) {
			fprintf($fh,"<url>".
			"<loc>{$param['protocol']}://{$param['config']}/photo/%d</loc>".
			"<lastmod>%s</lastmod>".
			"</url>\n",
			$recordSet->fields['gridimage_id'],
			$date
			);
		}
		if ($param['images']) {
			$image=new GridImage;
			$image->fastInit($recordSet->fields);
			fprintf($fh3,"<url>".
			"<loc>{$param['protocol']}://{$param['config']}/photo/%d</loc>".
			"<lastmod>%s</lastmod>".
			"<image:image>\n".
			"<image:loc>%s</image:loc>".
			"<image:title>%s</image:title>".
			"<image:caption>by %s</image:caption>".
			"<image:license>https://creativecommons.org/licenses/by-sa/2.0/</image:license>\n".
			"</image:image>".
			"</url>\n",
			$recordSet->fields['gridimage_id'],
			$date,
			$image->_getFullpath(false,true),
                        xmlentities(latin1_to_utf8($image->title)),
                        xmlentities(utf8_encode($image->realname))
			);
		}

		$count++;
		@$stat[$sitemap][$param['protocol']]++;
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

	$unixtime=strtotime("$maxdate 23:59:59");
	if ($unixtime>time()) //it could be updated TODAY, claiming end of day is wrong!
		$unixtime=time();

	if ($param['normal']) {
		fprintf($fh, '</urlset>');
		fclose($fh);
		`gzip $filename -f`;
		touch("$filename.gz",$unixtime);
	}

	if ($param['images']) {
		fprintf($fh3, '</urlset>');
		fclose($fh3);
		`gzip $filename3 -f`;
		touch("$filename3.gz",$unixtime);
	}
}

#################################################################################################

function indexHeader($filename) {
        $fh=fopen($filename, "w");

        fprintf($fh, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
        fprintf($fh, '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n");
	return $fh;
}

function indexEntry($fh, $fname, $protocol = 'http') {
	global $param;

	global $mtime; //use a global, so can set it outside the function
	static $mtimestr;

        fprintf($fh, "<sitemap>");

	if (empty($mtime)) {
		$mtime=filemtime($param['dir']."/public_html/sitemap/root/".$fname);
                $mtimestr=strftime("%Y-%m-%dT%H:%M:%S+00:00", $mtime);
	}

        fprintf($fh, "<loc>$protocol://{$param['config']}/%s</loc>", $fname);
        fprintf($fh, "<lastmod>%s</lastmod>", $mtimestr);
        fprintf($fh, "</sitemap>\n");
}

function indexFooter($fh) {
        fprintf($fh, '</sitemapindex>');
        fclose($fh);
}

#################################################################################################
$fh = array();

//now we write an index file pointing to our hand edited sitemap sitemap0000.xml)
//and our generated ones above
if ($param['normal']) {
	$filename=sprintf('%s/public_html/sitemap/root/sitemap%s.xml', $param['dir'], $param['suffix']);
	$fh['normal-http']=indexHeader($filename);
	$filename=sprintf('%s/public_html/sitemap/root/sitemap%s-https.xml', $param['dir'], $param['suffix']);
	$fh['normal-https']=indexHeader($filename);
}

if ($param['images']) {
	$filename=sprintf('%s/public_html/sitemap/root/sitemap-%s%s.xml', $param['dir'], $param['secret'], $param['suffix']);
	$fh['images-http']=indexHeader($filename);
	$filename=sprintf('%s/public_html/sitemap/root/sitemap-%s%s-https.xml', $param['dir'], $param['secret'], $param['suffix']);
	$fh['images-https']=indexHeader($filename);
}

#################################

if ($param['normal']) {
	//first file is not compressed...
	$fname = "sitemap0000{$param['suffix']}.xml";
	indexEntry($fh['normal-http'], $fname);
}

for ($s=1; $s<=$sitemaps; $s++)
{
	$mtime = null; //so it gets calculated by the first indexEntry call.

	if ($param['normal']) {

		$fname=sprintf("sitemap%04d%s.xml.gz", $s, $param['suffix']);
		if (isset($stat[$s]['http']))
			indexEntry($fh['normal-http'], $fname);
		if (isset($stat[$s]['https']))
			indexEntry($fh['normal-https'], $fname, 'https');
	}

	if ($param['images']) {

		$fname=sprintf("sitemap-%s%04d%s.xml.gz", $param['secret'], $s, $param['suffix']);
		if (isset($stat[$s]['http']))
			indexEntry($fh['images-http'], $fname);
		if (isset($stat[$s]['https']))
			indexEntry($fh['images-https'], $fname, 'https');
	}
}

#################################

foreach ($fh as $key => $value) {
	indexFooter($value);
}


