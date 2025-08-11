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


$USER->mustHavePerm("basic");

 customExpiresHeader(3600,false,true);

	$smarty->display('_std_begin.tpl');

?>
<h2>Testing Discussion Autocomplete</h2>

<p>Note: this page is to test searching <b>Discussions</b> - you dont have to accept the search suggestion(s), can just click Search to search the 'posts' text directly for your query.</p>

<form method=get action="/finder/discussions.php" style="background-color:#eee;padding:10px;font-size:1.4em" id="form1">
<input name="model" type=radio value="Keystone" id="mKeystone"><label for="mKeystone">Keystone</label>
<input name="model" type=radio value="Nexus" id="mNexus" checked><label for="mNexus">Nexus</label>
<input name="model" type=radio value="Cipher" id="mCipher"><label for="mCipher">Cipher</label>

[ <label><input type=checkbox name=auto checked>Search threads as type</label> ]
<br>
<input type="search" name="q" value="" placeholder="(enter search term)" id="loc" size=50 style="font-size:1.1em"> <input type=submit id=button1 value="Search Posts..." style="font-size:1.1em"><br>
 <span id="placeMessage"></span>
</form>

<form method=get action="/finder/discussions.php" style="background-color:#eee;padding:10px;font-size:1.4em;display:none;" id="form2">
	<a href="#" style="font-size:1.2em">View Thread</a><br><br>

	- or -<br><br>

	<input type=hidden name="thread_id">
	<input type=search name="q" value="" size=50 style="font-size:1.1em" placeholder="{enter keywords to search within thread}">
	<input type=submit id=button2 value="Search Posts..." style="font-size:1.1em">
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
			if (!$('input[name=auto]:checked').length) {
				return
			}

			if (request.term.length < 3) {
				response([]);
                                return;
                        }
			var model = $('input[name=model]:checked').val();
                        var url = "/finder/discussions.json.php?threads=1&q="+encodeURIComponent(request.term)+"&titleonly=1";

			if (model == 'Nexus') {
	                        url += "&vector=1";

			} else if (model == 'Keystone') {
	                        url += "&vector=1&rerank=1";
			}

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
						if(item.topic_title) {
							const dateObject = new Date(item.post_time * 1000);
							const formattedDate = dateObject.toLocaleDateString('en-GB');

							results.push({value:item.topic_id+' '+item.topic_title, label:item.topic_title, title:formattedDate+" by "+item.topic_poster_name});
						} else if(item.title) {
							const dateObject = new Date(item.day);
							const formattedDate = dateObject.toLocaleDateString('en-GB');
							results.push({value:item.topic_id+' '+item.title, label:item.title, title:formattedDate+" by "+item.name});
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
				$('#loc').val(ui.item.value);
				//form1 is no longer the search - it was just used to find a thread
				$('#button1').hide();
				//form2 is the one use can use to search!
				$('#form2').show();
				if (m = ui.item.value.match(/^(\d+) (.*)/)) {
					$('#form2 input[name=thread]').val(m[1])
					$('#form2 a').attr('href',"/discuss/index.php?action=vthread&topic="+m[1]).text("View Thread "+m[2]);
				}
			}
			return true;
                }
        })
        .data( "autocomplete" )._renderItem = function( ul, item ) {
                var re=new RegExp('('+$("#loc").val()+')','gi');
                if (!item.title) item.title = '';
                return $( "<li></li>" )
                        .data( "item.autocomplete", item )
                        .append( "<a title='"+item.value+"'>" + item.label.replace(re,'<b>$1</b>') + (item.title?(" <small>&middot; " + item.title.replace(re,'<b>$1</b>') + "</small></a>"):'') )
                        .appendTo( ul );
        };

});







</script>
<?

	$smarty->display('_std_end.tpl');
