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

		$('head').append('<style>#appeal_block { box-sizing: border-box; color:black; \
			 background-color:BlanchedAlmond; font-family:arial; line-height:1.3em; padding: 10px; margin-bottom:20px } \
			#appeal_block div.break { height:10px; }\
			#appeal_block a { color:blue; }\
			#appeal_block a.closer { display:block; float:right; margin-top:-9px; margin-right:-9px; background-color:silver; font-weight: bold; padding:10px; color:red; pointer:hand;} \
			#appeal_block div.float { float:right; margin-left:10px; text-align: center } \
			#appeal_block div a.btn { padding:10px; font-weight:bold; display:block; width:150px; background-color:purple; color:white; border-radius:10px; text-decoration:none; } \
		</style>');

		$('#maincontent_block').prepend('<div id="appeal_block"><a class=closer href="#">Close</a>\
<b>Please donate to Geograph!</b><div class=break></div>\
We provide completely free access to over 7 million images that support activities such as school projects, \
local and family history, parish magazines and news articles, planning walks and holidays, or just armchair \
exploring. However, it costs thousands of pounds every year to run Geograph. Alongside fundraising efforts \
such as our <a href="/calendar/">calendar project</a> and recent advertising trial, we are primarily reliant on donations from users \
to keep the site up and running. Hosting and support costs continue to rise in line with current economic \
conditions.<div class=break></div>\
<div class=float><a href="/help/appeal" class="btn">Donate Now</a></div>\
If you value Geograph, please consider <a href="/help/appeal">donating now</a>. We understand these are difficult times, but are pleased \
to accept one-off donations, or even better, regular monthly gifts, with Gift Aid if you are eligible. \
Thank you!</div>');

		$('body #appeal_block a.closer').click(function() {
			$('#appeal_block').hide('fast');

			var value = 1;
			//this is only a session cookie.
			document.cookie = "appeal="+value+"; path=/";

			return false;
		});

	});
}


