<?php /**
 * $Project: GeoGraph $
 * $Id: ecard.php 3886 2007-11-02 20:14:19Z barry $
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

require_once('geograph/global.inc.php');

$attribs = array('font','style','weight','gravity','pointsize');
#'stretch' - doenst seem to work on scone/toast
if (empty($_GET['gravity']))
	$_GET['gravity'] = 'South';


if (!empty($_GET['id']) && ctype_digit($_GET['id']) && strpos($_SERVER['HTTP_HOST'],'t0.') === 0) {
	customExpiresHeader(3600*24*180,true,true);

		$image=new GridImage();
		$ok = $image->loadFromId(intval($_GET['id']));

		if (!$ok || $image->moderation_status=='rejected') {
			header("HTTP/1.0 410 Gone");
			header("Status: 410 Gone");
			die("Sorry, unable to load image! <a href=\"/\" target=\"_top\">Open Geograph Homepage</a>");
		} else {
			//bit late doing it now, but at least if smarty doesnt have it cached we might be able to prevent generating the whole page
			customCacheControl(strtotime($image->upd_timestamp),$cacheid);


			if ($image->reference_index == 2)
				$_GET['ie'] = true;
		}
} else {
	init_session();

	$smarty = new GeographPage;

	dieUnderHighLoad(1);
	customGZipHandlerStart();

	$smarty->display("_std_begin.tpl",$_SERVER['PHP_SELF']);

	if (!empty($_GET['id']) && ctype_digit($_GET['id'])) {

                $image=new GridImage();
                $ok = $image->loadFromId(intval($_GET['id']));

                if (!$ok || $image->moderation_status=='rejected') {
			$image = null;
		} else {
			$image->_getFullSize(); //just because it sets original_width!
		}
	}

	?>
		<h2>Get CC-Stamped image</h2>
		<p>This tool produces a .jpg file for any Geograph image, which includes the Create Commons reference and attribution required - to make it easy to comply with the CC reuse requirements. You can download and use the resultant image in your project, knowing that suitable attribution is preserved.</p>

		<form action="<? echo $CONF['TILE_HOST']; ?>/stamp.php" method="get" target="targetbox" onsubmit="document.getElementById('show').style.display='';">
		<noscript>
			<form action="<? echo $CONF['TILE_HOST']; ?>/stamp.php" method="get">
		</noscript>

			Image ID: <input type=text name=id value="<? echo @htmlentities($_GET['id']); ?>"/> &nbsp;
			<? if (!empty($image) && $image->original_width>640) {
				print "Size: <select name=large>";
				print "<option value=0>Normal size</option>";
				foreach (array(800,1024,1600) as $size) {
					if ($image->original_width>$size || $image->original_height>$size) {
						printf('<option value="%s"%s>%s</option>',$size,($_GET['large'] == $size)?' selected':'',"$size Nominal");
					}
				}
				printf('<option value="%s"%s>%s</option>',1,($_GET['large'] == '1')?' selected':'',"{$image->original_width} x {$image->original_height} px");
				print "</select> &nbsp; ";
			} ?>
			Options:
			<input type=checkbox name=title id=title/><label for=title>Include image title</label>,
			<input type=checkbox name=link id=link value=0 /><label for=link>Hide geograph link</label>,
			<input type=checkbox name=ie id=ie value=1 <? if ($_SERVER['HTTP_HOST'] == 'www.geograph.ie') { ?> checked<? } ?>/><label for=ie>.ie link</label>,
			<input type=checkbox name=invert id=invert value=1 /><label for=invert>invert text color</label><br/>
			<hr/>
	<?
		foreach ($attribs as $list) {
			if ($list == 'pointsize') {
				$lines = range(8,36);
			} elseif ($list == 'weight') {//list weight doesnt work?
				$lines = array('All','Bold','Bolder','Lighter','Normal');
			} else {
				$lines = explode("\n",trim(`convert -list $list`));
				if ($list == 'gravity') {
					$lines[] = 'left';
					$lines[] = 'right';
				}
			}
			if (count($lines) < 3) {
				continue;
			}
			print "$list:<select name=$list>";
			print "<option></option>";
			foreach ($lines as $line) {
				if ($list == 'font') {
					if (!preg_match('/Font: ([\w-]+)/',$line,$m) || preg_match('/(Bold|Italic|Oblique)+$/',$m[1])) {
						continue;
					}
					$line = $m[1];
				} elseif ($line == 'Any' || $line == 'All' || $line == 'Normal' || $line == 'None' || $line == 'Forget')
					continue;
				printf('<option value="%s"%s>%s</option>',$line,(!empty($_GET[$list]) && $_GET[$list] == $line)?' selected':'',$line);
			}
			print "</select> &nbsp; ";
		}
	?>
		<hr/>
		<input type="submit" value="Get Stamped Image &gt;&gt;"/>
		</form>
		<div <? if (empty($_GET['id']) || empty($_GET['title'])) { ?>style="display:none"<? } ?> id="show">
		Right click the image and select "Save image as" (exact wording varies by browser)<br/><br/>
		<iframe src="<? echo (empty($_GET['id']) || empty($_GET['title']))?"about:blank":($CONF['TILE_HOST']."/stamp.php?".htmlentities($_SERVER['QUERY_STRING'])); ?>" width=650 height=650 name="targetbox" frameborder=0></iframe>
		</div>
	<?
	$smarty->display("_std_end.tpl",'test');

	exit;
}


if (!empty($_GET['large'])) {
	switch($_GET['large']) {
                case 640:
                case 800:
                case 1024:
                case 1600:
                        $file = ".".$image->getImageFromOriginal(intval($_GET['large']),intval($_GET['large']));
                        break;
		default:
			$file = ".".$image->_getOriginalpath();
			break;
	}
} else {
	$file = ".".$image->_getFullpath();
}
$id = intval($image->gridimage_id);
$title = "by {$image->realname}";

if (!empty($_GET['title'])) {
	if (!function_exists('smarty_modifier_truncate')) {
		require_once("smarty/libs/plugins/modifier.truncate.php");
	}

	$title = smarty_modifier_truncate($image->title,30,"...")." $title";
}
if (!(isset($_GET['link']) && empty($_GET['link'])))
	$title .= " - geograph.".(empty($_GET['ie'])?'org.uk':'ie')."/p/$id";

$options = '';

foreach ($attribs as $attrib) {
	if (!empty($_GET[$attrib]) && preg_match('/^[\w-]+$/',$_GET[$attrib])) {
		$options .= " -$attrib {$_GET[$attrib]}";
	}
}

$annotate = escapeshellarg("cc-by-sa/2.0 - $title");
if (!empty($_GET['invert'])) {
	$options .= " -stroke white -strokewidth 2 -annotate 0 $annotate -stroke none -fill '#000C' -annotate 0 $annotate";
} else {
	$options .= " -stroke '#000C' -strokewidth 2 -annotate 0 $annotate -stroke none -fill white -annotate 0 $annotate";
}

//bodge!
// see http://www.imagemagick.org/Usage/annotating/#gravity_left
if (strpos($options,'-gravity left') !== FALSE) {
	$options = " -rotate -90 ".str_replace('-gravity left','-gravity south',$options)." -rotate 90";
} elseif (strpos($options,'-gravity right') !== FALSE) {
	$options = " -rotate 90 ".str_replace('-gravity right','-gravity south',$options)." -rotate -90";
}

if (($ar = getimagesize($file)) !== FALSE && isset($ar['channels']) && $ar['channels'] == 1) {
     $options .= ' -colorspace Gray'; // hack avoids problem in ImageMagick 6.7.7 with grayscale
}

$command = "convert $file $options jpg:-";

if (!empty($_GET['cmd'])) {
	print $command;
	exit;
}

                        $filename = "geograph {$id} by {$image->realname}.jpg";
                        $filename = preg_replace('/ /','-',trim($filename));
                        $filename = preg_replace('/[^\w\.-]+/','',$filename);

		if (!empty($_GET['download'])) {
			header("Content-Disposition: attachment; filename=\"$filename\"");
		} else {
                        header("Content-Disposition: inline; filename=\"$filename\"");
		}

header("Content-Type: image/jpeg");
passthru($command);

