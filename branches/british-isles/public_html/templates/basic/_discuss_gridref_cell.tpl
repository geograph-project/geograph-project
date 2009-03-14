
{dynamic}
	<h3 style="float:left; margin-top:0; padding-left:10px; font-weight:normal;"><a href="/gridref/{$gridref}" style="color:silver;">{$gridref}</a> :: &nbsp;</h3>
	{if $place.distance}
		 {place place=$place h3=' style="text-align:left; margin-top:0; padding-left:10px; font-weight:normal; color:silver;"'}
	{/if}

	{if count($images) > 0}
	<div style="{if count($images) > 3}height:520px; overflow:auto; {/if}position:relative; background-color:{$backgroundcolor}" onscroll="return showThumbnails(this);" id="scrollDiv">
		<div style="position:relative;height:250px;">
		{foreach from=$images item=image name=i}

			<div class="photo33" style="float:left;width:200px;height:220px;padding:3px"><a title="{$image->title|escape:'html'} by {$image->realname} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120,false,true,'class="ithumb" lowsrc')}</a>
				<div class="caption"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a></div>

				{if $is_admin}
					<div class="statuscaption">classification: {$image->moderation_status} {if $image->ftf}(first){/if}</div>
				{/if}

				<div style="font-size:0.7em"><br/>Insert: <a href="#" onclick="return paste_strinL('[[[{$image->gridimage_id}]]]',0)">Thumbnail</a> or <a href="#" onclick="return paste_strinL('[[{$image->gridimage_id}]]',0)">Text Link</a></div>
			</div>

		{if $smarty.foreach.i.iteration%3 == 0 && $smarty.foreach.i.iteration < count($images)}
			<br style="clear:both"/>
			</div><div style="position:relative;height:250px;">
		{/if}

		{/foreach}
		<br style="clear:both"/>
		</div>
	</div>
	{/if}
	<br style="clear:both"/>

{/dynamic}
{literal}
<script type="text/javascript">
function showThumbnails(that) {
	t = that.scrollTop;
	i = document.images;
	c = 0; r = 0; p = 0; show = (Math.abs(t-p) < 520);
	for(q=0;q<i.length;q++) {
		if (typeof i[q].lowsrc != 'undefined' && i[q].className == 'ithumb') {
			if (show && (i[q].src == ''))
				i[q].src = i[q].lowsrc;
			
			c=c+1;
			if (c%3==0) {
				r=r+1;
				p = r * 260; show = (Math.abs(t-p) < 520);
			}
		}
	}
	return true;
}
function pageLoad() {
	showThumbnails(document.getElementById('scrollDiv'));
}
AttachEvent(window,'load',pageLoad,false);
</script>
{/literal}

