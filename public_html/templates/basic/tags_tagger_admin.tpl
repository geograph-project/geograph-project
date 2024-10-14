{assign var="page_title" value="Tagging Box"}
{include file="_basic_begin.tpl"}
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<link href="{"/js/select2-3.3.2/select2.css"|revision}" rel="stylesheet"/>
<script src="{"/js/select2-3.3.2/select2.js"|revision}"></script>
<script src="{"/js/to-title-case.js"|revision}"></script>
<script src="{"/js/jquery.storage.js"|revision}"></script>
<div style="position:fixed;top:200px;left:10px;border:1px solid red"></div>
<div style="padding:6px">
<style>{literal}
body {
	background-color: #f0f0f0 !important;
}
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
.select2-container-multi .select2-choices .select2-search-field {
	z-index:121000;
	position:relative;
}

.column1 {
	height:240px;
	padding-right:260px;
}
.column2 {
	float:right;
	width:220px;
	margin-left:10px;
	position:relative;
	font-size:0.9em;
	z-index:10000;
}
@media all and (max-width: 510px) {
       .column1 { width:400px; }
}

.experimental {
	display:none;
}
{/literal}</style>

{dynamic}

<form method="post" action="{$script_name}?admin=1&amp;gr={$gr|escape:'url'}&amp;upload_id={$upload_id|escape:'url'}&amp;gridimage_id={$gridimage_id}" style="background-color:#f0f0f0;" name="theForm">
	<div id="savebutton" style="float:right;display:none">
		<input type="submit" name="save" value="Save Changes" style="font-size:1.2em"/>
		<div id="autoSave" style="font-size:0.7em"></div>
	</div>

	{if $upload_id}<input type="hidden" name="upload_id" value="{$upload_id}" />{/if}
	{if $gridimage_id}<input type="hidden" name="gridimage_id" value="{$gridimage_id}" />{/if}
	{if $ids}<input type="hidden" name="ids" value="{$ids|escape:'html'}" />{/if}
	{if $gr}<input type="hidden" name="gr" value="{$gr|escape:'html'}" />{/if}


	<div class="column2">
		<div style="text-align:right;margin-bottom:6px;"><a href="/article/Tags" title="Article about Tags" class="about" target="_blank">More about Tags</a></div>

		<span class="experimental">
		<input type="radio" name="selector" accesskey="1" value="alpha" id="sel_alpha"/> <label for="sel_alpha">All Tags - Alphabetical</label><br/></span>
		<input type="radio" name="selector" accesskey="2" value="ranked" id="sel_ranked" checked/> <label for="sel_ranked">All Tags<span class="experimental"> - Ranked</span></label><br/>
	<span style="display:none">
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
	</span>
		{if $hide_context}<span class="experimental">{/if}
		<input type="radio" name="selector" accesskey="8" value="top" id="sel_top"/> <label for="sel_top">Context List</label><br/>
		{if $hide_context}</span>{/if}
		<input type="radio" name="selector" accesskey="s" value="subject" id="sel_subject"/> <label for="sel_subject">Subject List</label><br/>
		<span class="experimental">
		<input type="radio" name="selector" accesskey="9" value="bucket" id="sel_bucket"/> <label for="sel_bucket">Bucket List</label><br/>
	<span style="display:none">
		<input type="radio" name="selector" accesskey="0" value="categories" id="sel_categories"/> <label for="sel_categories">Your Category list</label><br/>
	</span>
		</span>
		<br/>
		<a href="javascript:void(export_tags())">Export current Tags as text</a>

		<br/>
		<input type=checkbox onclick="toggle_compact(this)" id="compact"/> <label for="compact">Compact Listing Format</label><br/>
		<input type=checkbox onclick="toggle_experimental(this)" id="experimental"/> <label for="experimental">Show Extended Modes</label>
	</div>

	<div class="column1">
		{if $topicstring}<input type="hidden" name="topicstring" value="{$topicstring|escape:'html'}" />{/if}
		<input type=hidden name="__newtag" id="__newtag" value="{$usedtext|escape:html}" size="50" style="width:100%"/>

		<div style="font-size:0.8em;padding-right:20px;padding-top:10px">
			&middot; NOTE: This is a <b>special admin version</b> available for Suggestion Moderators, for modifing PUBLIC Tags on images.<br/><br/>
			&middot; When remove, or add a tag via this interface, it's <b>added an item to the most recent open Suggestion</b><br/>
			&middot; A new suggestion will be created if there are no open tickets.<br/><br/>
			&middot; Make all required changes here, then <a href="javascript:void(parent.history.go(0))">Reload Main Window</a> to see updated ticket<br/><br/>
			&middot; In general this should only be used to correct mistakes, where the wrong tag is attached to an image.<br/><br/>
			&middot; <i>Wide scale to change tags for multiple images (eg to correct a typo in the tag itself) should be done via seperate interface.</i><br/><br/>
			<hr><br>
			&middot; Start typing in the white box above, to get a list of tag suggestions.<br/><br/>
			&middot; Having typed a tag, to start another, just type a comma or semicolon.<br/><br/>
			&middot; Tags should ONLY contain letters, numbers, spaces, and/or hyphens. (other charactors not recommended)<br/><br/>
		</div>

	</div>

