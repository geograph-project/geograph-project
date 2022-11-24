var gridimage_id = null;

var as_sidebar = (window.innerWidth >= 1024);
var supportsLazyLoad = ('loading' in document.createElement('img'));

if (window.location.pathname.match(/^\/photo\/(\d+)/) ) {

	if (as_sidebar) {
		//we can do this before even jquery loads...
		document.getElementById('maincontent_block').style.marginRight = "150px";
	}

	//we have to be extra careful checking if a real jquery, as jQl creates a fake jQuery object.
	if (typeof jQuery === "undefined" || jQuery === null || typeof jQuery.fn === "undefined" || typeof jQuery.fn.load === "undefined") {
		jQl.loadjQ('https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js');
	}

	$(function() {

		if (m = window.location.pathname.match(/photo\/(\d+)/)) {
			gridimage_id = parseInt(m[1],10);
		} else {
			return;
		}

		if (as_sidebar) {
			$('#maincontent_block').css('margin-right','150px');
			$('body').append('<div id="related" style="width:149px;background-color:white;border-left:1px solid silver;padding-top:4px;position:absolute;top:74px;right:0;text-align:center;min-height:900px"></div>');
		} else {
			$('body').append('<div id="related" style="background-color:white;border-top:1px solid silver;padding:10px;text-align:center;max-width:680px;margin-left:auto;margin-right:auto; margin-bottom:100px"></div>');
		}

		if ($('#maincontent_block').length)
			$('#related').css('backgroundColor',$('#maincontent_block').css('backgroundColor'));

		$("<style type='text/css'> #related a { color: "+$('#maincontent_block a').css('color')+"} #related .thumb { float:left; } </style>").appendTo("head");

		if (as_sidebar) //dynamiclly hide the sidebar, when make window small. todo, maybe could change its class and make it a bottom one.
			$( window ).resize(function() {
				if (window.innerWidth > 1024) {
					$('#maincontent_block').css('margin-right','150px');
					$('#related').show();
				} else {
					$('#maincontent_block').css('margin-right','0');
					$('#related').hide();
				}
			});

		$.ajaxSetup({
			cache: true
		});

		$('#related').append('<form><select onchange="renderRelatedImage()">'+
			'<option value="">Related Images</option>'+
			'<option value="recent">Recent Nearby</option>'+
			'<option value="takenday">Same Day</option>'+
			'<option value="contributor">Same Contributor</option>'+
			'<option value="centisquare">Same Centisquare</option>'+
			'<option value="grid_reference">Same Grid-Square</option>'+
			'</select></form>');

		$('#related').append('<div class="thumbs shadow" style="padding:5px">Loading...</div>');

		if (supportsLazyLoad) {
			renderRelatedImage();
		} else {
			$.getScript('//s1.geograph.org.uk/js/lazy.v73715774.js',function() {
				//initLazy(); //done after loading thumbnails now!

				//todo, check if a search result active, and if so use that to show images?
				renderRelatedImage();
			});
		}
	});

}

/**************************************
* Images related to a single specified image
*/


function renderRelatedImage() {
	var data = {
		select: 'myriad,hectad,grid_reference,takenyear,takenmonth,takenday,groups,tags,types,contexts,snippets,subjects,place,county,country,scenti,user_id,realname,imageclass',
		where: 'id='+gridimage_id
	}
	var params = {
		data: data,
		cache: true,
		dataType: 'json'
	};
	$.ajax('https://api.geograph.org.uk/api-facetql.php',params).done(function(data) {
		if (row = data.rows[0]) {
			processImage(row);
		} else {
			params['data'] = {id: gridimage_id};
			$.ajax('/stuff/image.json.php',params).done(function(data) {
				processImage(data);
			});
		}
	}).fail(function() {
		params['data'] = {id: gridimage_id};
		$.ajax('/stuff/image.json.php',params).done(function(data) {
			processImage(data);
		});
	});
}

