//Setups a hover based Preview Iframe. 
//uses jQuery, which it will load if NOT already loaded.  
//however does need jQl loaded (provided by geograph.js) 

var mytimer;
var timeon = 800;

//we have to be extra careful checking if a real jquery, as jQl creates a fake jQuery object. 
if (typeof jQuery === "undefined" || jQuery === null || typeof jQuery.fn === "undefined" || typeof jQuery.fn.load === "undefined") {
	jQl.loadjQ('http://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js');
}


$(function() {
	$.ajaxSetup({
	 	cache: true
	});
	$.getScript('/js/jquery.hoverIntent.minified.js',function() {
                initHover();
	});
});

function initHover() {
	$('a[href*="/photo/"]').hoverIntent(function(event) {
		if (mytimer) clearTimeout(mytimer);
		if ($('#hovercard').length >0) {
			displayHover($(this).attr('href'),event);
			return;
		}
		var href = $(this).attr('href');
		mytimer = setTimeout(function() {
			displayHover(href,event);
			timeon = 300;
		},timeon);
	},function() {
		if (mytimer) clearTimeout(mytimer);
		mytimer = setTimeout(function() {
			$('#hovercard').hide();
		},500);
	});
}

function displayHover(link,event) {
	if ($(window).height() > 540 && $(window).width() > 540 && (m = link.match(/photo\/(\d+)/))) {
		url = "http://t0.geograph.org.uk/tile-info.php?id="+m[1];
	} else {
		return false;
	}

	if ($('#hovercard').length ==0) {
		$('body').append('<iframe id=hovercard src="'+url+'" height=250 width="100%" style="width:100%;height:250;bottom:0;position:fixed;border:0;z-index:1000" frameborder=0></iframe>');

		$('#hovercard').load(function() { $(this).show(); } );

	} else {
		//http://www.ozzu.com/programming-forum/ignoring-iframes-with-javascript-history-t67189.html
		var w = $('#hovercard').get(0);
		if (w.contentWindow && w.contentWindow.location && w.contentWindow.location.replace) {
			w.contentWindow.location.replace(url);
			//if ($('#hovercard').is(":visible"))
				setTimeout(function () { $('#hovercard').show(); }, 1);
		} else {
			$('#hovercard').attr('src',url); //calls the load function
		}
	}

	if ((event.pageY-$(window).scrollTop()) > ($(window).height()-260)) {
		//if ($('#hovercard').css("border-bottom-style") != "solid")
			$('#hovercard').css({top:0,bottom:'inherit','border-bottom':'2px solid black','border-top':0});
	} else {
		//if ($('#hovercard').css("border-top-style") != "solid")
			$('#hovercard').css({top:'inherit',bottom:0,'border-bottom':0,'border-top':'2px solid black'});
	}
}
