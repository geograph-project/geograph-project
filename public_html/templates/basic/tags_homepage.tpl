{assign var="page_title" value="Publicly tagged images"}
{include file="_std_begin.tpl"}
{literal}<style>
	#taglist li {
		padding:2px
	}
	#taglist li a {
		text-decoration:none
	}
	#taglist li a:hover {
		text-decoration:underline
	}
	#dispThumbs {
		position:absolute;
		border:4px solid black;
		background-color:silver;
		padding:3px;
		z-index:1000;
	}
</style>{/literal}

<div class="tabHolder">
		<a href="/tags/primary.php" class="tab">Geographical Context</a>
		<a href="/article/Image-Buckets" class="tab">Buckets</a>
		<span class="tabSelected">Tags</span>
</div>
<div style="position:relative;" class="interestBox">
	<h2 style="margin:0">Tagged Images <sup><a href="/article/Tags" class="about" style="font-size:0.7em">About tags on Geograph</a></sup></h2>
</div>

<br/><br/>

<div style="position:relative;" class="interestBox">
	<form>
		Tag Search: <input type="text" name="tag" size="30" maxlength="60" onkeyup="{literal}if (this.value.length > 2) {loadTagSuggestions(this,event);} {/literal}" autocomplete="off"/>
		<input type="submit" value="View"/><br/>

		<div style="position:relative;">
			<div style="position:absolute;top:0px;left:0px;background-color:lightgrey;margin-left:86px;padding-right:20px" id="tagParent">
				<ul id="taglist">
				</ul>
			</div>
		</div>
	</form>
</div>
<div style="text-align:right">
	<a href="?prefixes">View list of tag prefixes</a>
</div>

<br/>

{foreach from=$taglist item="data"}

	<div style="padding:2px;float:left;width:200px;font-size:0.8em">
		<h3>{$data.title}</h3>

		<ol>
			{foreach from=$data.tags item=item}
				<li{if $item.count} value="{$item.count}"{/if}><span class="tag">
						{if $item.prefix && $item.prefix != 'bucket' && $item.prefix != 'top'}<small>{$item.prefix|escape:'html'}:<br/></small>{/if}<a href="/tags/?tag={if $item.prefix}{$item.prefix|escape:'url'}:{/if}{$item.tag|escape:'url'}" class="taglink">{$item.tag|escape:'html'}</a>
					</span></li>
			{/foreach}
		</ol>
	</div>

{/foreach}

<br style="clear:both"/>

{literal}
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js" type="text/javascript"></script>
<script>

	function loadTagSuggestions(that,event) {

		var unicode=event.keyCode? event.keyCode : event.charCode;
		if (unicode == 13) {
			//useTags(that);
			return;
		}

		param = 'q='+encodeURIComponent(that.value);

		$.getJSON("/tags/tags.json.php?"+param+"&callback=?",

		// on search completion, process the results
		function (data) {
			var div = $('#taglist').empty();
			$('#tagParent').show();

			if (data && data.length > 0) {

				for(var tag_id in data) {
					var text = data[tag_id].tag;
					if (data[tag_id].prefix && data[tag_id].prefix!='term' && data[tag_id].prefix!='cluster' && data[tag_id].prefix!='wiki') {
						text = data[tag_id].prefix+':'+text;
					}
					text = text.replace(/<[^>]*>/ig, "");
					text = text.replace(/['"]+/ig, " ");

					div.append("<li><a href=\"?tag="+text+"\">"+text+"</a></li>");
				}

			} else {
				div.append("<li><a href=\"?tag="+text+"\">"+text+"</a></li>");
			}
		});
	}

	$(function() {
		$('#tagParent').hide();

		var xOffset = 20;
		var yOffset = 20;

		$('a.taglink').hover(
			function(e) {
				$("body").append("<div id='dispThumbs'></div>").css('backgroundColor','red');
				$("#dispThumbs")
					.css("top",(e.pageY + xOffset) + "px")
					.css("left",(e.pageX + yOffset) + "px");

				// on search completion, process the results
				$.ajax({
					url: "/syndicator.php"+this.search+"&format=JSON&perpage=3",
					dataType: 'json',
					cache: true,
					success: function (data) {
						$.each(data.items, function(i,item){
							$("#dispThumbs").append('<a href="http://www.geograph.org.uk/photo/'+item.guid+'" title="'+item.title+' by '+item.author+'" class="i">'+item.thumbTag+'</a>');
						});
					}
				});

			},
			function() {
				$("body").css('backgroundColor','blue');
				$("#dispThumbs").remove();
			}
		).mousemove(function(e){
			$("#dispThumbs")
				.css("top",(e.pageY + xOffset) + "px")
				.css("left",(e.pageX + yOffset) + "px");
		});
	});

</script>
{/literal}

{include file="_std_end.tpl"}
