
<form method=get name=locForm>
	Optional Location Focus: <input type="search" name="loc" value="{$loc|escape:'html'}" placeholder="(enter coordinate/placename/postcode)" id="loc" size=50>
	<input type=submit value="Go&gt;">
	<a href="#" onclick="getLocation();return false">Find my Location</a><br>
	<small>Search for a place to focus the results on the location, so that if possible will highlight local images first</small>
	{if $loc}
		<br>Showing images nearest <b>{$loc|escape:'html'}</b>: 
		{if $region}(note: images <i>aren't guaranteed</i> to be {if $label}<a href="/curated/sample.php?label={$label|escape:'html'}&amp;region={$region|escape:'html'}">{/if}within the region</a>){/if}
	{/if}
</form>


<link type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.22/themes/ui-lightness/jquery-ui.css" rel="stylesheet"/>

<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.22/jquery-ui.min.js"></script>

<script type="text/javascript">
//<![CDATA[

var regions = {$regions|default:'null'};

{literal}

function getLocation() {
		$.getScript( "https://m.geograph.org.uk/js/jquery.geolocation.js" ).done(function( script, textStatus ) {
			$.geolocation.get({success: function(position) {
				$('#loc').val(position.coords.latitude + "," + position.coords.longitude);
			}, fail:function() {
				alert('Unable to load location');
			}});
		});
}

$(function () {

        $( "#loc" ).autocomplete({
                minLength: 0,
                source: function( request, response ) {

			if (request.term.length <2 && regions) {
				var results = [];			
				$.each(regions, function(i,item){
                                        results.push({value:item,label:item});
                                });

				response(results);
				return;
			}
			if (request.term.length < 2) {
				response([]);
                                return;
                        }


                        var url = "https://www.geograph.org.uk/finder/places.json.php?q="+encodeURIComponent(request.term)+"&new=1";

                        $.ajax({
                                url: url,
                                dataType: 'jsonp',
                                jsonpCallback: 'serveCallback',
                                cache: true,
                                success: function(data) {

                                        if (!data || !data.items || data.items.length < 1) {
                                                $("#message").html("No places found matching '"+request.term+"'");
                                                $("#placeMessage").show().html("No places found matching '"+request.term+"'");
                                                setTimeout('$("#placeMessage").hide()',3500);
                                                return;
                                        }
                                        var results = [];
                                        $.each(data.items, function(i,item){
                                                results.push({value:item.gr+' '+item.name,label:item.name,gr:item.gr,title:item.localities});
                                        });
                                        results.push({value:'',label:'',title:data.query_info});
                                        results.push({value:'',label:'',title:data.copyright});
                                        response(results);
                                }
                        });
                },
                select: function(event,ui) {
                        document.locForm.elements['loc'].value = ui.item.value;
                        jumpLocation(document.locForm);
                        return false;
                }
        })
        .data( "autocomplete" )._renderItem = function( ul, item ) {
                var re=new RegExp('('+$("#loc").val()+')','gi');
                if (!item.title) item.title = '';
                return $( "<li></li>" )
                        .data( "item.autocomplete", item )
                        .append( "<a>" + item.label.replace(re,'<b>$1</b>') + " <small> " + (item.gr||'') + "<br>" + item.title.replace(re,'<b>$1</b>') + "</small></a>" )
                        .appendTo( ul );
        };

	$( "#loc" ).focus(function () {
		if ($(this).val() == '' && regions)
			$(this).autocomplete( "search", "" ); //need to trigger the empty search
	});
});


function jumpLocation(form) {
	form.submit();
        var value = form.elements['loc'].value;
}

</script>{/literal}



