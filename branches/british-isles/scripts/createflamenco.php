<?php
/**
 * $Project: GeoGraph $
 * $Id: recreate_maps.php 2996 2007-01-20 21:39:07Z barry $
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
	'dir'=>'/var/www',		//base installation dir

	'config'=>'www.geograph.virtual', //effective config

	'timeout'=>14, //timeout in minutes
	'sleep'=>10,	//sleep time in seconds
	'load'=>100,	//maximum load average
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
recreate_maps.php 
---------------------------------------------------------------------
php recreate_maps.php 
    --dir=<dir>         : base directory (/home/geograph)
    --config=<domain>   : effective domain config (www.geograph.org.uk)
    --timeout=<minutes> : maximum runtime of script (14)
    --sleep=<seconds>   : seconds to sleep if load average exceeded (10)
    --load=<loadavg>    : maximum load average (100)
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


$db = GeographDatabaseConnection(true);

$a = array();

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

@mkdir("flamenco/");

$h = array();

$h['items'] =	fopen("flamenco/items.tsv",'w');
$h['text'] =	fopen("flamenco/text.tsv",'w');

#####################################################

$h['attrs'] =	fopen("flamenco/attrs.tsv",'w');
fwrite($h['attrs'],"title	Image Title\n");
fwrite($h['attrs'],"realname	Photographer Credit\n");
fwrite($h['attrs'],"thumbnail	Thumbnail\n");

$h['facets'] =	fopen("flamenco/facets.tsv",'w');
fwrite($h['facets'],"grid_reference	Grid Reference	Subject Location Grid Reference\n");
fwrite($h['facets'],"imagetaken	Date Taken	The date the photo was taken\n");
fwrite($h['facets'],"user_id	Contributor	The name of the contributor (not the Photographer/Credit)\n");
fwrite($h['facets'],"imageclass	Category	Subject Category\n");
#fwrite($h['facets'],"reference_index	Grid	The Country of the photo\n");
fwrite($h['facets'],"cluster	Cluster	Automatically deduced label for the image\n");
fwrite($h['facets'],"place	Place	Grouping by place\n");
fwrite($h['facets'],"moderation_status	Moderation	Moderation Classification\n");
fwrite($h['facets'],"ftf	First	One if the photo is a 'First'\n");

#####################################################

foreach (explode(' ',"user_id grid_reference place moderation_status ftf imagetaken imageclass reference_index cluster") as $key) {
	$h["{$key}_terms"] = fopen("flamenco/{$key}_terms.tsv",'w');
	$h["{$key}_map"] = fopen("flamenco/{$key}_map.tsv",'w');
}

#####################################################
# grid_reference_terms

$sql = "SELECT gridsquare_id, grid_reference
FROM tmpflam INNER JOIN gridimage g USING (gridimage_id) INNER JOIN gridsquare gs USING (gridsquare_id) GROUP BY gridsquare_id";

print "$sql\n";

$recordSet = &$db->Execute($sql);

while (!$recordSet->EOF) 
{
	$r =& $recordSet->fields;

	preg_match('/^([A-Z]{1,2})(\d)\d(\d)\d$/',$r['grid_reference'], $m);
	fwrite($h['grid_reference_terms'],	implode("\t",array($r['gridsquare_id'],$m[1],"{$m[1]}{$m[2]}{$m[3]}",$r['grid_reference'])). "\n");
	
	$recordSet->MoveNext();
}

#####################################################
# user_id_terms

$sql = "SELECT GROUP_CONCAT(user_id) as user_id, realname,count(*) as c FROM user INNER JOIN user_stat USING (user_id) GROUP BY realname ORDER BY user.user_id";

print "$sql\n";

$recordSet = &$db->Execute($sql);

while (!$recordSet->EOF) 
{
	$r =& $recordSet->fields;

	if ($r['c'] > 1) {
		foreach (explode(',',$r['user_id']) as $user_id) {
			fwrite($h['user_id_terms'],	implode("\t",array($user_id,"{$r['realname']}/$user_id")). "\n");
		}
	} else {
		fwrite($h['user_id_terms'],	implode("\t",array($r['user_id'],$r['realname'])). "\n");
	}
	$recordSet->MoveNext();
}

#####################################################

#moderation_status_terms
fwrite($h['moderation_status_terms'],	implode("\t",array(3,'supplemental')). "\n");
fwrite($h['moderation_status_terms'],	implode("\t",array(4,'geograph')). "\n");
	
#ftf_terms
#fwrite($h['ftf_terms'],	implode("\t",array(0,'')). "\n");
fwrite($h['ftf_terms'],	implode("\t",array(1,'first')). "\n");

#reference_index_terms
fwrite($h['reference_index_terms'],	implode("\t",array(1,'Great Britain')). "\n");
fwrite($h['reference_index_terms'],	implode("\t",array(2,'Ireland')). "\n");

#####################################################

$sql = "SELECT gridimage_id, gi.comment,
gi.title, gi.realname,
gi.user_id, gridsquare_id, placename_id, gi.moderation_status+0 as moderation_status, gi.ftf, gi.imagetaken, gi.imageclass, gi.reference_index
FROM tmpflam INNER JOIN gridimage_search gi USING (gridimage_id) INNER JOIN gridimage g2 USING (gridimage_id)";
//todo use coalease(g2.placename_id,gs.placename_id) - but check it works on 0 (rather than just null)
print "$sql\n";

$image = new GridImage;

$months = array(0=>'');
foreach (range(1,12) as $month) {
	$months[$month] = date("M",strtotime("2005-$month-04"));
}

$recordSet = &$db->Execute($sql);
$t = 1; $c = 1; $p = 1;
$imagetaken_map = $imageclass_map = array();
$places = array();
while (!$recordSet->EOF) 
{
	$r =& $recordSet->fields;
	
	$image->fastInit($r);
	$details = $image->getThumbnail(120,120,2);
	$thumbnail = $details['server'].$details['url']; 
	
	
	
	fwrite($h['items'], implode("\t",array($r['gridimage_id'],str_replace("\t",' ',$r['title']),$r['realname'],$thumbnail)). "\n");


	fwrite($h['user_id_map'], 		implode("\t",array($r['gridimage_id'],$r['user_id'])). "\n");

	fwrite($h['grid_reference_map'],	implode("\t",array($r['gridimage_id'],$r['gridsquare_id'])). "\n");

	if ($r['placename_id']) {
		fwrite($h['place_map'],		implode("\t",array($r['gridimage_id'],$r['placename_id'])). "\n");

		$places[$r['reference_index']][$r['placename_id']] = 1;
	}

	fwrite($h['moderation_status_map'], 	implode("\t",array($r['gridimage_id'],$r['moderation_status'])). "\n");

	if ($r['ftf'])
		fwrite($h['ftf_map'], 		implode("\t",array($r['gridimage_id'],$r['ftf'])). "\n");

	if (strpos($r['imagetaken'],'0000') === FALSE) {
		if (empty($imagetaken_map[$r['imagetaken']])) {
			$b = explode('-',$r['imagetaken']);
			fwrite($h['imagetaken_terms'], 	trim(implode("\t",array($t,preg_replace('/\d$/','0s',$b[0]),$b[0],$months[intval($b[1])],$b[2]>0?smarty_function_ordinal($b[2]):''))). "\n");

			$imagetaken_map[$r['imagetaken']] = $t;
			$t++;
		}
		fwrite($h['imagetaken_map'], 	implode("\t",array($r['gridimage_id'],$imagetaken_map[$r['imagetaken']])). "\n");
	}

	if (empty($imageclass_map[$r['imageclass']])) {
		fwrite($h['imageclass_terms'], 	implode("\t",array($c,$r['imageclass'])). "\n");
		
		$imageclass_map[$r['imageclass']] = $c;
		$c++;
	}
	fwrite($h['imageclass_map'], 		implode("\t",array($r['gridimage_id'],$imageclass_map[$r['imageclass']])). "\n");

	#fwrite($h['reference_index_map'], 	implode("\t",array($r['gridimage_id'],$r['reference_index'])). "\n");

	$text = implode(" ",array($r['title'],$r['comment'],$r['imageclass']));
	$text = trim(strtolower(preg_replace("/[^\w]+/",' ',$text)));

	fwrite($h['text'], 	implode("\t",array($r['gridimage_id'],$text)). "\n");

	//write out progress and send mysql keepalive every 500 results...
	if (!($p%500)) {
		print "...done $p\n";
		$dummy = $db->getOne("SELECT COUNT(*) FROM queries");
		sleep(1);
	}
	$p++;
	
	$recordSet->MoveNext();
}

$recordSet->Close();

$imagetaken_map = $imageclass_map = array();

#####################################################

$sql = "SELECT gridimage_id,label FROM gridimage_group INNER JOIN tmpflam USING (gridimage_id) WHERE label NOT LIKE '%Other%'";

print "$sql\n";

$recordSet = &$db->Execute($sql);

$cluster_map = array();
$c = 1;
while (!$recordSet->EOF) 
{
	$r =& $recordSet->fields;

	if (empty($cluster_map[$r['label']])) {
		fwrite($h['cluster_terms'], 	implode("\t",array($c,$r['label'])). "\n");
		
		$cluster_map[$r['label']] = $c;
		$c++;
	}
	fwrite($h['cluster_map'], 		implode("\t",array($r['gridimage_id'],$cluster_map[$r['label']])). "\n");

	$recordSet->MoveNext();
}

$recordSet->Close();

#####################################################

if (!empty($places[1])) {
	//inner join os_gaz on (placename_id-1000000 = os_gaz.seq)
	$ids = '';
	foreach (array_keys($places[1]) as $pid) {
		if ($pid)
			$ids .= ($pid-1000000).",";
	}
	$ids = substr($ids,0,strlen($ids)-1);
	
	$sql = "select 
			os_gaz.seq+1000000 as placename_id,
			def_nam as Place,
			full_county as County,
			loc_country.name as Country,
			has_dup,km_ref
		from os_gaz 
			left join os_gaz_county on (os_gaz.co_code = os_gaz_county.co_code)
			left join loc_country on (country = loc_country.code)
		where os_gaz.seq IN ($ids)";

	print "$sql\n";

	$recordSet = &$db->Execute($sql);

	while (!$recordSet->EOF) 
	{
		$r =& $recordSet->fields;

		if (empty($r['Country'])) {
			$r['Country'] = 'Unknown';
		}
		if (empty($r['Country'])) {
			$r['County'] = 'Unknown';
		}
		fwrite($h['place_terms'], 		implode("\t",array($r['placename_id'],$r['Country'],$r['County'],$r['Place'].($r['has_dup']?"/{$r['km_ref']}":''))). "\n");

		$recordSet->MoveNext();
	}

	$recordSet->Close();

}

#####################################################

if (!empty($places[2])) {
	//inner join loc_placenames on (placename_id = id)
	$ids = implode(',',array_keys($places[2]));
	
	$sql = "select 
			id as placename_id,
			full_name as Place,
			loc_adm1.name as County,
			loc_country.name as Country,
			has_dup,e,n
		from loc_placenames
			left join loc_adm1 on (loc_placenames.adm1 = loc_adm1.adm1 and loc_adm1.country = loc_placenames.country)
			left join loc_country on (loc_placenames.country = loc_country.code)
		where loc_placenames.id IN ($ids)";
	
	print "$sql\n";

	$recordSet = &$db->Execute($sql);

	require_once('geograph/conversions.class.php');
	$conv = new Conversions;

	while (!$recordSet->EOF) 
	{
		$r =& $recordSet->fields;

		if ($r['has_dup'] && empty($r['gridref'])) {
			list($r['gridref'],) = $conv->national_to_gridref($r['e'],$r['n'],4,2);
		}
		if (empty($r['County'])) {
			$r['County'] = 'Unknown';
		}
		fwrite($h['place_terms'], 		implode("\t",array($r['placename_id'],$r['Country'],$r['County'],$r['Place'].($r['gridref']?"/{$r['gridref']}":''))). "\n");

		$recordSet->MoveNext();
	}

	$recordSet->Close();
}

#####################################################

print "done\n";


