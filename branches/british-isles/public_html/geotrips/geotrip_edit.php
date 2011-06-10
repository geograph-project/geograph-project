<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 Rudi Winter (http://www.geograph.org.uk/profile/2520)
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
 
ini_set("display_errors",1);

require_once('geograph/global.inc.php');

init_session();

$smarty = new GeographPage;

//you must be logged in to request changes
$USER->mustHavePerm("basic");

include('./geotrip_func.php');

  // get track from database
  $db=sqlite_open('../db/geotrips.db');
  if (!empty($_GET['trip'])) $trip=sqlite_fetch_array(sqlite_query($db,"select * from geotrips where id='{$_GET['trip']}'"));
  else $trip=sqlite_fetch_all(sqlite_query($db,"select * from geotrips where uid={$USER->user_id}"));
  sqlite_close($db);

$smarty->assign('page_title', 'Geo-Trip editor :: Geo-Trips');


$smarty->display('_std_begin.tpl');


    if (!empty($trip['uid']) && $trip['uid']==$USER->user_id) {  // editing your own trip?
    
      if (!isset($_POST['submit2'])) {  // input form
?>
        <div class="panel maxi">
          <h3>Geo-Trip edit form</h3>
          <p>
Each input field shows the values currently stored.  Edit any that need updating and submit.
There is no undo facility, but you can always edit again if you change your mind.
          </p>
          <p>
If you make changes to your images on Geograph (such as adding a camera position or description, or correcting
coordinates etc.), those changes will take up to a week before they make it through to Geo-Trips.
          </p>
          <form name="trip" method="post" action="<?php echo $PHP_SELF;?>" enctype="multipart/form-data">
            <hr style="color:#992233">
            <p>
              <b>Location</b> <span class="hlt">(required)</span><br />
              <input type="text" name="loc" size="72" value="<?php print_r($trip['location']); ?>" /><br />
            </p>
            <hr style="color:#992233">
            <p>
              <b>Starting point</b> <span class="hlt">(required)</span><br />
              <input type="text" name="start" size="72" value="<?php print($trip['start']); ?>" /><br />
            </p>
            <hr style="color:#992233">
            <p>
              <b>Type of trip</b> <span class="hlt">(required)</span><br />
              <select name="type">
                <option value="walk"<?php if ($trip['type']=='walk') print('selected="selected"'); ?>>walk</option>
                <option value="bike"<?php if ($trip['type']=='bike') print('selected="selected"'); ?>>cycle ride</option>
                <option value="road"<?php if ($trip['type']=='road') print('selected="selected"'); ?>>road trip</option>
                <option value="rail"<?php if ($trip['type']=='rail') print('selected="selected"'); ?>>train journey</option>
                <option value="boat"<?php if ($trip['type']=='boat') print('selected="selected"'); ?>>boat trip</option>
                <option value="bus"<?php if ($trip['type']=='bus') print('selected="selected"'); ?>>scheduled public transport</option>
              </select>
            </p>
            <hr style="color:#992233">
            <p>
              <b>Geograph search id</b> <span class="hlt">(required)</span><br />
              <input type="text" name="search" size="72" value="<?php print($trip['search']); ?>" /><br />
            </p>
            <p>
<a target="_blank" href="http://www.geograph.org.uk/search.php?i=<?php print($trip['search']); ?>&displayclass=more">This search</a>
(opens in a new window) is currently in use.  To refine it, use the <em>mark</em> buttons on the search page.  When finished,
click <em>view as search results</em> at the bottom of the search page.  Then copy the URL of the resulting custom search in the
box above.
            </p>
            <hr style="color:#992233">
            <p>
              <b>Title</b> (optional)<br />
              <input type="text" name="title" size="72" value="<?php print($trip['title']); ?>" /><br />
            </p>
            <hr style="color:#992233">
            <p>
              <b>Upload a new GPS track</b> in GPX format (optional)<br />
              <input type="file" name="gpxfile" size="40">
            </p>
            <hr style="color:#992233">
<?php
            // fetch Geograph thumbnail
            $csvf=fopen(fetch_url("http://www.geograph.org.uk/export.csv.php?key=7u3131n73r&i={$trip['search']}&count=250&en=1&thumb=1&desc=1&dir=1&ppos=1&big=1"),'r');
            fgets($csvf);  // discard header
            while ($line=fgetcsv($csvf,4092,',','"')) if ($line[0]==$trip['img']) $thumb=$line[6];
            fclose($csvf);
            $thumb=str_replace("_120x120.jpg","_213x160.jpg",$thumb);
?>
            <div class="inner flt_r">
              <img alt="" title="Currently selected featured image." src="<?php print($thumb); ?>" />
            </div>
            <p>
              <b>Geograph image id</b> (optional)<br />
              <input type="text" name="img" size="52" value="<?php print($trip['img']); ?>" /><br />
            </p>
            <p>
The image to the right shows the picture currently selected.  It won't update until you press <em>Submit</em>.
            </p>
            <p>
If you've changed the Geograph search used for this trip, please make sure that the featured image
is still included, or choose a new one.
            </p>
            <div class="row"></div>
            <hr style="color:#992233">
            <p>
              <b>Description</b> (optional)<br />
              <textarea rows="8" cols="80" name="descr"><?php print(preg_replace('/\n/','</p><p>',$trip['descr'])); ?></textarea>
            </p>
            <hr style="color:#992233">
            <p>
              <b>Continuation</b> (optional)<br />
              <input type="text" name="contfrom" size="72" value="<?php if ($trip['contfrom']) print($trip['contfrom']); ?>" /><br />
            </p>
            <div style="text-align:center;background-color:green">
              <input type="submit" name="submit2" value="Ok" style="margin:10px" />
            </div>
          </form>
        </div>
<?php
      } else {  // input received - update database
        $db=sqlite_open('../db/geotrips.db');
        if ($_POST['type']!=$trip['type'])
          sqlite_query($db,"update geotrips set type='{$_POST['type']}' where id={$trip['id']}");
        if ($_POST['loc']&&$_POST['loc']!=$trip['location'])
          sqlite_query($db,"update geotrips set location='".str_replace('\\','',sqlite_escape_string($_POST['loc']))."' where id={$trip['id']}");
        if ($_POST['start']&&$_POST['start']!=$trip['start'])
          sqlite_query($db,"update geotrips set start='".str_replace('\\','',sqlite_escape_string($_POST['start']))."' where id={$trip['id']}");
        if ($_POST['title']!=$trip['title'])
          sqlite_query($db,"update geotrips set title='".str_replace('\\','',sqlite_escape_string($_POST['title']))."' where id={$trip['id']}");
        if ($_POST['img']&&$_POST['img']!=$trip['img']) {
          $img=explode('/',$_POST['img']);
          $img=$img[sizeof($img)-1];
          sqlite_query($db,"update geotrips set img=$img where id={$trip['id']}");
        }
        if ($_POST['descr']!=$trip['descr'])
          sqlite_query($db,"update geotrips set descr='".str_replace('\\','',sqlite_escape_string($_POST['descr']))."' where id={$trip['id']}");
        sqlite_query($db,"update geotrips set updated='".date('U')."' where id={$trip['id']}");
        if ($_POST['search']&&$_POST['search']!=$trip['search']) {
          $search=explode('=',$_POST['search']);
          $search=$search[sizeof($search)-1];
          $cachepath="../cache/file".md5($url).".cache";
          @$csvf=fopen(fetch_url("http://www.geograph.org.uk/export.csv.php?key=7u3131n73r&i=$search&count=250&taken=1&en=1&thumb=1&desc=1&dir=1&ppos=1"),'r') or die('Geograph seems to be down at the moment.  Please don\'t navigate away from this page and press F5 in a few minutes.');
          fgets($csvf);  // discard header
          while ($line=fgetcsv($csvf,4092,',','"')) {
            if (
              $line[10]                                                  // camera position defined
              && $line[3]==$_SESSION['uname']                            // taken by submitter
              && $line[12]>4                                             // camera position at least six figures
              && ($line[14]||$line[7]!=$line[10]||$line[8]!=$line[11])   // view direction given, or camera and subject different
              && $line[13]==$trip['date']
            ) $geograph[]=$line;
          }
          fclose($csvf);
          if (count($geograph)>=3)   // we need three different images for the thumbnails at the top
            sqlite_query($db,"update geotrips set search=$search where id={$trip['id']}");
          else {
?>
            <div class="panel maxi">
              <h3 class="hlt">Search update failed</h3>
              <p>
It seems the new search contains no suitable images - we'll revert to the old one.  Images need to:-
              </p>
              <ul>
                <li>be taken by yourself</li>
                <li>be taken during the same trip on the same day as previously submitted</li>
                <li>have a six-figure or better camera position</li>
                <li>have either a view direction, or subject and camera position must be different</li>
              </ul>
              <p>
and there is a limit of 250 images per trip.
              </p>
              <p>
If you've made changes to any other fields, these will have been updated.
              </p>
            </div>
<?php
          }
        }
        if (file_exists($_FILES['gpxfile']['tmp_name'])) {
          $gpxf=fopen($_FILES['gpxfile']['tmp_name'],'r');
          $xml_data=fread($gpxf,999999);
          fclose($gpxf);
          $xml_parser=xml_parser_create();
          xml_set_element_handler($xml_parser,'xml_startTag',null);
          xml_parse($xml_parser,$xml_data);
          xml_parser_free($xml_parser);
          $trk='';
          foreach ($trkpt as $point) {
            if (isset($point['LAT'])) {
              $bng=wgs2bng($point['LAT'],$point['LON']);
              $ee[]=$bng[0];
              $nn[]=$bng[1];
              $trk=$trk.$bng[0].' '.$bng[1].' ';
            }
          }
          $bbox=min($ee).' '.min($nn).' '.max($ee).' '.max($nn);
          sqlite_query($db,"update geotrips set track='$trk' where id={$trip['id']}");
          sqlite_query($db,"update geotrips set bbox='$bbox' where id={$trip['id']}");
        }
        if ($_POST['contfrom']=="") $_POST['contfrom']=0;
        if ($_POST['contfrom']!=$trip['contfrom']) {
          $contfrom=explode('=',$_POST['contfrom']);
          $contfrom=$contfrom[sizeof($contfrom)-1];
          sqlite_query($db,"update geotrips set contfrom=$contfrom where id={$trip['id']}");
        }
?>
        <div class="panel maxi">
          <p>
Thanks for updating your trip.
          </p>
          <p>
If all has gone well, the changes should be visible on the
<a href="geotrip_show.php?osos&trip=<?php print($_GET['trip']); ?>">trip page</a> now.  Please
<a href="http://www.geograph.org.uk/usermsg.php?to=2520">let me know</a> if anything doesn't
work as expected.
          </p>
        </div>
<?php
      }
    } else {  // someone else's trip
?>
      <div class="panel maxi">
        <p>
You can only edit your own trips.  Choose one from the list below:
        </p>
<?php
        for ($i=0;$i<count($trip);$i++) if ($trip[$i]['uid']==$USER->user_id) {
          if ($trip[$i]['title']) $title=$trip[$i]['title'];
          else $title=$trip[$i]['location'].' from '.$trip[$i]['start'];
          $descr=preg_replace('/\n/','</p><p>',$trip[$i]['descr']);
          if (strlen($descr)>500) $descr=substr($descr,0,500).'...';
          // fetch Geograph thumbnail
		  $csvf=fopen(fetch_url("http://www.geograph.org.uk/export.csv.php?key=7u3131n73r&i={$trip[$i]['search']}&count=250&en=1&thumb=1&desc=1&dir=1&ppos=1"),'r');
          fgets($csvf);  // discard header
          $line=fgetcsv($csvf,4092,',','"');   // take the thumb of the first pic in case the requested one is beyond the 250 pic search limit...
          $thumb=$line[6];
          while ($line=fgetcsv($csvf,4092,',','"')) if ($line[0]==$trip[$i]['img']) $thumb=$line[6];  // ...then replace it if we can
          fclose($csvf);
          $thumb=str_replace("_120x120.jpg","_213x160.jpg",$thumb);
          $cred="<span style=\"font-size:0.6em\">Image &copy; <a href=\"http://www.geograph.org.uk/profile/{$trip[$i]['uid']}\">{$trip[$i]['user']}</a> and available under a<br /><a href=\"http://creativecommons.org/licenses/by-sa/2.0/\">Creative Commons licence</a><img alt=\"external link\" title=\"\" src=\"http://users.aber.ac.uk/ruw/templates/external.png\" /> via<br /><a href=\"http://www.geograph.org.uk\">Geograph Britain&amp;Ireland</a><img alt=\"external link\" title=\"\" src=\"http://users.aber.ac.uk/ruw/templates/external.png\" /></span>";
          print('<div class="inner">');
          print("<div class=\"inner flt_r\" style=\"max-width:213px\"><img src=\"$thumb\" alt=\"\" title=\"$title\" /><br />$cred</div>");
          print("<b>$title</b><br />");
          print("<em>{$trip[$i]['location']}</em> -- A ".whichtype($trip[$i]['type'])." from {$trip[$i]['start']}<br />");
          print("by <a href=\"http://www.geograph.org.uk/profile/{$trip[$i]['uid']}\">{$trip[$i]['user']}</a>");
          print("<p>$descr&nbsp;[<a href=\"geotrip_edit.php?osos&trip={$trip[$i]['id']}\">edit</a>]</p>");
          print('<div class="row"></div>');
          print('</div>');
        }
?>
      </div>
<?php
    }
    
    
$smarty->display('_std_end.tpl');
exit;

