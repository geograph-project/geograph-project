<?php
/**
 * $Project: GeoGraph $
 * $Id: glossary.php 2960 2007-01-15 14:33:27Z barry $
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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

$smarty->assign('responsive',1);
$smarty->assign('page_title','Image Randomizer');
$smarty->display('_std_begin.tpl',md5('1'.$_SERVER['PHP_SELF']));
?>

<h2><a href=\"/explore/\">Explore</a> :: Image Randomizer</h2>

<p>Can also <a href=\"/search.php?orderby=random&displayclass=black&do=1\">get random image slideshow</a> or just <a href=\"/stuff/browse-random.php\">jump to random square</a></p>

<form name=theForm>
	<div>
		Input: <input type="text" name="textinput" placeholder="type something here" id="textinput" size="60" onkeyup="return myPress(this.form)"> <input type=button value="Random!" onclick="this.form.textinput.value = Math.random();myPress(this.form)"> <input type=button value="Now!" onclick="this.form.textinput.value = (new Date());myPress(this.form)"> <input type=button value="Today!" onclick="this.form.textinput.value = (new Date()).toDateString();myPress(this.form)">
	</div>
</form>
<br>
<div id="results">
	This simple app just selects an image based on what enter in box above. Images are selected using a hashing function, so while not really random, the images chosen are pretty much arbitrary. If unsure what to type click one of the buttons to get a changing value. 
</div>

<style>
form[name=theForm] {
	background-color:#eee;
	max-width:758px;
	padding:4px;
}
form[name=theForm] input {
	max-width:95%;
}
#results {
  background-color:black;
  padding:4px;
  border-radius:10px;
  max-width:680px;
  text-align:center;
  color:white;
}
#results img {
  max-width: 100%;
}
#results a {
  color:cyan;
}
</style>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script>

var max_id = 10000;
var endpoint = "https://api.geograph.org.uk/api-facet.php";

var timer = null;
function myPress(that) {
	if (timer)
		clearTimeout(timer)
	timer = setTimeout(function() {
		updateValue(that.textinput.value);
	}, 250);
}
function updateValue(value) {
    var crc = simpleHash(value);
//    var gid = crc % max_id;
    var gid = Math.floor((crc / 0xFFFFFFFF) * (max_id + 1)); //modulus tends to shuffle a lot!

  var query = {
     range: gid+","+gid,
     limit: 1,
     select: 'realname,title,grid_reference,user_id,takenday,hash',
     a: 1
  }

  $.getJSON(endpoint, query, function(data) {
      if (data && data.matches) {
          var ele = $('#results').empty();

          var image = data.matches[0];
          image.gridimage_id = image.id;
          image.attrs.thumbnail = getGeographUrl(image.id, image.attrs.hash, 'small');
          image.attrs.full = getGeographUrl(image.id, image.attrs.hash, 'full');

  ele.append('<a href="/photo/'+image.gridimage_id+'" target="_blank"><img src="'+image.attrs.full+'"/></a>');
  ele.append('<p><a href="/photo/'+image.gridimage_id+'" target="_blank">'+image.attrs.title+'</a> by <a href="/profile/'+image.attrs.user_id+'">'+image.attrs.realname+'</a></p>');
  ele.append('<p>For <a href="/gridref/'+image.attrs.grid_reference+'" target="_blank">'+image.attrs.grid_reference+'</a>, taken '+space_date(image.attrs.takenday)+'</p>');

      }
    }
  );

}

$(function() {
    $.getJSON(endpoint+"?rank=2&group=one&limit=1&a=1&select=1+as+one%2Cmax%28id%29+as+mx",function(data) {
        if (data && data.matches) {
              var first = data.matches.pop();
              max_id = parseInt(first.attrs.mx,10);
              $('#results').append("<p>Images available = "+ max_id+"</p>" );
        }
    });
});


function getGeographUrl(gridimage_id, hash, size) {

        yz=zeroFill(Math.floor(gridimage_id/1000000),2);
        ab=zeroFill(Math.floor((gridimage_id%1000000)/10000),2);
        cd=zeroFill(Math.floor((gridimage_id%10000)/100),2);
        abcdef=zeroFill(gridimage_id,6);

        if (yz == '00') {
                fullpath="/photos/"+ab+"/"+cd+"/"+abcdef+"_"+hash;
        } else {
                fullpath="/geophotos/"+yz+"/"+ab+"/"+cd+"/"+abcdef+"_"+hash;
        }

        switch(size) {
                case 'full': return "https://s0.geograph.org.uk"+fullpath+".jpg"; break;
                case 'med': return "https://s"+(gridimage_id%4)+".geograph.org.uk"+fullpath+"_213x160.jpg"; break;
                case 'small':
                default: return "https://s"+(gridimage_id%4)+".geograph.org.uk"+fullpath+"_120x120.jpg";
        }
}

function zeroFill(number, width) {
        width -= number.toString().length;
        if (width > 0) {
                return new Array(width + (/\./.test(number)?2:1)).join('0') + number;
        }
        return number + "";
}

function space_date(datestr) {
    if (datestr && datestr.length == 8)
       return datestr.substring(0,4)+'-'+datestr.substring(4,6)+'-'+datestr.substring(6,8);
    return datestr;
}




function simpleHash(str) {
  let hash = 0;
  for (let i = 0, len = str.length; i < len; i++) {
    const chr = str.charCodeAt(i);
    hash = ((hash << 5) - hash) + chr;
    hash |= 0; // Convert to 32bit integer
  }
  // Ensure the hash is always positive (unsigned 32-bit equivalent);
  return (hash >>> 0); // Unsigned right shift by 0
}
</script>


<?
$smarty->display('_std_end.tpl');

