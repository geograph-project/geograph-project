//enabled lazy loading of images
//uses jQuery, which it will load if NOT already loaded.  
//however does need jQl loaded (provided by geograph.js) 


//we have to be extra careful checking if a real jquery, as jQl creates a fake jQuery object. 
if (typeof jQuery === "undefined" || jQuery === null || typeof jQuery.fn === "undefined" || typeof jQuery.fn.load === "undefined") {
	jQl.loadjQ('https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js');
}


$(function() {
	$.ajaxSetup({
	 	cache: true
	});
	$.getScript('//s1.geograph.org.uk/js/jquery.lazyload.js',function() {
                initLazy();
		if (typeof initLazy2 == 'function') { 
			initLazy2(); 
		}
	});
});


function initLazy() {

	$("img[data-src]").lazyload({
		data_attribute: 'src',
		effect: 'fadeIn', 
		container: ($('#scroller').length == 1)?$('#scroller'):window
	});
}
