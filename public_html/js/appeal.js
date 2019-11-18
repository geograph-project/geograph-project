if (window.innerWidth < 1100) {
        //return;
} else if (document.cookie && document.cookie.length > 1 && document.cookie.indexOf('appeal=') > -1 && location.href.indexOf('appeal') == -1) {
        //return;
} else {

	//we have to be extra careful checking if a real jquery, as jQl creates a fake jQuery object. 
	if (typeof jQuery === "undefined" || jQuery === null || typeof jQuery.fn === "undefined" || typeof jQuery.fn.load === "undefined") {
		jQl.loadjQ('https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js');
	}
	
	$(function() { 

		$('head').append('<style>#appeal_block { box-sizing: border-box; position:absolute; width:900px; top:0; left:200px; max-width:80%; \
			 background-color:BlanchedAlmond; font-family:arial; line-height:1.3em; padding: 10px; \
			 z-index:2000; border-bottom: 2px solid black; line-height:1.3em; border-left:2px solid #006; border-right: 2px solid #006; } \
			#appeal_block div.break { height:10px; }\
			#appeal_block a.closer { display:block; float:right; margin-top:-9px; margin-right:-9px; background-color:silver; font-weight: bold; padding:10px; color:red; pointer:hand;} \
			#appeal_block div.float { float:right; margin-left:10px; text-align: center } \
			#appeal_block div a.btn { padding:20px; font-weight:bold; display:block; width:180px; background-color:purple; color:white; border-radius:10px; text-decoration:none; } \
		</style>');
	
		$('body').prepend('<div id="appeal_block"><a class=closer href="#">Close</a> \
			Help Geograph move to the cloud!<div class=break></div>\
			Our target of &pound;7,500 has been reached.  Thank you to everyone who donated - a great effort.  We still need money for our running costs so please continue to donate.\
			<div class=break></div> \
			<div class=float><a href="/help/appeal" class="btn">Donate Now</a></div> \
			<i>Thank you</i>. </div>');

		$('body #appeal_block a.closer').click(function() {
			$('#appeal_block').hide('fast');

			var value = 1;			
			//this is only a session cookie.
			document.cookie = "appeal="+value+"; path=/";

			return false;
		});

	});
}


