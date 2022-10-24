{assign var="page_title" value="High Resolution Landscapes Feed"}
{include file="_std_begin.tpl"}

<style>
{literal}
div#preview {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    grid-gap: 18px;
    grid-row-gap: 20px;
}
div#preview > div {
        text-align:center;
        float:left; /* ignored in grid, but to support older browsers! */
}

div#preview img {
   max-width:calc( 50vw - 100px );
   max-height:auto;
   border: 1px solid black;
}
div#preview div, div#preview a{
   position:relative;
   color:gray;
}
div#preview .date {
   position:absolute;right:4px;bottom:0;font-size:2.7em;opacity:0.7;
   text-shadow: 1px 1px 0px #c3c3c3;
}


div#preview div.json {
   text-align:left;
   border:1px solid silver;
}
div#preview div.json i {
   width: 5em;
   display: inline-block;
   text-align:right:
}

{/literal}
</style>

<h2>Free High Resolution Landscape Photos of Britain and Ireland</h2>
<p><small>Geograph provide this Feed/API of high resolution images, so that developers can get a free collection of high quality images for use in apps and websites. The Creative Commons licence allows easy reuse of Geograph Images, the feed provides a pre-stamped image including suitable attribution, so can just display the provided image directly in application. Can use the form below to try out different combinations of filters etc, to get varied selections. Please try to cache the feed (say for 24 hours) to avoid calling it too often, but welcome to directly link the high resolution images as provided. Please try to use the URLs exactly as provided in feed.</small></p>

<form id="filters" onsubmit="return false" style="background-color:silver;padding:4px;">

<span class=nowrap>
<select name=ratio id=ratio>
<option value="">Any Aspect Ratio</option>
<option value="l">Any Landscape</option>
<option value="p">Any Portrait</option>
<option value="1.0">Square</option>
</select>
</span>

<span class=nowrap>
<select name=large id=large>
<option value="">Min 1600px on longest size</option>
<option value="1024">1024px</option>
<option value="800">800px</option>
</select> </span>
</span>

<span class=nowrap>
<select name=country id=country>
<option value="">Any Country</option>
</select>
</span>

<span class=nowrap>
<select name=taken id=taken>
<option value="">Taken Anytime</option>
<option value="recent">Last 5 years</option>
<option value="historic">Over 20 years ago</option>
</select>
</span>

<span class=nowrap>
<select name=geo id=geo>
<option value="1">Geograph Images</option>
<option value="2">Geograph+CrossGrids</option>
<option value="0">Non Geograph</option>
<option value="">Mixed Images</option>
</select>
</span>

<span class=nowrap>
<select name=tab id=tab>
<option value="">Long Term Highest Rated</option>
<option value="daily">New Image Every Day</option>
<option value="fresher">Fresher Selection</option>
<option value="recent">Recent Taken</option>
<option value="submitted">Recently Submitted</option>
<option value="twitter">Featured on Twitter</option>
<option value="poty">in Photograph of the Week/Year</option>
</select>
</span>

<span class=nowrap>
View JSON: <input type=checkbox id=json>
</span>

<br><br>

Feed URL: (<b id="count"></b>)<br>
<input type=text id=urlDisplay readonly size=120 style=max-width:95% style=background-color:cream><br>
<small><i>These are low resolution 640px previews, the feed provides the high resolution image URL! Can omit the format param, to get <a href="https://en.wikipedia.org/wiki/Media_RSS">Media RSS/XML format</a> but JSON is recommended.</i></small>
</form>

<div class="tab_info tab_daily">The "New Image Every Day" selection aims to showcase a new image every day, the feed includes the last five days. The first is typically that days images. Sometimes (particullically if add lots of filters), there may not be a image that day, so the day before reused.</div>
<div class="tab_info tab_fresher">The "Fresher" selection aims to provide a more varied selection, shuffling around more regually, whereas the normal highest rated is very slow to change.</div>

<p>If want to download a image below, <b>right click and select 'Save link as...'</b> (or similar), if use 'save image as', will just save the small thumbnail.</p>

<div id="preview"></div>
<br class=clear/>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script src="https://s1.geograph.org.uk/js/lazysizes.min.js" async=""></script>


<script>
{literal}
var endpoint = "https://api.geograph.org.uk/api-wallpaper.php?format=JSON";

//_call_cors_api(endpoint,data,uniquename,success,error)

