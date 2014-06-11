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
		if ($('#hovercard').length > 0) {
			var m = $(this).attr('href').match(/\/(\d+)$/);
			$('#hovercard iframe').attr('src','/frame.php?id='+m[1]);
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
			$('#hovercard').remove();
		},500);
	}).mousemove(function(event){
		$("#hovercard").css({
			'top':(event.pageY + 20) + "px",
			'left':(event.pageX + 20) + "px"
		});
	});
}

function displayHover(link,event) {
	var m = link.match(/\/(\d+)$/);
	if ($('#hovercard').length ==0) {
		$("body").append('<div id="hovercard" style="position:absolute;z-index:1000;top:'+(event.pageY+20)+'px;left:'+(event.pageX+20)+'px">'+
			'<iframe src="/frame.php?id='+m[1]+'" width="550" height="280" frameborder="0"></iframe></div>');
	} else {
		$('#hovercard iframe').attr('src','/frame.php?id='+m[1]);
	}
}
