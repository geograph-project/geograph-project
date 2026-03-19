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
                                        var results = [];

                                        if (!data || !data.items || data.items.length < 1) {
                                                $("#message").html("No places found matching '"+request.term+"'");
                                                $("#placeMessage").show().html("No places found matching '"+request.term+"'");
                                                setTimeout('$("#placeMessage").hide()',3500);

    results.push({
        label: "&#128269; Search using a map...",
        value: "",
        isMapLink: true, // Custom flag
        searchTerm: request.term
    });

                                        response(results);

                                                return;
                                        }

// Inject the "Open on Map" option at the top
    results.push({
        label: "&#128269; View these results on a map...",
        value: "",
        isMapLink: true, // Custom flag
        searchTerm: request.term
    });

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

        if (ui.item.isMapLink) {
            // Prevent the input from being filled with the label
            event.preventDefault();
            
            // Launch your new map tool!
            openPlaceSearch(ui.item.searchTerm, function(name, gr, lat, lng) {
                // This is the callback from your modal
                // You can update your main page fields here
                $("#loc").val(gr + " " + name);
                console.log("Selected from map:", name, gr);
            });
            
            return false;
        }

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




//has no external dependancies, not even jquery

function openPlaceSearch(query, callback) {
    // Reference the top-level parent window (but CAN be self if not a iframe!)
    var targetWindow = window.parent;
    var targetDoc = targetWindow.document;

    // 1. Create the Backdrop on the Parent Document
    var backdrop = targetDoc.createElement('div');
    backdrop.style.cssText = "position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.2); z-index:100000; display:flex; align-items:center; justify-content:center; backdrop-filter:blur(4px);";

    // 2. Create the Modal Container
    var modal = targetDoc.createElement('div');
    modal.style.cssText = "width:90vw; height:90vh; background:white; border-radius:8px; position:relative; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,0.5);";
	if (targetWindow.innerWidth < 500)
		modal.style.width = '98vw'; //let the iframe grow bigger on small screens

    // 3. Create the Iframe
    var iframe = targetDoc.createElement('iframe');
    // Ensure the path to search_map.html is absolute or relative to the PARENT
    iframe.src = "/finder/place.php?inner&loc=" + encodeURIComponent(query);
    iframe.style.cssText = "width:100%; height:100%; border:none;";

    // 4. Close Button
    var closeBtn = targetDoc.createElement('button');
    closeBtn.innerHTML = "&times;";
    closeBtn.style.cssText = "position:absolute; top:10px; right:10px; z-index:100001; background:pink; border:1px solid #ccc; border-radius:50%; width:30px; height:30px; cursor:pointer; font-size:20px;";

    modal.appendChild(closeBtn);
    modal.appendChild(iframe);
    backdrop.appendChild(modal);
    targetDoc.body.appendChild(backdrop);

    // 5. Cleanup Function
    var destroyModal = function() {
        targetWindow.removeEventListener('message', messageHandler);
        targetDoc.body.removeChild(backdrop);
    };

    // 6. Listener on the Parent Window
    var messageHandler = function(event) {
        // Important: event.data check
        if (event.data && event.data.type === 'PLACE_SELECTED') {
            destroyModal();
            // Fire the original callback in our local scope
            callback(event.data.name, event.data.gr, event.data.lat, event.data.lng);
        }
    };

    targetWindow.addEventListener('message', messageHandler);

    closeBtn.onclick = destroyModal;
    backdrop.onclick = function(e) { if (e.target === backdrop) destroyModal(); };

	// Inside openPlaceSearch on the Parent Page
	if (targetWindow.visualViewport) {
	    var resizeHandler = function() {
	        var vv = targetWindow.visualViewport;

	        // 1. Calculate the real visible height
	        // We subtract a little (e.g., 20px) if we want the modal to not touch the keyboard
	        var availableHeight = vv.height;

	        // 2. Adjust the backdrop or modal container
	        // If we want the modal to remain centered in the VISIBLE area:
	        backdrop.style.height = availableHeight + "px";
	        backdrop.style.top = vv.offsetTop + "px";

	        // 3. Update the modal height so it doesn't get cut off
	        // We make the modal 90% of the CURRENT visual height
	        modal.style.height = (availableHeight * 0.96) + "px";

	        // 4. Notify the iframe to redraw the map
	        if (iframe.contentWindow) {
	            iframe.contentWindow.postMessage({ type: 'VIEWPORT_RESIZE' }, '*');
	        }
	    };

	    targetWindow.visualViewport.addEventListener('resize', resizeHandler);
	    targetWindow.visualViewport.addEventListener('scroll', resizeHandler);

	    // Initial call to set size
	    resizeHandler();
	}

}

// How you use it:
// openPlaceSearch('Brighton', function(name, gr, lat, lng) {
//    console.log("Selected:", name);
//    loadImages(name);
// });
