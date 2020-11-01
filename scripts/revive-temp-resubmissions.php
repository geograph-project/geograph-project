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

############################################

//these are the arguments we expect
$param=array(
	'execute'=>false,
	'single'=>true,
	'start'=>6618237,
	'limit'=>50,
	'old'=>true,
);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

############################################

if (!is_dir("{$_SERVER['DOCUMENT_ROOT']}/photos/01/"))
	die("photos folder not mounted?\n");

if (trim(`whoami`) != 'www-data')
	die("must be run as www-data\n");

############################################

$submitted = $db->getOne("SELECT submitted from gridimage where gridimage_id = {$param['start']}");


//$rows = $db->getAll("select * from submission_method where gridimage_id > 6618237 limit 50");
$rows = $db->getAll($sql = "
select gridimage_id,upload_id,user_id,status, pending_id
from gridimage_pending
 where type = 'original' AND suggested > '$submitted' and status = 'rejected'
AND LENGTH(upload_id) > 10 limit {$param['limit']}");
print "$sql;\n";

foreach ($rows as $row) {

        if (empty($row['user_id'])) {
                $a = '?';
        } else {
                $a = $row['user_id']%10;
        }
        if ($param['old']) {
                $cmd = "find {$CONF['photo_upload_dir']}*/$a/ -name '*{$row['upload_id']}*'";
        } else {
                $cmd = "find {$CONF['photo_upload_dir']}/$a/ -name '*{$row['upload_id']}*'";
        }

	print str_repeat('#',80)."\n";
	print "$cmd\n";


//Actully we know the filename format, so ratner than using find (which does slow directly scan, look for files explicitly!
#/var/www/geograph_live/upload_tmp_dir_old/2/newpic_u13502_8e38e115b94a86e275cb0658a97c3503.jpeg

$cmd = "ls -1 {$CONF['photo_upload_dir']}/$a/newpic_u{$row['user_id']}_{$row['upload_id']}.original.jpeg";
$cmd .=     " {$CONF['photo_upload_dir']}/$a/newpic_u{$row['user_id']}_{$row['upload_id']}.jpeg";
if ($param['old']) {
	$cmd .=     " {$CONF['photo_upload_dir']}_old/$a/newpic_u{$row['user_id']}_{$row['upload_id']}.original.jpeg";
	$cmd .=     " {$CONF['photo_upload_dir']}_old/$a/newpic_u{$row['user_id']}_{$row['upload_id']}.jpeg";
}

$cmd .= " 2> /dev/null"; //silence warnings about non existing files!

	$param['execute'] = 0; //we no longer accept it from command line, we enable it interactivelly.
	restart:

	$sqls = $cmds = array();
	$image = null;
	foreach (explode("\n",`$cmd`) as $filename) {
		if (empty($filename)) continue;

//the exif file - if any - is not needed
		if (strpos($filename,'.exif')) {


//the 'original' file makes "_pending" to replace the largest upload
		} elseif (strpos($filename,'.original.jpeg')) {
			if (empty($image))
				$image = new Gridimage($row['gridimage_id']);
			print "Mod: {$image->moderation_status}   https://www.geograph.org.uk/admin/resubmissions.php?review={$row['gridimage_id']}\n";

//show exta data, run a NEW query to get ALL rows, in case many!
$info = $db->getAll("select upload_id,user_id,gridimage_ticket_id,suggested,type,status,updated from gridimage_pending where gridimage_id = {$row['gridimage_id']} ORDER BY suggested");
foreach ($info as $i)
	print implode("\t",$i)."\n";
$sqls[] = "update gridimage_pending set status = 'invalid' where pending_id = {$row['pending_id']}";

			print `ls -l $filename`;

		//	in thise case the 'original' should have ALREADY been resized (ie done in efs, even if transfer to S3 failed

			//$ok =$image->storeImage($orginalfile,$this->use_new_upload,'_pending');

	                        $path = $image->_getOriginalpath(true,false,'_pending');
        	                if (basename($path) == "error.jpg") {
                	                print "path = ".$image->_getOriginalpath(false,false,'_pending')."\n";


					//function storeOriginal($srcfile, $movefile=true)
					//function storeImage($srcfile, $movefile=false, $suffix = '')
					print "image[{$row['gridimage_id']}]->storeImage($filename,false,'_pending')";
					if ($param['execute'])
						print "->".$image->storeImage($filename,false,'_pending');
					print "\n";

				} else {
					print "#$path already exists!\n";
					print `ls -l {$_SERVER['DOCUMENT_ROOT']}$path`;
					$cmds[] = "rm {$_SERVER['DOCUMENT_ROOT']}$path";
				}

//may as well show the current original too!
			$path = $image->_getOriginalpath(true,false);
                        if (basename($path) == "error.jpg")
				print "# There is no current _original\n";
			else
				print `ls -l {$_SERVER['DOCUMENT_ROOT']}$path`;

//the 'fullsize' makes the "_preview" to act as a preview of the main file
		} else {
			if (empty($image))
				$image = new Gridimage($row['gridimage_id']);
			//print "Mod: {$image->moderation_status}   https://www.geograph.org.uk/editimage.php?id={$row['gridimage_id']}\n";
			print `ls -l $filename`;

			$path = $image->_getOriginalpath(true,false,'_preview');
			if (basename($path) == "error.jpg") {
				print "path = ".$image->_getFullpath(false,false,'_preview')."\n";

				//function storeImage($srcfile, $movefile=false, $suffix = '')

				//if ($ok = $image->storeImage($src,$this->use_new_upload,'_preview')) {

				print "image[{$row['gridimage_id']}]->storeImage($filename,false,'_preview')";
				if ($param['execute'])
					print "->".$image->storeImage($filename,false,'_preview');
				print "\n";

			} else {
				print "#$path already exists!\n";
				print `ls -l {$_SERVER['DOCUMENT_ROOT']}$path`;
				$cmds[] = "rm {$_SERVER['DOCUMENT_ROOT']}$path";
			}
		}

		if (strpos($filename,'_old')===FALSE) {
			$new = str_replace('upload_tmp_dir','upload_tmp_dir_old',$filename);

			print "mv $filename $new\n";
			if ($param['execute'])
				rename($filename,$new);
		}
		print "\n";
	}

	if (!empty($sqls) || !empty($cmds)) {
		if ($param['execute'])
			print "# Note fixes run above\n";
		print implode("\n",$cmds)."\n";
		print implode(";\n",$sqls).";\n";
		$r = readline("Execute fixes?");

		if ($r == 'e') {
			$param['execute'] = 1; //turn on the main execute fucntion
			goto restart;
		} elseif ($r == 'y') {
			foreach ($cmds as $c)
				passthru($c);
			foreach ($sqls as $s)
				$db->Execute($s) or die(mysql_error());
		}
	}

	if (!empty($image) && $param['single'])
		die();
}




