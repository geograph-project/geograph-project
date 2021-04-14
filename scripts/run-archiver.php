#!/usr/bin/php
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
	//source = gridimage_queue
	//dest = gridimage
	'execute'=>true,
);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

if (empty($CONF['use_insertionqueue']))
	die("Error: doesnt appear to be using use_insertionqueue\n");

//--source h=db-master-pvt,u=geograph,p=m4pp3r,D=geograph_live,t=gridimage_queue
$source = "--source h={$CONF['db_connect']},u={$CONF['db_user']},p={$CONF['db_pwd']},D={$CONF['db_db']},t=gridimage_queue";

// --dest t=gridimage
$destination = "--dest t=gridimage";

// --where '1'
$where  = "--where '1'";

//http://www.geograph.org.uk";
$url_prefix = $CONF['CONTENT_HOST'];

############################################

$last = '';
while (1) {
        $output = array();
        $cmd = "/usr/bin/pt-archiver $source $destination $where --txn-size 0 --statistics --nosafe-auto-increment 2>&1";
				 #last option is safe on myisam table (which tracks the auto-incr value directly), not innodb

	print "$cmd\n";
	if (!$param['execute'])
		exit;

	exec($cmd,$output);

        $str = implode("\n",$output);
        print "$str\n";

        if (preg_match('/DBD/', $str) && $str != $last) {

		if (false) {
			print $str."\n\n";
			print "$url_prefix/_scripts/increment_gridsquare.php?gs={$m[1]}";
			print "\n\n";

	                if (preg_match("/Duplicate entry '(\d+)-\d+' for key (2|'gridsquare_id') /", $str, $m)) {
				print_r($m);
			}

			print "memcahce: ".($memcache->valid)."\n\n";
			$mkey = $m[1];
			$result = $memcache->name_get('sid2',$mkey);
			var_dump($result);
			print "\n\n";
			exit;
		}


                if (preg_match("/Duplicate entry '(\d+)-\d+' for key (2|'gridsquare_id') /", $str, $m)) {

			//now this a proper script, the CLI should have access to memcache!
			if (!empty($memcache) && $memcache->valid) {
				//we will open a new connection each time! We could of been running a long time!
				$db = GeographDatabaseConnection(false);

				$sql = "update gridimage_queue set seq_no=seq_no+1 where gridsquare_id = {$m[1]}";
				$db->Execute($sql);
				$str .= "Affected Rows: ".$db->Affected_Rows()."\n";

				$mkey = $m[1];
				$result = $memcache->name_delete('sid2',$mkey);
				$str .= "Memcache Delete said: $result\n";

			} else {
	                        //NOTE: we use an external script, rather than just doing db query here, as it needs to clear memcach too
        	                $str .= "\n\n". file_get_contents("$url_prefix/_scripts/increment_gridsquare.php?gs={$m[1]}");
			}
                }

       	        $subject = explode("\n",wordwrap($str));
               	$subject = $subject[0];

                if (!mail('geograph@barryhunter.co.uk', '[Geograph] '.$subject, $str)) {
	                #mail doesnt work on jam for some reason...

	                $str = date('r')."\n".$str;

        	        file_get_contents("http://www.nearby.org.uk/mailme.php?subject=".urlencode('[Geograph] '.$subject).'&msg='.urlencode($str));
		}

                sleep(10);
        } else {

                if (!empty($subject)) { #we have a subject from the LAST error - so sending a 'all clear' with the same subject.
			if (!mail('geograph@barryhunter.co.uk', '[Geograph] '.$subject, $str)) {
	                         file_get_contents("http://www.nearby.org.uk/mailme.php?subject=".
        	                urlencode('[Geograph] '.$subject).'&msg='.urlencode($str));
			}
                }
                $subject = '';

                sleep(2);
        }
        $last = $str;
}


############################################