{/dynamic}

	<br style="clear:both"/>
</form>


<script src="{"/js/anyascii.js"|revision}"></script>

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


function cleanTag(text) {
	//Allows chars: A-Z a-z 0-9 _ ( ) + . & / ! ? % @ # - (plus space)

        //basic HTML injection protection
        text = text.replace(/\\/g, "").replace(/<[^>]*>/g, "").replace(/[<>]+/ig, " ");

	//clean up text, doing fairly full unicode->ascii transliteration
	text = anyAscii(text);

	//standardize brackets
	text = text.replace(/[\{\(\[<]+/g, "(").replace(/[\}\)\]>]+/g, ")");

	//hive off the prefix
        var prefix = null;
        if (text.indexOf(':') > -1) {
                var bits = text.split(/\s*:+\s*/,2);
                text = bits[1].replace(/:/g,' ');

                //prefixes have particully restricted charactor set.
                prefix = bits[0].toLowerCase().replace(/[^\w]+/," ").replace(/[ _]+/g, " ").replace(/(^\s+|\s+$)/g, "");
        }
	
	//special support for listin building rating
	text = text.replace(/\*/g,'(star)');

	//quotes not supported
	text = text.replace(/['"`]+/g, ""); //dont want to replace with space, because of apos

	//then remove any none supported chars (by now only have ascii left to deal with)
	text = text.replace(/[^\w()\+\.&\/!?%@#-]+/g, " ");

        //clean/collapse whitespace
        text = text.replace(/[ _\t\n\r]+/g, " ").replace(/(^\s+|\s+$)/g, "");

        //this is a well known and common issue to fix, our house style doesnt have dot after st.
        text = text.replace(/\b(st)\.+\s*/i, '$1 ');

        //just to catch odd cases were tag ends up actully blank!
        text = text.replace(/^\s*$/,'blank');

	//add the prefix again
        if (prefix)
                text = prefix+':'+text;
        return text;
}


function export_tags() {
	var list = $('#__newtag').select2('val');
	if (!list || list.length == 0) {
		alert("Please select some tags first!");
	} else {
		prompt('Current tags:',list.join('; ')+';');
	}
	return false;
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
	$('#__newtag').select2({
		multiple: true,
		separator: ';',
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
				if ((mode == 'suggestions' || mode == 'prospective' || mode == 'automatic') && $("input[name=topicstring]").length > 0) {
					data.term = ''; //send a empty string to help with caching
					data.string = $("input[name=topicstring]").val();
				} else if (mode == 'nearby' && $("input[name=gr]").length > 0) {
					data.gr = $("input[name=gr]").val();
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
				data.admin = 1;
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
			term = cleanTag(term);
			return {id: term, text: term};
		},
		//formatCreateNew: function (term) { return "\"" + term + "\" (create as new tag)"; },
		initSelection: function (element, callback) {
			var data = [];
			$(element.val().split(/;/)).each(function () {
				data.push({id: this, text: this.capitalizeTag() });
			});
			callback(data);
		}

	}).on('change', function (e) {
		//console.log(e.val,e.added,e.removed);
		if (e.added) {
			submitTag(e.added.text, 2);

			//bodge to prevent the event firing when emptying the box (Select2.updateResults does if (equal(term, lastTerm)) return;
			$.data($('.select2-container'), "select2-last-term", '');

			//empty the search box
			$('.select2-input').val('');

			var mode =$("input[name=selector]:checked").val();
			if (!sentFirst) {
				if (current = $.localStorage('tagger_mode_'+mode)) {
					if (defaultMode != mode || current < 10 || Math.random() > 0.95) {
						//if its the current leader, then dont allow it race too far ahead
						$.localStorage('tagger_mode_'+mode, current+1);
					}
				} else {
					$.localStorage('tagger_mode_'+mode, 1);
				}
				sentFirst = true;
			}
                        $.ajax({
                                url: '/stuff/record_usage.php',
                                data: {action:'_newtag', param:mode, value:e.added.text},
                                xhrFields: { withCredentials: true }
                        });

		} else if (e.removed) {
			submitTag(e.removed.text, 0);
		}
	});


	$("input[name=selector]").click(function() {
		var txt = $('.select2-input').val();
		$('#__newtag').select2('close');
		$('#__newtag').select2('open');
		if (txt.length > 0) {
			$('.select2-input').val(txt);
		}
		var mode =$("input[name=selector]:checked").val();
		$('.select2-input').prop('disabled',(mode == 'suggestions' || mode == 'prospective' || mode == 'automatic'));
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

});


	function submitTag(tag,status) {
		var data = new Object;
		data['tag'] = tag;
		data['status'] = status;
		data['admin'] = 1;

		var form= document.forms['theForm'];
		if (form.gridimage_id)
			data['gridimage_id'] = form.gridimage_id.value;
		if (form.ids)
			data['ids'] = form.ids.value;

		$.ajax({
			url: "/tags/tagger.json.php",
			data: data
		});
	}

</script>
{/literal}

</div>
</body>
</html>
