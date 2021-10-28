{include file="_search_begin.tpl"}

{if $engine->resultCount}


<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js" type="text/javascript"></script>
<script src="{"/js/screensaver.js"|revision}"></script>
{literal}
<script>

SImages = new Array();

{/literal}
	{foreach from=$engine->results item=image name=results}
		{$image->_getFullSize()}
		SImages.push({literal}{{/literal}
			grid_reference:'{$image->grid_reference}',
			title:{$image->title|latin1_to_utf8|json_encode},
			url:'{$image->getLargestPhotoPath(true)}',
			taken:'{$image->imagetaken}',
			realname:{$image->realname|latin1_to_utf8|json_encode}
		{literal}}{/literal});

	{foreachelse}
		alert('no results');
	{/foreach}
{literal}

var autoAdvance = 2000;
function showSaver2() {
	if (SImages[SIndex]['url'].indexOf('/') ==0)
		SImages[SIndex]['url'] = 'https://s0.geograph.org.uk'+SImages[SIndex]['url'];

	$('#SSaver').fadeIn('slow').css({'background':' url('+SImages[SIndex]['url']+') no-repeat center center fixed #111', 'background-size':'contain'});
	$('#STitle').text(SImages[SIndex]['grid_reference'] + ': ' + SImages[SIndex]['title']);
	$('#SDate').text(SImages[SIndex]['taken']);
	$('#SCredit').text('by '+SImages[SIndex]['realname']);
	SIndex++;
	if (!SImages[SIndex])
		SIndex=0;

	if (STimer)
                clearTimeout(STimer);
	STimer = setTimeout(showSaver2,autoAdvance+Math.round(Math.random()*10000));

	$('#SMessage').hide();
}

function hideSaver() {
	$('#SSaver').fadeOut('fast');
	if (STimer)
		clearTimeout(STimer);
}
$(document).keyup(function(e) {
	if (e.key === "Escape") { // escape key maps to keycode `27`
		hideSaver();
	} else if (e.keyCode == 37) { //left arrow
		autoAdvance = 10000;
		SIndex=SIndex-2; //showSaver already advanced it!
		if (!SImages[SIndex])
			SIndex = SImages.length-1
		showSaver2()
	} else if (e.keyCode == 39) { //rigth arrow
		autoAdvance = 10000;
		showSaver2();
	}
});
</script>

	Click to start: <input type=button onclick=showSaver2() value="Show Slideshow" style="padding:30px;font-size:2em;background-color:lightgreen"> (press Escape to exit)

	<br>Tip: Use left/right cursor keys to move backwards/forwards. Can press F11 to open full-screen.
{/literal}



	{if $engine->results}
	<br style="clear:both"/>
	<p>Search took {$querytime|string_format:"%.2f"} secs, ( Page {$engine->pagesString()})
	{/if}
{else}
	{include file="_search_noresults.tpl"}
{/if}

{include file="_search_end.tpl"}