$(function() {
  loadImages();

  var list = '1024x768,1200x800,1366x768,1440x900,1600x900,1680x1050,1920x1080,1920x1200';
  var ratios = new Object(); //use a 'associative array' to deduplicate
  $.each(list.split(/,/), function(index,value) {
     var bits = value.split(/x/);
     var ratio = (bits[0]/bits[1]).toFixed(4);
     ratios[ratio] = value;

     var ratio = (bits[1]/bits[0]).toFixed(4);
     ratios[ratio] = bits[1]+"x"+bits[0];
  });
  $.each(ratios, function(key,value) {
     $('#ratio').append('<option value="'+key+'">'+key+' for example: '+value+'</option>');
  });

  list = 'Scotland,England,Wales,Isle of Man,Northern Ireland,Republic of Ireland';
  $.each(list.split(/,/), function(index,value) {
     $('#country').append('<option value="'+value+'">'+value+'</option>');
  });

  list = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
  $.each(list, function(index,value) {
     $('#taken').append('<option value="'+(index+1)+'">in '+value+'</option>');
  });

  $('form#filters select').change(function() {
     loadImages();
  });
  $('form#filters input[type=checkbox]').click(function() {
     loadImages();
  });
});

function loadImages() {
  var data = {};
  if (ratio = $('#ratio').val()) {
     data.ratio = ratio;
  }
  if (large = $('#large').val()) {
     data.large = large;
  }
  if (country = $('#country').val()) {
     data.country = country;
  }
  if (taken = $('#taken').val()) {
     data.taken = taken;
  }

  if (tab = $('#tab').val()) {
     data.tab = tab;
  }
  $('.tab_info').hide();
  $('.tab_'+tab).show();

  var geo = $('#geo').val();
  if (geo.length) //tested differently because need to tell between empty string and 0 !
     data.geo = geo;

  $('#urlDisplay').val(endpoint+"&"+$.param(data)); //tofix, only works if endpoint alrady has "?"

  $('#preview').css('opacity',0.5); //to signal something is happening!
  _call_cors_api(endpoint, data, "previewLoader", function(data) {
     if (data && data.items && data.items.length) {
          $('#preview').empty().css('opacity',1);
          $.each(data.items, function(index,value) {
               if ($('#json:checked').length) {
			var $div = $('<div class=json></div>');
			var $ele = $('<div>result.items['+index+'] = {</div>');
			$div.append($ele);

			$.each(value, function(k,v) {
			    var $ele = $('<div><i>'+k+'</i>: <b></b></div>');
			    $ele.find('b').text(v);
			    $div.append($ele);
			});

			$('#preview').append($div);
               } else {

                    value.preview = value.enclosure.replace(/large=\w+/,'large=');
                    value.preview = value.preview.replace(/pointsize=\w+/,'pointsize=');

                    $('#preview').append('<div id="div'+value.guid+'"><a href="'+value.enclosure+'"><img class="lazyload" data-src="'+value.preview+'"></a><br><a href="'+value.link+'">'+value.link+'</div>');
                    $('#preview #div'+value.guid+' a').attr('title',value.title+' by '+value.author+'\n\n'+(value.description || ''));
                    if (value.dimensions)
                        $('#preview #div'+value.guid).append('  ['+value.dimensions+']');
                    if (value.imageTaken && value.imageTaken.length > 4 && value.imageTaken.substr(0,4) != '0000')
                        $('#preview #div'+value.guid+' a:first-child').append('<div class=date>'+value.imageTaken.substr(0,4)+'</div>');
               }
          });
         $('#count').text('currently '+data.items.length+' results'); 
     } else {
         $('#preview').html('<big>No images currently. There may be images in future</big>');
         $('#count').text('currently no results');
     }
  });

}

// function to allow using cors if possible
function _call_cors_api(endpoint,data,uniquename,success,error) {
  crossDomain = true; //todo/tofix!
  if (uniquename && crossDomain && !jQuery.support.cors) {
    //use a normal JSONP request - works accorss domain
    endpoint += (endpoint.indexOf('?')>-1?'&':'?')+"callback=?&";
    $.ajax({
      url: endpoint,
      data: data,
      dataType: 'jsonp',
      jsonpCallback: uniquename,
      cache: true,
      success: success,
      error: error
    });
  } else {
    //works as a json requrest - either same domain, or a browser with cors support
    $.ajax({
      url: endpoint,
      data: data,
      dataType: 'json',
      cache: true,
      success: success,
      error: error
    });
  }
}
{/literal}

</script>


{include file="_std_end.tpl"}
