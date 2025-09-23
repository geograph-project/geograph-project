<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 5502 2009-05-13 14:18:23Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 BArry Hunter (geo@barryhunter.co.uk)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;
$USER->mustHavePerm("basic"); //mainly just as anti-scraping

if (!empty($_POST['format'])) {
	customGZipHandlerStart();
	customExpiresHeader(3600,false,true);

	$db = GeographDatabaseConnection(true);
        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	$where = array();
	$cols = "e.gridimage_id"; //the actual embedding added at end!
	$join = '';
	$group = '';

	if (!empty($_POST['meta']))
		$cols = "e.gridimage_id,grid_reference,title,realname,imagetaken";
	if (!empty($_POST['ll']))
		$cols .= ",wgs84_lat,wgs84_long";

	if (!empty($_POST['tags'])) {
		switch($_POST['tags']) {
			case 'category':
				$cols .= ", imageclass";
				$where[] = "gi.imageclass != ''";
				break;
			case 'derived':
				$cols .= ", m.subject as tag, imageclass";
				$join = "INNER JOIN category_mapping AS m USING (imageclass)";
				break;
			case 'score':
				$cols .= ", baysian as score";
				$where[] = "baysian is not null";
				break;
			case 'free':
				$cols .= ", CONCAT_WS(':',NULLIF(prefix,''),tag) AS tag";
				$where[] = "not(prefix in ('top','type','subject') and canonical =0)";
				$join = "INNER JOIN gridimage_tag USING (gridimage_id) INNER JOIN tag USING (tag_id) INNER JOIN tag_stat USING (tag_id)";
				$where[] = "gridimage_tag.status = 2";
				//because using tag_stat implies tag.status=1
				$where[] = "count >= 500";
				break;

			case 'subject':	$prefix = 'subject'; break;
			case 'top':	$prefix = 'top'; break;
			case 'type':	$prefix = 'type'; break;
		}

		if (!empty($prefix)) {
			$where[] = "canonical=0"; //offical only!
			$where[] = "prefix = ".$db->Quote($prefix);
			$cols .= ", tag"; //doesnt need the prefix!
			$join = "INNER JOIN gridimage_tag USING (gridimage_id) INNER JOIN tag USING (tag_id)";
			$where[] = "gridimage_tag.status = 2";
			$where[] = "tag.status = 1";
		}
	}

	if (!empty($_POST['type']) && preg_match('/^\w+$/',$_POST['type'])) {
		$where[] = "e.type = ".$db->Quote($_POST['type']);
	} else {
		$cols .= ", e.type";
	}

	$limit = 10;
	if (!empty($_POST['limit']))
		$limit = min(20000, intval($_POST['limit']));

	if (empty($where)) $where[] = 1;
	if (!empty($group)) $group = "GROUP BY $group ORDER BY NULL";

	if (!empty($_POST['tags']) && in_array($_POST['tags'],array('subject','top','type'))) {
		//actully for now, lets use a sample table to get a more varied selection

	$sql = "SELECT e.gridimage_id,grid_reference,title,realname,imagetaken, tag, embeddings
		 FROM gridimage_sample2 STRAIGHT_JOIN gridimage_embedding e USING (gridimage_id) STRAIGHT_JOIN gridimage_search gi USING (gridimage_id)
		 STRAIGHT_JOIN gridimage_tag USING (gridimage_id) STRAIGHT_JOIN tag USING (tag_id)
		 WHERE gridimage_sample2.{$_POST['tags']} = 1 AND ".implode(" AND ",$where)."
                $group
                LIMIT $limit";

	} else {

	$sql = "SELECT $cols, embeddings
		FROM gridimage_embedding e
		INNER JOIN gridimage_search gi USING (gridimage_id)
		$join
		WHERE ".implode(" AND ",$where)."
		$group
		LIMIT $limit";
	}

	if ($_POST['format'] == 'csv') {

                $recordSet = $db->Execute($sql);

		header("Content-type: application/octet-stream");
		header("Content-Disposition: attachment; filename=\"geograph-embeddings-".date('Y-m-d').".csv\"");

                $f = fopen("php://output", "w");
                if (!$f) {
                        die("ERROR:unable to open output stream");
                }

                fputcsv($f,array_keys($recordSet->fields));
                while (!$recordSet->EOF) {
			if (!empty($recordSet->fields['title'])) {
                                $recordSet->fields['title'] = latin1_to_utf8($recordSet->fields['title']);
                                $recordSet->fields['realname'] = utf8_encode($recordSet->fields['realname']);
			}
			$recordSet->fields['embeddings'] = base64_encode($recordSet->fields['embeddings']);

                        fputcsv($f,$recordSet->fields);
                        $recordSet->MoveNext();
                }
	}
	if ($_POST['format'] == 'jsonl') {

                $recordSet = $db->Execute($sql);

		header("Content-type: application/octet-stream");
		header("Content-Disposition: attachment; filename=\"geograph-embeddings-".date('Y-m-d').".jsonl\"");

                $f = fopen("php://output", "w");
                if (!$f) {
                        die("ERROR:unable to open output stream");
                }

                while (!$recordSet->EOF) {
			$recordSet->fields['gridimage_id'] = intval($recordSet->fields['gridimage_id']);
			//we dont bother with floatval on lat/long as what will add more decimal places!
			if (!empty($recordSet->fields['title'])) {
                                $recordSet->fields['title'] = latin1_to_utf8($recordSet->fields['title']);
                                $recordSet->fields['realname'] = utf8_encode($recordSet->fields['realname']);
			}
			$recordSet->fields['embeddings'] = base64_encode($recordSet->fields['embeddings']);

			fwrite($f, json_encode($recordSet->fields)."\n");
                        $recordSet->MoveNext();
                }
	}
	exit;
}

