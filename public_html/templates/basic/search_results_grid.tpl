{include file="_search_begin.tpl"}

{if $engine->resultCount}
	{assign var="thumbw" value="213"}
	{assign var="thumbh" value="160"}

<style>{literal}
.gridded.med {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    grid-gap: 18px;
    grid-row-gap: 20px;
}
.gridded > div {
	text-align:center;
	float:left; /* ignored in grid, but to support older browsers! */
}
.gridded img {
	width:100%;
}

.gridded .shadow {
	position:relative;
}
.gridded .floater {
	position:absolute;
	top:0;
	left:0;
	background-color:white;
	padding:10px;
	z-index:1000;
	display:none;
}

.gridded .shadow:hover .floater {
	display:block;
}
</style>
<script>
//todo, this needs increating to the functions in geograph.js, not seperate functions!
function remarkImage(image) {
	ele = document.getElementById('mark'+image);
	if(ele.innerText != undefined) {
		newtext = ele.innerText;
	} else {
		newtext = ele.textContent;
	}
	ele = document.getElementById('img'+image);
	if (newtext == 'marked') {
		ele.style.border = "2px solid red";
	} else {
		ele.style.border = "none";
	}
}
function remarkAllImages() {
	setTimeout(function() { //this is ugly, the original function might not of run yet, so need more delay!
	var str = 'marked';
	for(var q=0;q<document.links.length;q++) {
		if (document.links[q].text == str) {
			remarkImage(document.links[q].id.substr(4));
		}
	}
	}, 1000);
}

function setColumns(num) {
	document.getElementById("gridcontainer").style.gridTemplateColumns = 'repeat('+num+', 1fr)';

	if (lazySizes && lazySizes.autoSizer)
		lazySizes.autoSizer.checkElems();

	createCookie('GridCols',num,10);
	return false;
}

function loadColumnsFromCookie() {
	var num = readCookie('GridCols');
	if (num && num > 0)
		//do this directly, as dont need to reset cookie, and call autoSizer onload
		document.getElementById("gridcontainer").style.gridTemplateColumns = 'repeat('+num+', 1fr)';
}

AttachEvent(window,'load',remarkAllImages,false);


	//we have to be extra careful checking if a real jquery, as jQl creates a fake jQuery object. 
	if (typeof jQuery === "undefined" || jQuery === null || typeof jQuery.fn === "undefined" || typeof jQuery.fn.load === "undefined") {
		jQl.loadjQ('https://ajax.googleapis.com/ajax/libs/jquery/1.8/jquery.min.js');
	}

	$(function() {
		$(".shadow img").contextmenu(function() {
			if (this.currentSrc && (m = this.currentSrc.match(/\/(\d{6,}_\w{8}\.jpg)/))) {
				$(this).attr('srcset',"https://t0.geograph.org.uk/stamped/"+m[1]);
			}
		});
	});

</script>
{/literal}

	<script src="{$static_host}/js/lazysizes.min.js" async=""></script>

	<div style="text-align:right;margin-top:-2em;padding-bottom:8px">
		Columns: 
		<a href="#1" onclick="return setColumns(this.text)">1</a>
		<a href="#2" onclick="return setColumns(this.text)">2</a>
		<a href="#3" onclick="return setColumns(this.text)">3</a>
		<a href="#4" onclick="return setColumns(this.text)">4</a>
		<a href="#5" onclick="return setColumns(this.text)">5</a>
		<a href="#6" onclick="return setColumns(this.text)">6</a>
		<a href="#7" onclick="return setColumns(this.text)">7</a>
		<a href="#8" onclick="return setColumns(this.text)">8</a>
		<a href="#12" onclick="return setColumns(this.text)">12</a>
	</div>

	
	<div class="gridded med" id="gridcontainer">
	{foreach from=$engine->results item=image}
	{searchbreak image=$image}
		<div class="shadow">
			<div class="floater">
				<a href="/editimage.php?id={$image->gridimage_id}">Edit</a> <a href="/reuse.php?id={$image->gridimage_id}">Download</a>
				[<a href="#" onclick="markImage({$image->gridimage_id});remarkImage({$image->gridimage_id}); return false" id="mark{$image->gridimage_id}">Mark</a>]</div>
			<a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getResponsiveImgTag(120,640,true)}</a>
		</div>
	{foreachelse}
	 	{if $engine->resultCount}
	 		<p style="background:#dddddd;padding:20px;"><a href="/search.php?i={$i}{if $engine->temp_displayclass}&amp;displayclass={$engine->temp_displayclass}{/if}"><b>continue to results</b> &gt; &gt;</a></p>
	 	{/if}
	{/foreach}
	<br style="clear:both"/>
	</div>

<script>
loadColumnsFromCookie(); //inline, not async. But needs be here, AFTER gridcontainer created in DOM.
</script>


	(hover over a thumbnail to add individual images to your marked list) 
	{include file="_search_marked_footer.tpl"}

	{if $engine->results}
	<p style="clear:both">Search took {$querytime|string_format:"%.2f"} secs, ( Page {$engine->pagesString()})
	{/if}
{else}
	{include file="_search_noresults.tpl"}
{/if}


{include file="_search_end.tpl"}
