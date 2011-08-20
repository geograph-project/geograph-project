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
</style>
<h2 style="float:left;margin:0;margin-right:20px">Multi Tagger</h2>

<p id="message">This page allows you to run a keyword search to find images, and then add tags to in bulk.</p>

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
			<input type="submit" value="Search"/> <input type="button" value="Clear" onclick="clearThumbs()"/><br/>

			<label for="onlymine">Only your images?</label> <input type="checkbox" name="onlymine" id="onlymine" {if $onlymine}checked{/if} {dynamic}value="{$user->user_id|escape:'html'}"{/dynamic}/> -
			<label for="onlynull">Only images without any tags?</label> <input type="checkbox" name="onlynull" id="onlynull" {if $onlynull}checked{/if}/>
		</div>
	</form>

<div style="width:940px">

	<div id="" class="tagbar" style="background-color:yellow">
		<ul id="mainlist" class="Sortable">
			List of tags will appear here. This list will persist as you switch between images. <br/><br/>

			TIP: Drag and drop the tags in the list, to organise them for ease of use!
		</ul>
	</div>

	<div id="" class="tagbar" style="margin-top:2px">
		{assign var=tab value="3"}
		<div class="tabHolder">
			<a class="tab{if $tab == 1}Selected{/if} nowrap" id="tab1" onclick="tabClick('tab','div',1,3);loadTagSuggestions('/tags/primary.json.php?')">C'text</a>
			<a class="tab{if $tab == 2}Selected{/if} nowrap" id="tab2" onclick="tabClick('tab','div',2,3);loadTopics(gridimage_id);">Topics</a>
			<a class="tab{if $tab == 3}Selected{/if} nowrap" id="tab3" onclick="tabClick('tab','div',3,3)">Add</a>
		</div>
		<form class="interestBox" id="div3" style="display:block" onsubmit="return false()">
			<input type="text" name="__newtag" size="16" onkeyup="{literal}if (this.value.length > 2) {loadTagSuggestions('/tags/tags.json.php?q='+encodeURIComponent(this.value)); tabClick('tab','',3,3)} {/literal}"/> <input type="button" value="Add" onclick="createTag(this.form.elements['__newtag'].value)"/><br/>
		</form>
		<ul id="suggestlist" class="interestBox">
			Tag Suggestions will appear here
		</ul>
	</div>

	<div id="mainimage" style="">
		<br/>
		<p>This is an experimental new method to bulk add tags. Run a search above to find images. </p>

		<p>Works best if work in batchs by subject. So for example if you submitted a lot of churches, then search for "Church" and add tags. Repeat for other subjects.</p>



		<h3>Current Limitations</h3>
		<ul>
			<li>Creates Public tags on your own images, Private on other peoples images. <b>Can not</b> currently create private tags on your own.</li>

			<li>The 'search' for images, is based on the main search engine, can only load the first 1000 images of any given search.</li>

			<li>The 'Only images without any tags?' filter is not realtime - so if tags recently added, might still show in results.</li>
		</ul>
	</div>

	<div id="thumbar" style="">

	</div>

	<br style="clear:both"/>
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
			//jsonpCallback: 'serveCallback', //prevents cache busting varying callback name
			success: function(data) {
				serveCallback(data);
			}
		});
		$("#message").html('Loading...');
	}

	var saved = new Object;

	function serveCallback(data) {
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
	}

	var gridimage_id = null;

	function showMain(item) {
		if (typeof item != 'object') {
			item = saved[item];
		}
		gridimage_id = item.guid;
		$("#mainimage").html('<a href="'+item.link+'" title="'+item.title+'" target="_blank"><img src="'+_fullsize(item.thumb)+'" style="width:400px"/></a>');

		$("#mainimage").append('<br/><b>'+item.title+'</b>');
		$("#mainimage").append(' by <a href="'+item.source+'">'+item.author+'</a>');
		if (item.description)
			$("#mainimage").append('<br/><small>'+item.description+'</small>');

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

							createTag(text);
						}
					}
				});

			$('#mainlist li input').each(function(index) {
				if ($(this).attr('checked')) {
					$(this).attr('checked',false);
					toggleTag($(this).get(0));
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
						id = encodeURIComponent(text).replace(/%/g,'__');
						if (!document.getElementById(id))
							ele.append(
									['<li id="li-'+id+'">',
									'<input type="checkbox" title="use this tag" id="'+id+'" onclick="addTag(this)" value="'+text+'"/>',
									'<label for="'+id+'">'+text.replace(/^top:/,'')+'</label>',
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

function toggleTag(that) {
	var id = that.id;
	if (that.checked) {
		$('#li-'+id).addClass('highlight');
	} else {
		$('#li-'+id).removeClass('highlight');
	}
}

function addTag(that) {
	var id = that.id;
	var label = $('#li-'+id+' label').text();
	var value = that.value;

	removeTag(id);
	if ($('#mainlist').text().indexOf('will appear here') > -1) {
		$('#mainlist').empty();
	}
	$('#mainlist').append(
		['<li id="li-'+id+'">',
		'<a href="javascript:void(removeTag(\''+id+'\'))" title="remove tag from list">X</a>',
		'<input type="checkbox" title="enable/disable this tag" id="'+id+'" onclick="toggleTag(this)" value="'+value+'" checked/>',
		'<label for="'+id+'">'+label+'</label>',
		'</li>'].join('')
	);
	toggleTag(document.getElementById(id));
}

function createTag(text) {

	if ($('#mainlist').text().indexOf('will appear here') > -1) {
		$('#mainlist').empty();
	}

	id = encodeURIComponent(text).replace(/%/g,'__');

	var ele = $('#'+id);

	if (ele.length>0) {
		//we have one!
		if (ele.attr('title') == 'enable/disable this tag') { //todo - have a more robust method!
			//its in the main list, so make sure its ticked

			if (ele.attr('checked')) {
				//do nothing
			} else {
				ele.attr('checked',true);
				toggleTag(document.getElementById(id));
			}
		} else {
			//its somewhere in the suggestions already, so lets 'promote' it :)
			addTag(document.getElementById(id));
		}
	} else {
		//need to create it

		$('#mainlist').append(
				['<li id="li-'+id+'">',
				'<a href="javascript:void(removeTag(\''+id+'\'))" title="remove tag from list">X</a>',
				'<input type="checkbox" title="enable/disable this tag" id="'+id+'" onclick="toggleTag(this)" value="'+text+'" checked/>',
				'<label for="'+id+'">'+text.replace(/^top:/,'')+'</label>',
				'</li>'].join('')
		);
		toggleTag(document.getElementById(id));
	}
}

/////////////////////////////////////

$(function() {
	$(".Sortable").sortable();

});


</script>
{/literal}
{include file="_std_end.tpl"}

