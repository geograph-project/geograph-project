{include file="_std_begin.tpl"}


<div style="position:relative;float:right">
	<a href="?recent=1">View your recent images</a>
</div>

<h2>tag or not</h2>

{dynamic}

{if $tag}
	<div class="interestBox">Tag:
		<b><span class="tag">{if $tag.prefix}{$tag.prefix|escape:'html'}:{/if}<a class="taglink">{$tag.tag|escape:'html'}</a></span></b>
		{if $tag.description}
			<blockquote>
				{$tag.description|escape:'html'}
			</blockquote>
		{/if}
	</div>
		<br/><br/>
{/if}

<script>
	var tag_id='{$tag.tag_id|escape:'javascript'}';
</script>


<div class="interestBox draggable" style="width:200px;float:left;text-align:center;background-color:#888888;">
	<input type="button" class="nextButton" onclick="loadNextImage(null,1)" value="Tag with '{$tag.tag|escape:'html'}'" style="background-color:lightgreen;font-size:1.2em"/><br/>
	<input type="button" class="nextButton" onclick="loadNextImage(null,0)" value="Don't Tag" style="background-color:pink;font-size:1.2em" title="this image does not match the tag description"/><br/><br/>

	<input type="button" class="nextButton" onclick="loadNextImage(null,-1)" value="Skip" style="" title="let someone else decide..."/>
</div>


<div class="interestBox draggable" style="width:700px;height:800px;float:left;background-color:gray;text-align:center;color:white" id="output">
	Loading....
</div>



{/dynamic}


<br style="clear:both"/>
Tip: Drag either of the dark gray boxes, to get a confortable layout.

{literal}
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js" type="text/javascript"></script>
	<script src="/draggable/jqueryui.com/ui/jquery.ui.core.js"></script>
	<script src="/draggable/jqueryui.com/ui/jquery.ui.widget.js"></script>
	<script src="/draggable/jqueryui.com/ui/jquery.ui.mouse.js"></script>
	<script src="/draggable/jqueryui.com/ui/jquery.ui.draggable.js"></script>

<script>
	var gridimage_id;

	function loadNextImage(dummy,doit) {

		param = 'tag_id='+encodeURIComponent(tag_id);

		if (gridimage_id) {
			param = param + '&gridimage_id='+encodeURIComponent(gridimage_id);
		}
		if (doit) {
			param = param + '&doit='+encodeURIComponent(doit);
		}

		$('.nextButton').attr('disabled',true);

		$.getJSON("/tags/tagornot.json.php?"+param,

		// on search completion, process the results
		function (data) {

			if (data && data.tag_id) {
				$('#output').html('<img src="'+data.image+'"/><br/><b>'+data.title+'</b>');
				$('#output').append('<br/>in '+data.grid_reference+' by '+data.realname);
				$('#output').append('<br/><small>'+data.comment+'</small>');
				$('#output').append('<br/><small>Category:'+data.imageclass+'</small>');
				$('#output').append('<br/><a href="/photo/'+data.gridimage_id+'" target="_blank">open in new window</a>');

				gridimage_id = data.gridimage_id;

				$('.nextButton').attr('disabled',false);

			} else if (data.error) {
				alert(data.error);
			}
		});
	}

	AttachEvent(window,'load',loadNextImage,false);

	$(function() {
		$(".draggable").draggable();
	});


</script>
{/literal}

{include file="_std_end.tpl"}

