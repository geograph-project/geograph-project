<?

require_once('geograph/global.inc.php');

$smarty = new GeographPage;



######################

if (empty($_GET['id'])) {
	header("HTTP/1.0 404 Not Found");
        $smarty->display('static_404.tpl');
        exit;
}
$id = intval($_GET['id']);
$_GET['full'] = 1; //always load the full version for now!

$image = new Gridimage($id, true); //only gets mod images, and includs the 'tags' value!

if (empty($image) || !$image->isValid() || $image->moderation_status=='rejected') {
	header("HTTP/1.0 404 Not Found");
        $smarty->display('static_404.tpl');
        exit;
}

if (stripos($image->tags,"panorama") === FALSE) {
	header("HTTP/1.0 404 Not Found");

	$smarty->display("_std_begin.tpl",md5($_SERVER['PHP_SELF']));

	print "<p>this image does not appear to be marked as panorama. If your own image, see this <a href=\"/article/Panoramas-and-Photospheres-on-Geograph\">Article</a>, with more details how to enable this viewer";

	$smarty->display("_std_end.tpl",'test');

	exit;
}

######################

$smarty->assign('responsive', true);

$smarty->display("_std_begin.tpl",md5($_SERVER['PHP_SELF']));

######################

if ($id == 5087080) {
        //this is a special case, the 360 image was rejected, and a single frame was uploaded as a new image.
        /// so when viewing the single image, use the larger upload fro mthe rejected image! (confused yet?)
        $id = 5085553;
}

$db = $image->_getDB(true);

$row2 = $db->getRow("SELECT * FROM gridimage_size WHERE gridimage_id = $id");
$great = max($row2['width'],$row2['height'],$row2['original_width'],$row2['original_height']);
if (!empty($_GET['full']) && $great > 640)
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
else
	$path = $image->_getFullpath();

######################

//do this in PHP as easier to make dynamic!
$json = array(
        "type"=> "equirectangular", //use this type, even for cylindrical, which just set vaov!
        "panorama"=> $CONF['STATIC_HOST'].$path,
        "autoLoad"=> true,
        "autoRotate"=> 3,
);

################
//set defaults feild of view

$type='photosphere'; //default for pannellum too!

if (strpos($image->tags,"sphere") === FALSE  && strpos($row['comment'],"photosynth") === FALSE
        && (strpos($image->tags,"360") !== FALSE || strpos($row['title'],"360") !== FALSE || strpos($row['comment'],"360") !== FALSE)) {
        //default, maybe make this dynamic? maybe a [vfov:50] tag?
        $json["vaov"] = 50;
	$type='360';
}

if ($id == 2271694 || stripos($image->tags,"wideangle") !== FALSE || stripos($image->tags,"panoramic") !== FALSE) {
        $json["vaov"] = 40;
        $json["haov"] = 120;
	$type='wideangle';
}

if (!isset($json["vaov"]) && !empty($_GET['n'])) {
//5359187
        $json["vaov"] = 8; //set VERY small, so its flat, and not very distorted!
        $json["haov"] = 12; //max view of image
}

################
//allow override

if (preg_match('/vfov:(\d+\.?\d*)/',$image->tags,$m))
	$json["vaov"] = floatval($m[1]);
if (preg_match('/hfov:(\d+\.?\d*)/',$image->tags,$m))
	$json["haov"] = floatval($m[1]);

if (!empty($_GET['v']))
        $json["vaov"] = floatval($_GET['v']);
if (!empty($_GET['h']))
        $json["haov"] = floatval($_GET['h']);

if (!empty($_GET['v']) || !empty($_GET['h'])) {
	print "TAGS: <tt>panorama:$type;vfov:{$json['vaov']}";
	if (!empty($json['haov']))
		print ";hfov:{$json['haov']}";
	print "</tt><hr>";
}

################

if (isset($json["haov"]) && $json["haov"]< 60) {
        $json["hfov"] = $json["haov"]+ 3; //the starting (setting this means we start 'zoomed' in!
         $json["minHfov"] = 3;
}

if (isset($json["haov"]))
        unset($json["autoRotate"]);

################

if (preg_match('/panodirection:(\d+\.?\d*)/',$image->tags,$m))
	$json["northOffset"] = floatval($m[1]);
elseif (!empty($image->view_direction) && $image->view_direction > -1)
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

    </style>

<h2><a href="/photo/<? echo $image->gridimage_id; ?>"><? print htmlentities($image->title); ?></a> by <? print htmlentities($image->realname); ?></h2>

<? if (empty($json["haov"])) { ?>
        <p><i>The image appears to be a full 360 degree panorama, <span>so can fully rotate the view in the viewer below (drag with mouse).</span>
        <? if (empty($json["vaov"])) { ?>
                <span>And in fact appears to be a full photosphere, so can look up and down too!</span>
        <? } ?>
        </i></p>

<? } ?>

<div id="panorama"></div>
<p class=compassnote><i>Note: The compass is only accurate if the photographer set the view direction of the center of the panorama</i></p>
<script>
pannellum.viewer('panorama', <? print json_encode($json); ?>);
</script>

<p>&copy; Copyright <a href="https://www.geograph.org.uk<? echo $image->profile_link; ?>"><? print htmlentities($image->realname); ?></a> and licensed for reuse under this <a href="http://creativecommons.org/licenses/by-sa/2.0/" rel=licence>Creative Commons Licence</a>.</p>

<p>Source: <a href="https://www.geograph.org.uk/photo/<? echo $image->gridimage_id; ?>">geograph.org.uk/photo/<? echo $image->gridimage_id; ?></a>
or download on the <a href="https://www.geograph.org.uk/more.php?id=<? echo $image->gridimage_id; ?>">more sizes</a> page</p>

<?

$smarty->display("_std_end.tpl",'test');
