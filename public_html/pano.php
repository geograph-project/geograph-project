<?

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;



######################

if (empty($_GET['id'])) {
	header("HTTP/1.0 404 Not Found");
        $smarty->display('static_404.tpl');
        exit;
}
$id = intval($_GET['id']);
$_GET['full'] = 1; //always load the full version for now!

if ($id == 7793712 && empty($_GET['size']))
	$_GET['size'] = 12000; //temporally bodge. Need to autodetect when needed!

$image = new Gridimage($id,false); // use gridimage to load the image - so will grab pending images too (we loading ->tags directly anyway

if (empty($image) || !$image->isValid() || $image->moderation_status=='rejected') {
	header("HTTP/1.0 404 Not Found");
        $smarty->display('static_404.tpl');
        exit;
}

$db = $image->_getDB(true);

######################

$check = $db->getOne("SELECT status FROM gridimage_pending WHERE type = 'original' AND gridimage_id = {$image->gridimage_id} AND type IN ('new','open')");
if ($check) {
	//block display of images with a pending larger moderation.
	// ... this is mainly so the larger image is NOT loaded via CDN(s), which risks caching the old image!

	header("HTTP/1.0 503 Unavailable");

	$smarty->display("_std_begin.tpl",md5($_SERVER['PHP_SELF']));

	print "<p>Sorry, this image is not currently viewable. The larger upload needs moderation. Please try again in a few hours. <a href=\"javascript:history.go(-1);\">Go Back</a>";

	$smarty->display("_std_end.tpl");
	exit;
}

//check tag_public in case freshly added! (plus might not be from gridiamge_search anyway)
$image->loadTags();

######################

//todo, should perhaps be checking panorama: prefix, currenty we do TRY to allow non formal taged images to display!

if (stripos($image->tags,"panorama") === FALSE) {

	header("HTTP/1.0 404 Not Found");

	$smarty->display("_std_begin.tpl",md5($_SERVER['PHP_SELF']));

	print "<p>this image does not appear to be marked as panorama. If your own image, see this <a href=\"/article/Panoramas-and-Photospheres-on-Geograph\">Article</a>, with more details how to enable this viewer";

	$smarty->display("_std_end.tpl");

	exit;
}

######################

$smarty->assign('responsive', 1);

$smarty->display("_std_begin.tpl",md5($_SERVER['PHP_SELF']).'1');

######################

if ($id == 5087080) {
        //this is a special case, the 360 image was rejected, and a single frame was uploaded as a new image.
        /// so when viewing the single image, use the larger upload fro mthe rejected image! (confused yet?)
        $image->gridimage_id = $id = 5085553;
}

$row2 = $db->getRow("SELECT * FROM gridimage_size WHERE gridimage_id = $id");
$great = max($row2['width'],$row2['height'],$row2['original_width'],$row2['original_height']);
if ($row2['original_height'] > 0)
	$ratio = $row2['original_width']/$row2['original_height'];
else
	$ratio = 0;
$ratio2 = $row2['width']/$row2['height'];

######################

if (stripos($image->tags,"360") || stripos($image->tags,"photosphere")) {
	//try to catch when try viewing a 360 panorama, that hasnt beem uploaded yet.
	//... ie the larger upload is still the normal view, not the full resoution version
	//... dont want to view the current larger image, as risks it being cached on CDN, so wont be visible when the real image is moderated

	if ($ratio2 == $ratio && $image->gridimage_id > 7000000) {
		print "<p>This image does not appear to have the larger uploaded added yet. If you are the contributor, it will need uploading via the <a href=\"editimage.php?id={$image->gridimage_id}\">edit page</a> and moderation";

		print "<p>Contact us, if you have uploaded the panorama already, and still see this!";

	        $smarty->display("_std_end.tpl");
        	exit;
	}
}

######################

if (!empty($_GET['size']) && $_GET['size'] == 12000 && $great >= 12000) { //create 'thumbnail', even if image is already 12000, as a way to 'bust' the cache
	$path = $image->getImageFromOriginal(12000,12000);
	$row2['original_width'] = 12000;
	$row2['original_height'] = 12000 /$ratio;
} elseif (!empty($_GET['size']) && $_GET['size'] == 8192 && $great > 8192) {
	$path = $image->getImageFromOriginal(8192,8192);
	$row2['original_width'] = 8192;
	$row2['original_height'] = 8192 /$ratio;
} elseif (!empty($_GET['size']) && $_GET['size'] == 4096 && $great > 4096) {
	$path = $image->getImageFromOriginal(4096,4096);
	$row2['original_width'] = 4096;
	$row2['original_height'] = 4096 /$ratio;
} elseif (!empty($_GET['full']) && $great > 640)
	$path = $image->_getOriginalpath(true, false);
