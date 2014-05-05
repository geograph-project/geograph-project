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
 

if ($_SERVER['SERVER_ADDR']=='127.0.0.1') {
	require_once('./geograph_snub.inc.php');
} else {
	require_once('geograph/global.inc.php');
}
init_session();

$smarty = new GeographPage;

//you must be logged in to request changes
$USER->mustHavePerm("basic");

include('./geotrip_func.php');


$db = GeographDatabaseConnection(false);
// can now use mysql_query($sql); directly, or mysql_query($sql,$db->_connectionID);


  // get track from database
  if (!empty($_GET['trip'])) $trip=$db->getRow("select * from geotrips where id=".intval($_GET['trip']));
  else $trips=$db->getAll("select * from geotrips where uid={$USER->user_id}"); 


$smarty->assign('page_title', 'Geo-Trip editor :: Geo-Trips');


$smarty->display('_std_begin.tpl','trip_edit');
print '<link rel="stylesheet" type="text/css" href="/geotrips/geotrips.css" />';

print "<h2><a href=\"./\">Geo-Trips</a> :: Edit Trip</h2>";


    if (!empty($trip['uid']) && ($trip['uid']==$USER->user_id || $USER->hasPerm("moderator"))) {  // editing your own trip?
    
      if (!isset($_POST['submit2'])) {  // input form
?>
        <div class="panel maxi">
          <p>
Each input field shows the values currently stored.  Edit any that need updating and submit.
There is no undo facility, but you can always edit again if you change your mind.
          </p>
          <p>
If you make changes to your images on Geograph (such as adding a camera position or description, or correcting
coordinates etc.), those changes will take up to a week before they make it through to Geo-Trips.
          </p>
          <form name="trip" method="post" enctype="multipart/form-data">
            <hr style="color:#992233">
            <p>
              <b>Location</b> <span class="hlt">(required)</span><br />
              <input type="text" name="loc" size="72" value="<?php print(htmlentities($trip['location'])); ?>" /><br />
            </p>
            <hr style="color:#992233">
            <p>
              <b>Starting point</b> <span class="hlt">(required)</span><br />
              <input type="text" name="start" size="72" value="<?php print(htmlentities($trip['start'])); ?>" /><br />
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
<a target="_blank" href="/search.php?i=<?php print($trip['search']); ?>&displayclass=more">This search</a>
(opens in a new window) is currently in use.  To refine it, use the <em>mark</em> buttons on the search page.  When finished,
click <em>view as search results</em> at the bottom of the search page.  Then copy the URL of the resulting custom search in the
box above.
            </p>
            <hr style="color:#992233">
            <p>
              <b>Title</b> (optional)<br />
              <input type="text" name="title" size="72" value="<?php print(htmlentities($trip['title'])); ?>" /><br />
            </p>
            <hr style="color:#992233">
            <p>
              <b>Upload a new GPS track</b> in GPX format (optional)<br />
              <input type="file" name="gpxfile" size="40">
            </p>
            <hr style="color:#992233">
<?php
            require_once('geograph/gridimage.class.php');
            $image = new GridImage($trip['img']);
            if (!$image->isValid()) {
              //FIXME
            }
?>
            <div class="inner flt_r">
              <img alt="" title="Currently selected featured image." src="<?php print($image->getThumbnail(213,160,true)); ?>" />
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
              <textarea rows="8" cols="80" name="descr"><?php print(htmlentities(str_replace("\r",'',str_replace('</p><p>',"\n",$trip['descr'])))); ?></textarea>
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
        if ($_POST['type']!=$trip['type'])
          $db->Execute("update geotrips set type='".mysql_real_escape_string($_POST['type'])."' where id={$trip['id']}");
        if ($_POST['loc']&&$_POST['loc']!=$trip['location'])
          $db->Execute("update geotrips set location='".mysql_real_escape_string($_POST['loc'])."' where id={$trip['id']}");
        if ($_POST['start']&&$_POST['start']!=$trip['start'])
          $db->Execute("update geotrips set start='".mysql_real_escape_string($_POST['start'])."' where id={$trip['id']}");
        if ($_POST['title']!=$trip['title'])
          $db->Execute("update geotrips set title='".mysql_real_escape_string($_POST['title'])."' where id={$trip['id']}");
        if ($_POST['img']&&$_POST['img']!=$trip['img']) {
          $img=explode('/',$_POST['img']);
          $img=intval($img[sizeof($img)-1]);
          $db->Execute("update geotrips set img=$img where id={$trip['id']}");
        }
        if ($_POST['descr']!=$trip['descr'])
          $db->Execute("update geotrips set descr='".mysql_real_escape_string(strip_tags($_POST['descr']))."' where id={$trip['id']}");
        $db->Execute("update geotrips set updated='".date('U')."' where id={$trip['id']}");
        if ($_POST['search']&&$_POST['search']!=$trip['search']) {
          $search=explode('=',$_POST['search']);
          $search=intval($search[sizeof($search)-1]);
		require_once('geograph/searchcriteria.class.php');
		require_once('geograph/searchengine.class.php');
		$geograph = array();
		$engine = new SearchEngine($search);
		$engine->criteria->resultsperpage = 250; // FIXME really?
		$recordSet = $engine->ReturnRecordset(0, true);
		while (!$recordSet->EOF) {
			$image = $recordSet->fields;
			if (    $image['nateastings']
			    &&  $image['viewpoint_eastings']
			    #&&  $image['realname'] == $USER->realname
			    &&  $image['user_id'] == $USER->user_id
			    &&  $image['viewpoint_grlen'] > 4
			    &&  $image['natgrlen'] > 4
			    && (   $image['view_direction'] != -1 
			        || $image['viewpoint_eastings'] != $image['nateastings']
			        || $image['viewpoint_northings'] != $image['natnorthings'])
			    &&  $image['imagetaken'] === $trip['date'] //FIXME allow update of date but require all dates to be identical?
			) {
				$geograph[] = $image;
			}
			$recordSet->MoveNext();
		}
		$recordSet->Close();
		//FIXME update bounding box if no track given
		//FIXME search can change. we need a way to update the bounding box accordingly, e.g. everytime this form is submitted
          if (count($geograph)>=3)   // we need three different images for the thumbnails at the top
            $db->Execute("update geotrips set search=$search where id={$trip['id']}");
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
          $ee = array();
          $nn = array();
          $trk='';
          $trkpt = array();
          $gpxf=fopen($_FILES['gpxfile']['tmp_name'],'r');
          $xml_data=fread($gpxf,filesize($_FILES['gpxfile']['tmp_name']));
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
          $db->Execute("update geotrips set track='$trk', bbox='$bbox' where id={$trip['id']}");

        //if there is no tracklog and we have some images, we should update the bbox.
        } elseif (empty($trip['track']) && !empty($geograph)) {
          $ee = array();
          $nn = array();
          $len=count($geograph);
          for ($i=0;$i<$len;$i++) {
            $ee[$i]=$geograph[$i]['viewpoint_eastings'];
            $nn[$i]=$geograph[$i]['viewpoint_northings'];
          }
          $ee=array_filter($ee);  // remove zero eastings/northings (camera position missing)
          $nn=array_filter($nn);
          $bbox=min($ee).' '.min($nn).' '.max($ee).' '.max($nn);

          $db->Execute("update geotrips set bbox='$bbox' where id={$trip['id']}");
        }
        if ($_POST['contfrom']=="") $_POST['contfrom']="0";
        if ($_POST['contfrom']!=$trip['contfrom'] && preg_match('/(\d+)\s*$/',$_POST['contfrom'],$m)) {
          $contfrom=intval($m[1]);
          $db->Execute("update geotrips set contfrom=$contfrom where id={$trip['id']}");
        }
?>
        <div class="panel maxi">
          <p>
Thanks for updating your trip.
          </p>
          <p>
If all has gone well, the changes should be visible on the
<a href="/geotrips/<?php print(intval($_GET['trip'])); ?>">trip page</a> now.  Please
<a href="/contact_us.php">let us know</a> if anything doesn't
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
        for ($i=0;$i<count($trips);$i++) if ($trips[$i]['uid']==$USER->user_id) { 
          if ($trips[$i]['title']) $title=htmlentities($trips[$i]['title']);
          else $title=htmlentities($trips[$i]['location'].' from '.$trips[$i]['start']);
          $descr=str_replace("\n",'</p><p>',htmlentities($trips[$i]['descr']));
          if (strlen($descr)>500) $descr=substr($descr,0,500).'...';
          require_once('geograph/gridimage.class.php');
          $image = new GridImage($trips[$i]['img']);
          if (!$image->isValid()) {
            //FIXME
          }
          $thumb=$image->getThumbnail(213,160,true);
          $cred="<span style=\"font-size:0.6em\">Image &copy; <a href=\"/profile/{$trips[$i]['uid']}\">".htmlentities($trips[$i]['user'])."</a> and available under a <a href=\"http://creativecommons.org/licenses/by-sa/2.0/\">Creative Commons licence</a><img alt=\"external link\" title=\"\" src=\"http://{$CONF['STATIC_HOST']}/img/external.png\" /></span>";
          print('<div class="inner">');
          print("<div class=\"inner flt_r\" style=\"max-width:213px\"><img src=\"$thumb\" alt=\"\" title=\"$title\" /><br />$cred</div>");
          print("<b>$title</b><br />");
          print("<em>".htmlentities($trips[$i]['location'])."</em> &ndash; A ".whichtype($trips[$i]['type'], false)." from ".htmlentities($trips[$i]['start'])."<br />");
          print("by <a href=\"/profile/{$trips[$i]['uid']}\">".htmlentities($trips[$i]['user'])."</a>");
          print("<p>$descr&nbsp;[<a href=\"geotrip_edit.php?trip={$trips[$i]['id']}\">edit</a>]</p>");
          print('<div class="row"></div>');
          print('</div>');
        }
?>
      </div>
<?php
    }
    
    
$smarty->display('_std_end.tpl');

