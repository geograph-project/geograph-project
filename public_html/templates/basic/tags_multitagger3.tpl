{assign var="page_title" value="Multi Tagger"}
{include file="_std_begin.tpl"}
{literal}
<style>
.tagbar {
	float:right;
	width:200px;
	font-size:0.9em;
	height:500px
}

.tagbar ul { list-style-type: none; margin: 0; padding: 0; margin-bottom: 10px; display:block; overflow:auto; height:100%}
.tagbar li { margin: 2px; padding: 3px; width: 168px; border:1px solid silver; text }

.tagbar li a { float:right; color:red }

.tagbar li.highlight label { font-weight:bold }
.tagbar li.highlight { border: 1px solid black; background-color:#eeeeee; }

#mainimage {
	height:500px;
}

#thumbar {
	width:100%;
	height:145px;
	overflow:auto;
	white-space:nowrap;
}

#thumbar .highlight{
	background-color:yellow;
	height:120px;
}

#message b {
	background-color:pink;
}
</style>
<h2 style="float:left;margin:0;margin-right:20px">Multi Tagger</h2>

<p id="message">This page allows you to run a keyword search to find images, and then add tags to each in quick succession.</p>

  <script type="text/javascript">

  function focusBox() {
  	if (el = document.getElementById('fq')) {
  		el.focus();
  	}
  }
  AttachEvent(window,'load',focusBox,false);

  </script>

{/literal}

	<form action="javascript:void()" method="get" onsubmit="return updateImages()" style="clear:both">
		<div class="interestBox">
			<label for="fq">Keywords</label>: <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
			<sup><a href="/article/Word-Searching-on-Geograph" class="about" title="More details about Keyword Searching">About</a></sup>
			<input type="submit" value="Search"/> <br/>

			<label for="onlymine">Only your images?</label> <input type="checkbox" name="onlymine" id="onlymine" {if $onlymine}checked{/if} {dynamic}value="{$user->user_id|escape:'html'}"{/dynamic}/> -
			<label for="onlynull">Only images without any tags?</label> <input type="checkbox" name="onlynull" id="onlynull" {if $onlynull}checked{/if}/>
		</div>
	</form>

<div style="width:940px">

	<div id="" class="tagbar" style="background-color:#cccccc">
		<ul id="mainlist" class="Sortable">
			List of tags will appear here.<br/><br/> This list will persist as you switch between images for quick reuse. <br/><br/>

			TIP: Drag and drop the tags in the list, to organise them for ease of use!<br/><br/>

			<b>Key:</b></br>

			<li><a href="javascript:void()" title="remove tag from list (does not delete the tag from this image)">X</a><input type=checkbox disabled> <label>Inactive tag</label></li>
			<li class="highlight"><a href="javascript:void()" title="remove tag from list (does not delete the tag from this image)">X</a><input type=checkbox checked onclick="this.checked=true"> <label>Active tag</label></li>

			Only 'active' tags are currently applied to the current image.<br/><br/> To enable a tag click the checkbox <input type=checkbox>
		</ul>
	</div>

	<div id="" class="tagbar" style="margin-top:4px">
		{assign var=tab value="4"}
		<table style="height:100%;width:100%" cellspacing="0" cellpadding="0" border="0"><tr><td height="1.1em" width="100%">
		<div class="tabHolder" style="font-size:0.8em">
			<a class="tab{if $tab == 1}Selected{/if} nowrap" id="tab1" onclick="tabClick('tab','div',1,4);loadTagSuggestions('/tags/primary.json.php?')">Context</a>
			<a class="tab{if $tab == 2}Selected{/if} nowrap" id="tab2" onclick="tabClick('tab','div',2,4);loadTopics(gridimage_id);">Topics</a>
			<a class="tab{if $tab == 3}Selected{/if} nowrap" id="tab3" onclick="tabClick('tab','div',3,4);loadTagSuggestions('/tags/recent.json.php?');">Recent</a>
			<a class="tab{if $tab == 4}Selected{/if} nowrap" id="tab4" onclick="tabClick('tab','div',4,4)">Add</a>
		</div>
		</td></tr><tr id="div4" style="display:block"><td height="1.2em" width="100%">
		<form class="interestBox" onsubmit="return false()">
			<input type="text" name="__newtag" size="16" onkeyup="{literal}if (this.value.length > 2) {loadTagSuggestions('/tags/tags.json.php?q='+encodeURIComponent(this.value));} {/literal}"/> <input type="button" value="Add" onclick="createTag(this.form.elements['__newtag'].value)"/><br/>
		</form>
		</td></tr><tr><td height="96%" width="100%">
		<ul id="suggestlist" class="interestBox">
			Tag Suggestions will appear here.<br/><br/>

			<li><input type=checkbox disabled> <label>Inactive tag</label></li>

			To enable a tag click the checkbox <input type=checkbox>, it will jump to right hand list.
		</ul>
		</td></tr></table>
	</div>

	<div id="mainimage" style="">
		<br/>
		<p>This is an experimental new method to make adding tags to successive images easier. Run a search above to find images. </p>

		<p>Works best if work in batchs by subject. So for example if you submitted a lot of churches, then search for "Church" and add tags. Repeat for other subjects.</p>

		<p>To use a 'tag' from the left list, tick the checkbox. It just jump to the right hand list (and become active).</p>

		<p style="background-color:pink;padding:4px">NOTE: Only tags with a 'tick' <input type=checkbox checked> are 'active' on the current image. Tags stay in the right hand list between image for quick reuse, but to apply a tag to the image, must enable the tag by ticking.</p>

		<h3>Current Limitations</h3>
		<ul>
			<li>The 'search' for images, is based on the main search engine, can only load the first 1000 images of any given search.<br/><br/></li>

			<li>The 'Only images without any tags?' filter is not realtime - so if tags recently added, might still show in results.</li>
		</ul>
	</div>

	<div id="thumbar" style="">

	</div>

	<br style="clear:both"/>

	<form style="display:block" onsubmit="return false()">
		<input type="button" value="Clear Thumbnails" id="clearThumb" onclick="clearThumbs()" disabled/>
		Create Public Tags? <input type="checkbox" id="createPublic" checked/> <small>(tags on other peoples images will always be private anyway)</small>
	</form>
