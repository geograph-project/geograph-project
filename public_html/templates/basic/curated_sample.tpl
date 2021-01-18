{assign var="meta_description" value="Download over `$imagecount` curated Geography images here. Free for any use, even commerical, in return for attribution. Creative Commons BY-SA."}
{include file="_std_begin.tpl"}

<h2><a href="/teachers/">Education Photos</a> &gt; Download Samples</h2>

<p>Download over {$imagecount} curated Geography images here. Free for any use, even commerical, in return for attribution. Creative Commons BY-SA.</p>
<p>This is only a small selection of our entire collection, focusing on images relevent to the Geography Curriculum. To explore more images on Geograph, enter keywords in the search box above.</p>

{literal}<style>
#thumbnails div {
	float:left;
	position:relative;
	width:223px;
	height:190px;
	text-align:center;
}
#thumbnails.shadow img {
	margin-bottom:0;
}
#thumbnails a.thumb {
	display:block;
}
#thumbnails a.download, #thumbnails a.reuse {
	text-decoration:none;
	color:gray;
	font-size:0.9em;
	margin-left:2px;
	margin-right:2px;
}

#thumbnails a.reuse::before {
	content: "/";
}

#thumbnails a.section {
	position:absolute;
	top:0;
	left:20px;
	width:183px;
	opacity:0.7;
	text-shadow: 0px 0px 13px rgba(0, 0, 0, 1);
	text-decoration:none;
	color:white;
	font-weight:bold;
	text-align:center;
	cursor:pointer;
	padding-top:20px;
	height:70px;
	background-color:black;
}
#thumbnails a.section:hover {
	opacity:1;
}
#thumbnails:after {
  visibility: hidden;
  display: block;
  content: "";
  clear: both;
  height: 0;
}
option.many {
	font-weight:bold;
	background-color:yellow;
}
.progress-bar {
	height:10px;
	background-color:lightgreen;
}
.interestBox {
	border-radius:2px;
	background-color:lightgreen;
}
form#theForm select {
	font-size:1.05em;
}
</style>{/literal}

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script type="text/javascript" src="{$static_host}/js/jquery.ba-hashchange.min.js"></script>

<p><small>Tip: Can click 'download' under an image, to download a high-resolution version, with the Creative-Commons attribution/credit automatically applied. There is also a button at the bottom to download all visible images as a single .zip file.</small></p>


<div class=interestBox>
<form id="theForm" name=theForm>
	<b>Filters</b>: 
	<select name="group"></select>
	<select name="label"></select>
	<select name="region"></select>
	<select name="decade"></select>
</form></div>
<br>
<div id="thumbnails" class="shadow"> <div style="height:300px;font-size:2.2em">Loading.... Please wait!</div> </div>
<br><br>
<form id="actions">
	<button id="download-button">Download images above in .zip File</button>
	<!--button class="browser-button" onclick="browserLink(true)">View these images in advanced viewer</button-->
	<button class="browser-button" onclick="browserLink(false)">View images more matching keywords</button>
</form>


    <script type="text/javascript" src="{$static_host}/js/jszip.min.js"></script>
    <script type="text/javascript" src="{$static_host}/js/jszip-utils.js"></script>
    <script type="text/javascript" src="{$static_host}/js/FileSaver.js"></script>
    <script type="text/javascript" src="{$static_host}/js/downloader.js"></script>

<div class="progress hide" id="progress_bar">
    <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;">
    </div>
</div>

<p class="hide" id="result"></p>

<p style=display:none>This page only explores a very small sample of geograph images, we have about <b id=total_count></b> matching <tt id="keywords_query">query</tt>. Use the button above to explore them.</p>

{literal}
<script>

function browserLink(showcase) {
	var bits = [];
	if ($('form#theForm select[name=label]').prop('selectedIndex') > 0) {
		bits.push("q="+encodeURIComponent($('form#theForm select[name=label]').val())+"");
	}
	if ($('form#theForm select[name=decade]').prop('selectedIndex') > 0) {
		bits.push("decade+%22"+encodeURIComponent($('form#theForm select[name=decade]').val().substr(0,3))+"tt%22");
	}
	//cant do region!
	if (showcase)
		bits.push("content_id=1");

	///display=group/group=country/n=4/gorder=alpha%20asc
	//bits.push("display=group");
	//bits.push("group=country");
	//bits.push("n=4");
	//bits.push("gorder=alpha%20asc");
	bits.push("display=plus");

	window.open("/browser/#!/"+bits.join('/'),'_blank');
}