################################

	$smarty->display('_std_begin.tpl');

?>
<h2>Download CLIP Embedding Prototype</h2>

<form method=post style=max-width:60em>

	<p>This page only downloads a TINY sample of data, for testing as a proof of concept. Can use to make sure can decode the embeddings ok.
	Later will will share this data in bulk (will be datafiles about 47Gb in size for full 8M images)
	<ul>
		<li>The precomputed CLIP embeddings are intended for downstream use, without having to process the raw images yourself.
		<li>This data is <b>just the Embedding (a 512 dimension vector) + metadata</b>, not the images themselves.
		<li>We can also supply a file that only includes images already tagged with specific tags, intended to be used for training AI models.
		<li>Note the samples, arent garenteed to be representative samples, may end up with very few tags.
	</ul>

<?

################################

if (empty($_GET['custom'])) {

	$types = array(
		'top' => array('<b>Images with [context] tag(s).</b> Could for example be used to train a model to predict our Top Level Context tags. 49 tags that describe the general area of the image',5700000),
		'subject' => array('<b>Images with a [subject] tag.</b> This images where the contributor has choosen from a list of about 1000 tags that depict the primary subject of the image',1600000),
		'type' => array('<b>Images with [type] tag(s).</b> Moderators choose from a short list of Type tags to classify non-geograph images. Could be used to see if a model could do similar prediction',3200000),
		'' => array('All Images. Just download the clip embeddings, regardless of the tags',8100000),
	);

$base = array(
'model' => 'clip',
'type' => 'image',
'format' => 'jsonl',
'meta' => 'on',
'tags' => 'subject',
'limit' => 10000,
);

	print "<table cellspacing=0 cellpadding=20 border=1 bordercolor=#eee>";
	foreach($types as $type => $data) {
		list($desc, $count) = $data;
		print "<tr>";
		print "<td>".$desc;
		print "<td>";
		print "<form method=post>";
		$base['tags'] = $type;
		foreach($base as $key => $value)
			print "<input type=hidden name=$key value=$value>";
		print "<button type=submit>Download {$base['limit']} Sample</button>";
		print "</form>";
		print "<td><button disabled>Download ".formatApproximateNumber($count)." images</button><br>";
		print "<i>Coming soon</i>";
	}
	print "<tr>";
		print "<td colspan=3 align=center>Note: If there are multiple tags for the same image, will get multiples rows, one row per tag;<br> so the gridimage_id column is not unique)<br>About 30Mb for 10000 rows. Formatted as JSONL file.";
	print "</table>";
	print "<a href=?custom=1>Custom Download</a> (more options)";


} else {

################################

?>
	<div style="border:1px solid silver; border-radius:10px; padding:10px">
	<h3 style=margin-top:0>Model</h3>
	<input type=radio name=model value=clip checked>CLIP ViT-B/32 - only model available currently<br><br>

	<input type=radio name=type value="image" checked>Image embedding Only<br>
	<input type=radio name=type value="title">Title embedding Only<br>
	<input type=radio name=type value="">Image+Text embedding<br>
	</div>
<br>

	<div style="border:1px solid silver; border-radius:10px; padding:10px">
	<h3 style=margin-top:0>Format</h3>
	<input type=radio name=format value=csv>CSV - simple format for testing purposes (embeddings are base64 encoded)<br>
	<input type=radio name=format value=jsonl checked>JSONL - JSON Objects seperated by newlines - easier to parse than pure JSON (embeddings are base64 encoded)<br>
	<br>
	<input type=checkbox name=meta checked>Include basic metadata (otherwise will only get embeddings)<br>
	<input type=checkbox name=ll>Include WGS84 Lat/long (for more detailed coordinates, see the <a href="https://data.geograph.org.uk/">dumps</a>)<br>
	(we have much more metadata, this just includes stuff, particulally the Creative Commons credit/name)
	</div>
<br>

	<div style="border:1px solid silver; border-radius:10px; padding:10px">
	<h3 style=margin-top:0>Get Tagged Images</h3>
	<input type=radio name=tags value="" checked>All Images<br>
	<input type=radio name=tags value="subject">'Subject' Tags (1.6M Images Total, 684 Classes)<br>
	<input type=radio name=tags value="top">'Context' Tags (5.7M Images Total, 49 Classses)<br>
	<input type=radio name=tags value="type">'Type' Tags (3.2M Images Total, 7 Classes)<br>
	<input type=radio name=tags value="category">Category (2.3M Images Total, 1.8k Classes)<br>
	<input type=radio name=tags value="derived">Derived 'Subject' Tags (3.9M Images Total - inferred from category)<br>
	<input type=radio name=tags value="free">Freeform Tags (3.2M Images Total, for 1.5k tags with over 500 images)<br>
	<input type=radio name=tags value="score">Score (numeric score from our gallery)<br>
	(Note: If there are multiple tags for the same image, will get multiples rows, one row per tag, so the gridimage_id column is not unique)
	</div>
<br>

	<div style="border:1px solid silver; border-radius:10px; padding:10px">
	<h3 style=margin-top:0>Sample Size</h3>
	<input type=radio name=limit value=10>10 rows (image+title embedding for 5 images)<br>
	<input type=radio name=limit value=100 checked>100 rows<br>
	<input type=radio name=limit value=1000>1000 rows<br>
	<input type=radio name=limit value=10000>10000 rows<br>
	<input type=radio name=limit value=20000>20000 rows<br>
		...bigger datasets are still being worked on
	</div>
<br>

	<div style="border:1px solid silver; border-radius:10px; padding:10px">
	<input type=submit value="Download File">
	</div>

</form>
	<br>

<? }

