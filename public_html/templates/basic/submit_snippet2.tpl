{assign var="page_title" value="Shared Description Box"}
{include file="_basic_begin.tpl"}
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<link href="{"/js/select2-3.3.2/select2.css"|revision}" rel="stylesheet"/>
<script src="{"/js/select2-3.3.2/select2.js"|revision}"></script>
<script src="/js/jquery.storage.js"></script>
<div style="position:fixed;top:200px;left:10px;border:1px solid red"></div>
<div style="padding:6px">
<style>{literal}
body {
	background-color: #f0f0f0 !important;
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

.select2-result-label {
        white-space:nowrap;
        overflow:hidden;
        text-overflow: ellipsis;
}
.select2-result-label small {
	color:silver;
}
.select2-compact .select2-result-label small {
	display:none;
}
.select2-search-choice small {
	display:none;
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

<form method="post" action="{$script_name}?gr={$gr|escape:'url'}&amp;upload_id={$upload_id|escape:'url'}&amp;gridimage_id={$gridimage_id}" style="background-color:#f0f0f0;" name="theForm">
	<div id="savebutton" style="float:right;display:none">
		<input type="submit" name="save" value="Save Changes" style="font-size:1.2em"/>
		<div id="autoSave" style="font-size:0.7em"></div>
	</div>

	{if $upload_id}<input type="hidden" name="upload_id" value="{$upload_id}" />{/if}
	{if $gridimage_id}<input type="hidden" name="gridimage_id" value="{$gridimage_id}" />{/if}
	{if $ids}<input type="hidden" name="ids" value="{$ids|escape:'html'}" />{/if}
	{if $gr}<input type="hidden" name="gr" value="{$gr|escape:'html'}" />{/if}


	<div class="column2">
		<div style="text-align:right;margin-bottom:6px;"><a href="/article/Shared-Descriptions" title="Article about SDs" class="about nowrap" target="_blank">About Shared-Descriptions</a></div>

		<span class="experimental">
		<input type="radio" name="selector" accesskey="1" value="alpha" id="sel_alpha"/> <label for="sel_alpha">All - Alphabetical</label><br/></span>
		<input type="radio" name="selector" accesskey="2" value="ranked" id="sel_ranked" checked/> <label for="sel_ranked">All <span class="experimental"> - Ranked</span></label><br/>
		<input type="radio" name="selector" accesskey="3" value="selfrecent" id="sel_selfrecent"/> <label for="sel_selfrecent">Created By You <span class="experimental"> - Recently Used</span></label><br/>
		<span class="experimental">
		<input type="radio" name="selector" accesskey="e" value="selfimages" id="sel_selfimages"/> <label for="sel_selfimages">Created By You - Most Used</label><br/>
		<input type="radio" name="selector" accesskey="4" value="selfalpha" id="sel_selfalpha"/> <label for="sel_selfalpha">Created By You - Alphabetical</label><br/></span>
		{if $gr}
			<input type="radio" name="selector" accesskey="5" value="nearby" id="sel_nearby"/> <label for="sel_nearby">Used Nearby</label><br/>
		{/if}
		<br/>

		<br/>
		<input type=checkbox onclick="toggle_compact(this)" id="compact"/> <label for="compact">Compact Listing Format</label><br/>
		<input type=checkbox onclick="toggle_experimental(this)" id="experimental"/> <label for="experimental">Show Extended Modes</label>
	</div>

	<div class="column1">
		<input type=hidden name="__newsnippet" id="__newsnippet" value="{$usedtext|escape:html}" size="50" style="width:100%"/>

		<div style="font-size:0.8em;padding-right:20px;padding-top:10px">

			&middot; Having added a description, to start another, just type a comma or semicolon.<br/><br/>
			&middot; Can add multiple shared descriptions to a image<br/><br/>
			&middot; If you dont provide normal image description, and add one shared description, it will be used 'as' the description<br/><br/>

			&middot; <b><a href="?create=1{if $upload_id}&amp;upload_id={$upload_id}{/if}{if $gridimage_id}&amp;gridimage_id={$gridimage_id}{/if}{if $ids}&amp;ids={$ids}{/if}{if $gr}&amp;gr={$gr}{/if}">Create New Shared Description</a></b> 

		</div>

	</div>

{/dynamic}

	<br style="clear:both"/>
</form>


{literal}

<script type="text/javascript">

function toggle_compact(that) {
	$.localStorage('snippet_compact', that.checked);
	if (that.checked) {
		$(".select2-drop").addClass("select2-compact");
	} else {
		$(".select2-drop").removeClass("select2-compact");
	}
}
function toggle_experimental(that) {
	$.localStorage('snippet_experimental', that.checked);
	if (that.checked) {
		$(".experimental").show();
	} else {
		$(".experimental").hide();
	}
}

var sentFirst = false;
var defaultMode = false;

$(function() {
	$('#__newsnippet').select2({
		multiple: true,
		separator: ';',
		placeholder: 'enter title or search here',
		closeOnSelect: false,
		//tokenSeparators: [';',','],
		ajax: {
			quietMillis: 200,
			url: "/snippets.json.php",
			cache: true,
			jsonpCallback: 'snippetsFunc',
			dataType: 'jsonp',
			data: function (term, page) {
				var mode =$("input[name=selector]:checked").val();
				var data = {mode: mode, term: term};
				if (mode == 'nearby' && $("input[name=gr]").length > 0) {
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
				return data;
			},
			results: function (data, page) { // parse the results into the format expected by Select2.
				var more = (data.length == 60 && (page*60) < 1000);
				var results = [];
				$.each(data, function(){
					if (this.grid_reference && this.grid_reference.length > 3)
						this.title = this.title + " ("+this.grid_reference+")";
					if (this.user_id && this.user_id == window.user_id)
						this.title = this.title + "; by you";
					else if (this.realname && this.realname.length > 2)
						this.title = this.title + "; by "+ this.realname;

					if (this.comment && this.comment != this.title)
						this.title = this.title + " <small>"+this.comment;//+"</small>";

					results.push({id: this.snippet_id, text: this.title, comment: this.comment });
				});
				return {results: results, more: more};
			}
		},
		escapeMarkup: function (input) {
			return $.fn.select2.defaults.escapeMarkup(input).replace(/&lt;(\/?)small&gt;/g,'<$1'+'small>');
		},
		createSearchChoice: function (term) {
			//alert("You have entered a title, not matched to existing shared description. Will create a new shared description, and after image submission, will prompt to add the full text");
			return {id: term, text: term};
		},
		formatCreateNew: function (term) { return "\"" + term + "\" (create as new description)"; },
		initSelection: function (element, callback) {
			var data = [];
			$(element.val().split(/;/)).each(function () {
				var bits = this.split(':',2)
				data.push({id: bits[0], text: bits[1] });
			});
			callback(data);
		}
	}).on('change', function (e) {
		//console.log(e.val,e.added,e.removed);
		if (e.added) {
			submitSnippet(e.added.id, e.added.text, 1);

			//bodge to prevent the event firing when emptying the box (Select2.updateResults does if (equal(term, lastTerm)) return;
			$.data($('.select2-container'), "select2-last-term", '');

			//empty the search box
			$('.select2-input').val('');

			var mode =$("input[name=selector]:checked").val();
			if (!sentFirst) {
				if (current = $.localStorage('snippet_mode_'+mode)) {
					if (defaultMode != mode || current < 10 || Math.random() > 0.95) {
						//if its the current leader, then dont allow it race too far ahead
						$.localStorage('snippet_mode_'+mode, current+1);
					}
				} else {
					$.localStorage('snippet_mode_'+mode, 1);
				}
				sentFirst = true;
			}
	        } else if (e.removed) {
	                submitSnippet(e.removed.id, e.removed.text, 0);
		}

	});


	$("input[name=selector]").click(function() {
		var txt = $('.select2-input').val();
		$('#__newsnippet').select2('close');
		$('#__newsnippet').select2('open');
		if (txt.length > 0) {
			$('.select2-input').val(txt);
		}
	});

	//fix for firefox to allow the search box to be clicked to focus (works with just the z-index bodge on other browsers)
	$(".select2-search-field input").bind('click',function(e) {
		$(this).focus();
	});

	if ($.localStorage('snippet_compact')) {
		$('#compact').prop('checked',true);
		$(".select2-drop").addClass("select2-compact");
	}
	if ($.localStorage('snippet_experimental')) {
		$('#experimental').prop('checked',true);
		$(".experimental").show();
	}

	var mode = null;
	var num = 0;
	$("input[name=selector]").each(function(index){
		current = $.localStorage('snippet_mode_'+$(this).val());
		if (current && current > num) {
			mode = $(this).val();
			num = current;
		}
	});
	if (mode) {
		defaultMode = mode;
		$("input[name=selector][value="+mode+"]").prop('checked',true);
		setTimeout(function() {
			$('#__newsnippet').select2('open');
		}, 150);
	}

});


	function submitSnippet(id,text,status) {
		var data = new Object;
		data['snippet_id'] = id;
		data['title'] = text;
		data['status'] = status;

		var form= document.forms['theForm'];
		if (form.upload_id)
			data['upload_id'] = form.upload_id.value;
		if (form.gridimage_id)
			data['gridimage_id'] = form.gridimage_id.value;
		if (form.ids)
			data['ids'] = form.ids.value;

		$.ajax({
			url: "/snippet.json.php",
			data: data
		});
	}

{/literal}

{dynamic}
	var user_id = {$user->user_id};
{/dynamic}
</script>

</div>
</body>
</html>
