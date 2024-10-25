<?php
/**
 * $Project: GeoGraph $
 * $Id: viewps.php 2295 2006-07-05 12:15:49Z barryhunter $
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

$USER->mustHavePerm("admin");


$param = $_GET; //loki-wrapper started for use in scripts so uses $param directly!

// json  doesn work with with pattern! so put stream into the 'base' query. base query should be updated to use stream in initial selector, rather than json!
$param['stream'] = '';

require_once('3rdparty/loki-wrapper.inc.php');
//note, sets up $start & $end for use with getgroups/getlogs (as well as setting up $pattern, used by get_base_query - so could change it!

//function get_base_query($param, $add_pattern = false) {
//function getlogs($query, $fp = null, $limit = 5000, $start = null, $end = null) {
//function getgroups($query, $grouper, $funct = 'rate', $period = '10m',  $fp = null, $start = null, $end = null) {


if (!empty($_GET['json'])) {
	if (!empty($_GET['host'])) {
		customExpiresHeader(3600*24,true,true);
		$host = gethostbyaddr($_GET['host']);
		outputJSON($host); //uses pass by ref
		exit;
	}
	customExpiresHeader(900,true,true);
	customGZipHandlerStart();

	if ($_GET['json'] == 'logs2') {
		$_GET['json'] = 'logs';
		$param['direction'] = 'backward';
	}

	if ($_GET['json'] == 'groups') {
		if (empty($_GET['group']) || !ctype_alnum($_GET['group']))
			die('{"error":"invalid group"}'."\n");

		$grouper = $_GET['group'];
		if ($grouper === 'snub') {
			$query = get_base_query($param);
			$query .= ' | regexp "(HEAD|GET) /(?P<snub>[\\\\w\\\\.-]+)"';
		} elseif ($grouper === 'snub2') {
			$query = get_base_query($param);
			$query .= ' | regexp "(HEAD|GET) /(?P<snub2>\\\\w+/[\\\\w\\\\.-]+)"';
		} elseif ($grouper === 'file') {
			$query = get_base_query($param);
			$query .= ' | regexp "(HEAD|GET) /(?P<file>[\\\\w\\\\./-]+)"';
		} elseif ($grouper === 'date') {
			//66.249.64.131 - 0 [01/May/2024:00:57:42 +0100] "GET /photo/945305
			$pattern = 'pattern `<ip> - <uid> [<date>:<_>:<_>:<_> <_>] "GET <path> <_>" "<_>" <status> <_> "<_>" "<agent>"`';

			$query = get_base_query($param, $add_pattern = true);

		} elseif ($grouper === 'hour') {
			//66.249.64.131 - 0 [01/May/2024:00:57:42 +0100] "GET /photo/945305
			$pattern = 'pattern `<ip> - <uid> [<date>:<hour>:<_>:<_> <_>] "GET <path> <_>" "<_>" <status> <_> "<_>" "<agent>"`';

			$query = get_base_query($param, $add_pattern = true);

		} elseif ($grouper === 'ip2') {
			//4FKLwxuM')) OR 551=(SELECT 551 FROM PG_SLEEP(15))--, 146.70.55.236 - 0 [14/Dec/2023:23:27:42 +0000] "GET /article/Positions
			$pattern = 'pattern `<_>, <ip2> - <_> <uid> "GET <path> <_>" "<_>" <status> <_> "<_>" "<agent>"`';

			$query = get_base_query($param, $add_pattern = true);

			//this attempts to remove rows with single IP address, as the pattern above doesnt match them!
			$query .= ' !~ "^\\\\d+\\\\.\\\\d+\\\\.\\\\d+\\\\.\\\\d+ -"';

		} else {
			$query = get_base_query($param, $add_pattern = true); //definitly need patter for the grouping!
		}

		$generator = getgroups($query, $grouper, 'count_over_time', $period = '10m',
	             $fp = null, $start, $end);

		if (!empty($_GET['raw'])) {
			$stat = array();
			//its a generator, so still need to use a loop.
	                foreach ($generator as $line) {
                	        $stat[]=$line;
        	        }
	                outputJSON($stat);
			exit;
		}


		$stat = array();
		foreach ($generator as $line) {
		        //the function is only executed once loop once!

		        list($item,$time,$value) = $line;

			//there may be muliple rows per item, (as per $period) so aggregate. later could return all rows, to plot graphs over time!
		        @$stat[$item]+=$value;
		}
		outputJSON($stat);

	} elseif ($_GET['json'] == 'logs') {
		if (empty($param['limit']))
			$param['limit'] = 10;

		$query = get_base_query($param);

		//note this also accepts options via the 'global' $param array
		$generator = getlogs($query, $fp = null, $param['limit'], $start, $end);

		//not using outputJSON as return could be long!
		header("Content-Type:application/json");
		print "[";
		$sep = '';
		foreach ($generator as $line) {
			print $sep.json_encode($line);
			$sep = ",\n";
		}

		print "]\n";
	}
	exit;
}

###################################

?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>

<? print "<script src=\"".smarty_modifier_revision("/js/geograph.js")."\"></script>"; ?>
<? print "<script src=\"".smarty_modifier_revision("/sorttable.js")."\"></script>"; ?>

<form id="theForm" style="background-color:silver;padding:8px" onsubmit="return captureSubmit(this)">
	String: <input type=search size=30 name=string value="<? echo htmlentities(@$_GET['string']); ?>">
	<input type=search size=10 name=second value="<? echo htmlentities(@$_GET['second']); ?>">
	Not: <input type=search size=10 name=not value="<? echo htmlentities(@$_GET['not']); ?>">
	Hours: <input type=text size=3 name=hours value="<? echo htmlentities(@$_GET['hours']); ?>">

	IP: <input type=text size=10 name=ip value="<? echo htmlentities(@$_GET['ip']); ?>">
	Stat: <input type=text size=3 name=status value="<? echo htmlentities(@$_GET['status']); ?>">
	Ref: <input type=text size=10 name=refer value="<? echo htmlentities(@$_GET['refer']); ?>">
	UA: <input type=text size=10 name=ua value="<? echo htmlentities(@$_GET['ua']); ?>">

	limit: <input type=text size=3 name=limit value="<? echo htmlentities(@$_GET['limit']); ?>">

	nobot:<input type=checkbox name=nobot <? if (!empty($_GET['nobot'])) { echo "checked"; } ?>>
	nocommon:<input type=checkbox name=common <? if (!empty($_GET['common'])) { echo "checked"; } ?>>
	users:<input type=checkbox name=users <? if (!empty($_GET['users'])) { echo "checked"; } ?>>
</form>

<div id="tabs" style=padding:10px>
	<button id="g_ip" onclick="runQuery(this.id,'groups','ip');">By IP</button>
	(<button id="g_ip2" onclick="runQuery(this.id,'groups','ip2');">IP2</button>) &nbsp;

	<button id="g_date" onclick="runQuery(this.id,'groups','date');">By Date</button>
	<button id="g_hour" onclick="runQuery(this.id,'groups','hour');">By Hour</button> &nbsp;

	<button id="g_path" onclick="runQuery(this.id,'groups','path');">By Path</button>
	(<button id="g_snub" onclick="runQuery(this.id,'groups','snub');">By Snub</button>
	<button id="g_snub2" onclick="runQuery(this.id,'groups','snub2');">By Snub2</button>
	<button id="g_file" onclick="runQuery(this.id,'groups','file');">By File</button>) &nbsp;

	<button id="g_status" onclick="runQuery(this.id,'groups','status');">By Status</button> &nbsp;
	<button id="g_agent" onclick="runQuery(this.id,'groups','agent');">By Agent</button> &nbsp;
	<button id="logs" onclick="runQuery(this.id,'logs');">Raw Logs F</button> &nbsp;
	<button id="logs2" onclick="runQuery(this.id,'logs2');">Raw Logs B</button> &nbsp;
	<button onclick="dedup()">Dedup</button>
</div>

<table id="results" class="report sortable" cellspacing=0 cellpadding=3 border=1 bordercolor=#eeeeee style="overflow-wrap: anywhere;"></table>
<div id="stats"></div>

<script>
$(function() {
	//todo, if(location.search) captureSubmit()...
});

function captureSubmit(form) {
	//todo, this should perhaps just return the current search (which ever tab selected)
	data = $(form).serialize();
	console.log('d',typeof data, data);
	return false;
}

var grouper;
var button_id;
function runQuery(id,type,group) {
	$('#tabs button').css('backgroundColor','');
	button_id = id;

	var data = $('#theForm').serialize();
	data = data.replace(/\w+=(&|$)/g,'$1').replace(/&{2,}/g,'&');
	history.pushState(data,null,"?"+data);

	data = data+"&json="+type;
	if (group) {
		grouper = group;
		data = data+"&group="+group;
	}
	$.getJSON("?"+data, render);
}

function render(data) {
	$('#tabs button#'+button_id).css('backgroundColor','lightgreen');

	var $table = $('table#results').empty();

	if ($.isArray(data)) {
		//raw logs!
		$table.addClass('rawlog');
		$table.removeClass('groupby');

		var $tr = $('<tr>');
		$('<th>').text('ip').appendTo($tr);
		$('<th>').text('id').appendTo($tr);
		$('<th>').text('date').appendTo($tr);
		$('<th>').text('time').appendTo($tr);
		$('<th>').text('method').appendTo($tr);
		$('<th>').text('path').appendTo($tr);
		$('<th>').text('domain').appendTo($tr);
		$('<th>').text('status').appendTo($tr);
		$('<th>').text('bytes').appendTo($tr);
		$('<th>').text('refer').appendTo($tr);
		$('<th>').text('agent').appendTo($tr);
		$('<th>').text('time').appendTo($tr);

		$('<thead>').append($tr).appendTo($table);

		var $tbody = $('<tbody>');
		var stat = {};
		$.each(data,function(index,line) {
			//todo, we should 'parse' the logs, and render proper columns
			var $tr = $('<tr>');

			if (m = line.match(/^([\da-f:., ]+) - (\w+|-) \[(\d+\/\w+)\/\d+:(\d+:\d+:\d+) \+0\d00\] "(\w+) (.*?) HTTP\/\d.\d"( "[\w.]+")? (\d+) (\d+|-) "(.*?)" "(.*?)"(\s?[\d.]*)( https?)?/)) {
				m[7] = m[7].replace('www.','').replace('geograph.','').replace(/"/g,'');
				m[7] = m.pop()+'.'+m[7];
				for (i in m) {
					if (i > 0)
						$('<td>').text(m[i]).appendTo($tr);
				}
				if (p = m[6].match(/\/api\/.+?\/(\w+)(\?|$)/)) {
					//this is far from perfect, only looking at the LAST part, which MAY not be the key
					stat[p[1]] = stat[p[1]]?(stat[p[1]]+1):1;
				} else if (p = m[6].match(/key=(\w+)(&|$)/)) {
					stat[p[1]] = stat[p[1]]?(stat[p[1]]+1):1;
				}
			} else {
				$('<td>').attr('colspan',12).text(line).attr('style','color:gray').appendTo($tr);
			}
			$tr.appendTo($tbody);
		});
		$tbody.appendTo($table);

		if (Object.keys(stat).length) {
			$('div#stats').empty().append('<table>');
			$table = $('div#stats table').append('<caption>API KEYS</caption>');
			$.each(stat,function(index,value) {
				var $tr = $('<tr>');
				$('<td>').text(index).appendTo($tr);
				$('<td>').text(value).appendTo($tr);
				$tr.appendTo($table);
			});
		}

	} else if (typeof data == 'object') {
		//group by query!

		$table.removeClass('rawlog');
		$table.addClass('groupby');

		var $tr = $('<tr>');
		$('<th>').text('count').appendTo($tr);
		if (grouper == 'agent') // or ip?
			$('<th>').text('robot').appendTo($tr);
		$('<th align=left>').text(grouper).appendTo($tr);
		if (grouper == 'ip') // or ip2?
			$('<th>').text('host').appendTo($tr);
		$('<thead>').append($tr).appendTo($table);

		var $tbody = $('<tbody>');
		$.each(data,function(line,value) {
			var $tr = $('<tr>');
			$('<td>').attr('align','right').text(value).appendTo($tr);
			if (grouper ==  'agent') // or ip?
				$('<td>').appendTo($tr); //will be populated later!
			$('<td>').text(line).appendTo($tr);
			if (grouper == 'ip') { // or ip2?
				var hash = md5(line);
				$('<td>').attr('id',hash).text(line).appendTo($tr);
				if (line.match(/^([\da-f]+[:\.])+[\da-f]*\s*\,/)) {
					//geniune multiple IPs. so strip down to to first. its the real IP, followups are added on the end. 
					//66.249.64.109, 64.252.114.107
					line = line.replace(/, .*/,'');
				} else {
					//stirp any 'SQL injections'!
					// 1 waitfor delay '0:0:15' --, 146.70.55.214
					line = line.replace(/^.+, /,'');
				}
				$.getJSON("?host="+encodeURIComponent(line.replace(/, .*/,''))+"&json=1", function(value) {
					$('td#'+hash).text(value);
				});
			}
			$tr.appendTo($tbody);
		});
		$tbody.appendTo($table);

		if (grouper == 'agent') {
			//todo check string doenst contain potmel?
			populateRobotColumn(grouper);
		}
	}


	if (sortables_init && typeof sortables_init === 'function')
                sortables_init();
}

