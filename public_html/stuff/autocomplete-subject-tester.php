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

 customExpiresHeader(3600,false,true);

$db = GeographDatabaseConnection(true);

$smarty->assign('responsive',true);

	$smarty->display('_std_begin.tpl',md5($_SERVER['PHP_SELF']));

$subjects = $db->getCol("select subject from subjects order by subject");

$subjects = array_map('htmlentities',$subjects); //just to make sure!

?>
<h2>Testing Subjects 'dropdown'</h2>

        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
                <link href="/js/select2-3.3.2/select2.css" rel="stylesheet"/>
                <script src="/js/select2-3.3.2/select2.min.js"></script>

<form>

A. Plain:<br>
<select>
	<option>Select Subject</option>
	<? foreach($subjects as $subject) {
		printf('<option>%s</option>', $subject);
	} ?>
</select><br>
<br>
B. Combined with search:<br>
<select name=subject id="s2">
	<option>Select Subject</option>
	<? foreach($subjects as $subject) {
		printf('<option>%s</option>', $subject);
	} ?>
</select>
<script>
$(function() {
	$('select#s2').select2();
});
</script>
<br><br>

C. Search (advanced model):<br>
<input type="search" name="loc" value="" placeholder="(search for subject)" id="loc" size=50 style="max-width:100%"><br> <span id="placeMessage"></span>


<br>
D. Simple Input with search:<br>
<input type=search name=subject list=subjects placeholder="(select subject)">

<br><br>
<button>Continue</button> (doesnt go anywhere!)

</form>

<datalist id=subjects>
        <? foreach($subjects as $subject) {
                printf('<option>%s</option>', $subject);
        } ?>
</datalist>

<script>

const subjectInput = document.querySelector('input[name="subject"]');
const datalist = document.querySelector('#subjects');
subjectInput.addEventListener('blur', function() {
  const options = datalist.querySelectorAll('option');
  let isValid = false;
  for (const option of options) {
    if (option.value === this.value) {
      isValid = true;
      break;
    }
  }
  if (!isValid) {
    this.setCustomValidity('Please select a valid subject from the list.');
  } else {
    this.setCustomValidity('');
  }
});
</script>


<div style="min-height:400px">
</div>

<link type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.22/themes/ui-lightness/jquery-ui.css" rel="stylesheet"/>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.22/jquery-ui.min.js"></script>

<script>

$(function () {
	var model = 'Keystone';
	var filter = 'subject';

        $( "#loc" ).autocomplete({
                minLength: 3,
                source: function( request, response ) {

			if (request.term.length < 3) {
				response([]);
                                return;
                        }

                        var url = "/tags/tags.json.php?q="+encodeURIComponent(request.term);

			if (model == 'Nexus' || model == 'Keystone') {
				url += "&vector=1";
				if (model == 'Keystone') {
					url += "&rerank=1";
				}
			}

			if (filter) { //omitting filter, would give different results, as the defaults are currently different!
				url += "&mode="+filter; // mode=tag will helpfully be ignored by non-vector search while still excluding top/subject!
				//need mode=all to specifically get all on original (default 'ranked' excludes subject), but also helpfully ignored with vector!
			}

                        $.ajax({
                                url: url,
                                dataType: 'jsonp',
                                jsonpCallback: 'serveCallback',
                                cache: true,
                                success: function(data) {

                                        if (!data || data.length < 1) {
                                                $("#message").html("No tags found matching '"+request.term+"'");
                                                $("#placeMessage").show().html("No tags found matching '"+request.term+"'");
					            $("#loc").autocomplete("close"); //close, it incase it open from another (when switch!)
                                                setTimeout('$("#placeMessage").hide()',3500);
                                                return;
                                        }
                                        var results = [];
                                        $.each(data, function(i,item){
						//if (item.prefix)
						//	item.tag = item.prefix+':'+item.tag;
						results.push({value:item.tag, label:item.tag});
                                        });
					if (data.query_info)
	                                        results.push({value:'',label:'',title:data.query_info});
					if (data.copyright)
	                                        results.push({value:'',label:'',title:data.copyright});
                                        response(results);
                                }
                        });
                },
                select: function(event,ui) {
                        $("#loc").val(ui.item.value);
                        return false;
                }
        })
        .data( "autocomplete" )._renderItem = function( ul, item ) {
                var re=new RegExp('('+$("#loc").val()+')','gi');
                if (!item.title) item.title = '';
		//if (m = item.label.match(/^subject:(.*)/))
		//	item.title = "- image is marked as primary subject of "+m[1];
		//if (m = item.label.match(/^top:(.*)/))
		//	item.title = "- image has the general context";
                return $( "<li></li>" )
                        .data( "item.autocomplete", item )
                        .append( "<a>" + item.label.replace(re,'<b>$1</b>') + " <small>" + item.title.replace(re,'<b>$1</b>') + "</small></a>" )
                        .appendTo( ul );
        };

});

</script>
<?

	$smarty->display('_std_end.tpl');
