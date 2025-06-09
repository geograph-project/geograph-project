<?

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;



######################

if (empty($_GET['id']) || $_GET['id'] != 8053319) {
	header("HTTP/1.0 404 Not Found");
        $smarty->display('static_404.tpl');
        exit;
}


	$smarty->display("_std_begin.tpl",md5($_SERVER['PHP_SELF']));

	print "<h3>Interactive 3D Render</h3>";
	print "<p>It's using NeRF technology, so not super high resolution, and contains lots of digital artifacts, but allows you to move around within the approximate confines of the park. Click to focus on a point, drag to rotate and use arrow keys to move around</p>";

	$url = "https://lumalabs.ai/embed/af69710f-4ce3-49ff-9da6-e3f7461b544a?mode=sparkles"; //id=8053319

	print "<iframe src=\"$url\" style=\"width:100%;height:80vh\"></iframe>";


	$smarty->display("_std_end.tpl");
