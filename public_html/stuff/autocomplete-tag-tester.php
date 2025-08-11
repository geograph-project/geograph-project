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

	$smarty->display('_std_begin.tpl');

?>
<h2>Testing Tag Autocomplete</h2>

<form method=get onsubmit="return false" style="background-color:#eee;padding:10px;font-size:1.4em">
<fieldset>
<legend>Matching Model</legend>
<input name="model" type=radio value="Keystone" id="mKeystone" checked><label for="mKeystone">Keystone</label>
<input name="model" type=radio value="Nexus" id="mNexus"><label for="mNexus">Nexus</label>
<input name="model" type=radio value="Echo" id="mEcho"><label for="mEcho">Echo</label>
</fieldset>
<fieldset>
<legend>Filter</legend>
<input name="filter" type=radio value="top" id="fContext"><label for="fContext">Context</label>
<input name="filter" type=radio value="subject" id="fSubject" checked><label for="fSubject">Subject</label>
<input name="filter" type=radio value="tag" id="fFreeform"><label for="fFreeform">Freeform Tags</label>
</fieldset>
<br>
<input type="search" name="loc" value="" placeholder="(enter tag)" id="loc" size=50 style="font-size:1.1em"><br> <span id="placeMessage"></span>
</form>
<div style="min-height:400px">
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>

<link type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.22/themes/ui-lightness/jquery-ui.css" rel="stylesheet"/>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.22/jquery-ui.min.js"></script>

<script>

$(function () {

    // Handle radio button changes
    $('input[name=model], input[name=filter]').on('change', function() {
        // Get the current value from the input field
        var currentQuery = $("#loc").val();

        // Check if the current query meets the minLength requirement
        if (currentQuery.length >= 3) {
            // Trigger a search with the current query to re-open the dropdown
 //           $("#loc").autocomplete("search", currentQuery);
		$("#loc").autocomplete("search")
        } else {
            // Close the autocomplete if the query is too short
            $("#loc").autocomplete("close");
        }
    });

        $( "#loc" ).autocomplete({
                minLength: 3,
                source: function( request, response ) {

			if (request.term.length < 3) {
				response([]);
                                return;
                        }
			var model = $('input[name=model]:checked').val();
			var filter = $('input[name=filter]:checked').val();
                        var url = "/tags/tags.json.php?q="+encodeURIComponent(request.term);

			if (model == 'Nexus' || model == 'Keystone') {
				url += "&vector=1";
				if (model == 'Keystone') {
					url += "&rerank=1";
				}
			}

			if (filter) {
				url += "&mode="+filter; // mode=tag will helpfully be ignored by non-vector search
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
						if (item.prefix)
							item.tag = item.prefix+':'+item.tag;
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
                return $( "<li></li>" )
                        .data( "item.autocomplete", item )
                        .append( "<a>" + item.label.replace(re,'<b>$1</b>') + " <small>" + item.title.replace(re,'<b>$1</b>') + "</small></a>" )
                        .appendTo( ul );
        };

});







</script>
<?

	$smarty->display('_std_end.tpl');
