<?

require_once('geograph/global.inc.php');

init_session();

$USER->mustHavePerm("basic");

$db = GeographDatabaseConnection(false);

//$filesystem = new FileSystem();

?>
<style>
input[type=checkbox]:checked {
	background-color:red;
}
label {
	height:100%;
}

</style>


Only use this page in a browser that does do 'exif rotation' (eg modern chrome)
... ie make sure that this image DOES appear sideways (the image itself is rotated correctly, but has incorrect EXIF flag, meaning browsers now 'display' it sideways. 
<img src="https://data.geograph.org.uk/6400488_f978ee09_1024x1024.jpg" style="max-width:213px;max-height:160px"><br>
<b>If the image above appears correct to you, then DO NOT CONTINUE.</b>
<br>
<hr>
<br>
<h3>Images where 'larger upload' has EXIF rotation flag set in the jpg file</h3>
<p style=color:red>Note: this page is currently only scanning images with id greater than 6400000 (needs more work to scan older images)</p>
Three main senarios<ol>
	<li>Small thumbnail is correct, larger image appears rotated. (the EXIF flag is INCORRECT, and needs stripping)
	<li>Small thumbnail is incorrect, larger image correct. (EXIF flag is correct, submission needs rotating so displays without flag)

</ol>
Where it was rotated, but corrected during submission, not shown here (because the EXIF flag was stipped from iamge)<br>
Simillally, if the full resolution was not released, then the downsizing (server side!) at time of submission WOULD have stipped the exif flag, without actully rotating. 
(ie resizing client side - as part of multi-upload - wouldnt of stripped flag!)
<hr>
<i>Note in general the 640px is 'ok' (and we dont check it here!) because it was created as part of submission, and so has exif stripped. The only way for the 640px to be rotated, is if <640 as uploaded (so no resizing happended) ... still need to scan for them seperatly</i><br> 
<br>
<form>
<h4>Tick the box next to any image(s) that are dont appear to be correctly orientated</h4>
<table border=1 cellpadding=4 cellspacing=0 style=background-color:white>

<tr>
	<td><b>213px Thumbnail</b><br>, garenteed to have exif stripped
	<td><b>1024px Thumbnail</b><br>, doesnt strip, so MAY still have it (1024 thumbnial created from original does NOT strip exif)
	<td>Orientation from EXIF at time of upload<br> (may since changed!)
<?


$limit = 10;
if (!empty($_GET['limit']))
	$limit = intval($_GET['limit']);

$rows = $db->getAll("select gridimage_id,original_width,user_id,title,grid_reference,realname, orient_original
	 from exif_rotated
		inner join gridimage_search using (gridimage_id)
		inner join gridimage_size using (gridimage_id)
	where orient_original like 'Orientation%' and orient_original not like '%1'
	and exif_rotated.gridimage_id between 6509000 and 6600000
	 limit $limit");

foreach ($rows as $row) {
	$image = new GridImage();
	$image->fastInit($row);

	print "<tr>";
	print "<td>";
	$id = "s{$row['gridimage_id']}";
	print "<label for=$id><a href=/photo/{$row['gridimage_id']}>";
	print $image->getThumbnail(213,160);
	print "</label><input type=checkbox id=$id name=\"i[{$row['gridimage_id']}][s]\">";


	print "<td>";
	if ($row['original_width'] > 1024) {
                $path = $image->getImageFromOriginal(1024,1024,true);
	} else {
                $path = $image->_getOriginalpath(true,true);
	}
	$id = "l{$row['gridimage_id']}";
	print "<label for=$id>";
	print "<img src=$path style=\"max-width:213px;max-height:160px\">";
	print "</label><input type=checkbox id=$id name=\"i[{$row['gridimage_id']}][l]\">";
	print "<td>";
	print $row['orient_original'];

}
