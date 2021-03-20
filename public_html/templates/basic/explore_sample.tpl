{include file="_std_begin.tpl"}

<h2>Geograph Photos - Quick Explore</h2>
{literal}<style>
#thumbnails div {
	float:left;
	position:relative;
	width:223px;
	height:190px;
	text-align:center;
}
#thumbnails a.download {
	display:block;
	text-decoration:none;
	color:gray;
	font-size:0.9em;
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

<p>View a small selection of Geograph images here. To explore more images, the search box above.</p>
<p><small>Tip: Can click 'download' under an image, to download a high-resolution version, with the Creative-Commons attribution/credit automatically applied. There is also a button at the bottoom to download all images.</small></p>


<div class=interestBox>
<form id="theForm" name=theForm>
	<b>Filters</b>: 
	<select name="context"></select>
	<select name="subject"></select>
	<select name="region"></select>
	<select name="decade"></select>
</form></div>
<br>
<div id="thumbnails" class="shadow"> <div style="height:300px;font-size:2.2em">Loading.... Please wait!</div> </div>
<form id="actions">
	<button id="download-button">Download images above in .zip File</button>
	<button onclick="browserLink(true)">View these images in advanced viewer</button>
	<button onclick="browserLink(false)">View <b>even more</b> images like these</button>
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

<p style=display:none>This page only explores a very small sample of geograph images, we have about <b id=total_count></b> matching your filters. Use the buttons above to explore them</p>

{literal}
<script>

function browserLink(showcase) {
	var bits = [];
	if ($('form#theForm select[name=context]').prop('selectedIndex') > 0) {
		bits.push("contexts+%22"+encodeURIComponent($('form#theForm select[name=context]').val())+"%22");
	}
	if ($('form#theForm select[name=subject]').prop('selectedIndex') > 0) {
		bits.push("q=["+encodeURIComponent($('form#theForm select[name=subject]').val())+"]");
	}
	if ($('form#theForm select[name=decade]').prop('selectedIndex') > 0) {
		bits.push("decade+%22"+encodeURIComponent($('form#theForm select[name=decade]').val())+"tt%22");
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
		loadImages();
	}
});

function selectValue(path,value) {
        if ($(path).length) {
                $(path).val(value);
                if ($(path).prop('selectedIndex') <1) { //failed to find it in dropdown!
                        $(path).append( $('<option/>').attr('value', value).text(value));
	                $(path).val(value);
		}
        }
}

$(function() {
	if (window.location.hash.length == 0) {
		loadImages();
	} else {
		$(window).hashchange();
	}
	$('form#theForm select').change(function() {
		loadImages();
	});
	$('#thumbnails').on('click','a.section',function() {
		var path = 'form#theForm select[name=context]';
		var value = $(this).text();
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

function loadImages() {
	var url = "https://api.geograph.org.uk/explore/sample.json.php"; //api can cache it!
	var data = $('form#theForm').serialize();
	disable_hashevent = true; //frustratingly the event fires even we change it!
	setTimeout(function () { disable_hashevent = false;  } ,500);
	window.location.hash = data;	
	$.ajax({
	  dataType: "json",
	  url: url,
	  data: data,
	  cache: true,
	  success: renderImages
	});	

	var q = [];
	$('form#theForm select').each(function() {
		v = $(this).val();
		if (v) q.push(v);
	});
	if (true) {
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
		  }
	        });
	}

}
function renderImages(data) {
	if (data.context) {
		var ele=$('form#theForm select[name=context]');
		var select = ele.val();
		ele.empty();
		$('<option/>').attr('value', '').text("<select Geographical Context...>").appendTo(ele);	
		var last = '';
		var optgroup = null;
		$.each(groups,function(index,row) {
			if (row.grouping != last) {
				optgroup = $('<optGroup/>').attr('label', row.grouping).appendTo(ele);
			}
			last = row.grouping;
			if (data.context[row.top]) {
				$('<option/>').attr('value', row.top).text(row.top+' ['+data.context[row.top]+']').appendTo(optgroup);
			}
		});
		if (select)
			ele.val(select);
	}
	if (data.subject) {
		var ele=$('form#theForm select[name=subject]');
		var select = ele.val();
		ele.empty();
		$('<option/>').attr('value', '').text("<select Subject...>").appendTo(ele);	
		$.each(data.subject,function(key,value) {
			$('<option/>').attr('value', key).text(key+' ['+value+']').addClass(value>20?'many':'').appendTo(ele);
		});
		if (select)
			ele.val(select);
	}
	if (data.region) {
		var ele=$('form#theForm select[name=region]');
		var select = ele.val();
		ele.empty();
		$('<option/>').attr('value', '').text("<select Region...>").appendTo(ele);	
		$.each(data.region,function(key,value) {
			$('<option/>').attr('value', key).text(key+' ['+value+']').appendTo(ele);
		});
		if (select)
			ele.val(select);
	}
	if (data.decade) {
		var ele=$('form#theForm select[name=decade]');
		var select = ele.val();
		ele.empty();
		$('<option/>').attr('value', '').text("<select Decade...>").appendTo(ele);	
		$.each(data.decade,function(key,value) {
			$('<option/>').attr('value', key).text(key+'0s ['+value+']').appendTo(ele);
		});
		if (select)
			ele.val(select);
	}
	var div = $('#thumbnails').empty();
	if (data.images) {
		$.each(data.images,function(index,image) {
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
				).data('latlong',image.wgs84_lat+','+image.wgs84_long)
                        ).append(
				$('<a/>').attr('href',download).text('download').addClass('download').data('filename','geograph-'+image.gridimage_id+'.jpg')
                        );
			if (image.section)
				$div.append(
					$('<a/>').text(image.section).addClass('section').prop('title','Click to view more ['+image.section+'] images')
				);
			$div.appendTo(div);
		});
	}
}
{/literal}

var groups = {$groups};
</script>


{include file="_std_end.tpl"}
