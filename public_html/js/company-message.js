if (document.cookie && document.cookie.length > 1 && document.cookie.indexOf('company2026=') > -1 && location.href.indexOf('company') == -1) {
        //return;
} else {

	//we have to be extra careful checking if a real jquery, as jQl creates a fake jQuery object.
	if (typeof jQuery === "undefined" || jQuery === null || typeof jQuery.fn === "undefined" || typeof jQuery.fn.load === "undefined") {
		jQl.loadjQ('https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js');
	}

	$(function() {

		$('head').append(`<style>#appeal_block { box-sizing: border-box; color:black;
			 background-color:BlanchedAlmond; font-family:arial; line-height:1.3em; padding: 10px; margin-bottom:20px }
			#appeal_block div.break { height:10px; }
			#appeal_block a { color:blue; }
			#appeal_block a.closer { display:block; float:right; margin-top:-12px; margin-right:-12px; background-color:#e4e4fc; padding:10px; color:black; pointer:hand; border-radius:10px;}
			#appeal_block div.float { float:right; margin-left:10px; text-align: center }
			#appeal_block div a.btn { padding:10px; font-weight:bold; display:block; width:150px; background-color:purple; color:white; border-radius:10px; text-decoration:none; }
		</style>`);

		$('#maincontent_block').prepend(`<div id="appeal_block"><a class=closer href="#">Close</a>\
Your opinion matters! The Geograph AGM is held <a a href="https://www.geograph.org.uk/events/">this June in Nottingham</a>.
If you can't join us in person, please ensure your voice is heard by casting a proxy vote.<br><br>
You can find the link to the Company Minisite at the top of <a href="/profile.php">your profile</a>, where you can also review recent messages regarding the meeting.
Thank you!</div>`);

		$('body #appeal_block a.closer').click(function() {
			$('#appeal_block').hide('fast');

			var value = 1;
			const days = 30;
			const seconds = days * 24 * 60 * 60;
			document.cookie = "company2026=" + value + "; max-age=" + seconds + "; path=/; SameSite=Lax";

			return false;
		});
	});
}


