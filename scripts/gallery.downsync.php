<?

$param = array(
	'debug' => posix_isatty(STDOUT),
);

chdir(__DIR__);
require "./_scripts.inc.php";

################################################################################

if (file_exists("/go/data/showcase/gallery.export.php") || file_exists("../public_html/showcase/gallery.export.php")) {
        //the database is now local!

	if ($param['debug']) print "Fetching Locally\n";

	$db = GeographDatabaseConnection(false);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	if ($db->getOne("SELECT 1 FROM showcase.gallery_image")) {//limit 1 is implicit! ... checking can connect to the database!
		if ($param['debug']) print "dropping...\n";
		$db->Execute("drop table if exists gallery_ids");

		if ($param['debug']) print "executeing...\n";
		$db->Execute("create table gallery_ids (`id` INT NOT NULL,primary key (`id`)) engine=myisam select floor(substring_index(url,'/',-1)) as id,users,showday,baysian,fetched,first_vote,last_vote from showcase.gallery_image where length(title) > 2");

		if ($param['debug']) print "counting...\n";
		if ($db->getOne("SELECT COUNT(*) FROM gallery_ids") > 20000) {
			file_get_contents("http://www.geograph.org.uk/project/systemtask.php?id[]=75&spotcheck=1&api=1&method=POST");
		}
	        exit;
	}
}

################################################################################

$file = '/tmp/gallery.downsync.tmp.mysql';

#$data = file_get_contents($file);

$data = file_get_contents('http://www.geograph.org/gallery.export.php');
if (strlen($data) > 100)
	file_put_contents($file,$data);

if (substr_count($data,'DROP TABLE IF EXISTS `gallery_ids`') != 1) exit;
if (substr_count($data,'DROP') != 1) exit;
if (substr_count($data,'CREATE TABLE `gallery_ids`') != 1) exit;
if (substr_count($data,'CREATE') != 1) exit;
if (substr_count($data,'INSERT INTO `gallery_ids` VALUES') < 2) exit;
#if (substr_count($data,'') != ) exit;


$crit = "-h{$CONF['db_connect']} -u{$CONF['db_user']} -p{$CONF['db_pwd']} {$CONF['db_db']}";

print `mysql $crit < $file`."\n";

$lines = "echo 'select count(*) from gallery_ids' | mysql $crit";


################################################################################

if (preg_match('/(\d+)/',`$lines`,$m) && $m > 20000 && strlen($data) > 1000) {
	file_get_contents("http://www.geograph.org.uk/project/systemtask.php?id[]=75&spotcheck=1&api=1&method=POST");
	unlink($file);
}

