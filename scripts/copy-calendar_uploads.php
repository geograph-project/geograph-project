<?php
/**
 * $Project: GeoGraph $
 * $Id: create_tpoint.php 8956 2019-05-27 09:58:47Z barry $
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

$param = array(
        'execute'=>false,
);

chdir(__DIR__);
require "./_scripts.inc.php";

#######################################################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$data = $db->getAll("
select calendar_id, gridimage_id, calendar.user_id, upload_id
from calendar inner join gridimage_calendar using (calendar_id) where
ordered > '1000-01-01' and upload_id != ''
"); //using calendar.user_id, as that is the user that will have done the upload, although tye should only upload their own images anyway!

//in THIS case can CANT use uploadmanager, as it may it someone elses image!
$uploadmanager=new UploadManager;


foreach ($data as $row) {
        $image = new GridImage();
        $image->fastInit($row); //wont create a 'usable' gridimage object, but enough for our purposes to lookup the upload!

                //so have to do it long form...
                $id = $image->upload_id;
                if ($uploadmanager->use_new_upload) {
                        $u = $image->user_id;
                        $a = $image->user_id%10;
                        $b = intval($image->user_id/10)%10;
                        $orginalfile = "{$uploadmanager->tmppath}/$a/$b/$u/newpic_u{$u}_{$id}.original.jpeg";
                } else {
                        $orginalfile = $uploadmanager->tmppath.'/'.($image->user_id%10).'/newpic_u'.$image->user_id.'_'.$id.'.original.jpeg';
                }

        $base = basename($orginalfile);
        $copy = "/mnt/efs/calendar-files/$base";

        if (file_exists($copy))
                continue;

        print "{$row['calendar_id']}: {$row['gridimage_id']}: $orginalfile\n";


        if (!file_exists($orginalfile)) {
                //print "$orginalfile #NOT FOUND!\n";
                $oldfile = str_replace('upload_tmp_dir','upload_tmp_dir_old',$orginalfile);
                if (!file_exists($oldfile)) {
                        print "$oldfile #NOT FOUND!\n";
                } else {
                        print "cp -p $oldfile $copy\n";
                        if ($param['execute'])
                                copy($oldfile,$copy);
                }
        } else {
                print "cp -p $orginalfile $copy\n";
                if ($param['execute'])
                        copy($orginalfile,$copy);
        }
}