</div>



	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js" type="text/javascript"></script>
	<script src="{"/js/ui/jquery.ui.core.js"|revision}"></script>
	<script src="{"/js/ui/jquery.ui.widget.js"|revision}"></script>
	<script src="{"/js/ui/jquery.ui.mouse.js"|revision}"></script>
	<!--script src="{"/js/ui/jquery.ui.draggable.js"|revision}"></script>
	<script src="{"/js/ui/jquery.ui.droppable.js"|revision}"></script-->
	<script src="{"/js/ui/jquery.ui.sortable.js"|revision}"></script>


{literal}
<script type="text/javascript">

///////////////////////////////////////
// Functions for loading images

	function updateImages() {
		query = document.getElementById("fq").value;
		if (document.getElementById("onlymine").checked) {
			query = query + " user_id:" + document.getElementById("onlymine").value;
		}
		if (document.getElementById("onlynull").checked) {
			query = query + " tags:^null";
		}
		url = "/syndicator.php?format=JSON&text="+encodeURIComponent(query);
		//if (page > 1) {
		//	url = url + "&page=".parseInt(page,10);
		//}
		loadImages(url);
		return false;
	}

	function loadImages(url) {
		var ele = $('#aloadMore');
		if (ele.length > 0) {
			ele.remove();
		}

		$.ajax({
			url: url+'&callback=?',
			dataType: 'jsonp',
			jsonpCallback: 'serveCallback', //prevents cache busting varying callback name
			success: function(data) {
				displayImages(data);
			}
		});
		$("#message").html('Loading...');
	}

	var saved = new Object;

	function displayImages(data) {
		if (!data || data.length < 1 || data.items.length < 1) {
			alert("no images found");
		}
		count = 0;
		$.each(data.items, function(i,item){
			$("#thumbar").append('<a href="javascript:void(showMain('+item.guid+'));" class="thumb'+item.guid+'" title="'+item.title+'">'+item.thumbTag+'</a> ');
			saved[item.guid] = item;
			count++;
		});

		showMain(data.items[0]);

		if (data.nextURL) {
			$("#thumbar").append('<a href="javascript:void(loadImages(\''+data.nextURL+'?\'));" id="aloadMore" title=\"'+data.nextURL+'?\">Load More...</a> ');
		}

		if (count > 0) {
		  $("#message").html('Loaded '+count+' of "'+data.description+'" images');
		}

		$('#clearThumb').attr('disabled',false);
	}

	var gridimage_id = null;

	function showMain(item) {
		if (typeof item != 'object') {
			item = saved[item];
		}

		if ($('.thumb'+item.guid).length > 0) {
			var ele = $('.thumb'+item.guid+' img').first();
			if (ele.width() > ele.height()) {
				size = ' style="width:460px"';
			} else {
				size = ' style="height:400px"';
			}
		} else {
			size = '';
		}

		gridimage_id = item.guid;
		$("#mainimage").html('<a href="'+item.link+'" title="'+item.title+'" target="_blank"><img src="'+_fullsize(item.thumb)+'" '+size+'/></a>');

		$("#mainimage").append('<br/><b>'+item.title+'</b>');
		$("#mainimage").append(' by <a href="'+item.source+'">'+item.author+'</a>');
		if (item.description)
			$("#mainimage").append('<br/><small>'+item.description+'</small>');
		if (item.category)
			$("#mainimage").append('<br/>Category: <b>'+item.category+'</b> <small><small><br/><br/><a href="'+item.link+'" target="_blank">View full photo page in new window</a></small></small>');

		if ($('#tab1').hasClass('tabSelected')) {
			loadTagSuggestions('/tags/primary.json.php?');
		} else if ($('#tab2').hasClass('tabSelected')) {
			loadTopics(gridimage_id)
		}

		$('#thumbar a.highlight').removeClass('highlight');
		$('.thumb'+item.guid).addClass('highlight');

		var positionLeft = $('.thumb'+item.guid).first().position().left;
		var scrollLeft = $('#thumbar').scrollLeft();
		$('#thumbar').scrollLeft(positionLeft + scrollLeft - 250);

		refreshMainList();

		if ($('#nextButton').length < 1) {
			$("#mainimage").before('<div id="nextButton" style="float:right"><input type=button value="next" onclick="nextImage()"/></div>');
		}

	}

	function _fullsize(thumbnail) {
		return thumbnail.replace(/_\d+x\d+\.jpg$/,'.jpg').replace(/s[1-9]\.geograph/,'s0.geograph');
	}

	function nextImage() {
		if (gridimage_id) {
			var ele=$('.thumb'+gridimage_id);
			if (ele.length > 0) {
				var ele2 = ele.next();

				if (ele2.length > 0) {
					//ele2.click(); //todo -wtf does this not work?
					if (ele2.attr('id') == 'aloadMore') {
						loadImages(ele2.attr('title'));
					} else {
						showMain(ele2.attr('className').replace(/[^\d]/g,''));
					}

				} else {
					alert("no more images");
				}
			}
		}
	}

	function clearThumbs() {
		$('#thumbar').empty();
		$('#clearThumb').attr('disabled',true);
		$('#nextButton').remove();
	}

