<?

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

$USER->mustHavePerm("basic");

$user_id = intval($USER->user_id);

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

##############################################
// need a lookup of gid->transfer_id
$uploadmanager=new UploadManager;
$data = $uploadmanager->getUploadedFiles(); //todo, maybe want a wayt to skip exif data?

$tmpfiles = $db->getAssoc("SELECT gridimage_id,1 FROM gridimage_hash WHERE source = 'tmp' AND user_id = $user_id");
foreach($data as $row) {

        $gid = crc32($row['transfer_id'])+4294967296;
        $gid += $USER->user_id * 4294967296;

	//if dont have a hash, no point including!
        if (empty($tmpfiles[$gid]))
                continue;

	//the whole point is to lookup the transfer_id from the gid
	$tmpfiles[$gid] = $row;
}

##############################################

$smarty->display('_std_begin.tpl');

##############################################

$where = "and user_id = $user_id";
$having = "and i > 1";

if (!empty($_GET['all'])) {
	$where = "";
	$having = "and i > 1 and sources NOT like '%pending%'";
}


$data = $db->getAll("select phash,user_id,count(*) c
	,group_concat(gridimage_id order by gridimage_id) as ids, count(distinct gridimage_id) as i
	,group_concat(source order by gridimage_id) as sources
 from gridimage_hash where phash != '' $where
 group by phash having c > 1 $having
 limit 10");

foreach ($data as $row) {
	print "<h3 style=clear:both;background-color:#eee;padding:2px;margin-bottom:0>{$row['phash']}</h3>";

	$ids = explode(',', $row['ids']);
	$sources = explode(',', $row['sources']);
	for($idx=0;$idx<count($ids);$idx++) {
		$gid = $ids[$idx];
		$source = $sources[$idx];
		$s = ($idx>0 && $gid > 100000000)?';background-color:pink':'';
		print "<div style=\"float:left; width:220px; height: 230px; text-align:center $s\">$source<br>";
		if ($gid < 100000000) {
			$row['gridimage_id'] = $gid;
			//$image = new GridImage();
			//$image->fastInit($row);
			$image = new GridImage($gid);
			if ($source == 'full') {
				print "<a href=/photo/$gid>";
				print $image->getThumbnail(213,160);
			} else {
				print "<a href=/more.php?id=$gid>";
				if ($source == '640px') {
					$path = $image->getImageFromOriginal(640,640, true);
				} elseif ($source == '800px') {
					$path = $image->getImageFromOriginal(800,800, true);
				} elseif ($source == 'original') {
					$path = $image->_getOriginalpath(false, true);
				}
				print "<img src=$path style=max-width:213px><br>";
			}
			print "<br>{$gid} - ".htmlentities($image->title);
			print "</a>";
		} else {
			$transfer_id = $tmpfiles[$gid]['transfer_id'];
			$preview_url="/submit.php?preview=".$transfer_id;
			print "<img src=$preview_url style=max-width:213px><br>";
			print strftime("%a, %e %b %Y at %H:%M",$tmpfiles[$gid]['uploaded']);
		}
		print "</div>";
	}
}
print "<br style=clear:both>";

##############################################

$smarty->display('_std_end.tpl',md5($_SERVER['PHP_SELF']));

