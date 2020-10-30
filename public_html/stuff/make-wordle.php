<?php
/**
 * $Project: GeoGraph $
 * $Id: make-wordle.php 7224 2011-04-24 12:35:28Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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


$db = GeographDatabaseConnection(true);

$where = array();
if (isset($_GET['mine']) && $USER->user_id) {
	$where[] = "user_id = {$USER->user_id}";
} 

if (!empty($_GET['u'])) {
	$where[] = "user_id = ".intval($_GET['u']);
}

if (isset($_GET['tags'])) {

	if (count($where)) {
		$where[] = "prefix NOT IN('type','bucket')";

		$where = "WHERE ".implode(' AND ',$where);

		$wordcount = $db->getAssoc("SELECT REPLACE(REPLACE(tag,',',''),' ','_'),COUNT(*) AS images FROM tag_public t $where GROUP BY tag_id ORDER BY images DESC LIMIT 250");
	} else {
		$where = "WHERE tagtext NOT like 'top:%' AND tagtext NOT like 'type:%'";

		$wordcount = $db->getAssoc("SELECT REPLACE(REPLACE(tagtext,',',''),' ','_'),ROUND(LN(SUM(`count`))) AS images FROM tag_stat $where GROUP BY final_id ORDER BY images DESC LIMIT 250");
	}

} else {

	if (!empty($_GET['myriad']) && preg_match('/^\w{1,3}$/',$_GET['myriad'])) {
		$where[] = "grid_reference like '{$_GET['myriad']}____'";
	}

	if (!empty($_GET['hectad']) && preg_match('/^(\w{1,3}\d)(\d)$/',$_GET['hectad'],$m)) {
		$where[] = "grid_reference like '{$m[1]}_{$m[2]}_'";
	}

	if (!empty($_GET['gridref']) && preg_match('/^(\w{1,3})(\d{4})$/',$_GET['gridref'],$m)) {
		$where[] = "grid_reference = '{$_GET['gridref']}'";
	}

	if (!empty($_GET['category'])) {
		$where[] = "imageclass = ".$db->Quote($_GET['category']);
	}

	if (!empty($_GET['when']) && preg_match('/^\d{4}-\d{2}(-\d{2}|)$/',$_GET['when'])) {
		if (strlen($_GET['myriad']) == 10) {
			$where[] = "imagetaken = '{$_GET['when']}'";
		} else {
			$where[] = "imagetaken like '{$_GET['when']}%'";
		}
	}

	if (count($where)) {
		$sql = "select title from gridimage_search where ".implode(' and ',$where);
		if (empty($_GET['u']) && empty($_GET['mine'])) {
			$sql .= " limit 10000";
		}
	} else {
		$max = $db->getOne("select max(gridimage_id) from gridimage_search"); //Select tables optimized away

		$sql = "select title from gridimage_search where gridimage_id > ".($max-1200)." order by gridimage_id limit 1000";
	}

	$wordcount = array();

	$recordSet = $db->Execute($sql);
	while (!$recordSet->EOF) {
		$words = preg_split('/[^a-zA-Z0-9]+/',trim(str_replace("'",'',$recordSet->fields['title'])));

		foreach ($words as $word) {
			@$wordcount[$word]++;
		}

		$recordSet->MoveNext();
	}
	$recordSet->Close();
	unset($wordcount['']);
	arsort($wordcount,SORT_NUMERIC);
}

if (count($wordcount) > 250)
	$wordcount = array_slice($wordcount,0,250,true);

?>
<html>
<head>
<title>Word Cloud</title>
<style>
body {
	font-family:georgia;
	--background-color:#e4e4fc;
}

</style>
<script src="/js/d3.v2.min.js"></script>
<script src="/js/d3.layout.cloud.js"></script>

</head>
<body>

<h2>Geograph Word Clouds</h2>

<h3>3d-cloud</h3>

<p>Rendered using <a href="https://github.com/jasondavies/d3-cloud">https://github.com/jasondavies/d3-cloud</a>, using basic default settings.</p>

	<div id="vis"></div>

<script>
var fill = d3.scale.category20();

var layout = d3.layout.cloud()
    .size([800, 600])
    .words([<?
$sep = '';
foreach ($wordcount as $word => $count) {
	print $sep.json_encode(array('text'=>$word,'count'=>$count));
	$sep = ",";
}
$basesize = (max($wordcount) > 150)?12:18;

    ?>])
    .padding(3)
    .rotate(function() { return Math.round((Math.random()-0.5) * 10); })
    .font("Impact")
    .fontSize(function(d) { return <? echo $basesize; ?>*Math.log(d.count); })
    .on("end", draw);

layout.start();

function draw(words) {
  d3.select("body").append("svg")
      .attr("width", layout.size()[0])
      .attr("height", layout.size()[1])
    .append("g")
      .attr("transform", "translate(" + layout.size()[0] / 2 + "," + layout.size()[1] / 2 + ")")
    .selectAll("text")
      .data(words)
    .enter().append("text")
      .style("font-size", function(d) { return d.size + "px"; })
      .style("font-family", "Impact")
      .style("fill", function(d, i) { return fill(i); })
      .attr("text-anchor", "middle")
      .attr("transform", function(d) {
        return "translate(" + [d.x, d.y] + ")rotate(" + d.rotate + ")";
      })
      .text(function(d) { return d.text; });
}

</script>

<hr/>

<form>
You may also be able copy this text: (all should select when click the box, then just press Ctrl-C to copy it)<br>
	<textarea rows=3 cols=50 onclick="this.select()"><?
foreach ($wordcount as $word => $count) {
	print str_repeat("$word ",$count);
}
?></textarea><br>
 into the demo at <a href="https://www.jasondavies.com/wordcloud/">https://www.jasondavies.com/wordcloud/</a> - which gives you more options to play around. Alas the demo itself doesnt seem to be open-source, so can't put the interactive interface here.
</form>


<hr/>
<h3>Wordle.net</h3>
<p>Can still try loading the words on wordle.net using the button below, alas its still based on Java technology, that doesnt work in many modern browsers. </p>

<form action="http://www.wordle.net/compose" method="post" name="theForm">
<input type=hidden name=wordcounts value="<?
foreach ($wordcount as $word => $count) {
	print "$word:$count,";
}
?>">
<input type=submit value="load on wordle.com">
</form>
</body>
</html>