elseif ($great > 1600)
	$path = $image->getImageFromOriginal(1600,1600);
elseif ($great > 1024)
	$path = $image->getImageFromOriginal(1024,1024);
elseif ($great > 800)
	$path = $image->getImageFromOriginal(800,800);
elseif ($great > 640 && $great < 1024 && $row2['original_diff'] == 'yes')
	$path = $image->getImageFromOriginal(640,640);
elseif ($great > 640)
	$path = $image->_getOriginalpath(true, false);
else {
	$path = $image->_getFullpath();
	$ratio = $row2['width']/$row2['height'];
}

######################

//do this in PHP as easier to make dynamic!
$json = array(
        "type"=> "equirectangular", //use this type, even for cylindrical, which just set vaov!
        "panorama"=> $CONF['STATIC_HOST'].$path,
        "autoLoad"=> true,
        "autoRotate"=> 3,
	"backgroundColor"=>[250,250,250]
);

################
//set defaults feild of view

$type='photosphere'; //default for pannellum too!

if (strpos($image->tags,"sphere") === FALSE  && strpos($image->comment,"photosynth") === FALSE
        && (strpos($image->tags,"360") !== FALSE || strpos($image->title,"360") !== FALSE || strpos($image->comment,"360") !== FALSE)) {
        //todo, maybe make this dynamic? maybe a [vfov:50] tag? or based on $ratio
	$type='360';
}

if ($id == 2271694 || stripos($image->tags,"wideangle") !== FALSE || stripos($image->tags,"panoramic") !== FALSE) {
        $json["haov"] = 120;
	$type='wideangle';
}

if (!isset($json["vaov"]) && !empty($_GET['n'])) {
//5359187
        $json["haov"] = 12; //set VERY small, so its flat, and not very distorted!
}

################
//allow override

//set horizontal first
if (!empty($_GET['h']))
        $json["haov"] = floatval($_GET['h']);
elseif (preg_match('/(\d+\.?\d*)( degree|)panorama/i',$image->tags,$m))
	$json["haov"] = floatval($m[1]);
elseif (preg_match('/hfov:(\d+\.?\d*)/',$image->tags,$m))
	$json["haov"] = floatval($m[1]);

//then set vertical, as default to based on horizontal - works even for photospheres!
if (!empty($_GET['v']))
        $json["vaov"] = floatval($_GET['v']);
elseif (preg_match('/vfov:(\d+\.?\d*)/',$image->tags,$m))
	$json["vaov"] = floatval($m[1]);
elseif (!empty($json["haov"])) //use same aspect ratio as image
	$json["vaov"] = $json["haov"] / $ratio;
else
	$json["vaov"] = 360 / $ratio;


//provide the tags for easy copy/paste
if (!empty($_GET['v']) || !empty($_GET['h']) || !empty($_GET['d'])) {
	print "Copy these Tags: <tt>panorama:$type";
	if (!empty($json['haov']))
		print ";vfov:{$json['vaov']}";
	if (!empty($json['haov']))
		print ";hfov:{$json['haov']}";
	print "</tt><hr>";
}

################

if ($great > 2000)
	$json["minHfov"] = 3;

//base the starting view on the vertical height! (so that the pano is shown full height - with lots of sideway scrolling)
if (!empty($_GET['s'])) {
        $json["hfov"] = min($json["vaov"]?$json["vaov"]:180,100) * 1000 / 700;
        $json["minHfov"] = $json["hfov"]/4; //todo, should base this on the resolution!

//otherwise set it based on the width (so whole image should be visible)
} elseif (isset($json["haov"]) && $json["haov"] <= 60)
        $json["hfov"] = $json["haov"]+ 3; //the starting (setting this means we start 'zoomed' in!
// else "hfov" defaults to 100 degrees wide.

