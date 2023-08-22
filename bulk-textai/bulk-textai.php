<?php
/**
 * $Project: GeoGraph $
 * $Id: recreate_maps.php 2996 2007-01-20 21:39:07Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 Barry Hunter (geo@barryhunter.co.uk)
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

$param = array('sleep'=>0, 'folder'=>'/mnt/efs/data/','filename'=>'geograph_labeldata_001', 'limit' => 100, 'prefix'=>'subject','n'=>0,
	 'shard'=>'', 'comment'=>false, 'minimum'=>20);

//chdir(__DIR__);
//require "./_scripts.inc.php";
$param['config'] = '...'; //dummy!

//very simple argument parser
for($i=1; $i<count($_SERVER['argv']); $i++) {
        $arg=$_SERVER['argv'][$i];
        if (substr($arg,0,2)=='--') {
                $arg=substr($arg,2);
                $bits=explode('=', $arg,2);
                if (isset($param[$bits[0]])) {
                        //if we have a value, use it, else just flag as true
                        $param[$bits[0]]=isset($bits[1])?$bits[1]:true;
                }
                else die("unknown argument --$arg\n");
        }
        else die("unexpected argument $arg\n");
}


//$db = GeographDatabaseConnection(true);

$db = mysqli_connect($_SERVER['CONF_DB_CONNECT'], $_SERVER['CONF_DB_USER'], $_SERVER['CONF_DB_PWD'], 'geograph_live');


######################################################################################################################################################

print "Writing to {$param['folder']}/{$param['filename']}\n";

if (!is_dir($param['folder'].'/'.$param['filename']))
	mkdir($param['folder'].'/'.$param['filename']);

//log the command
$h = fopen($param['folder'].$param['filename']."/cmd.txt", 'a');
fwrite($h, implode(' ',$argv)."\n");
fclose($h);

#########################################

//todo, combine tmp_subject_shard & tmp_subject_stat to allo using shard&comment at once!

if (strlen($param['shard'])) {
        $sql = "SELECT gridimage_id, tag, realname, title, upd_timestamp
        FROM gridimage_search
	inner join gridimage_tag using (gridimage_id)
	inner join tmp_subject_shard using (tag_id)
	where shard = {$param['shard']} and status = 2
        LIMIT {$param['limit']}";
	/* create table tmp_subject_shard select tag_id,tag,regexp_replace(tag,'[^\\w]+','') as `value`,crc32(regexp_replace(tag,'[^\\w]+','')) mod 10 as shard
		 from  tag_stat inner join tag using (tag_id)
		 where `count` > 20 and prefix = 'subject';
	*/
} elseif ($param['comment']) {
	//this is specifically looking for long comments

	$sql = "SELECT gridimage_id, tag, realname, title, comment, upd_timestamp
	FROM gridimage_search
	INNER JOIN gridimage_tag USING (gridimage_id)
	INNER JOIN tmp_subject_stat USING (tag_id)
	WHERE status=2
	AND longcomments > {$param['minimum']}
	AND length(comment) > 250
	AND length(comment) < 8000
	LIMIT {$param['limit']}";
	//the prejoined table is just subject, ignoring $param['prefix']

	//Dont know limit in for liner.ai, but 12392 bytes failed, so use 8k for now?

} else {
	$sql = "SELECT gridimage_id, tag, realname, title, upd_timestamp
	FROM gridimage_search
	INNER JOIN tag_public USING (gridimage_id)
	INNER JOIN tag_stat USING (tag_id)
	WHERE prefix = '{$param['prefix']}'
	AND `count` > {$param['minimum']}
	LIMIT {$param['limit']}";
}

$pop = 2; ///dont write last column to metadata file!

//	$recordSet = $db->Execute($sql);
	$result = mysqli_query($db,$sql);

//	$count = $recordSet->RecordCount();
	$count = mysqli_num_rows($result);
	if (!$count)
		exit;

	print "$count. ";

#########################################


require_once 'vendor/autoload.php';
use splitbrain\PHPArchive\Tar;
use splitbrain\PHPArchive\FileInfo;


	$row = mysqli_fetch_assoc($result);

	$h = fopen($param['folder'].$param['filename'].'/'.$param['filename'].'.metadata.csv','wb'); //we writing utf8!
	if (!filesize($param['folder'].$param['filename'].'/'.$param['filename'].'.metadata.csv')) {
        	$keys = array_keys($row);
	        array_unshift($keys,'filename'); foreach(range(1,$pop) as $l) { array_pop($keys); } //remove not needed
	        fputcsv($h,$keys);
	}