/////////////////////////////////////
// functions for tag lists

	function refreshMainList() {
		if (gridimage_id) {

			var url = '/tags/tags.json.php?gridimage_id='+encodeURIComponent(gridimage_id);

			$.getJSON(url+"&callback=?",

				// on completion, process the results
				function (data) {
					if (data) {

						for(var tag_id in data) {
							var text = data[tag_id].tag;
							if (data[tag_id].prefix && data[tag_id].prefix!='term' && data[tag_id].prefix!='cluster' && data[tag_id].prefix!='wiki') {
								text = data[tag_id].prefix+':'+text;
							}
							text = text.replace(/<[^>]*>/ig, "");
							text = text.replace(/['"]+/ig, " ");

							createTag(text,false);
						}
					}
				});

			$('#mainlist li input').each(function(index) {
				if ($(this).attr('checked')) {
					$(this).attr('checked',false);
					toggleTag($(this).get(0),false);
				}
			});
		} else {
			$('#suggestlist').empty().html('Select an image first!');
		}
	}

	var uniqueid = 1;

	function loadTagSuggestions(url) {

		$.getJSON(url+"&callback=?",

			// on completion, process the results
			function (data) {
				if (data) {

					var ele = $('#suggestlist').empty();

					for(var tag_id in data) {

						var text = data[tag_id].tag;
						if (data[tag_id].prefix && data[tag_id].prefix!='term' && data[tag_id].prefix!='cluster' && data[tag_id].prefix!='wiki') {
							text = data[tag_id].prefix+':'+text;
						}
						text = text.replace(/<[^>]*>/ig, "");
						text = text.replace(/['"]+/ig, " ");

						//id = uniqueid; uniqueid++;
						id = $.base64Encode(text).replace(/=/g,'_');
						if (!document.getElementById(id))
							ele.append(
									['<li id="li-'+id+'">',
									'<input type="checkbox" title="use this tag" id="'+id+'" onclick="addTag(this)" value="'+text+'"/>',
									'<label for="'+id+'">'+text+'</label>',
									'</li>'].join('')
							);

					}
				}
			});
	}

	function loadTopics(guid) {
		if (guid) {
			loadTagSuggestions('/tags/topics.json.php?gridimage_id='+encodeURIComponent(guid));
		} else {
			$('#suggestlist').empty().html('Select an image first!');
		}
	}


/////////////////////////////////////
// functions for manipulating tags...

function removeTag(id) {
	$('#li-'+id).remove();
}

function toggleTag(that,sendToServer) {
	if (typeof sendToServer == 'undefined' ) sendToServer = 'true';

	var id = that.id;
	if (that.checked) {
		$('#li-'+id).addClass('highlight');
		if (sendToServer)
			submitTag(that.value,2)
	} else {
		$('#li-'+id).removeClass('highlight');
		if (sendToServer)
			submitTag(that.value,0)
	}

	var ccount = 0;
	var tcount = 0;
	$('#mainlist input').each(function(i) {
		if ($(this).attr('checked'))
			($(this).attr('value').indexOf('top:') == 0)?ccount++:tcount++;
	});
	if (ccount == 0) ccount = "<b>"+ccount;
	if (tcount == 0) tcount = "<b>"+tcount;

	$('#message').html("Current image: "+ccount+" context</b> and "+tcount+" tags</b>");
}

function addTag(that,sendToServer) {
	if (typeof sendToServer == 'undefined' ) sendToServer = 'true';

	var id = that.id;
	var label = $('#li-'+id+' label').text();
	var value = that.value;

	removeTag(id);
	if ($('#mainlist').text().indexOf('will appear here') > -1) {
		$('#mainlist').empty();
	}
	$('#mainlist').append(
		['<li id="li-'+id+'">',
		'<a href="javascript:void(removeTag(\''+id+'\'))" title="remove tag from list (does not delete the tag from this image)">X</a>',
		'<input type="checkbox" title="enable/disable this tag" id="'+id+'" onclick="toggleTag(this)" value="'+value+'" checked/>',
		'<label for="'+id+'">'+label+'</label>',
		'</li>'].join('')
	);
	toggleTag(document.getElementById(id),sendToServer);
}

function createTag(text,sendToServer) {
	if (typeof sendToServer == 'undefined' ) sendToServer = 'true';

	if ($('#mainlist').text().indexOf('will appear here') > -1) {
		$('#mainlist').empty();
	}

	id = $.base64Encode(text).replace(/=/g,'_');

	var ele = $('#'+id);

	if (ele.length>0) {
		//we have one!
		if (ele.attr('title') == 'enable/disable this tag') { //todo - have a more robust method!
			//its in the main list, so make sure its ticked

			if (ele.attr('checked') == true) {
				//do nothing
			} else {
				//tick it
				ele.attr('checked',true);
				toggleTag(document.getElementById(id),sendToServer);
			}
		} else {
			//its somewhere in the suggestions already, so lets 'promote' it :)
			addTag(document.getElementById(id),sendToServer);
		}
	} else {
		//need to create it

		$('#mainlist').append(
				['<li id="li-'+id+'">',
				'<a href="javascript:void(removeTag(\''+id+'\'))" title="remove tag from list (does not delete the tag from this image)">X</a>',
				'<input type="checkbox" title="enable/disable this tag" id="'+id+'" onclick="toggleTag(this)" value="'+text+'" checked/>',
				'<label for="'+id+'">'+text+'</label>',
				'</li>'].join('')
		);
		toggleTag(document.getElementById(id),sendToServer);
	}
}

	function submitTag(tag,status) {
		if (gridimage_id) {
			var data = new Object;
			data['tag'] = tag;
			if (status == 2 && !$('#createPublic').attr('checked'))
				data['status'] = 1;
			else
				data['status'] = status;

			data['gridimage_id'] = gridimage_id;

			$.ajax({
				url: "/tags/tagger.json.php",
				data: data
			});
		}
	}

/////////////////////////////////////

$(function() {
	$(".Sortable").sortable();

});



	/**
	 * jQuery BASE64 functions
	 *
	 * 	<code>
	 * 		Encodes the given data with base64.
	 * 		String $.base64Encode ( String str )
	 *		<br />
	 * 		Decodes a base64 encoded data.
	 * 		String $.base64Decode ( String str )
	 * 	</code>
	 *
	 * Encodes and Decodes the given data in base64.
	 * This encoding is designed to make binary data survive transport through transport layers that are not 8-bit clean, such as mail bodies.
	 * Base64-encoded data takes about 33% more space than the original data.
	 * This javascript code is used to encode / decode data using base64 (this encoding is designed to make binary data survive transport through transport layers that are not 8-bit clean). Script is fully compatible with UTF-8 encoding. You can use base64 encoded data as simple encryption mechanism.
	 * If you plan using UTF-8 encoding in your project don't forget to set the page encoding to UTF-8 (Content-Type meta tag).
	 * This function orginally get from the WebToolkit and rewrite for using as the jQuery plugin.
	 *
	 * Example
	 * 	Code
	 * 		<code>
	 * 			$.base64Encode("I'm Persian.");
	 * 		</code>
	 * 	Result
	 * 		<code>
	 * 			"SSdtIFBlcnNpYW4u"
	 * 		</code>
	 * 	Code
	 * 		<code>
	 * 			$.base64Decode("SSdtIFBlcnNpYW4u");
	 * 		</code>
	 * 	Result
	 * 		<code>
	 * 			"I'm Persian."
	 * 		</code>
	 *
	 * @alias Muhammad Hussein Fattahizadeh < muhammad [AT] semnanweb [DOT] com >
	 * @link http://www.semnanweb.com/jquery-plugin/base64.html
	 * @see http://www.webtoolkit.info/
	 * @license http://www.gnu.org/licenses/gpl.html [GNU General Public License]
	 * @param {jQuery} {base64Encode:function(input))
	 * @param {jQuery} {base64Decode:function(input))
	 * @return string
	 */

	(function($){

		var keyString = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";

		var uTF8Encode = function(string) {
			string = string.replace(/\x0d\x0a/g, "\x0a");
			var output = "";
			for (var n = 0; n < string.length; n++) {
				var c = string.charCodeAt(n);
				if (c < 128) {
					output += String.fromCharCode(c);
				} else if ((c > 127) && (c < 2048)) {
					output += String.fromCharCode((c >> 6) | 192);
					output += String.fromCharCode((c & 63) | 128);
				} else {
					output += String.fromCharCode((c >> 12) | 224);
					output += String.fromCharCode(((c >> 6) & 63) | 128);
					output += String.fromCharCode((c & 63) | 128);
				}
			}
			return output;
		};

		var uTF8Decode = function(input) {
			var string = "";
			var i = 0;
			var c = c1 = c2 = 0;
			while ( i < input.length ) {
				c = input.charCodeAt(i);
				if (c < 128) {
					string += String.fromCharCode(c);
					i++;
				} else if ((c > 191) && (c < 224)) {
					c2 = input.charCodeAt(i+1);
					string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
					i += 2;
				} else {
					c2 = input.charCodeAt(i+1);
					c3 = input.charCodeAt(i+2);
					string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
					i += 3;
				}
			}
			return string;
		}

		$.extend({
			base64Encode: function(input) {
				var output = "";
				var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
				var i = 0;
				input = uTF8Encode(input);
				while (i < input.length) {
					chr1 = input.charCodeAt(i++);
					chr2 = input.charCodeAt(i++);
					chr3 = input.charCodeAt(i++);
					enc1 = chr1 >> 2;
					enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
					enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
					enc4 = chr3 & 63;
					if (isNaN(chr2)) {
						enc3 = enc4 = 64;
					} else if (isNaN(chr3)) {
						enc4 = 64;
					}
					output = output + keyString.charAt(enc1) + keyString.charAt(enc2) + keyString.charAt(enc3) + keyString.charAt(enc4);
				}
				return output;
			},
			base64Decode: function(input) {
				var output = "";
				var chr1, chr2, chr3;
				var enc1, enc2, enc3, enc4;
				var i = 0;
				input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");
				while (i < input.length) {
					enc1 = keyString.indexOf(input.charAt(i++));
					enc2 = keyString.indexOf(input.charAt(i++));
					enc3 = keyString.indexOf(input.charAt(i++));
					enc4 = keyString.indexOf(input.charAt(i++));
					chr1 = (enc1 << 2) | (enc2 >> 4);
					chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
					chr3 = ((enc3 & 3) << 6) | enc4;
					output = output + String.fromCharCode(chr1);
					if (enc3 != 64) {
						output = output + String.fromCharCode(chr2);
					}
					if (enc4 != 64) {
						output = output + String.fromCharCode(chr3);
					}
				}
				output = uTF8Decode(output);
				return output;
			}
		});
	})(jQuery);

</script>
{/literal}
{include file="_std_end.tpl"}