if (isset($json["haov"]))
        unset($json["autoRotate"]); //only autorotate full spheres

################

if (preg_match('/panodirection:(\d+\.?\d*)/',$image->tags,$m)) {
	$json["northOffset"] = floatval($m[1]);
	$json["compass"] = true;
} elseif (!empty($image->view_direction) && $image->view_direction > -1)
	$json["northOffset"] = floatval($image->view_direction);

?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css"/>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>
    <style>
	#panorama {
		width: 1000px;   max-width:90vw;
		height: 700px;  max-height:90vh;
	}
	#maincontent p {
		width: 1000px;   max-width:90vw;
		text-align:center;
		color:gray;
	}
	.pnlm-info-box {
		margin:inherit;
		width:inherit;
		left:inherit;
		table-layout:unset;
	}

    </style>

<h2><a href="/photo/<? echo $image->gridimage_id; ?>"><? print htmlentities($image->title); ?></a> by <? print htmlentities($image->realname); ?></h2>

<? if (!empty($_GET['debug'])) { ?>
	<script>
	var gl = document.createElement('canvas').getContext('experimental-webgl');
	var maxWidth = gl.getParameter(gl.MAX_TEXTURE_SIZE);

	document.write('this device\'s maximum supported size is ' + (maxWidth * 2) + 'px.<br>');
	</script>
	<div style=" overflow-wrap: break-word; ">
	<?
	print "Aspect Ratio: $ratio<br>";
	print "Image: <b>{$row2['original_width']}</b> x {$row2['original_height']}<br>";
	foreach ($json as $key => $value) {
		if (!is_array($value))
			print "$key: <b>".htmlentities($value)."</b><br>";
	}
	print "<hr>";
	print "</div>";
}

if ($row2['original_width'] > 8192) {
	print "<p>This image is very high resolution, and may have issues on some devices.<br> If the image doesnt display below, ";
	print "then try midscale <a href=\"?id={$image->gridimage_id}&amp;size=8192\">8192 pixels</a> version.</a>";
} elseif ($row2['original_width'] > 4096) {
	if ($_GET['size'] && $_GET['size'] > 4096) {
		print "<p>If the image still doesn't display below,";
	} else {
		print "<p>This image is very high resolution, and may have issues on some devices. If the image doesnt display below, ";
	}
	print " then try <a href=\"?id={$image->gridimage_id}&amp;size=4096\">4096 pixels</a>.<br>";
}
if ($great > 4096 && !empty($_GET['size'])) {
	print "Viewing reduced resolution version. Can still download the full resolution version from <a href=\"more.php?id={$image->gridimage_id}\">More Sizes</a> page";
}

if (empty($json["haov"]) || $json["vaov"] == 360) { ?>
        <p><i>The image appears to be a full 360 degree panorama, <span>so can fully rotate the view in the viewer below (drag with mouse).</span>
        <? if (empty($json["vaov"]) || $json["vaov"] == 180) { ?>
                <span>And in fact appears to be a full photosphere, so can look up and down too!</span>
        <? } ?>
        </i></p>
<? } elseif ($json["haov"] > 60) { ?>
	<p>Drag the image below to rotate the view. <? if ($greatest > 640) { print "Use the +/- buttons, or mousewheel to zoom in/out."; } ?>
<? } ?>

<div id="panorama"></div>
<script>
pannellum.viewer('panorama', <? print json_encode($json); ?>);
</script>

<? if (!empty($json["compass"]) || $type=='photosphere') { ?>
	<p class=compassnote><i>Note: The compass is only accurate if the photographer set the view direction of the center of the panorama</i></p>
<? } ?>

<p>&copy; Copyright <a href="https://www.geograph.org.uk<? echo $image->profile_link; ?>"><? print htmlentities($image->realname); ?></a> and licensed for reuse under this <a href="http://creativecommons.org/licenses/by-sa/2.0/" rel=licence>Creative Commons Licence</a>.</p>

<p>Source: <a href="https://www.geograph.org.uk/photo/<? echo $image->gridimage_id; ?>">geograph.org.uk/photo/<? echo $image->gridimage_id; ?></a>
or download on the <a href="https://www.geograph.org.uk/more.php?id=<? echo $image->gridimage_id; ?>">more sizes</a> page</p>

<?

$smarty->display("_std_end.tpl",'test');
