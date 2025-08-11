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
<h2>Testing Documentation Autocomplete</h2>

<p>Note: Only use this page for testing searching for <b>Documentation</b>.</p>

<form method=get onsubmit="return false" style="background-color:#eee;padding:10px;font-size:1.4em">
<input name="model" type=radio value="Keystone" id="mKeystone" checked><label for="mKeystone">Keystone</label>
<input name="model" type=radio value="Nexus" id="mNexus"><label for="mNexus">Nexus</label>
<input name="model" type=radio value="Echo" id="mEcho"><label for="mEcho">Echo</label>
<input name="model" type=radio value="Cipher" id="mCipher"><label for="mCipher">Cipher</label>
<input name="model" type=radio value="Sieve" id="mSieve"><label for="mSieve">Sieve</label>
<br>
<input type="search" name="loc" value="" placeholder="(enter search term)" id="loc" size=50 style="font-size:1.1em"><br> <span id="placeMessage"></span>
</form>
<div style="min-height:400px">
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>

<link type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.22/themes/ui-lightness/jquery-ui.css" rel="stylesheet"/>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.22/jquery-ui.min.js"></script>

<script>

$(function () {

    // Handle radio button changes
    $('input[name=model]').on('change', function() {
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
                        var url = "/content/docs.json.php?q="+encodeURIComponent(request.term);
/*
			if (model == 'Nexus') {
	                        var url = "https://development.geograph.org.uk/finder/places.json.php?q="+encodeURIComponent(request.term)+"&vector=1";

			} else if (model == 'Keystone') {
	                        var url = "https://development.geograph.org.uk/finder/places.json.php?q="+encodeURIComponent(request.term)+"&vector=1&rerank=1";

			} else if (model == 'Cipher') {
	                        var url = "https://api.geograph.org.uk/finder/places.json.php?q="+encodeURIComponent(request.term)+"";

			} else if (model == 'Sieve') {
	                        var url = "https://api.geograph.org.uk/finder/places.json.php?q="+encodeURIComponent(request.term)+"&legacy=1";
			}
*/
                        $.ajax({
                                url: url,
                                dataType: 'jsonp',
                                jsonpCallback: 'serveCallback',
                                cache: true,
                                success: function(data) {

                                        if (!data || data.length < 1) {
                                                $("#message").html("No documents found matching '"+request.term+"'");
                                                $("#placeMessage").show().html("No documents found matching '"+request.term+"'");
					            $("#loc").autocomplete("close");
                                                setTimeout('$("#placeMessage").hide()',3500);
                                                return;
                                        }
                                        var results = [];
                                        $.each(data, function(i,item){
						if(item.title) {
							results.push({value:item.url, label:item.title, title:item.extract});
						} else if (item.query_info) {
							results.push({value:'',label:'',title:item.query_info});
						}
                                        });
                                        response(results);
                                }
                        });
                },
                select: function(event,ui) {
			if (ui.item.value) {
				window.location.href = ui.item.value;
			}
			return false;
                }
        })
        .data( "autocomplete" )._renderItem = function( ul, item ) {
                var re=new RegExp('('+$("#loc").val()+')','gi');
                if (!item.title) item.title = '';
                return $( "<li></li>" )
                        .data( "item.autocomplete", item )
                        .append( "<a>" + item.label.replace(re,'<b>$1</b>') + "<br><small>" + item.title.replace(re,'<b>$1</b>') + "</small></a>" )
                        .appendTo( ul );
        };

});







</script>
<?

	$smarty->display('_std_end.tpl');
