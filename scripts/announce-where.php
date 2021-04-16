<?php
/**
 * $Project: GeoGraph $
 * $Id: viewsearches.php,v 1.5 2005/08/06 12:35:01 barryhunter Exp $
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
$param=array();

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

############################################

if (!$db->getOne("SHOW TABLES LIKE 'whereisit'"))
	die(posix_isatty(STDOUT)?"whereisit table doesnt exist\n":''); //dont say anything in cron

############################################


$topic_id = 14107;

function oneof($a) {
	shuffle($a);
	return $a[0];
}



$last = $db->getRow("SELECT * FROM whereisit ORDER BY id DESC limit 1");

$message = "";

if (count($last) > 1) {

	$check = $db->getRow("SELECT * FROM geobb_posts WHERE topic_id = $topic_id AND post_id > {$last['post_id']} AND (post_text LIKE '%{$last['grid_reference']}%' OR post_text LIKE '%[[{$last['gridimage_id']}]]%')");

	if (count($check) > 1) {
                $msg = oneof(array("Correct!","You got it :)","You win!","Well done.","Excellent :)"));
		$message = "<b>{$check['poster_name']}</b><br>$msg<br><br>";

		if (rand(1,100) > 98) {
			$poster = $check['poster_id'];
		        $rand = rand(1,$db->getOne("SELECT max(gridimage_id) FROM gridimage_search"));
		        $image = $db->getRow("SELECT gridimage_id,grid_reference FROM gridimage_search WHERE gridimage_id > $rand ORDER BY user_id = $poster DESC, gridimage_id LIMIT 1");
		} elseif (rand(1,10) > 8) {
		        $seq = $db->getOne("SELECT MAX(seq_id) FROM gridimage_post")-1000;
			$image = $db->getRow("SELECT gridimage_id,grid_reference FROM gridimage_post INNER JOIN gridimage_search USING (gridimage_id) WHERE seq_id > $seq AND topic_id != $topic_id ORDER BY RAND() LIMIT 1");
		}

                $message .= oneof(array("Another..?<br><br>",
                              "Next!<br><br>",
                              "Lets go again!<br><br>",
                              "Post the 4 figure grid reference for this, please...<br><br>",
                              "Who can get this one?<br><br>"));
	} else {
		$guesses = $db->getAll("SELECT * FROM geobb_posts WHERE topic_id = $topic_id AND post_id > {$last['post_id']} AND poster_id!=23277 AND post_text REGEXP '[[:<:]][A-Z]{1,2}[[:digit:]]{4}[[:>:]]'");

		if (count($guesses) > 0) {
			foreach ($guesses as $guess) {
				preg_match_all('/\b([A-Z]{1,2}\d{4})\b/',$guess['post_text'],$m);

				if (count($m[1]) > 0) {
					$gr = $m[1][0];

					$message = "<b>{$guess['poster_name']}</b><br>Your guess $gr";

					$done =  $db->getRow("SELECT * FROM geobb_posts WHERE topic_id = $topic_id AND post_id > {$last['post_id']} AND post_text LIKE ".$db->quote($message.'%'));

					if (empty($done)) {


					$from = $db->getRow("SELECT x,y FROM gridsquare WHERE grid_reference = '$gr'");

					if ($from['x'] == 0) {
						$message .= " is in the middle of nowhere.";
					} else {
						$to = $db->getRow("SELECT x,y FROM gridsquare WHERE grid_reference = '{$last['grid_reference']}'");

						if (rand(1,149) > 148 && strlen($gr) == strlen($last['grid_reference'])) {
							$d = abs($from['y']-$to['y'])+abs($from['x']-$to['x']);
							$message .= " needs a journey though $d grid squares to reach the target. (no diagionals - boats allowed)";
						} elseif (rand(1,20) > 18) {
							preg_match('/^(\w{1,2})(\d)\d(\d)\d$/',$last['grid_reference'],$m);

							if (preg_match("/^{$m[1]}{$m[2]}\d{$m[3]}/",$gr)) {
								$message .= " is in the right hectad :)";
							} elseif (preg_match("/^{$m[1]}\d/",$gr)) {
								$message .= " is in the right myriad.";
							} elseif (rand(1,10) > 9) {
								$message .= " is not in the right myriad.";
							} else {
								$d = sqrt(pow($from['x']-$to['x'],2)+pow($from['y']-$to['y'],2));
                                                                $d = round($d/100);

								$message .= " is within about $d myriad widths.";
							}

						} elseif (rand(1,10) > 8) {
							if (rand(1,10) > 5) {
								$d = $from['y']-$to['y'];
								$dir = ($d > 0)?'north':'south';
							} else {
								$d = $from['x']-$to['x'];
								$dir = ($d > 0)?'east':'west';
							}
							$d = abs($d);
							if ($d > 200) {
                                                                //$d = round($d/100)*100;
								$message .= " is way too far $dir.";
                                                        } elseif ($d > 20) {
                                                                $d = round($d/10)*10;
								$message .= " is about $d km $dir.";
                                                        } else {
                                                                //$d = round($d/2)*2;
								$message .= " is only a little $dir.";
                                                        }

						} elseif (rand(1,50) > 48) {
							$d = sqrt(pow($from['x']-$to['x'],2)+pow($from['y']-$to['y'],2));
							$angle = rad2deg(atan2( $from['x']-$to['x'], $from['y']-$to['y'] ));
							$d = number_format($d,2);
				                        $message .= " is $d km ".heading_string($angle)." as the peacock flies.";
						} else {
							$d = sqrt(pow($from['x']-$to['x'],2)+pow($from['y']-$to['y'],2));

							if ($d > 200) {
								$d = round($d/100)*100;
							} elseif ($d > 20) {
								$d = round($d/10)*10;
							} else {
								$d = round($d/2)*2;
							}
							if (rand(1,50) > 47) {
								$onepitch = 0.105; //km
								$d = round($d/$onepitch);
								$message .= " is about $d football fields away...";
							} elseif (rand(1,100) > 98) {
								$onesmoot = 0.0017018; //km
								$d = ($d/$onesmoot);
								$message .= " is within $d smoots.";
							} else {
								$message .= " is ".oneof(array("about $d km away...","within $d km or so.","somewhere within a $d km circle"));
							}
						}
					}



        $sql = "INSERT INTO geobb_posts SET topic_id = $topic_id,forum_id=19,poster_id=23277,poster_name='socket'";
        $sql .= ",post_time = NOW()";
        $sql .= ",post_text = ".$db->quote($message);

        $result = $db->Execute($sql) or die ("Couldn't insert : $sql " . $db->ErrorMsg() . "\n");
        $id = $db->Insert_ID();

        $sql = "UPDATE geobb_topics SET topic_last_post_id = $id,posts_count=posts_count+1 WHERE topic_id = $topic_id";
        $result = $db->Execute($sql) or die ("Couldn't insert : $sql " . $db->ErrorMsg() . "\n");
						//print $message;
						exit;
					}
					$message = "";
				}

			}
		}

		$int = "12 HOUR";

		if (!empty($_GET['int']) && preg_match('/^\d+ \w+$/',$_GET['int']))
			$int = $_GET['int'];

		$date = $db->getOne("SELECT DATE_SUB(NOW(),INTERVAL $int)");
		if ($date > $last['created']) {
			$ids = $db->getCol("SELECT gridimage_id FROM whereisit WHERE grid_reference = '{$last['grid_reference']}'"); //want to get them all!)
			$ids_str = implode(',',$ids);

			$image = $db->getRow($sql = "SELECT gridimage_id,grid_reference FROM gridimage_search WHERE grid_reference = '{$last['grid_reference']}' AND gridimage_id NOT IN ($ids_str) ORDER BY RAND() LIMIT 1");
			if (!empty($image)) {
				$message = oneof(array("Lets try another image from the square...<br><br>",
					"See if this nearby image helps...<br><br>",
					"another try...<br><br>"));
			}
		}
	}

} else {
	$message = "Where is this? Please post your answer by posting the 4 figure grid reference of the square containing the image<br><br>";
}

if (empty($message)) {
	exit;
}


if (empty($image)) {
	$rand = rand(1,$db->getOne("SELECT max(gridimage_id) FROM gridimage_search"));

	$image = $db->getRow("SELECT gridimage_id,grid_reference FROM gridimage_search WHERE gridimage_id > $rand ORDER BY gridimage_id LIMIT 1");
}



if ($image) {

        $token=new Token;
        $token->setValue("id", intval($image['gridimage_id']));

#       print "TOKEN: <TT>".$token->getToken()."</TT>";

        $src = "http://www.geograph.org.uk/stuff/captcha.php?token=".$token->getToken()."&amp;/med.jpg";


	$message .= "<img src=\"$src\" border=\"0\" align=\"\" alt=\"\">";

if (rand(1,100) > 93) {
	$images = $db->getAll("SELECT gridimage_id,grid_reference FROM gridimage_search WHERE grid_reference = '{$image['grid_reference']}' and gridimage_id != {$image['gridimage_id']} ORDER BY RAND() LIMIT 4");
	if (!empty($images)) {
		foreach ($images as $img) {
			$token->setValue("id", intval($img['gridimage_id']));
			$src = "http://www.geograph.org.uk/stuff/captcha.php?token=".$token->getToken()."&amp;/med.jpg";
			$message .= " <img src=\"$src\" border=\"0\" align=\"\" alt=\"\">";
		}
	}
}


	$sql = "INSERT INTO geobb_posts SET topic_id = $topic_id,forum_id=19,poster_id=23277,poster_name='socket'";
	$sql .= ",post_time = NOW()";
	$sql .= ",post_text = ".$db->quote($message);

	$result = $db->Execute($sql) or die ("Couldn't insert : $sql " . $db->ErrorMsg() . "\n");
        $id = $db->Insert_ID();

        $sql = "UPDATE geobb_topics SET topic_last_post_id = $id,posts_count=posts_count+1 WHERE topic_id = $topic_id";
        $result = $db->Execute($sql) or die ("Couldn't insert : $sql " . $db->ErrorMsg() . "\n");

        $sql = "INSERT INTO whereisit SET gridimage_id = {$image['gridimage_id']}, grid_reference='{$image['grid_reference']}',post_id = $id ";
        $result = $db->Execute($sql) or die ("Couldn't insert : $sql " . $db->ErrorMsg() . "\n");

	//print "SAVED $id";
} else {
	print "no id!";
}