$tar = new Tar();
$tar->create($param['folder'].$param['filename'].'/'.$param['filename'].'.tar.gz');


	$c=0;
	$labels = array();
	$done = array();
	do {
		if ($param['n'] && @$done[$row['tag']] > $param['n'])
			continue;
		@$done[$row['tag']]++;

		$value = preg_replace('/[^\w]+/','',$row['tag']);

		@$labels[$value]++;
/*
if (strlen($param['shard'])) {
        $crc = sprintf("%u", crc32($value));
        if ($crc%10 != $param['shard']) {
//                 print "Skipping $value (crc $crc % ".($crc%10).")\n";
                 continue;
        }
}
*/

		$filename = sprintf('%d.txt', $row['gridimage_id']);

		$relative = "$value/$filename";

		        $row['title'] = latin1_to_utf8($row['title']);
			$row['realname'] = latin1_to_utf8($row['realname']);

			//liner doesnt actully cope with utf8 - even with a BOM - so transliterate
				//note we STILL convert to utf8 first, rather than detect ISO-8859-15 directly (ie more than ascii), because latin1_to_utf8 first decodes entities, which mb_detect_encoding wont pickup
			$enc = mb_detect_encoding($row['title'], 'UTF-8, ISO-8859-15, ASCII');
			if ($enc == 'UTF-8') // should no longer ever detect ISO-8859-15
				$row['title'] = translit_to_ascii($row['title'], "UTF-8");

			//add the comment to the text...
			if (!empty($row['comment'])) {
				$row['comment'] = latin1_to_utf8($row['comment']);

				//remove the links. Probably wont help
				$row['comment'] = preg_replace('/(?<!["\'>F=])(https?:\/\/[\w\.-]+\.\w{2,}\/?[\w\~\-\.\?\,=\'\/\\\+&%\$#\(\)\;\:\@\!]*)(?<!\.)(?!["\'])/', '', $row['comment']);
				   $row['comment'] = preg_replace('/(?<![>\/F\."\'])(www\.[\w\.-]+\.\w{2,}\/?[\w\~\-\.\?\,=\'\/\\\+&%\$#\(\)\;\:\@\!]*)(?<!\.)(?!["\'])/', '', $row['comment']);

				//remove geograph links too!
				$row['comment'] = preg_replace('/\[\[(\[?)([a-z]+:)?(\w{0,3} ?\d+ ?\d*)(\]?)\]\]/', '', $row['comment']);

				//... and append
				$enc = mb_detect_encoding($row['comment'], 'UTF-8, ISO-8859-15, ASCII');
				if ($enc == 'UTF-8') { // should no longer ever detect ISO-8859-15
					$row['title'] .= ".\n\n".translit_to_ascii($row['comment'], "UTF-8");
				} else {
					$row['title'] .= ".\n\n".$row['comment'];
				}
			}

			$info = new FileInfo($relative);
			$info->setMTime(strtotime($row['upd_timestamp']));
			$tar->addData($info, $row['title']);

		        foreach(range(1,$pop) as $l) { array_pop($row); } //remove not needed
			fputcsv($h,$row);

		$c++;

		if (!($c%1000))
			print "$c ";

	} while ($row = mysqli_fetch_assoc($result));


print "\n\n";

##################################


print "cd {$param['folder']}{$param['filename']}\n";
	chdir("{$param['folder']}{$param['filename']}");

print "cp -p ../LICENCE ./\n";
        passthru("cp -p ../LICENCE ./");


$tar->addFile("LICENCE");
$tar->addFile("{$param['filename']}.metadata.csv");
$tar->close();


##################################


print "./aws/dist/aws s3 mv --storage-class INTELLIGENT_TIERING --acl public-read {$param['folder']}{$param['filename']}/{$param['filename']}.tar.gz s3://data.geograph.org.uk/datasets/{$param['filename']}.tar.gz\n";

$format = 'title.txt';
if ($param['comment'])
	$format = 'comment.txt';
$labels = count($labels);

print "insert into dataset set src_format = 'subdir', folder = '{$param['filename']}', imagesize = '$format', grouper='{$param['prefix']}'";
print ", src_download = 'https://s3.eu-west-1.amazonaws.com/data.geograph.org.uk/datasets/{$param['filename']}.tar.gz'";
 $size = filesize("{$param['filename']}.tar.gz");
 $time = filemtime("{$param['filename']}.tar.gz");
print ", src_size=$size,src_time=FROM_UNIXTIME($time)";

print ", images = {$images}, labels = {$labels};\n\n";


##################################



function latin1_to_utf8($input) {
        //our database has charactors encoded as entities (outside ISO-8859-1) - so need to decode entities.
        //and while we declare ISO-8859-1 as the html charset, we actully using windows-1252, as some browsers are sending us chars not valid in ISO-8859-1.
        //todo detect iconv not installed, and use utf8_encode as a fallback??
        //we dont utf8_encode if can help it, as it only supports ISO-8859-1, NOT windows-1252
        return html_entity_decode(
                iconv("windows-1252", "utf-8", $input),
                ENT_COMPAT, 'UTF-8');
}

function translit_to_ascii($in, $charset = 'ISO-8859-15') {

        $currentLocal = setlocale(LC_CTYPE, 0);
        //see comments on http://php.net/manual/en/function.iconv.php  //TRANSLIT may only work if set a UTF8 locale, even though NOT even using unicode (ie not set to charset, always utf8)
        setlocale(LC_CTYPE, "en_US.UTF-8");

        $new = iconv($charset, 'ASCII//TRANSLIT', $in);

        setlocale(LC_CTYPE, $currentLocal);

        return $new;
}