var disable_hashevent = false;
$(window).hashchange( function(){
   	if (!disable_hashevent && window.location.hash.length> 0) {

		var bits = window.location.hash.replace(/^#/,'').replace(/\+/g,'%20').split(/&/);
		for (var q=0;q<bits.length;q++) {
			parts = bits[q].split(/=/,2);
			var path = 'form#theForm select[name='+parts[0]+']';
			var value = decodeURIComponent(parts[1]);
			selectValue(path,value);
		}
		loadImages(true);
	}
});

window.onpopstate = function(event) {
	console.log("pop",event,window.location.search);
	var bits = window.location.search.replace(/^\?/,'').replace(/\+/g,'%20').split(/&/);
	if (bits && bits[0].length>0) {
	        for (var q=0;q<bits.length;q++) {
                        parts = bits[q].split(/=/,2);
                        var path = 'form#theForm select[name='+parts[0]+']';
                        var value = decodeURIComponent(parts[1]);
                        selectValue(path,value);
                }
	}
        loadImages(true);
};

function selectValue(path,value) {
        if ($(path).length) {
                $(path).val(value);
                if ($(path).prop('selectedIndex') <1) { //failed to find it in dropdown!
                        $(path).append( $('<option/>').attr('value', value).text(value));
	                $(path).val(value);
		}
        }
}

var sectioner = null;

$(function() {
	if (window.location.hash.length == 0) {
		window.onpopstate();
	} else {
		$(window).hashchange();
	}
	$('form#theForm select').change(function() {
		loadImages();
	});
	$('#thumbnails').on('click','a.section',function() {
		var path = 'form#theForm select[name='+sectioner+']';
		var value = $(this).text().replace(/ \[\d+\]/,'');
		selectValue(path,value);

		loadImages();
	});
	if (0)
	$('#thumbnails').on('mouseover','a',function() {
		var ll = $(this).data('latlong');
		if (ll) {
			var url = "https://maps.googleapis.com/maps/api/staticmap?markers=size:med|"+ll+"&zoom=7&size=640x640&scale=2&key={/literal}{$google_maps_api3_key}{literal}";
			$('body').css({
				'background-image':'url('+url+')',
				'background-size':'cover',
				'background-attachment':'fixed'
			});
		}
	});
});

function loadImages(reloading) {
	var url = "https://api.geograph.org.uk/curated/sample.json.php"; //api can cache it!
	var data = $('form#theForm').serialize();

	if (!reloading) { //loading images from onpopstate/hashchange DONT set new!
		if (history.pushState) {
			history.pushState({data:data}, '', "?"+data);
		} else {
			disable_hashevent = true; //frustratingly the event fires even we change it!
			setTimeout(function () { disable_hashevent = false;  } ,500);
			window.location.hash = data;	
		}
	}

	$.ajax({
	  dataType: "json",
	  url: url,
	  data: data,
	  cache: true,
	  success: renderImages
	});	

	var q = [];
	$('form#theForm select').each(function() {
		var name = $(this).attr('name');
		if (name == 'group' || name == 'region')
			return;
		v = $(this).val();
		if (v) q.push(v);
	});
	$('#keywords_query').text(q.join(' '));
	if (q.length >0) {
		var data = {
			select:'id',
			limit:'0.0',
			match:q.join(' '),
			option:'ranker=none'
		};
	        $.ajax({
        	  dataType: "json",
	          url: "https://api.geograph.org.uk/api-facetql.php",
        	  data: data,
	          cache: true,
        	  success: function(data) {
			$('#total_count').text(parseInt(data.meta.total_found,10).toLocaleString()).parent().show('fast');
			$('.browser-button').show();
		  }
	        });
	} else {
		$('#total_count').parent().hide();
		$('.browser-button').hide();
	}
}

function redoSelect(path,text,data) {
	var ele=$(path);
	var select = ele.val();
	ele.empty();
	$('<option/>').attr('value', '').text(text).appendTo(ele);	
	$.each(data,function(key,value) {
		$('<option/>').attr('value', key).text(key+' ['+value+']').addClass(value>20?'many':'').appendTo(ele);
	});
	if (select)
		ele.val(select);
}

function renderImages(data) {
	if (data.sectioner)
		sectioner= data.sectioner;
	if (data.group) {
		redoSelect('form#theForm select[name=group]','<select List...>',data.group);
	}
	if (data.label) {
		redoSelect('form#theForm select[name=label]','<select Label...>',data.label);
	}
	if (data.region) {
		redoSelect('form#theForm select[name=region]','<select Region...>',data.region);
	}
	if (data.decade) {
		redoSelect('form#theForm select[name=decade]','<select Decade...>',data.decade);
	}
	var div = $('#thumbnails').empty();
	if (data.images) {
		var count = 0;
		$.each(data.images,function(index,image) {
			if (count == 100)
				return;
			var hash = image.thumbnail.match(/\/(\d+)_(\w{8})_/);
			var download = "https://t0.geograph.org.uk/stamp.php?id="+hash[1]+"&title=on&gravity=SouthEast&hash="+hash[2]+"&download=1";
			if (image.largest && image.largest > 800) {
				download = download + "&large=800";
				//http://t0.geograph.org.uk/stamp.php?id=5452236&title=on&gravity=SouthEast&hash=50d17399&large=800&download=1
			}
			var title = image.grid_reference+' : '+image.title+' by '+image.realname;
                        var $div = $('<div/>').append(
				$('<a/>').attr('href','/photo/'+image.gridimage_id).attr('title',title).append(
					$('<img/>').attr('src',image.thumbnail)
				).data('latlong',image.wgs84_lat+','+image.wgs84_long).addClass('thumb')
                        ).append(
				$('<a/>').attr('href',download).text('download'+((image.section)?' example image':'')).addClass('download').data('filename','geograph-'+image.gridimage_id+'.jpg')
                        );
			if (image.section) {
				if (data[sectioner] && data[sectioner][image.section] && data[sectioner][image.section] > 1)
					image.section = image.section + ' [' + data[sectioner][image.section] + ']';
				$div.append(
					$('<a/>').text(image.section).addClass('section').prop('title','Click to view more ['+image.section+'] images')
				);
			} else {
				$div.append(
					$('<a/>').attr('href','/reuse.php?id='+image.gridimage_id).text('reuse').addClass('reuse')
				);
			}
			$div.appendTo(div);
			count++;
		});
		if (count == 100)
			div.append('This prototype can only view 100 images, there may be more matching. They are not currently accessible');
	}
}
{/literal}

</script>


<!--p align="center" style='background-color:#e6aae6;color:brown;padding:1em;margin:0'>Dissatisfied with these results? <a style='color:brown' href='#' onclick="jQl.loadjQ('/js/search-feedback.js');return false">Please take this short survey</a>.</p-->

{include file="_std_end.tpl"}
