<?php
/**
 * $Project: GeoGraph $
 * $Id: apikeys.php 945 2005-06-29 22:22:57Z barryhunter $
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

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');


if (!empty($_POST['url'])) {

	if (!empty($_POST['submit'])) {

			//can go ahead and add it
			$updates = array();

			$updates[] = "`user_id` = {$USER->user_id}";
			$updates[] = "`created` = NOW()";

			//loop though all and create the update array
			foreach (array('url','pattern') as $key)
				if (!empty($_POST[$key])) {
					$updates[] = '`'.$key.'` = '.$db->Quote($_POST[$key]);
				}

			$db->Execute($sql = 'INSERT INTO link_report SET '.implode(',',$updates));

			$message = "Thank you for submitting this report. Can submit another below";


mail_wrapper($CONF['developer_email'] ?? $CONF['contact_email'], "[Geograph] New Link Report",
"URL: {$_POST['url']}\n\nPattern: {$_POST['pattern']}\n\n",
"From: Geograph Website <noreply@geograph.org.uk>");

	} else {

		$url = parse_url($_POST['url']);
		if (!preg_match('/^(\w+[\w.-]+\w+)\.?$/',$url['host']))
			die('something went wrong');

		if (preg_match('/^www.(.+)/',$url['host'],$m))
			$results[] = count_urls("*{$m[1]}/*");

		$results[] = count_urls("*{$url['host']}/*");

		if (strlen(@$url['path']) > 1) { //always initial slahs?
			$bits = explode('/',$url['path']);
			$str='';
			for($q=0;$q<count($bits);$q++) {
				$str .= $bits[$q]."/";
				if ($q>0) //will always have already done single slash!
					$results[] = count_urls("*{$url['host']}$str*");
			}

			if (!empty($url['query']))
				$results[] = count_urls("*{$url['host']}{$url['path']}*"); //without query string

			$results[] = count_urls("*{$url['host']}{$url['path']}".(empty($url['query'])?'':"?{$url['query']}") );

		} //eithout a path, dont need to compuse one without query string, as the top level domain one will do that!

		$results[] = count_urls($_POST['url']);

		$data = array('found'=>$results);
		$data['sql'] = $sql;
		$data['db'] = $CONF['db_db'];
		outputJSON($data);
		exit;
	}
}

function count_urls($pattern) {
	global $db, $sql;
	$pattern2 = str_replace('*','%',$pattern); //LIKE uses % as wilecard
	return $db->getRow($sql = "SELECT ".$db->Quote($pattern)." AS pattern, count(distinct url) as links,count(distinct gridimage_id) as images FROM gridimage_link WHERE url LIKE ".$db->Quote($pattern2)." AND parent_link_id =0 and next_check < '2400-01-01'");
}

$smarty->display('_std_begin.tpl');

if (!empty($message))
	print "<p>".htmlentities($message);

?>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script>
function validate(fullurl) {
	let url;
	try {
		url = new URL(fullurl);
	}
	catch(err) {
		alert('does not appear to be valid URL (enter a the full url starting http/https)');
		return;
	}
	if (url.hostname) {
		$.post('?',{url: fullurl}, function(result) {
			if (result.found) {
				var found =0; //need to count, as may get rows without images!
				for(let q=0;q<result.found.length;q++) {
					let row = result.found[q];
					if (row && row.images && row.images > 0) {
						$('#select').append($('<input type=radio name=pattern>').val(row.pattern));
						$('#select').append($('<label>').text(row.pattern));
						$('#select').append($('<span style=color:gray>').text(" : "+row.links+" links, on "+row.images+" images"));
						$('#select').append('<br>');
						found++;
					}
				}
				if (found)
					$('input[name=submit]').prop('disabled',false);
				else
					alert("No Links found. Try another URL");
			}
		});
	} else {
		alert('does not appear to be valid URL');
	}
}
</script>


<h2>Report Link</h2>

<p>Use this form to report a link (or whole domain) that is now offline (or has been taken over by another entity, and no longer safe to link to!) 
... subject to moderation, we will remove all links to this URL (or site), and where possible replace with a functional 'Archive Link'.</p>

<form method=post>

URL: <input type=url name=url size=100 placeholder="enter full URL here, and click validate">
<input type=button value=validate onclick="validate(this.form.elements['url'].value)">

<div id="select">Choose Pattern:<br></div>

<input type=submit name=submit value="Submit Report" disabled>

</form>

<?
$smarty->display('_std_end.tpl');

