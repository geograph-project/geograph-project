<?

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

######################

$smarty->assign('responsive', true);
$smarty->display("_std_begin.tpl",md5($_SERVER['PHP_SELF']));

######################

?>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css"/>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>
    <style>
	.container {
		float:left; margin:10px;
	}

	.panorama {
		width: 400px;   max-width:90vw;
		height: 300px;  max-height:90vh;
		border-radius: 4px;
	}

.pnlm-load-button {
	opacity:0.5;
}
.pnlm-panorama-info {
	bottom:0;
}
.pnlm-title-box {
	opacity:0.8;
	font-size:1rem;
}
    </style>

<h2>Panorama Samples</h2>

<?

######################

$db = GeographDatabaseConnection(true);

$where = array();
$where[] = "original_width > 640";
$where[] = "prefix = 'panorama'";

if (!empty($_GET['tag']) && $_GET['tag'] == 360)
	$where[] = "tag IN ('360','photosphere')"; //photosphere are still 360!
elseif (!empty($_GET['tag']) && preg_match('/^\w+$/',$_GET['tag']))
	$where[] = "tag = ".$db->Quote($_GET['tag']);

if (!empty($_GET['user_id']))
	$where[] = "g.user_id = ".intval($_GET['user_id']);

$where = implode(' AND ',$where);

$rows = $db->getAll("
select distinct gridimage_id,width,height,original_width,original_height,original_diff, g.user_id,tags,title,realname,grid_reference,comment, imagetaken
 from gridimage_size s inner join gridimage_search g using (gridimage_id) inner join tag_public t using (gridimage_id)
 where $where
 order by t.created desc limit 20
");

foreach ($rows as $row) {
	$image = new GridImage();
	$image->fastInit($row);
	$image->compact();

	$great = max($row['width'],$row['height'],$row['original_width'],$row['original_height']);
	$ratio = $row['original_width']/$row['original_height'];
	/* if (!empty($_GET['full']) && $great > 640)
		$path = $image->_getOriginalpath(true, false);
	elseif ($great > 1600)
		$path = $image->getImageFromOriginal(1600,1600);
	elseif ($great > 1024)
		$path = $image->getImageFromOriginal(1024,1024);
	elseif ($great > 800)
		$path = $image->getImageFromOriginal(800,800);
	*/
	$preview = $image->_getFullpath();
	if ($great > 640) {
//		if ($great > 800) {
//			$path = $image->getImageFromOriginal(800,800);
//		} else
		         $path = $image->_getOriginalpath(true, false);

	} else {
		$path = $preview; //$image->_getFullpath();
		$ratio = $row['width']/$row['height'];
	}

	######################

	//do this in PHP as easier to make dynamic!
	$json = array(
	        "type"=> "equirectangular", //use this type, even for cylindrical, which just set vaov!
	        "panorama"=> $CONF['STATIC_HOST'].$path,
		"preview"=> $CONF['STATIC_HOST'].$preview,
	        "autoLoad"=> false,
	        "autoRotate"=> 3,
		"backgroundColor"=>[250,250,250]
	);

	################
	//set defaults feild of view

	$type='photosphere'; //default for pannellum too!

	if (strpos($image->tags,"sphere") === FALSE  && strpos($row['comment'],"photosynth") === FALSE
	        && (strpos($image->tags,"360") !== FALSE || strpos($row['title'],"360") !== FALSE || strpos($row['comment'],"360") !== FALSE)) {
	        //todo, maybe make this dynamic? maybe a [vfov:50] tag? or based on $ratio
		$type='360';
	}

	if ($image->gridimage_id == 2271694 || stripos($image->tags,"wideangle") !== FALSE || stripos($image->tags,"panoramic") !== FALSE) {
	        $json["haov"] = 120;
		$type='wideangle';
	}

	################
	//allow override

	//set horizontal first
	if (preg_match('/(\d+\.?\d*)( degree|)panorama/i',$image->tags,$m))
		$json["haov"] = floatval($m[1]);
	elseif (preg_match('/hfov:(\d+\.?\d*)/',$image->tags,$m))
		$json["haov"] = floatval($m[1]);

	//then set vertical, as default to based on horizontal - works even for photospheres!
	if (preg_match('/vfov:(\d+\.?\d*)/',$image->tags,$m))
		$json["vaov"] = floatval($m[1]);
	elseif (!empty($json["haov"])) //use same aspect ratio as image
		$json["vaov"] = $json["haov"] / $ratio;
	else
	        $json["vaov"] = 360 / $ratio;

	################

	if ($great > 2000)
		$json["minHfov"] = 3;

	//otherwise set it based on the width (so whole image should be visible)
	if (isset($json["haov"]) && $json["haov"] <= 60)
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

	$bits = array();
	$bits[] = "Click to";
	if ($great >= 8000)
		$bits[] = "Load High-Resolution";
	else
		$bits[] = "Load";
	if ($type=='360')
		$bits[] = "360&deg; Panorama";
	elseif ($type=='photosphere')
		$bits[] = "Photosphere";
	else
		$bits[] = "Panorama";
	$json['strings'] = array('loadButtonLabel' => implode('<br>',$bits));


	$json['title'] = $image->title;
	$json['author'] = $image->realname." / cc-by-sa/2.0";

	if ($image->imagetaken > date('Y'))
		$json['author'] .= " - taken ".date('M Y',strtotime($image->imagetaken));
	elseif ($image->imagetaken > 1000)
		$json['author'] .= " - taken ".substr($image->imagetaken,0,4);

	//$json['authorURL'] = $image->profile_link;
	$json['authorURL'] = "/photo/".$image->gridimage_id; //only get one link per image!

	?>
	<div class=container>
		<div class=panorama id="panorama<? echo $image->gridimage_id; ?>"></div>
		<script>$(function() {
			pannellum.viewer('panorama<? echo $image->gridimage_id; ?>', <? print json_encode($json); ?>);
		});
		</script>
	</div>

<?

}

print "<br style=clear:both>";

print "<p>Note, this is only images that have been specificially tagged as panoramic, we have some other images that have not been specifically tagged. They dont currently show here";

$smarty->display("_std_end.tpl",'test');
