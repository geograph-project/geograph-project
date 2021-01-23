/**
     * Guiders are created with guider.createGuider({settings}).
     *
     * You can show a guider with the .show() method immediately
     * after creating it, or with guider.show(id) and the guider's id.
     *
     * guider.next() will advance to the next guider, and
     * guider.hideAll() will hide all guiders.
     *
     * By default, a button named "Next" will have guider.next as
     * its onclick handler.  A button named "Close" will have
     * its onclick handler set to guider.hideAll.  onclick handlers
     * can be customized too.
     */

$(function() {

    guiders._wireEscape = function() {};  //these trample on the events setup by the application. 
    guiders._unWireEscape = function() {}; 
   
    guiders._defaultSettings.buttons = [{name: "Close"},{name: "Next"}]; //add an automatic next button
    guiders._defaultSettings.xButton = true;
    guiders._defaultSettings.overlay = true;
    guiders._defaultSettings.isHashable = false; //can never be used as we will have changed the hash already 

    guiders.createGuider({
      title: "Welcome to our quick guided tour of the Geograph Browser",
      id: "g_welcome",
      next: "g_searchbox",
      description: "This demo will guide you through some of the major features of the application. At various points in the tour, you can actually interact with the application to try out the feature described.<br/><br/>"+ 

"Jump direct to:<ul>"+
"<li><a href=\"javascript:void(guiders.hideAll(true).show('g_searchbox'))\">General Introduction</a></li>"+
"<li><a href=\"javascript:void(guiders.hideAll(true).show('g_filterbar'))\">Filter Bar</a></li>"+
"<li><a href=\"javascript:void(guiders.hideAll(true).show('g_content'))\">Content Area</a></li>"+
"<li><a href=\"javascript:void(guiders.hideAll(true).show('g_sidebar2'))\">Attribute Lists</a></li>"+
"<li><a href=\"javascript:void(guiders.hideAll(true).show('g_toggler'))\">Order By / Sample By</a></li>"+
"<li><a href=\"javascript:void(guiders.hideAll(true).show('g_map'))\">Map View</a></li>"+
"<li><a href=\"javascript:void(guiders.hideAll(true).show('g_dateslider'))\">Date Slider View</a></li>"+
"<li><a href=\"javascript:void(guiders.hideAll(true).show('g_historydiv'))\">History Window</a></li>"+

"</ul> or Click Next to continue though the general introduction...<br/><br/><b>Tip: use left/right cursor keys to move around in the tour</b>", 
      onShow: function() {
         var display = $('#display').val();
         $('#g_welcome .guider_description').removeClass('welcome_highlight');  
         if (display == 'map') {
            $('#g_welcome .guider_description a[href*="g_map"]').addClass('welcome_highlight');
         } else if (display == 'date_slider') {
            $('#g_welcome .guider_description a[href*="g_dateslider"]').addClass('welcome_highlight');
         } else if ($('#historydiv:visible').length) {
            $('#g_welcome .guider_description a[href*="g_historydiv"]').addClass('welcome_highlight');
         }
      }
    });

    guiders.createGuider({
      attachTo: "#q", highlight: "#keywordsdiv",
      position: 6,
      title: "The Keywords search box",
      id: "g_searchbox",
      next: "g_locationbox",
      description: "Here you can enter a keyword search, eg 'beach'. You dont need to enter any keywords to use the application, but its useful to quickly narrow the images. You can change the keywords at any time while browsing in this tool, for quick updates.<br/><br/>Tip: Use the down arrow to access a menu so can choose what fields the keywords you enter will match."
    });

    guiders.createGuider({
      attachTo: "#location", highlight: "#neardiv",
      position: 6,
      title: "The Location search box",
      id: "g_locationbox",
      next: "g_modedropdown",
      description: "Enter a placename, grid-reference or postcode here, to limit results to near a specific location. Once you have entered a location will be able to choose a radius - how far you want to search. Again its optional, dont need to enter anything here, but can of course add a location filter later to narrow your results."
    });

    guiders.createGuider({
      attachTo: "#display", highlight: "#display",
      position: 5,
      title: "Mode Selector",
      id: "g_modedropdown",
      next: "g_attributes",
      description: "Use this dropdown to choose a different display method, such as a listing format with more details or even plot the thumbnails on a map. We will explore these functions later in the demo."
    });

    guiders.createGuider({
      attachTo: "#sidebar", highlight: "#sidebar",
      position: 3,
      title: "Attribute Lists",
      id: "g_attributes",
      next: "g_filterbar",
      description: "Here we see the list of attributes for the currently selected images. They are broken down into groups, and show the top 10 most popular attributes. From here can click one of the attributes that will convert it to a filter - which will restrict what images are currently showing. We will explore the features here more fully later.<br/><br/>Note: The counts include all images matching the filter(s), not just the images visible on the current page"
    });


    guiders.createGuider({
      attachTo: "#filterbar", highlight: "#filterbar",
      position: 6,
      title: "Filter Bar",
      id: "g_filterbar",
      next: "g_content",
      description: "(<i>The filter bar is rather complicated, but its one of the more powerful features, so worth spending a few moments to get to grips with</i>)<br/><br/>This bar shows the currently active filters you have selected from the Attribute lists. The filters here restrict what images are shown in the content area, as well as the figures shown the attribute lists - ie everything updates to only count images matching all your active filters.<br/><br/> Here we have clicked 'Isle of Man' (from the Country group), so its now become an <b>Active Filter</b>.<br/><br/> Can click the <b><font style=\"color:red\">&#215;</font> to remove the filter</b>. Or <b>untick it to make it inactive</b>, an inactive filter is still listed, but has no effect. This is useful to be able to re-enable it later for quick before/after comparisons. You can also <b>click the filter itself, to toggle Positive/Negative</b>. A negative filter excludes images matching the particular filter.<br/><br/> <b><big>Try these features now on this filter!</big></b> <br/>(If you delete the filter - <a href=\"javascript:void(addFilter('country','Isle of Man','Isle of Man'))\">click here</a> - to add it back again)<br><br/><b>Filter Key</b>:<ul><li><span class=\"plus\"><input type=\"checkbox\" checked/> Positive</span> <span class=\"minus\"><input type=\"checkbox\" checked/> Negative</span> <span class=\"disabled\"><input type=\"checkbox\"/> Disabled</span></li></ul>",
      onShow: function () {
           addFilter('country','Isle of Man','Isle of Man');
      },
      onHide: function () {
            //TODO have a new function to do this. 
            for(var q=0;q<filters.length;q++) {
              if (filters[q][F_ENABLED] && filters[q][F_FACET]=='country' && filters[q][F_VALUE] == 'Isle of Man') {
                 deleteFilter(q,{},false);
              }
            }
      }, 
      width: 600
    });

    guiders.createGuider({
      highlight: "#results, #pagesize",
      title: "Content Area",
      id: "g_content",
      next: "g_simpleend",
      description: "This is where the images matching the current search keywords and filters are shown. You can use the page links to browse though the matching images. <br/><br/> Click a thumbnail to open a slideshow. <br/><br/> You can choose how many images are shown per page, using the dropdown top right. <br/><br/> Later on will also find this is where a map may be displayed. <br><br><b>Click a thumbnail now!</b>", 
    });

    guiders.createGuider({
      highlight: "blockquote#image",
      title: "Large Image Slideshow",
      id: "g_mainimage",
      next: "g_mainimage2",
      description: "This area shows the currently selected image. Use the Next/Prev at the top to change between images on the current page of results. Point at the image itself to get a map of where this photo is located. Or click to open the full Geograph page for the photo. "
    });

    guiders.createGuider({

      attachTo: "#attribs", highlight: "#attribs",
      position: 12,
autoFocus: true,
      title: "Attributes for this image",
      id: "g_mainimage2",
      next: "g_simpleend",
      description: "These show the attributes (date, location, tags etc) for the current image. You can click any one of them to make it into a filter - to find similar images",
      onhide: function () {
           closeImage();
      }
    });

    guiders.createGuider({
      title: "Quick introduction done",
      id: "g_simpleend",
      next: "g_sidebar2",
      buttons: [{name: "Back to Start", onclick: function() {guiders.hideAll(true).show('g_welcome')} },{name: "Close"},{name: "Next"}],
      description: "That's the end of the basic introduction. You should now have enough information to get started. You can always reopen the tour by clicking \"About/Help\" in the top bar. <br/><br/>Click Close to return to the application, or <b>click Next to continue to the more in-depth tour</b>. "
    });

    guiders.createGuider({
      attachTo: "#sidebar .title:eq(2) span", highlight: "#sidebar",
      position: 3,
autoFocus: true,
      title: "Attribute Lists - Open/Close groups",
      id: "g_sidebar2",
      next: "g_sidebar3",
      description: "In this sidebar, click any of the group headings to expand/contract a whole group. You only need to leave open the groups you actually want to use. <br/><br/><b><big>Try it now!</big></b>"
    });

    guiders.createGuider({
      attachTo: "#sidebar .title:eq(2) a.link1", highlight: "#sidebar",
      position: 3,
      title: "Attribute Lists - Open expanded Lists",
      id: "g_sidebar3",
      next: "g_sidebar4",
      description: "If you are only seeing the top ten items of a larger number of items - click the 'more' to expand the group to show 100 attributes. Remember the attributes are picked from the images matching the current filters. <br/><br/><b><big>Try clicking 'more' now!</big></b>",
      onShow: function () {
         if ($('#sidebar .title:eq(2) img').attr('src').indexOf('closed') > -1) { 
           togglerImg($('#sidebar .title:eq(2) img').get(0));
           loadFacet('ftakenyear','takenyear','takenyear');
         }
      }
    });

    guiders.createGuider({
      attachTo: "#sidebar .title:eq(1) a.link1", highlight: "#sidebar, #facetsearch",
      position: 7,
      title: "Attribute Lists - accessing a search",
      id: "g_sidebar4",
      next: "g_toggler",
      description: "When there are very many attributes, (particularly if over 100 available in the expanded list) you can search by keywords in the available filters. <b>Tip: The one in Countries groups is useful to search by placename</b><br/><br/><b><big>Try opening search now</big></b>",
      onShow: function () {
         if ($('#sidebar .title:eq(2) img').attr('src').indexOf('closed') > -1) { 
           togglerImg($('#sidebar .title:eq(2) img').get(0));
           loadFacet('ftakenyear','takenyear','takenyear');
         }
      }
    });

    guiders.createGuider({
      attachTo: "#facet_q", highlight: "#facetsearch",
      position: 3,
      title: "Attribute Search",
      id: "g_facetsearch",
      next: "g_toggler",
      description: "Type a query here to search though all the available attributes/filters - here can search placenames. <br/><br/>Note: Only attributes on images matching the current filters are shown here.<br/><br/> Just like in the Attribute Lists click a search result to turn it into an active filter.<br/><br/><b><big>Try typing a query now</big></b>",
      onHide: function () {
          $('#facetsearch, #lightbox-background').hide();
      }
    });

    guiders.createGuider({
      attachTo: ".toggler a", highlight: ".toggler",
      position: 7,
      title: "More Settings - Order/Sample",
      id: "g_toggler",
      next: "g_toggler2",
      description: "There are some additional settings that can be used to control the display of images.<br/><br/><b><big>Click now to open the settings bar</big></b>",
      onShow: function () {
          $('#facetsearch, #lightbox-background').hide();
      }
    });

    guiders.createGuider({
      attachTo: ".toggler a", highlight: ".toggler, #advancedbar",
      position: 7,
      title: "More Settings - Order/Sample",
      id: "g_toggler2",
      next: "g_map",
      description: "<b>Sample by</b>: <br/> These special filters apply a deduplication routine to the matching images. For example selecting 'contributor' as a sample by will mean the results will contain at most only a few images by that contributor. These are most useful when a resultset tends to be dominated by one particular attribute. Using this filter allows you to cut down on the similar images, and get a diverse range of results.<br/><br/><b>Order by:</b> <br/> This setting changes the order that the images are shown in the content area. If you leave it on 'Auto', a suitable order will be chosen for you depending on the filters you have applied, and the Mode. ",
      onShow: function () {
         if ($('.toggler a img').attr('src').indexOf('closed') > -1) { 
            togglerBar('advancedbar',$('.toggler a').get(0));
         }
      },
      onHide: function () {
         if ($('.toggler a img').attr('src').indexOf('open') > -1) { 
            togglerBar('advancedbar',$('.toggler a').get(0));
         }
      }
    });

    var prevDisplay = null;

    guiders.createGuider({
      highlight: "#results, #sortSelect",
      title: "Interactive Map Mode",
      id: "g_map",
      next: "g_mapcircle",
      description: "The map view gives you access to the thumbnails plotted on an interactive zoomable map. Use the tools on the left to move the map - but you can also just drag the map and use scroller wheel to zoom. <br/><br/>If you haven't searched by location (we'll find out in a moment what happens if have) the map will just update to show more images in the current view.<br/><br/>Once you have began zooming will get a special 'Map Extents' filter in the filter bar, you can delete this filter to re-zoom/centre to show the whole area of images matching the current filters. <br/><br/> <b>Tip: hover over an thumbnail or click to view bigger.</b>",
      onShow: function () {
          prevDisplay = $('#display').val();
          if (prevDisplay != 'map') {
              $('#display').val('map');
              loadThumbnails();
          }
      },
      onHide: function (myGuider, next) {
          if (prevDisplay != 'map' && next != 'g_mapcircle') {
              $('#display').val(prevDisplay);
              if ($("#filterBounds").length > 0) {
                  removeBoundsFilter();
              }
              loadThumbnails();
          }
      }
    });

    guiders.createGuider({
      highlight: "#results, #neardiv",
      title: "Interactive Map Mode - Circle view",
      id: "g_mapcircle",
      next: "g_dateslider",
      description: "If you've searched for a location - in the 'Near' box, the map will display a circle centered on the location. Alternatively, you can just click on the map to create a new circle - and search near the clicked point. <b id=\"createCirclePrompt\">Try clicking once on the map itself now - to create a circle</b><br/><br/>To <b>resize</b> the circle, just drag one of the squares on the edge, but you can also use the within dropdown above the map.<br/><br/>To move the circle just drag the centre square (if you make a mistake just use the undo icon that appears).<br/><br/>Tip: just click anywhere in the circle (not on an image) to re-center and zoom the map to see the whole circle. ",
      onShow: function () {

          prevDisplay = $('#display').val();
          if (prevDisplay != 'map') {
              $('#display').val('map');
              loadThumbnails();
          }
          if ($("#filterBounds").length > 0) {
              removeBoundsFilter();
          }
          $('#createCirclePrompt').css('display',($('#location').val().length == 0)?'':'none');
      },
      onHide: function (myGuider, next) {
          if (prevDisplay != 'map' && next != 'g_map') {
              $('#display').val(prevDisplay);
              if ($("#filterBounds").length > 0) {
                  removeBoundsFilter();
              }
              loadThumbnails();
          }
      }
    });

    guiders.highlightTimeslider = function() {
        if ($('#sliderbar').length == 0) {
            setTimeout('guiders.highlightTimeslider()',500);
	    return;
        }
        guiders.reposition();
        guiders._highlightElement('#sliderbar');
    }
    guiders.createGuider({
      attachTo: "#sliderbar", highlight: "#sliderbar",
      position: 6,
      title: "Date Slider Mode",
      id: "g_dateslider",
      next: "g_historydiv",
      description: "Here you can view images by date. Just drag the slider toggle to change the center point. The listing shows images closest to that date. This is great for making quick date comparisons. <br/><br/>Try dragging the slider now!</b> (On a touch device, just tap a new location on the bar)<br/><br/><i><i>Note: The filters and attribute lists work in this mode, but order by does not (items are always shown in date distance order).</i>",
      onShow: function () {
          prevDisplay = $('#display').val();
          if (prevDisplay != 'date_slider') {
              $('#display').val('date_slider');
              loadThumbnails();
          }
          guiders.highlightTimeslider();
      },
      onHide: function () {
          if (prevDisplay != 'date_slider') {
              $('#display').val(prevDisplay);
              loadThumbnails();
          }
      }
    });

    guiders.createGuider({
      highlight: "#historydiv, #linkbar a[href*=\"historydiv\"]",
      title: "History Window",
      id: "g_historydiv",
      next: "g_theend",
      description: "This window displays previous configurations. Like your browser history, you can use this to jump back to a previous setting. Useful for example if you have added lots of filters that have led to a dead end. Use the history window to quickly jump back to an earlier setup. Just click one of the Go buttons.<br/><br><b>Tip: Can always your normal browser back button to go back a few steps too!</b>",
      onShow: function () {
          prevDisplay = $('#historydiv:visible');
          if (prevDisplay.length == 0) {
              toggleLightBox('#historydiv');
          }
      },
      onHide: function () {
          if (prevDisplay.length == 0) {
              toggleLightBox('#historydiv');
          }
      }
    });

    guiders.createGuider({
      title: "End of Tour",
      id: "g_theend",
      next: "g_welcome",
      description: "That's it for now. <br/><br/>Click close to return to the application. Or Next to go back to the start of the tour!"
    });



});


