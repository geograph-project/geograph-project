
<?

$domains = array('s0.geograph.org.uk','s1.geograph.org.uk','s2.geograph.org.uk','s3.geograph.org.uk','s9.geograph.org.uk','t0.geograph.org.uk');

$count = count($domains);
print "<p>Check the <b>$count<?b> boxes below, and make sure you see a success message in each one!</p>";

foreach($domains as $domain) {
	print '<iframe src="https://'.$domain.'/challanged.html" border=1 width="300" height="300" style="margin:20px;background-color:pink"></iframe> ';
}