///////////////////////////////////////////////////////////////

		//string=robots.txt = has checked robots.txt
		//string=/potmel.php = seems to be a crawler
		//string=export.potmel.php = crawler, that ignores robots.txt

function populateRobotColumn(grouper) {
	var mygrouper = grouper; //for closuer!

	$.getJSON("?group="+mygrouper+"&hours=48&string=/robots.txt&json=groups", function(data_r) {
		markRobotColumn(data_r,'R ','lightgreen');

		$.getJSON("?group="+mygrouper+"&hours=72&string=/potmel.php&json=groups", function(data_p) {
			markRobotColumn(data_p,'C ','orange');

			$.getJSON("?group="+mygrouper+"&hours=72&string=/export.potmel.php&json=groups", function(data_e) {
				markRobotColumn(data_e,'S ','pink');

				$.getJSON("?group="+mygrouper+"&hours=72&string=MC0316%26distance%3D1%26groupby%3Dscenti&json=groups", function(data_e) {
					markRobotColumn(data_e,'M ','pink');
				});

			});
		});
	});
	markBotColumn('B ','yellow');
}


function markBotColumn(token,color) {
	$('div#stats').empty();
	var count = 0;
	var total = 0;
	$('table#results tbody tr').each(function(index) {
		var $tds = $(this).find('td');
		var value = $tds.eq(2).text();
		//fingerprint stolen from appearsToBePerson
		if (value.match(/(http|mailto|bot|Preview|Magnus|curl|python-requests|LWP:Siege|HeadlessChrome|InspectionTool|The Knowledge AI|GoogleOther)/)) {
			$tds.eq(1).append(token);
			if (color)
				$tds.css('backgroundColor',color);
			count++;
		}
		total++;
	});
	if (count)
		$('div#stats').append('Count:'+token+'  ='+count+'; ');
	if (total)
		$('div#stats').append('Total  ='+total+'; ');
}
function markRobotColumn(data,token,color) {
	var count = 0;
	$('table#results tbody tr').each(function(index) {
		var $tds = $(this).find('td');
		var value = $tds.eq(2).text();
		if (data[value]) {
			$tds.eq(1).append(token);
			if (color)
				$tds.css('backgroundColor',color);
			count++;
		}
	});
	if (count)
		$('div#stats').append('Count:'+token+'  ='+count+'; ');
}