function processImage(row) {
	if (!row)
		return;

	var mode = $('#related select').val();

	var required = [];
	var optional = [];
	if (mode == 'grid_reference') {
		required.push(row.grid_reference);
	} else {
		required.push(row.myriad);
		optional.push(row.hectad);

		if (mode == 'recent') {
			required.push(row.hectad);
		}
	}
	optional.push(row.grid_reference);
	if (mode == 'takenday') {
		required.push(row.takenday);
	} else {
		optional.push(row.takenyear);
		optional.push(row.takenmonth);
	}
	optional.push(row.takenday);

	var splits = ['contexts','groups','tags','snippets','subjects'];
	for(var g=0;g<splits.length;g++)
		if (row[splits[g]] && row[splits[g]].length > 5) {
			var list = row[splits[g]].replace(/(^\s*_SEP_\s*|\s*_SEP_\s*$)/g,'').replace(/(top|subject):/g,'').split(/ _SEP_ /);
			for(var i = 0; i < list.length; i++) {
				optional.push('"'+list[i]+'"');
			}
		}

	if (row.place && row.place.length > 2) {
		optional.push('"'+row.place+'"');
	}
	/* disabled for now, they greatly inflate the number of matches (partiucully 'England'!)
	if (row.county && row.county.length > 2) {
		optional.push('"'+row.county+'"');
	}
	if (row.country && row.country.length > 2) {
		optional.push('"'+row.country+'"');
	}*/
	if (mode == 'contributor') {
		required.push("user"+row.user_id);
	} else {
		optional.push("user"+row.user_id);
	}
	if (row.imageclass) {
		optional.push('"'+row.imageclass+'"');
	}

	var match = '';
	if (required.length)
		match = required.join(" ");
	if (optional.length)
		match = match + " ("+optional.join("|")+")";

	var data = {
		mode: mode,
		select: 'id,title,myriad,hectad,grid_reference,takenyear,takenmonth,takenday,hash,realname,user_id,place,county,country,hash,scenti,width,height',
		match: match,
		where: 'id!='+gridimage_id,
		limit: 10
	};
	if (mode == 'centisquare') {
		data['where'] = data['where']+' and scenti = '+row.scenti;
	} else if (mode == 'recent') {
		data['order'] = 'takenday desc';
	}

	$.ajax('https://api.geograph.org.uk/api-facetql.php',{
		data: data,
		cache: true,
		dataType: 'json'
	}).done(function(data){
		if (data && data.rows && data.rows.length) {
			$('#related .thumbs').empty();
			var attrib = 'loading="lazy" src'; //the first is always a normal src, not data-src
			$.each(data.rows, function(index,value) {
				var caption = [];
				if (row.takenday == value.takenday)						caption.push("taken same Day");
				else if (row.takenmonth == value.takenmonth)					caption.push("taken same Month");
				else if (row.takenyear == value.takenyear)					caption.push("taken same Year");
				if (row.scenti == value.scenti)							caption.push("same Centisquare");
				else if (row.grid_reference == value.grid_reference)				caption.push("same 1km Square");
				else if (row.hectad == value.hectad)						caption.push("same Hectad");
				else if (row.myriad == value.myriad)						caption.push("same Myriad");
				if (row.user_id == value.user_id)						caption.push("same Contributor");
				if (row.place == value.place && row.grid_reference != value.grid_reference)	caption.push("also near "+value.place);

				var width=120, height=120;
				if (value.width > value.height) { //landscape
					height=Math.round(120*value.height/value.width);
				} else { //portrait
					width=Math.round(120*value.width/value.height);
				}
				value.thumbnail = getGeographUrl(value.id, value.hash, 'small');
				window.requestAnimationFrame(function() {
					$('#related .thumbs').append('<div class="thumb"><a href="/photo/'+value.id+'" title="'+value.grid_reference+' : '+value.title+' by '+value.realname+' /'+space_date(value.takenday)+'\n'+caption.join(', ')+'" class="i"><img '+attrib+'="'+value.thumbnail+'" width="'+width+'" height="'+height+'"></a></div>');
				});
				if (!supportsLazyLoad)
					attrib = 'data-src';
			});

			setTimeout(function() { //delay adding these, to give some time for thumbnails to load, minimising CLS!
			$('#related .thumbs').append('<br style=clear:both>');

			$('#related .thumbs').append('<p><a href="/related.php?id='+encodeURIComponent(gridimage_id)+'&method=quick">More related images</a>');

			$('#related .thumbs').append('<p><a href="/browser/#!/q='+encodeURIComponent(match)+'">View more results in browser</a>');

			$('#related .thumbs').append('<p><a href="/finder/grouped.php?q='+row.grid_reference+'&number=3&group=all">Whats around here</a>');
			}, 2000);

			if (!supportsLazyLoad)
				setTimeout(initLazy,50);
		} else {
			$('#related .thumbs').html("No Related Images Found");
		}

	});
}

/**************************************
* Utility Functions
*/


function getGeographUrl(gridimage_id, hash, size) {

	yz=zeroFill(Math.floor(gridimage_id/1000000),2);
	ab=zeroFill(Math.floor((gridimage_id%1000000)/10000),2);
	cd=zeroFill(Math.floor((gridimage_id%10000)/100),2);
	abcdef=zeroFill(gridimage_id,6);

	if (yz == '00') {
		fullpath="/photos/"+ab+"/"+cd+"/"+abcdef+"_"+hash;
	} else {
		fullpath="/geophotos/"+yz+"/"+ab+"/"+cd+"/"+abcdef+"_"+hash;
	}

	switch(size) {
		case 'full': return "https://s0.geograph.org.uk"+fullpath+".jpg"; break;
		case 'med': return "https://s"+(gridimage_id%4)+".geograph.org.uk"+fullpath+"_213x160.jpg"; break;
		case 'small':
		default: return "https://s"+(gridimage_id%4)+".geograph.org.uk"+fullpath+"_120x120.jpg";
	}
}

function zeroFill(number, width) {
	width -= number.toString().length;
	if (width > 0) {
		return new Array(width + (/\./.test(number)?2:1)).join('0') + number;
	}
	return number + "";
}
function space_date(datestr) {
	return datestr.substring(0,4)+'-'+datestr.substring(4,6)+'-'+datestr.substring(6,8);
}

/**
* window.requestAnimationFrame()
* version 0.0.0
* Browser Compatibility:
* https://developer.mozilla.org/en-US/docs/Web/API/window/requestAnimationFrame#browser_compatibility
*/
if (!window.requestAnimationFrame) {
  window.requestAnimationFrame = function (callback) {
    return window.setTimeout(function () {
      callback(Date.now());
    }, 1000 / 60);
  };
}
