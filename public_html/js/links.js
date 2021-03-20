// note this needs either jquery, or geograph.js loaded

if ('sendBeacon' in navigator) {

	//we have to be extra careful checking if a real jquery, as jQl creates a fake jQuery object. 
	if (typeof jQuery === "undefined" || jQuery === null || typeof jQuery.fn === "undefined" || typeof jQuery.fn.load === "undefined") {
		jQl.loadjQ('https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js');
	}

	$(function() {

		$('a').click(function() {
			var href= $(this).attr("href");
			if (href && href.indexOf('/') != 0) {
				var data = JSON.stringify({'href': href, 'source': window.location.href	});
				navigator.sendBeacon("/stuff/record_link.php", data);
			}
		});

	});
}