///////////////////////////////////////////////////////////////

function dedup() {
	let last = null;
	$('table#results tbody tr').each(function() {
		var row = [];
		$(this).find('td').each(function(index) {
			var value = $(this).text();
			if (last && value === last[index])
				$(this).css('color','#ddd');
			row.push(value);
		});
		last = row;
	});
}


///////////////////////////////////////////////////////////////
//https://www.myersdaily.org/joseph/javascript/md5-text.html

function md5cycle(x, k) {
var a = x[0], b = x[1], c = x[2], d = x[3];

a = ff(a, b, c, d, k[0], 7, -680876936);
d = ff(d, a, b, c, k[1], 12, -389564586);
c = ff(c, d, a, b, k[2], 17,  606105819);
b = ff(b, c, d, a, k[3], 22, -1044525330);
a = ff(a, b, c, d, k[4], 7, -176418897);
d = ff(d, a, b, c, k[5], 12,  1200080426);
c = ff(c, d, a, b, k[6], 17, -1473231341);
b = ff(b, c, d, a, k[7], 22, -45705983);
a = ff(a, b, c, d, k[8], 7,  1770035416);
d = ff(d, a, b, c, k[9], 12, -1958414417);
c = ff(c, d, a, b, k[10], 17, -42063);
b = ff(b, c, d, a, k[11], 22, -1990404162);
a = ff(a, b, c, d, k[12], 7,  1804603682);
d = ff(d, a, b, c, k[13], 12, -40341101);
c = ff(c, d, a, b, k[14], 17, -1502002290);
b = ff(b, c, d, a, k[15], 22,  1236535329);

a = gg(a, b, c, d, k[1], 5, -165796510);
d = gg(d, a, b, c, k[6], 9, -1069501632);
c = gg(c, d, a, b, k[11], 14,  643717713);
b = gg(b, c, d, a, k[0], 20, -373897302);
a = gg(a, b, c, d, k[5], 5, -701558691);
d = gg(d, a, b, c, k[10], 9,  38016083);
c = gg(c, d, a, b, k[15], 14, -660478335);
b = gg(b, c, d, a, k[4], 20, -405537848);
a = gg(a, b, c, d, k[9], 5,  568446438);
d = gg(d, a, b, c, k[14], 9, -1019803690);
c = gg(c, d, a, b, k[3], 14, -187363961);
b = gg(b, c, d, a, k[8], 20,  1163531501);
a = gg(a, b, c, d, k[13], 5, -1444681467);
d = gg(d, a, b, c, k[2], 9, -51403784);
c = gg(c, d, a, b, k[7], 14,  1735328473);
b = gg(b, c, d, a, k[12], 20, -1926607734);

a = hh(a, b, c, d, k[5], 4, -378558);
d = hh(d, a, b, c, k[8], 11, -2022574463);
c = hh(c, d, a, b, k[11], 16,  1839030562);
b = hh(b, c, d, a, k[14], 23, -35309556);
a = hh(a, b, c, d, k[1], 4, -1530992060);
d = hh(d, a, b, c, k[4], 11,  1272893353);
c = hh(c, d, a, b, k[7], 16, -155497632);
b = hh(b, c, d, a, k[10], 23, -1094730640);
a = hh(a, b, c, d, k[13], 4,  681279174);
d = hh(d, a, b, c, k[0], 11, -358537222);
c = hh(c, d, a, b, k[3], 16, -722521979);
b = hh(b, c, d, a, k[6], 23,  76029189);
a = hh(a, b, c, d, k[9], 4, -640364487);
d = hh(d, a, b, c, k[12], 11, -421815835);
c = hh(c, d, a, b, k[15], 16,  530742520);
b = hh(b, c, d, a, k[2], 23, -995338651);

a = ii(a, b, c, d, k[0], 6, -198630844);
d = ii(d, a, b, c, k[7], 10,  1126891415);
c = ii(c, d, a, b, k[14], 15, -1416354905);
b = ii(b, c, d, a, k[5], 21, -57434055);
a = ii(a, b, c, d, k[12], 6,  1700485571);
d = ii(d, a, b, c, k[3], 10, -1894986606);
c = ii(c, d, a, b, k[10], 15, -1051523);
b = ii(b, c, d, a, k[1], 21, -2054922799);
a = ii(a, b, c, d, k[8], 6,  1873313359);
d = ii(d, a, b, c, k[15], 10, -30611744);
c = ii(c, d, a, b, k[6], 15, -1560198380);
b = ii(b, c, d, a, k[13], 21,  1309151649);
a = ii(a, b, c, d, k[4], 6, -145523070);
d = ii(d, a, b, c, k[11], 10, -1120210379);
c = ii(c, d, a, b, k[2], 15,  718787259);
b = ii(b, c, d, a, k[9], 21, -343485551);

x[0] = add32(a, x[0]);
x[1] = add32(b, x[1]);
x[2] = add32(c, x[2]);
x[3] = add32(d, x[3]);

}

