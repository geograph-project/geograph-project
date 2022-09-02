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
        'execute'=>false, //set to true to actully execute. otherwise a dry run!
        'single'=>true, //only process one image, kinda like hardcoded limt
        'start'=>6618237, //when doing scan, start from here. ignored when spefigin 'id'
        'limit'=>50, //max to process 
        'user_id'=>false, //optionallt filter to a single user
	'mode'=>'larger', //larger means look for images that appear to be uploaded with a larger image, but none found
        'clear'=>false, //clear memcache and gridimage_size
        'id'=>false, //look for a single image
        'old'=>true, //look in old folder as well?
        'size'=>false, //report on the size, but note may cache the current (incorrect? value to memcache!)
);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

############################################

$filesystem = GeographFileSystem();

$smarty = new GeographPage;

############################################

if (!empty($param['id']))
        $where = "gridimage_id = ".$param['id'];
elseif ($param['mode'] == 'larger')
	$where = "gridimage_id > {$param['start']} and original_width = 0 and largestsize > 640";
else
	$where = "gridimage_id > {$param['start']} and gridimage_size.gridimage_id is null";

$where .= " and moderation_status != 'pending'";

if ($param['user_id'])
        $where .= " and user_id = ".intval($param['user_id']);


//$rows = $db->getAll("select * from submission_method where gridimage_id > 6618237 limit 50");
$rows = $db->getAll("
select gridimage_id,preview_key,largestsize,user_id from submission_method
 inner join gridimage gi using (gridimage_id)
  left join gridimage_size using (gridimage_id)
 where {$where} limit {$param['limit']}");

$GLOBALS['USER'] = new GeographUser; //just to shutup a notice from the class. it expects USER to be defined! We only use _downsizeFile which doesnt need a USER

$up = new UploadManager();

foreach ($rows as $row) {
        print implode(', ',$row)."\n";
        if (empty($row['user_id'])) {
                $a = '?';
        } else {
                $a = $row['user_id']%10;
        }
        if ($param['old']) {
                $cmd = "find {$CONF['photo_upload_dir']}*/$a/ -name 'newpic_u{$row['user_id']}_{$row['preview_key']}*'";
        } else {
                $cmd = "find {$CONF['photo_upload_dir']}/$a/ -name 'newpic_u{$row['user_id']}_{$row['preview_key']}*'";
        }

        //Actully we know the filename format, so ratner than using find (which does slow directory scan), look for files explicitly!
        $cmd = "ls -1 {$CONF['photo_upload_dir']}/$a/newpic_u{$row['user_id']}_{$row['preview_key']}.original.jpeg";
        $cmd .=     " {$CONF['photo_upload_dir']}/$a/newpic_u{$row['user_id']}_{$row['preview_key']}.jpeg";
        if ($param['old']) {
                $cmd .=     " {$CONF['photo_upload_dir']}_old/$a/newpic_u{$row['user_id']}_{$row['preview_key']}.original.jpeg";
                $cmd .=     " {$CONF['photo_upload_dir']}_old/$a/newpic_u{$row['user_id']}_{$row['preview_key']}.jpeg";
        }

        $cmd .= " 2> /dev/null"; //silence warnings about non existing files!


        //print "$cmd  ##for {$row['gridimage_id']}\n";

        $image = null;
        $fixes=0;
        foreach (explode("\n",`$cmd`) as $filename) {
                if (empty($filename)) continue;

                $resized = null;
                if (strpos($filename,'.exif')) { //ignore these for now, but probably have been saved.
                } elseif (preg_match('/\.\d+\.jpeg$/',$filename)) { //these are previous resized images, ignroe them
                } elseif (strpos($filename,'.original.jpeg')) {
                        if (empty($image))
                                $image = new Gridimage($row['gridimage_id']);

                        if ($row['largestsize'] == 65536) {
                                $path = $image->_getOriginalpath(true);
                                if (basename($path) == "error.jpg") {
                                        $fixes++;
                                        print "path = ".$image->_getOriginalpath(false)."\n";


                                        //function storeOriginal($srcfile, $movefile=true)
                                        print "image[{$row['gridimage_id']}]->storeOriginal($filename,false)";
                                        if ($param['execute'])
                                                print "->".$image->storeOriginal($filename,false);
                                        print "\n";

                                        //print "mv $filename ... {$row['gridimage_id']}-original.jpg   ##[{$row['largestsize']}]\n";
                                } else {
                                        print "#$path already exists!\n";
                                        $bytes = $filesystem->filesize("{$_SERVER['DOCUMENT_ROOT']}$path");
                                        if ($bytes < 100) {
                                                print "#But is only $bytes bytes!!!\n";
                                                exit;
                                        }
                                }

                        } elseif (intval($row['largestsize']) > 640) {

                                $path = $image->_getOriginalpath(true);
                                if (basename($path) == "error.jpg") {
                                        $fixes++;
                                        print "path = ".$image->_getOriginalpath(false)."\n";

                                        $resized =  str_replace('original',$row['largestsize'],$filename);

                                        //function _downsizeFile($filename,$max_dimension,$source = '') {
                                        print "_downsizeFile($resized,{$row['largestsize']},$filename)\n";
                                        print "image[{$row['gridimage_id']}]->storeOriginal($resized,false)";

                                        if ($param['execute']) {
                                                $up->_downsizeFile($resized,$row['largestsize'],$filename);
                                                if ($filesystem->file_exists($resized)) {
                                                        print "->".$image->storeOriginal($resized,false);
                                                } else {
                                                        //in case of the image being the right size already, resized wont be created!
                                                        print "->".$image->storeOriginal($filename,false);
                                                }
                                        }
                                        print "\n";

                                 //       $cmds[]= "sudo -u www-data convert -resize {$row['largestsize']}x{$row['largestsize']} -quality 87 -strip
                                 //     print "convert $filename -resize {$row['largestsize']}x{$row['largestsize']} {$row['gridimage_id']}-original.jpg\n";
                                } else {
                                        print "#$path already exists!\n";
                                        $bytes = $filesystem->filesize("{$_SERVER['DOCUMENT_ROOT']}$path");
                                        if ($bytes < 100) {
                                                print "#But is only $bytes bytes!!!\n";
                                                exit;
                                        }
                                }
                        } else {
                                print "# largestsize = {$row['largestsize']}\n";
                        }

                } else {
                        if (empty($image))
                                $image = new Gridimage($row['gridimage_id']);

                        $path = $image->_getFullpath(true);
                        if (basename($path) == "error.jpg") {
                                $fixes++;
                                print "path = ".$image->_getFullpath(false)."\n";

                                //function storeImage($srcfile, $movefile=false, $suffix = '')

                                print "image[{$row['gridimage_id']}]->storeImage($filename,false)";
                                if ($param['execute'])
                                        print "->".$image->storeImage($filename,false);
                                print "\n";

                                //print "mv $filename ... {$row['gridimage_id']}\n";
                        } else {
                                print "#$path already exists!\n";
                                $bytes = $filesystem->filesize("{$_SERVER['DOCUMENT_ROOT']}$path");
                                if ($bytes < 100) {
                                        print "#But is only $bytes bytes!!!\n";
                                        exit;
                                }
                                if ($param['size']) {
                                        //we can print the size to check, but also adds to memcache, and the gridiamge_size, which verifies the upload
                                        $size = $image->_getFullSize();
                                        print implode(';  ',$size)."\n";
                                }
                        }
                }


                if (strpos($filename,'_old')===FALSE) {
                        $new = str_replace('upload_tmp_dir','upload_tmp_dir_old',$filename);

                        print "mv $filename $new\n";
                        if ($param['execute'])
                                rename($filename,$new);
                }
                if (!empty($resized) && strpos($resized,'_old')===FALSE) {
                        $new = str_replace('upload_tmp_dir','upload_tmp_dir_old',$resized);

                        print "mv $resized $new\n";
                        if ($param['execute'])
                                rename($resized,$new);
                }
        }

        if (($fixes || $param['clear']) && $param['execute']) {
                $gridimage_id = $row['gridimage_id'];

                //clear caches involving the image
                $ab=floor($gridimage_id/10000);
                $smarty->clear_cache('', "img$ab|{$gridimage_id}|");

                //clear memcache (:F contains details of 'Full' image, AND the 'larger' upload)
                $mkey = "{$gridimage_id}:F";
                $memcache->name_delete('is',$mkey);

                //delete the cache
                $db->Execute("DELETE FROM gridimage_size WHERE gridimage_id = $gridimage_id"); // could populate this now, but easier to delete, and let it autorecreate
        }

        print "\n";
        if (!empty($fixes) && $param['single'])
                die();
}


