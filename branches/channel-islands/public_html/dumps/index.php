<style type="text/css">
body {
	font-family:georgia;
}

.cc {
	text-align:center;
	border:1px solid green;
	background-color:lightgreen;
	width:780px;
	padding:10px;
	font-size:0.9em;
	margin-left:auto;
	margin-right:auto;
}

.cc_info {
        width:780px;
        padding:10px;
        font-size:0.9em;
        margin-left:auto;
        margin-right:auto;
	color:gray;
	border-bottom: 1px solid lightgreen;
	border-left: 1px solid lightgreen;
	border-right: 1px solid lightgreen;
}
table {
	font-family:monospace;
        margin-left:auto;
        margin-right:auto;
}
td {
	padding-right:20px;
}
p,h2 {
	width:600px;
        margin-left:auto;
        margin-right:auto;
}
</style>

<h2 align=center>Geograph Channel Islands Data Dumps</h2>

<div class="cc" style="font-size:1.3em"><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img alt="Creative 
Commons Licence [Some Rights Reserved]" src="http://creativecommons.org/images/public/somerights20.gif" border="0" align="left"></a> All 
datasets on this page &copy; Copyright <a href="http://www.geograph.org.gg/credits/">Geograph Project Limited</a><br> and
licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons 
Licence</a>.</div>

<div class="cc_info">You are free:<br/>&nbsp;<b>to Share</b> <sub>- to copy, distribute and transmit the work</sub><br/>&nbsp;<b>to Remix</b> 
<sub>- to adapt the work</sub><br/> under the following conditions:<br/>&nbsp;<b>Attribution</b> <sub>- You must attribute the work in 
the manner specified by the author or licensor</sub><br/>&nbsp;<b>Share Alike</b> <sub>- If you alter, transform, or build upon this 
work, you may distribute the resulting work only under the same or similar license to this one.</sub></div>

<br>

<table>
<?php

foreach (array_merge(glob("*.txt"),glob("*.tsv.gz"),glob("*.mysql.gz"),glob("*.tar.gz")) as $file) {
	print "<tr><td><a href=$file>$file</a></td>";
	print "<td align=right>".number_format(filesize($file),0)." bytes</td>";
	print "<td>".date('Y-m-d H:i',filemtime($file))."</td>";
	print "</tr>";
}

?>
</table>


<p>These dumps are modelled after the ones available for Geograph Britain and Ireland, <br>
	see <a href="http://data.geograph.org.uk/dumps/">http://data.geograph.org.uk/dumps/</a> for how to use the dump files. </p>

<p style="font-size:small">(If unsure which file to use, first try the <b>gridimage_search</b> table. That contains the potentially the most common data all in one table, a few columns are missing, but for many uses it might be enough. Technically is a materialized view, used thoughout the code as a easy way to get data about images.)</p>