function cmn(q, a, b, x, s, t) {
a = add32(add32(a, q), add32(x, t));
return add32((a << s) | (a >>> (32 - s)), b);
}

function ff(a, b, c, d, x, s, t) {
return cmn((b & c) | ((~b) & d), a, b, x, s, t);
}

function gg(a, b, c, d, x, s, t) {
return cmn((b & d) | (c & (~d)), a, b, x, s, t);
}

function hh(a, b, c, d, x, s, t) {
return cmn(b ^ c ^ d, a, b, x, s, t);
}

function ii(a, b, c, d, x, s, t) {
return cmn(c ^ (b | (~d)), a, b, x, s, t);
}

function md51(s) {
txt = '';
var n = s.length,
state = [1732584193, -271733879, -1732584194, 271733878], i;
for (i=64; i<=s.length; i+=64) {
md5cycle(state, md5blk(s.substring(i-64, i)));
}
s = s.substring(i-64);
var tail = [0,0,0,0, 0,0,0,0, 0,0,0,0, 0,0,0,0];
for (i=0; i<s.length; i++)
tail[i>>2] |= s.charCodeAt(i) << ((i%4) << 3);
tail[i>>2] |= 0x80 << ((i%4) << 3);
if (i > 55) {
md5cycle(state, tail);
for (i=0; i<16; i++) tail[i] = 0;
}
tail[14] = n*8;
md5cycle(state, tail);
return state;
}

