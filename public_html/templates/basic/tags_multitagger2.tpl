{assign var="page_title" value="Multi Tagger"}
{include file="_std_begin.tpl"}
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<link href="{"/js/select2-3.3.2/select2.css"|revision}" rel="stylesheet"/>
<script src="{"/js/select2-3.3.2/select2.js"|revision}"></script>
<script src="{"/js/to-title-case.js"|revision}"></script>
<script src="/js/jquery.storage.js"></script>


{literal}
<style>

.tagPublic span,.tagPublic a.taglink {
	background-color:lightgreen !important;
}

.tagPrivate span,.tagPrivate a.taglink {
	background-color:pink !important;
}
.tagGeneral span,.tagGeneral a.taglink {
	background-color:yellow !important;
}

.select2-search-choice {
	font-size: 0.8em;
}

.select2-drop .select2-results {
	border-top:1px dotted silver;
}
.select2-drop .select2-result {
	font-size: 0.8em;
	line-height: 1.1em;
	border-bottom: 1px solid #eee;
}

.select2-compact .select2-result {
	float: left;
	padding: 0px;
	border: 1px solid silver;
	margin: 3px;
	border-radius: 3px;
	background-color: #eee;
}
.select2-compact .select2-highlighted {
	background: #3875d7;
}
.select2-compact li.select2-more-results {
	clear:both;
}
.select2-dropdown-open .select2-choices .select2-search-field {
	z-index:121000;
	position:relative;
}
.content2 {
	position:inherit !important;
}
.column2 {
	float:right;
	width:220px;
	margin-left:10px;
	position:relative;
	font-size:0.9em;
	z-index:20000;
	background-color:white;
	border:1px solid black;
	padding:10px;
}


@media all and (max-width: 510px) {
       .column1 { width:400px; }
}

.experimental {
	display:none;
}
</style>
  <script type="text/javascript">

  function focusBox() {
  	if (el = document.getElementById('fq')) {
  		el.focus();
  	}
  }
  AttachEvent(window,'load',focusBox,false);

  </script>

{/literal}
	<div class="column2">
		<input type=hidden id=currentId>

		<div style="text-align:right;margin-bottom:6px;"><a href="/article/Tags" title="Article about Tags" class="about" target="_blank">More about Tags</a></div>

		<span class="experimental">
		<input type="radio" name="selector" accesskey="1" value="alpha" id="sel_alpha"/> <label for="sel_alpha">All Tags - Alphabetical</label><br/></span>
		<input type="radio" name="selector" accesskey="2" value="ranked" id="sel_ranked" checked/> <label for="sel_ranked">All Tags<span class="experimental"> - Ranked</span></label><br/>
		<input type="radio" name="selector" accesskey="3" value="selfrecent" id="sel_selfrecent"/> <label for="sel_selfrecent">Your Tags<span class="experimental"> - Recently Used</span></label><br/>
		<span class="experimental">
		<input type="radio" name="selector" accesskey="e" value="selfimages" id="sel_selfimages"/> <label for="sel_selfimages">Your Tags - Most Used</label><br/>
		<input type="radio" name="selector" accesskey="4" value="selfalpha" id="sel_selfalpha"/> <label for="sel_selfalpha">Your Tags - Alphabetical</label><br/></span>
		{if $gr}
			<input type="radio" name="selector" accesskey="5" value="nearby" id="sel_nearby"/> <label for="sel_nearby">Nearby Tags</label><br/>
		{/if}
		{if $topicstring}
			<input type="radio" name="selector" accesskey="7" value="automatic" id="sel_automatic" checked/> <label for="sel_automatic">Tags derived from description</label><br/>
		{/if}
		{if $hide_context}<span class="experimental">{/if}
		<input type="radio" name="selector" accesskey="8" value="top" id="sel_top"/> <label for="sel_top">Context List</label><br/>
		{if $hide_context}</span>{/if}
		<input type="radio" name="selector" accesskey="s" value="subject" id="sel_subject"/> <label for="sel_subject">Subject List</label><br/>
		<span class="experimental">
		<input type="radio" name="selector" accesskey="9" value="bucket" id="sel_bucket"/> <label for="sel_bucket">Bucket List</label><br/>
		<input type="radio" name="selector" accesskey="0" value="categories" id="sel_categories"/> <label for="sel_categories">Your Category list</label><br/>
		</span>
		<br/>

		<br/>
		<input type=checkbox onclick="toggle_compact(this)" id="compact"/> <label for="compact">Compact Listing Format</label><br/>
		<input type=checkbox onclick="toggle_experimental(this)" id="experimental"/> <label for="experimental">Show Experimental Modes</label>
	</div>


