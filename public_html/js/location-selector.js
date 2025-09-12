//How to use...

// <input type="search" name="loc" value="{$loc|escape:'html'}" placeholder="(enter coordinate/placename/postcode)" id="loc" size=50>
// <a href="#" onclick="getLocation();return false">Find my Location</a><br>

// the id of the input needs to be 'loc' but name can be whatever!

// <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
// <link type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.22/themes/ui-lightness/jquery-ui.css" rel="stylesheet"/>
// <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.22/jquery-ui.min.js"></script>

//define some regions, and initial selection for the dropdown
//var regions = {$regions|default:'null'};

//if want can create a function to use as a callback.
//function jumpLocation(form) {
//	form.submit();
//}

var regions = null;

function getLocation(callback) {
		$.getScript( "https://m.geograph.org.uk/js/jquery.geolocation.js" ).done(function( script, textStatus ) {
			$.geolocation.get({success: function(position) {
				$('#loc').val(position.coords.latitude + "," + position.coords.longitude);
				let gridref;
				if (typeof wgs2gridref !== 'undefined' && typeof GT_OSGB !== 'undefined') {
					gridref = wgs2gridref(position.coords.latitude,position.coords.longitude,8);
					if (gridref)
						$('#loc').val(gridref + " / from " + position.coords.latitude + "," + position.coords.longitude);
				}
				if (typeof callback !== 'undefined')
					callback(position.coords.latitude, position.coords.longitude, gridref);

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
                        $("#loc").val(ui.item.value);
			if (typeof jumpLocation !== 'undefined')
	                        jumpLocation($("#loc").parent('form')[0]);
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