/* there needs to be support for Unicode here,
 * unless we pretend that we can redefine the MD-5
 * algorithm for multi-byte characters (perhaps
 * by adding every four 16-bit characters and
 * shortening the sum to 32 bits). Otherwise
 * I suggest performing MD-5 as if every character
 * was two bytes--e.g., 0040 0025 = @%--but then
 * how will an ordinary MD-5 sum be matched?
 * There is no way to standardize text to something
 * like UTF-8 before transformation; speed cost is
 * utterly prohibitive. The JavaScript standard
 * itself needs to look at this: it should start
 * providing access to strings as preformed UTF-8
 * 8-bit unsigned value arrays.
 */
function md5blk(s) { /* I figured global was faster.   */
var md5blks = [], i; /* Andy King said do it this way. */
for (i=0; i<64; i+=4) {
md5blks[i>>2] = s.charCodeAt(i)
+ (s.charCodeAt(i+1) << 8)
+ (s.charCodeAt(i+2) << 16)
+ (s.charCodeAt(i+3) << 24);
}
return md5blks;
}

var hex_chr = '0123456789abcdef'.split('');

function rhex(n)
{
var s='', j=0;
for(; j<4; j++)
s += hex_chr[(n >> (j * 8 + 4)) & 0x0F]
+ hex_chr[(n >> (j * 8)) & 0x0F];
return s;
}

