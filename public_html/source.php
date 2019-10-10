<table>
<tr>
	<th>Filename (click to view in SVN)</th>
	<th>View Live</th>
	<th></th>
<?

$files = `find /var/www/geograph_toy/ -type f | grep -v /.svn/ |  grep -v /smarty/libs/internals/ | grep -v adodb- | grep -v _attic | grep -v %%`;

$svn = 'https://svn.geograph.org.uk/viewsvn/?do=view&project=geograph&path=/branches/toy$dir2/$file2';

foreach (explode("\n",$files) as $file) {
	if (empty($file))
		continue;
	if (strpos($file,'/smarty') !== FALSE && strpos($file,'/Smarty.class.php') === FALSE) {
		continue;
	}
	print "<tr>";
	$dir2 = str_replace('/var/www/geograph_toy','',dirname($file));
	$file2 = basename($file);
	print "<td style=\"font-family:monospace\">$dir2/";

	if (strpos($dir2,'/compiled') !== FALSE) { //todo, maybe use SVN info?
		print "$file2</td>";
	} else {
		$url = str_replace('$dir2',$dir2,$svn);
		$url = str_replace('$file2',$file2,$url);
		print "<b><a href=\"$url\">$file2</a></b></td>";
	}

	if (strpos($dir2,'/public_html') === 0 && preg_match('/\.(php|jpg|txt|png)$/',$file2) && strpos($dir2,'/compiled') === FALSE) {
		$url = str_replace('/var/www/geograph_toy/public_html/','https://toy.geograph.org.uk/',$file);
		print "<td><a href=\"$url\">view</a></td>";
	} else {
		print "<td></td>";
	}

	if (strpos($dir2,'libs/') === FALSE || strpos($dir2,'/libs/geograph') === 0) {
		if ( preg_match('/\.(php|jpg|conf|mysql|txt)$/',$file2)) {
			print "<td align=right>".filesize($file)." bytes";
		}
		if ( preg_match('/\.(php|conf|mysql)$/',$file2)) {
			print "<td align=right>".intval(`wc -l $file`)." lines";
		}
	}

	print "</tr>\n";

	if (strpos($file,'/Smarty.class.php') !== FALSE) {
		$url = "https://svn.geograph.org.uk/viewsvn/?do=browse&project=geograph&path=/branches/toy/libs/smarty/libs/";
		print "<tr><td colspan=3>... <i>rest of Smarty internals hidden for brevity, to see rest: <a href=\"$url\">view in SVN</a>";
	}

	if (strpos($file,'/adodb.inc.php') !== FALSE) {
		$url = "https://svn.geograph.org.uk/viewsvn/?do=browse&project=geograph&path=/branches/toy/libs/adodb/";
		print "<tr><td colspan=3>... <i>rest of adodb internals hidden for brevity, to see rest: <a href=\"$url\">view in SVN</a>";
	}

}


