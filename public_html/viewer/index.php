<?

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

$USER->mustHavePerm("basic");

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


customNoCacheHeader();


$images = array();

$user_id = intval($USER->user_id);

$count = $db->getOne("select count(*) from gridimage_search gi left join gridimage_hash using (gridimage_id) where gi.user_id = $user_id and gridimage_hash.gridimage_id is null");

if (empty($count)) {
	header("Location: /viewer/viewer.php");
}

$seconds = intval($count * 20 / 50);
if ($seconds < 360) {
	$seconds += 10; //just to will be a bit of extra overhead
	$time = "$seconds seconds";
} elseif ($seconds < 60*60) {
	$seconds += 100;
	$time = ceil($seconds/60)." minutes";
} else {
	$seconds += 1000;
	$time = "<b>".ceil($seconds/60/60)." hours</b> - you can leave it processing overnight";
}

$smarty->display('_std_begin.tpl');

?>
<h2>Image Processing Needed</h2>

<div style=max-width:940px>

	<p>You currently have <? echo number_format($count,0); ?> images that need processing. To be able to show you which image(s) are already
submitted, we need to process your existing submissions to create a 'perceptual hash' for each. This allows use to match the image 'visually', which in
concept can find copies regardless of resolution.

	<div class=interestBox><a href="processor.php" target="processor">Click here to open the processor</a><br>
	(<b>just open it once</b>, opening multiple windows won't make it go faster!)<br><br>

	With about <? echo number_format($count,0); ?> images, will probably take about <? echo $time; ?>. Once it completes, <a href="?">click here</a>.</div>

	<p>In theory just leave this open in a tab/window, and let it process. If the tab isn't in front, I think it will still process, just slower.
	Takes about 20 seconds per 50 images in my testing.
	<ul>
		<li>Leave it until get 'No images to process' !
		<li>It is downloading each image (at 640px) and creating the hashes on your own computer.
		<li>if there is a "larger" image, it may download that too (sometimes they are different images)
		<li>If you need to interrupt it just close the tab/window, and reopen it again later.
		<li>if the process 'breaks' for whatever reason, might have to restart it manually. (reload the tab)
		<br><br>
		<li>The hashes are saved on Geograph servers, so processing entire back-catalogue would be a one-off. But you will see this message again to process any new submissions since last time
	</ul>

	<p>If you don't want to wait, you can <a href="viewer.php">proceed</a> to the app. But note that any images/squares not processed will not be identified. Note that if you continue without waiting for the processor to complete, then new images wont show for 24 hours, even if processing has completed in the meantime. (its best to continue AFTER the process completes)
</div>
<?

$smarty->display('_std_end.tpl',md5($_SERVER['PHP_SELF']));

