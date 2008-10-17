<html>
<head>
<title>Search Results</title>
<script src="{"/sorttable.js"|revision}"></script>
<link rel="stylesheet" type="text/css" title="Monitor" href="/templates/basic/css/basic.css" media="screen" />
</head>
<body style="background-color:black" text="#FFFFFF">

{if $engine->resultCount}
	<script type="text/javascript" src="http://www.nearby.org.uk/geograph/mootools/mootools-1.2-core.js"></script>
	<script type="text/javascript" src="http://www.nearby.org.uk/geograph/mootools/mootools-1.2-more.js"></script>
	<link rel="stylesheet" type="text/css" href="http://www.nearby.org.uk/geograph/mootools/MooFlow.css" />
	<script type="text/javascript" src="http://www.nearby.org.uk/geograph/mootools/MooFlow.js"></script>

	<div id="MooFlow">
	{foreach from=$engine->results item=image}

	  <a href="/photo/{$image->gridimage_id}">{$image->getThumbnail(213,160,false,true)|replace:' alt=':' title='}</a>
	
	{foreachelse}
		{if $engine->resultCount}
			<p style="background:#dddddd;padding:20px;"><a href="/search.php?i={$i}{if $engine->temp_displayclass}&amp;displayclass={$engine->temp_displayclass}{/if}"><b>continue to results</b> &gt; &gt;</a></p>
		{/if}
	{/foreach}
	</div>
	<p>&middot; Double click center image to view full size</p>
	{literal}
	<script>
	window.addEvent('domready', function(){

		var mf = new MooFlow($('MooFlow'), {
			startIndex: 0,
			heightRatio: 0.5,
			factor: 82,
			useSlider: true,
			useAutoPlay: true,
			useCaption: true,
			useResize: true,
			useWindowResize: true,
			useMouseWheel: true,
			useKeyInput: true,
			'onClickView': function(mixedObject){
				window.open(mixedObject.href);
			}
		});

	});
	</script>
	{/literal}

{else}
	{include file="_search_noresults.tpl"}
{/if}

	
</body>
</html>

