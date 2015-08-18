//enabled lazy loading of images
//uses jQuery, which it will load if NOT already loaded.  
//however does need jQl loaded (provided by geograph.js) 


//we have to be extra careful checking if a real jquery, as jQl creates a fake jQuery object. 
if (typeof jQuery === "undefined" || jQuery === null || typeof jQuery.fn === "undefined" || typeof jQuery.fn.load === "undefined") {
	jQl.loadjQ('http://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js');
}


$(function() {
	$.ajaxSetup({
	 	cache: true
	});
	$.getScript('http://s1.geograph.org.uk/js/jquery.sonar.min.js',function() {
                initLazy();
		if (typeof initLazy2 == 'function') { 
			initLazy2(); 
		}
	});
});


function initLazy() {
        jQuery( 'img[data-src]' ).bind( 'scrollin', function() {
                var img = this, $img = jQuery(img);

                $img.hide();

                $img.unbind( 'scrollin' ); // clean up binding
		if ( src = $img.attr( 'data-src' ) ) {
	                img.src = src;
	                $img.attr( 'data-src', '' );
		}

                $img.css('height','');

                $img.fadeIn();
        });
}