<h2>Multi Tagger</h2>

<h3 style=color:red>Testing purposes only, tags are NOT being saved!</h3>

<p>This page allows you to run a keyword search to find images, and then add tags to the first 50 results on one page. The 50 limit may be removed later.

	<form action="{$script_name}" method="get" onsubmit="focusBox()">
		<div class="interestBox">
			<label for="fq">Keywords</label>: <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
			<input type="submit" value="Search"/><br/>
			<label for="onlymine">Only your images?</label> <input type="checkbox" name="onlymine" id="onlymine" {if $onlymine}checked{/if}/>
			<label for="onlynull">Only images without any tags?</label> <input type="checkbox" name="onlynull" id="onlynull" {if $onlynull}checked{/if}/>
		</div>
		<input type="hidden" name="preview" value="1"/>
	</form>

<br style="clear:both"/>

{if $images}

		{foreach from=$images item=image}
			 <div style="border-top: 1px solid lightgrey; padding-top:1px;" id="result{$image->gridimage_id}">

			  <div style="float:left; position:relative; width:213px; text-align:center; margin-right:10px">
				<a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(213,160,false,true,$src)}</a>
			  </div>
                          <div style="float:left; position:relative; width:700px">

				<a title="view full size image" href="/photo/{$image->gridimage_id}"><b>{$image->title|escape:'html'}</b></a>
				by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a><br/>
				{if $image->moderation_status == 'geograph'}geograph{else}{if $image->moderation_status == 'pending'}pending{/if}{/if} for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>
				<br/>

				{if $image->imagetakenString}<small>Taken: {$image->imagetakenString}</small><br/>{/if}
				{if $image->imageclass}<small style="text-decoration:line-through">Category: {$image->imageclass}</small><br/>{/if}

				{if $image->comment}
				<div class="caption" title="{$image->comment|escape:'html'}" style="font-size:0.7em;">{$image->comment|escape:'html'|truncate:350:"... (<u>more</u>)"|geographlinks}</div>
				{/if}
				<br/>
				<input type="text" name="tags[{$image->gridimage_id}]" id="tags_{$image->gridimage_id}" class="tags" value="{$image->tags|escape:'html'}" size="60"/>
				<input type="button" class="astext" value="As Text">
			   </div><br style="clear:both;"/>
			 </div>
		{/foreach}

	<p>Displaying {$imagecount} of {$totalcount|thousends} matches</p>

	{if $imagecount eq 50}
		<p>
			<small>&middot; To refine the results simply add more keywords</small>
		</p>
	{/if}

{if $src == 'data-src'}
	 <script src="{"/js/lazy.js"|revision}" type="text/javascript"></script>
{/if}

{literal}

<script type="text/javascript">

String.prototype.capitalizeTag = function () {
	var bits = this.split(":",2);
	if (bits.length == 2) {
		return bits[0].toLowerCase()+':'+bits[1].toTitleCase();
	} else {
		return this.toTitleCase();
	}
}

function toggle_compact(that) {
	$.localStorage('tagger_compact', that.checked);
	if (that.checked) {
		$(".select2-drop").addClass("select2-compact");
	} else {
		$(".select2-drop").removeClass("select2-compact");
	}
}
function toggle_experimental(that) {
	$.localStorage('tagger_experimental', that.checked);
	if (that.checked) {
		$(".experimental").show();
	} else {
		$(".experimental").hide();
	}
}

var sentFirst = false;
var defaultMode = false;