################################

 ?>

<p style=font-weight:bold;background-color:orange;padding:10px;>
Copyright 2025 Geograph Project Limited.<br><br>

and released under this Creative Commons Licence:<br>
http://creativecommons.org/licenses/by-sa/2.0/<br><br>

The individual photos that are used to build this dataset are Copyright the respective Licensors,<br>
see the full list of contributors here: http://www.geograph.org.uk/credits/<br><br>

====================<br><br>

If reproducing this work, you must acknowledge the original author.
</p>
<hr><hr>

<pre>
# The embeddings where created with binary_embedding = embedding.numpy().tobytes() - base64 is used for encoding in the CSV

# So should be decodable with something like this (contact us if want more complete code!)

import sys
assert(sys.byteorder == 'little')

original_dtype = np.float32
embedding_dimension = 512

binary_embedding = base64.b64decode(row["embeddings"])
numpy_embedding = np.frombuffer(binary_embedding, dtype=original_dtype)
pytorch_embedding = torch.from_numpy(numpy_embedding).reshape(embedding_dimension)
</pre>
<hr><hr>

        <div class="code-note">
            <h4>Using JSON Lines in Python with Pandas</h4>
            <p>The JSON Lines format is perfect for loading large datasets into a Pandas DataFrame without running out of memory. This is how you would read the downloaded file:</p>
            <pre><code>import pandas as pd
import base64
import struct

# This is an efficient way to read a large JSON Lines file
# the lines=True is key
df = pd.read_json('geograph_dataset.jsonl', lines=True)

# Define a function to decode the Base64-encoded vector
def decode_vector(encoded_str):
    # Decode the Base64 string to get the original binary data
    binary_data = base64.b64decode(encoded_str)
    # Use the struct module to unpack the binary data into a list of floats
    # 'g' is the format code for double-precision float (8 bytes), '*' means repeat as needed
    num_floats = len(binary_data) // struct.calcsize('g')
    return list(struct.unpack(f'>{num_floats}g', binary_data))

# Apply the decoding function to the 'embeddings' column
df['embedding-decoded'] = df['embeddings'].apply(decode_vector)

# Now you can work with your data in the DataFrame
print(df.head())
            </code>
       </div>

<hr><hr>


<?

	$smarty->display('_std_end.tpl');
	exit;

function formatApproximateNumber($number) {
    if (!is_numeric($number)) {
        return $number;
    }

    $units = ['', 'K', 'M', 'B', 'T'];
    $unitIndex = 0;

    // Determine the appropriate unit
    while ($number >= 1000 && $unitIndex < count($units) - 1) {
        $number /= 1000;
        $unitIndex++;
    }

    // Round the number to one decimal place
    $formattedNumber = round($number, 1);

    // If the number is a whole number (e.g., 2.0), remove the .0
    if ($formattedNumber == round($formattedNumber)) {
        $formattedNumber = round($formattedNumber);
    }

    // Construct the final string
    return 'about ' . $formattedNumber . $units[$unitIndex];
}