function hex(x) {
for (var i=0; i<x.length; i++)
x[i] = rhex(x[i]);
return x.join('');
}

function md5(s) {
return hex(md51(s));
}

/* this function is much faster,
so if possible we use it. Some IEs
are the only ones I know of that
need the idiotic second function,
generated by an if clause.  */

function add32(a, b) {
return (a + b) & 0xFFFFFFFF;
}

if (md5('hello') != '5d41402abc4b2a76b9719d911017c592') {
function add32(x, y) {
var lsw = (x & 0xFFFF) + (y & 0xFFFF),
msw = (x >> 16) + (y >> 16) + (lsw >> 16);
return (msw << 16) | (lsw & 0xFFFF);
}
}

</script>

<style>
table.rawlog tr td:nth-child(2),
table.rawlog tr td:nth-child(3),
table.rawlog tr td:nth-child(4),
table.rawlog tr td:nth-child(8),
table.rawlog tr td:nth-child(9),
table.rawlog tr td:nth-child(12)
{
	white-space:nowrap;
}


table.groupby tr td:nth-child(1)
{
        white-space:nowrap;
}

</style>

<pre>
B - ident as bot via UA
R - has fetched robots.txt
C - appears to be crawler (fetched a page only crawler would see)
S - appears to be crawler not honouring robots.txt (page only crawler see, and blocked in robots.txt)
M - Clicked around MC0316 - most likly a crawler! (was a common trait at one point)
</pre>
