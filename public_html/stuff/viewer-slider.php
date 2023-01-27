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
require_once('geograph/map.class.php');
require_once('geograph/mapmosaic.class.php');
init_session();

$smarty = new GeographPage;

$USER->mustHavePerm("basic");

$smarty->assign("responsive",1);
$smarty->display('_std_begin.tpl');

//$db = GeographDatabaseConnection(false);
//$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


#######################

$map=new GeographMap;
//standard 1px national map
$map->setOrigin(0,-10);
$map->setImageSize(900,1300);
$map->setScale(1);

$map->type_or_user = -10;

$blankurl=$map->getImageUrl();

#######################

//for the moment, just rely on the files already existing!

$base = "https://t0.geograph.org.uk/maps/detail/0/-10/detail_0_-10_900_1300_1_-2-y%s.png";

#######################

?>

<h2>By Taken Year - Geograph Coverage - Viewer</h2>

<form>
	Year: <input type=range name=year value=<? echo date('Y'); ?> min=1900 max=<? echo date('Y'); ?> style=width:800px><span class="txtyear"></span><br>
	Scale: <input type=range name=scale value=0.5 min=0.2 max=1.5 step=0.05 list=scales> &nbsp;

	Range: <input type=range name=range value=3 min=0 max=10><span id="txtrange"></span><br>
	<datalist id=scales>
		<option value=0.5></option>
		<option value=0.75></option>
		<option value=1></option>
		<option value=1.5></option>
	</datalist>
</form>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8/jquery.min.js"></script>
<script>
function updateYear() {
	var year = $('input[name=year]').val();
	$('.txtyear').text(year);

	var range = $('input[name=range]').val();
	$('#txtrange').text("+/- "+range+" years");

	for(y=<? echo date('Y'); ?>; y>=1900; y--) {
		var $ele = $('div#y'+y);
		if(y == year) {
			$ele.show().addClass('main').css('opacity',1);
		} else if ((diff = Math.abs(y-year)) <= range) {
			diff--; //gives bit extra range
			var opp = 1-(diff/range);
			$ele.show().removeClass('main').css('opacity',opp*0.8);
		} else {
			$ele.hide().removeClass('main');
		}
	}
}

function updateScale(event) {
	var scale = event.target.value;
	$('div.map').css('zoom',scale);
}

$(function() {
	updateYear(<? echo date('Y'); ?>);
	$('input[name=year]').on('input',updateYear);
	$('input[name=range]').on('input',updateYear);
	$('input[name=scale]').on('input',updateScale);
});
</script>

<div class=map style="position:relative; height:1300px;width:900px; zoom:0.5">
	<div>
		<img src="<? echo $blankurl; ?>"/>
	</div>
	<?
	for($t=date('Y'); $t>=1900; $t--) {
	       $displayYear = sprintf("%04d",$t);
		print "<div id=y$displayYear style=display:none><img loading=lazy src=".sprintf($base,$displayYear)."></div>\n";

	} ?>
</div>
<style>
	div.map div {
		position:absolute;top:0;left:0;
	}
	div.map img {
		image-rendering: -webkit-optimize-contrast; image-rendering: crisp-edges;
	}
	div.map div.main img {
		filter: drop-shadow(0px 0px 5px black);
	}
	div.map:hover div.main img {
                filter: drop-shadow(0px 0px 1px black);
        }

	input[type=range] {
		max-width:70vw;
	}
	/* resize the div and the images directly using vw, so that everything resizes together */
	div.map, div.map img {
		max-width: min(100vw,130vh);
		height:auto;
	}

	div.txtyear {
		position:fixed;
		left:0;
		bottom:0;
		background-color:white;
		padding:2px;
		z-index:1000;
	}
</style>

<div class="txtyear"></div>
<?




$smarty->display('_std_end.tpl');