$(function() {
	$('input.tags').select2({
		width:"600px",
		multiple: true,
		separator: '?',
		placeholder: 'enter or search for tags here',
		closeOnSelect: false,
		tokenSeparators: [';',','],
		ajax: {
			quietMillis: 200,
			url: "/tags/tags.json.php",
			cache: true,
			jsonpCallback: 'tagsFunc',
			dataType: 'jsonp',
			data: function (term, page) {
				var mode =$("input[name=selector]:checked").val();
				var data = {mode: mode, term: term};
				if (mode == 'nearby' && $("input[name=gr]").length > 0) {
					data.gr = $("input[name=gr]").val(); //todo - need gr of selected image!
				} else if (mode == 'selfrecent') {
					if (term.length > 0 && !$('.experimental').prop('checked')) {
						//if entered a term, fall back to 'Your Tags - Ranked'
						data.mode = 'ranked';
						data.mine = 1;
						data.page = page;
					} else {
						data.term = ''; //send a empty string to help with caching
					}
				} else {
					data.page = page;
				}
				return data;
			},
			results: function (data, page) { // parse the results into the format expected by Select2.
				var more = (data.length == 60 && (page*60) < 1000);
				var results = [];
				$.each(data, function(){
					results.push({id: this, text: this.capitalizeTag() });
				});
				return {results: results, more: more};
			}
		},
		createSearchChoice: function (term) {
			var mode =$("input[name=selector]:checked").val()
			if (mode == 'subject' || mode == 'top' || mode == 'bucket' || mode == 'categories')
				return false;
			return {id: term, text: term};
		},
		//formatCreateNew: function (term) { return "\"" + term + "\" (create as new tag)"; },
		initSelection: function (element, callback) {
			var data = [];
			$(element.val().split(/[;?]/)).each(function () {
				data.push({id: this, text: this.capitalizeTag() });
			});
			callback(data);
		}

	}).on('change', function (e) {
		console.log(e);
		console.log(e.val,e.added,e.removed);
	}).on('opening', function (e) {
		$('#currentId').val($(this).prop('id'));
	});

		$("input[name=selector]").click(function() {
			var id = $('#currentId').val();
			var txt = $('#s2id_'+id+' .select2-input').val();
			$('#'+id).select2('close');
			$('#'+id).select2('open');
			if (txt.length > 0) {
				$('#s2id_'+id+' .select2-input').val(txt);
			}
			var mode =$("input[name=selector]:checked").val();
			$('#s2id_'+id+' .select2-input').prop('disabled',(mode == 'suggestions' || mode == 'prospective' || mode == 'automatic'));
		});

		//fix for firefox to allow the search box to be clicked to focus (works with just the z-index bodge on other browsers)
		$(".select2-search-field input").bind('click',function(e) {
			$(this).focus();
		});

		if ($.localStorage('tagger_compact')) {
			$('#compact').prop('checked',true);
			$(".select2-drop").addClass("select2-compact");
		}
		if ($.localStorage('tagger_experimental')) {
			$('#experimental').prop('checked',true);
			$(".experimental").show();
		}

		var mode = null;
		var num = 0;
		$("input[name=selector]").each(function(index){
			current = $.localStorage('tagger_mode_'+$(this).val());
			if (current && current > num) {
				mode = $(this).val();
				num = current;
			}
		});
		if (mode) {
			defaultMode = mode;
			$("input[name=selector][value="+mode+"]").prop('checked',true);
		}

	$(".astext").on('click',function() {
		var ele = $(this);
		var list = ele.prev().select2('val');
		if (!list || list.length == 0) {
			return false;
		}

		ele.hide().siblings('.select2-container').hide();
		var textBox = $('<input/>', {
			type: 'text',
		    style: 'width:600px;font-size:1.1em',
		    value: list.join('; ')+';'
		});
		ele.before(textBox);
		textBox.select();
		textBox.focus();
		textBox.on('blur',function() {
			$(this).remove();
			ele.show().siblings('.select2-container').show();
		});
	});

});
</script>
{/literal}

{/if}


{include file="_std_end.tpl"}

