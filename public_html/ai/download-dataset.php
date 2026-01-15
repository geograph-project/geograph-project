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

if (empty($_REQUEST['format'])) //but do want to allow curl!
$USER->mustHavePerm("basic"); //mainly just as anti-scraping


        //for CLIP
        $table_embedding = "gridimage_embedding";
        $table_progress = "embedding_progress_clip";
        $model = 'clip';

	$dimensions = 512;
	$title = "CLIP";
	$variation = 'ViT-B/32';
	$hfref = 'openai/clip-vit-base-patch3';


        //Perception Encoder
        if (!empty($_REQUEST['model']) && $_REQUEST['model'] == 'pe') {
                $table_embedding = "gridimage_embedding_1024";
                $table_progress = "embedding_progress_pe";
                $model = 'pe';

		$dimensions = 1024;
		$title = "Perception Encoder";
		$variation = 'PE-Core-B16-224';
		$hfref = "facebook/PE-Core-B16-224";
        }



if (!empty($_REQUEST['format'])) {
	customGZipHandlerStart();
	customExpiresHeader(3600,false,true);

	$db = GeographDatabaseConnection(true);
        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	$where = array();
	$cols = "e.gridimage_id"; //the actual embedding added at end!
	$join = '';
	$group = '';

$join = "inner join types_dataset_1 using (gridimage_id)";
$cols .= ", split, types, coalesce(distance,'0') as distance";
$where[] = "v = 1";
$where[] = "types IS NOT NULL";

	if (!empty($_REQUEST['meta']))
		$cols = "e.gridimage_id,grid_reference,title,realname,imagetaken";
	if (!empty($_REQUEST['ll']))
		$cols .= ",wgs84_lat,wgs84_long";

	if (!empty($_REQUEST['type']) && preg_match('/^\w+$/',$_REQUEST['type'])) {
		$where[] = "e.type = ".$db->Quote($_REQUEST['type']);
	} else {
		$cols .= ", e.type";
	}

	$limit = 10;
	if (!empty($_REQUEST['limit']))
		$limit = min(20000, intval($_REQUEST['limit']));

	$where[] = "model = '$model'"; // not technically needed as the table is all one model, but just in case

	if (empty($where)) $where[] = 1;
	if (!empty($group)) $group = "GROUP BY $group ORDER BY NULL";

	$sql = "SELECT $cols, embeddings
		FROM $table_embedding e
		INNER JOIN gridimage_search gi USING (gridimage_id)
		$join
		WHERE ".implode(" AND ",$where)."
		$group
		LIMIT $limit";

	if ($_REQUEST['format'] == 'csv') {

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
	if ($_REQUEST['format'] == 'jsonl') {

                $recordSet = $db->Execute($sql);

		header("Content-type: application/octet-stream");
		header("Content-Disposition: attachment; filename=\"geograph-embeddings-".date('Y-m-d').".jsonl\"");

		$c=1;
                while (!$recordSet->EOF) {
			$recordSet->fields['gridimage_id'] = intval($recordSet->fields['gridimage_id']);
			//we dont bother with floatval on lat/long as what will add more decimal places!
			if (!empty($recordSet->fields['title'])) {
                                $recordSet->fields['title'] = latin1_to_utf8($recordSet->fields['title']);
                                $recordSet->fields['realname'] = utf8_encode($recordSet->fields['realname']);
			}
			$recordSet->fields['embeddings'] = base64_encode($recordSet->fields['embeddings']);

			print json_encode($recordSet->fields)."\n";
                        $recordSet->MoveNext();
			if (!($c%10000))
				flush(); //help prevent timeouts?
                        $c++;
                }
	}
	exit;
}

################################

	$smarty->display('_std_begin.tpl');

?>
<h2>Download <? echo $title; ?> Embedding Dataset (Functional Prototype)</h2>

<form method=post style=max-width:60em>

	<p>This page only downloads a TINY sample of data, for testing as a proof of concept. Can use to make sure can decode the embeddings ok.
	Later will will share this data in bulk (will be datafiles about 47Gb in size for full 8M images)
	<ul>
		<li>The precomputed embeddings are intended for downstream use, without having to process the raw images yourself.<br><br>
		<li>This data is <b>just the <a href="https://huggingface.co/<? echo $hfref; ?>"><? echo $variation; ?></a> model Embedding (a <? echo $dimensions; ?> dimension vector) + metadata</b>, not the images themselves.<br><br>
		<? if (empty($_GET['custom'])) { ?>
			<li>These sample files, contain the <? echo $variation; ?> Embeddings, <b>of the image itself</b>. <br><small>We also have the <? echo $title; ?> embeddings of the title available (click Custom below); but unless want alignment with the image embedding, we found the <? echo $title; ?> embeddings of image titles are not particully strong, as many titles contain proper (eg places) names, which don't encode well. The metadata contains the image title, to be able to run though your own more powerful text encoder.</small><br><br>
		<? } ?>
		<li>We can download a subset that only includes images already tagged with specific tags, intended to be used for training AI models for predicting those tags.<br><br>
		<li>Important Note: This sample is not a representative portion of the full dataset; the distribution of samples is arbitary. It's just for testing the format, rather than a formal sample.
	</ul>


	<div style="border:1px solid silver; border-radius:10px; padding:10px">
	<h3 style=margin-top:0>Model</h3>
	<input type=radio name=model value=clip <? echo ($model == 'clip')?'checked':''; ?>>CLIP ViT-B/32<br>
	<input type=radio name=model value=pe <? echo ($model == 'pe')?'checked':''; ?>>PE Core-B16-224<br><br>

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

<?

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
embedding_dimension = <? echo $dimensions; ?>

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
    return np.frombuffer(binary_data, dtype=np.float32)

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
