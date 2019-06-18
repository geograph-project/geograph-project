<?

require_once('geograph/global.inc.php');

?>

<h3>Geograph Demo/Toy Application</h3>

This site is a very basic implementation of the Geograph Application. Just enough implementation to test the various backend services needed.

If all components are working, should see responces via each service bewow... 
<hr>

<?


###################################
# Files!

print "<h4>File Serving</h4>";

$filesystem = new FileSystem;

$filename = "photos/test-photo.jpg";

if ($filesystem->exists($filename)) {
	print "<p>If see small image here: it works!";
	print "<img src=\"{$CONF['STATIC_HOST']}/$filename\" style=max-width:100px><hr>";
} else {
	print "Error: $filename NOT found<hr>";
}

###################################
# Mysql

###################################
# Sphinx/Manticore

print "<h4>Sphinx/Manticore</h4>";

$sph = GeographSphinxConnection();

$result = mysql_query("select * from user where match('bob')") or die(mysql_error());
$count = mysql_num_rows($result);

if ($count > 10) {
	print "Run query and got, $count matching rows. Good.";
} else {
	print "didnt obtain expected results";
}
print "<hr>";

###################################
# Redis

###################################
# Memcache (possibly Redis anyway!)

###################################
# Smarty

print "<h4>Smarty PHP Templateing</h4>";

$smarty = new GeographPage;

$smarty->display('toy.tpl');

print "<hr>";

###################################
#

###################################
#

###################################
#

print "<hr>. Page Generated: ".date('r');



