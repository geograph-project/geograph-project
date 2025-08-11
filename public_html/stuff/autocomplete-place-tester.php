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
<h2>Testing Place Autocomplete</h2>

<p>Note: Only use this page for testing searching for <b>placenames</b>. (while generally we support searching grid-reference, postcodes, lat/long, that doesn't really work in this demo.)

<form method=get onsubmit="return false" style="background-color:#eee;padding:10px;font-size:1.4em">
<input name="model" type=radio value="Fusion" id="mFusion"><label for="mFusion">Fusion</label>
<input name="model" type=radio value="Keystone" id="mKeystone" checked><label for="mKeystone">Keystone</label>
<input name="model" type=radio value="Nexus" id="mNexus"><label for="mNexus">Nexus</label>
<input name="model" type=radio value="Echo" id="mEcho"><label for="mEcho">Echo</label>
<input name="model" type=radio value="Cipher" id="mCipher"><label for="mCipher">Cipher</label>
<input name="model" type=radio value="Sieve" id="mSieve"><label for="mSieve">Sieve</label>
<br>
<input type="search" name="loc" value="" placeholder="(enter placename)" id="loc" size=50 style="font-size:1.1em"><br> <span id="placeMessage"></span>
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
    source: async function(request, response) {
        const term = request.term;
        if (term.length < 3) {
            response([]);
            return;
        }

        const model = $('input[name=model]:checked').val();
        let url = `https://api.geograph.org.uk/finder/places.json.php?q=${encodeURIComponent(term)}&new=1`;
        let fallbackUrl = null;

        // Determine the base URL and potential fallback URL
        switch (model) {
            case 'Nexus':
                url = `https://development.geograph.org.uk/finder/places.json.php?q=${encodeURIComponent(term)}&vector=1`;
                break;
            case 'Keystone':
                url = `https://development.geograph.org.uk/finder/places.json.php?q=${encodeURIComponent(term)}&vector=1&rerank=1`;
                break;
            case 'Cipher':
                url = `https://api.geograph.org.uk/finder/places.json.php?q=${encodeURIComponent(term)}`;
                break;
            case 'Sieve':
                url = `https://api.geograph.org.uk/finder/places.json.php?q=${encodeURIComponent(term)}&legacy=1`;
                break;
            case 'Fusion':
                // Fusion model uses the first URL and a specific fallback
                url = `https://api.geograph.org.uk/finder/places.json.php?q=${encodeURIComponent(term)}&new=1`;
                fallbackUrl = `https://development.geograph.org.uk/finder/places.json.php?q=${encodeURIComponent(term)}&vector=1&rerank=1&limit=15`;
                break;
        }

        let results = [];
        let found_str = 0;

        const fetchData = async (requestUrl) => {
            const data = await $.ajax({
                url: requestUrl,
                dataType: 'json',
                cache: true
            });
            return data;
        };

        try {
            // First API call
            const data = await fetchData(url);

            // Process the initial results
            if (data && data.items && data.items.length > 0) {
                const escapedVal = $.ui.autocomplete.escapeRegex(term);
                const re = new RegExp('(' + escapedVal + ')', 'gi');

                results = data.items.map(item => {
                    const label = item.full_name || item.name;
                    if (label.match(re)) {
                        found_str++;
                    }
                    let title = item.localities ?? '';
                    if (!title && item.reference_index !== undefined)
                        title = (item.reference_index == 2) ? 'Ireland' : 'Great Britain';
                    return {
                        value: (item.gridref || item.gr) + ' ' + label,
                        label: label,
                        gr: item.gridref || item.gr,
			title: title
                    };
                });
            }

            // Fallback logic for Fusion model
            if (fallbackUrl && results.length < 5 && found_str === 0) {
                const fallbackData = await fetchData(fallbackUrl);
                if (fallbackData && fallbackData.items) {
                    const fallbackResults = fallbackData.items.map(item => ({
                        value: item.gr + ' ' + item.name,
                        label: item.name,
                        gr: item.gr,
                        title: item.localities ?? ''
                    }));
                    results = results.concat(fallbackResults);
                }
            } else if (!data || !data.items || data.items.length < 1) {
		    $("#message").html("No places found matching '"+request.term+"'");
                    $("#placeMessage").show().html("No places found matching '"+request.term+"'");
                         $("#loc").autocomplete("close"); //close, it incase it open from another (when switch!)
                    setTimeout('$("#placeMessage").hide()',3500);
		//no return as want to still call response!
	    }

            // Append additional info if available
            if (data.query_info) {
                results.push({value: '', label: '', title: data.query_info});
            }
            if (data.copyright) {
                results.push({value: '', label: '', title: data.copyright});
            }

            response(results);
        } catch (e) {
            $("#placeMessage").show().html("An error occurred. Please try again.");
console.log(e);
            setTimeout(() => $("#placeMessage").hide(), 3500);
            response([]);
        }
    },

                select: function(event,ui) {
                        $("#loc").val(ui.item.value);
			if (typeof jumpLocation !== 'undefined')
	                        jumpLocation($("#loc").parent('form')[0]);
                        return false;
                }
        })
        .data( "autocomplete" )._renderItem = function( ul, item ) {
		var escapedVal = $.ui.autocomplete.escapeRegex( $("#loc").val() );
                var re=new RegExp('('+escapedVal+')','gi');
		if (item.gr && item.label.endsWith(item.gr)) item.gr = ''; //hide the duplicate gr, leave the big one - as it more important!
                if (!item.title) item.title = '';
                return $( "<li></li>" )
                        .data( "item.autocomplete", item )
                        .append( "<a>" + item.label.replace(re,'<b>$1</b>') + " <small> " + (item.gr||'') + " &middot; " + item.title.replace(re,'<b>$1</b>') + "</small></a>" )
                        .appendTo( ul );
        };

});

</script>
<style>

.ui-menu .ui-menu-item {
	padding-left: 2em;
    text-indent: -2em;
}

</style>
<?

	$smarty->display('_std_end.tpl');

