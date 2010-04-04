{assign var="page_title" value="Games :: Place Memory!"}
{include file="_std_begin.tpl"}
<script type="text/javascript" src="{"/games/place-memory.js"|revision}"></script>
<link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/2.3.1/build/autocomplete/assets/skins/sam/autocomplete.css" />

<script type="text/javascript" src="http://yui.yahooapis.com/2.3.1/build/yahoo/yahoo-min.js"></script>
<script type="text/javascript" src="http://yui.yahooapis.com/2.3.1/build/dom/dom-min.js"></script>
<script type="text/javascript" src="http://yui.yahooapis.com/2.3.1/build/event/event-min.js"></script>

<script type="text/javascript" src="http://yui.yahooapis.com/2.3.1/build/connection/connection-min.js"></script>
<script type="text/javascript" src="http://yui.yahooapis.com/2.3.1/build/autocomplete/autocomplete-min.js"></script>

{literal}
<style type="text/css">
.photo_box {
	float:left;
	position:relative;
	padding: 10px;
	
}

.navButtons A {
	border: 1px solid lightgrey;
	padding: 2px;
}

.yui-skin-sam {
	float:left;
	position:relative;
	padding: 10px;
}

.autocomplete { padding-bottom:2em;width:500px; }/* set width of widget here*/

.autocomplete .yui-ac-highlight .sample-info,
.autocomplete .yui-ac-highlight .sample-result,
.autocomplete .yui-ac-highlight .sample-result TT,
.autocomplete .yui-ac-highlight .sample-result SPAN,
.autocomplete .yui-ac-highlight .sample-result SUB,
.autocomplete .yui-ac-highlight .sample-query { color:#FFF; }

.autocomplete .sample-info { float:right; font-size:0.8em; line-height: 0.8em} /* push right */
.autocomplete .sample-result { color:#000; z-index:9999}
.autocomplete .sample-result TT { font-size:0.9em; color: red}
.autocomplete .sample-result SUB { font-size:0.8em; color: blue}

.autocomplete .sample-result SPAN { color:green; font-weight: bold; width:200px }

.autocomplete .sample-query { color:#000; }

#rastermap {
	z-index: 1;
}

#rastermap DIV {
	z-index: 2;
}

</style>
{/literal}

<h2><a href="/games/">Geograph Games</a> :: Place Memory</h2>
	
{dynamic}
{if $game->image} 

	{if !$rater}
	<div style="position:relative; float:right; width:60px; height:{$game->batchsize*32}px; border:1px solid red">
		{section loop=$game->batchsize name="batch"}
			{if $smarty.section.batch.index+1 > $game->batchsize-$game->games}
				<div style="width:60px;height:30px; border:1px solid gray; background-color:green; color:white; text-align:center">Done</div>
			{else} 
				<div style="width:60px;height:30px; border:1px solid gray"></div>
			{/if}
		{/section}
	</div>
	{/if}

  <form enctype="multipart/form-data" action="{$script_name}" method="post" name="theForm" onsubmit="if (this.imageclass) this.imageclass.disabled=false;" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">

	
	<p>Enter the Grid Reference of the square that contains the photo. You can also use the placename box to search for a grid reference. </p> 
	
	{if $message}
		<p style="color:#990000;font-weight:bold;">{$message}</p>
	{/if}

	<div id="responce"></div>
		
	<div class="photo_box">
		<p><a href="{$game->image->_getFullpath(true,true)}" target="gameimag"><img src="{$game->image->getThumbnail(213,160,true)}" alt="thumbnail loading..."></a>
		<small><small><br/>Click thumbnail to view full size</small></small></p>
		
		
		<p><label for="grid_reference"><b style="color:#0018F8">Grid Reference</b></label><br/> <input id="grid_reference" type="text" name="grid_reference" value="{$grid_reference|escape:'html'}" size="8" onfocus="stopMap()"/><small class="navButtons"><small><a href="javascript:doMove('grid_reference',-1,0);fetchMap()">W</a></small><sup><a href="javascript:doMove('grid_reference',0,1);fetchMap()">N</a></sup><sub><a href="javascript:doMove('grid_reference',0,-1);fetchMap()">S</a></sub><small><a href="javascript:doMove('grid_reference',1,0);fetchMap()">E</a></small></small> <input type="button" value="map" onclick="return game_map(this.form)"/> <input type="button" value="check..." onclick="return game_check(this.form)"/></p>
	
		
		<input type="hidden" name="token" value="{$gameToken}"/>
		<fieldset>
			<legend>Hamster Tokens Available <input type="text" name="points" value="{$game->points}" size="1" readonly="readonly"/></legend>
			{section loop=$game->points name="point"}
				<img src="http://{$static_host}/templates/basic/img/hamster-icon.gif" name="pointimg{$smarty.section.point.index}"/>
			{/section}
		</fieldset>
		
	</div>


<div class="yui-skin-sam" style="position:relative">
	<label for="ysearchinput1"><b style="color:#0018F8">Placename/Map Feature/Gazetteer Search</b></label><br/>
	<div id="example1" class="autocomplete">
		<input id="ysearchinput1" name="placename" type="text"/>
		<div id="ysearchcontainer1"></div>
	</div>
	<div style="font-size:0.8em">
		Start typing, then pause to display possible matches, keep adding terms to<br/> refine the search. Once you found your location, select it to use the grid reference. <br/><br/>
	</div>
	
	<div id="mapcontainer" style="width:300px;height:300px"></div>
</div>


	<br style="clear:both"/>
	<div style="text-align:right">{if $game->score}Score at beginning of this game: {$game->score}, with {$game->games} games played{/if} 
		{if $game->games == $game->batchsize-1}
			<input type="submit" name="save" value="save scores &gt;" disabled="disabled"/>
		{else}
			<input type="submit" name="next" value="next &gt; &gt;" disabled="disabled"/>
		{/if}
	</div>
</form>


<!-- Creative Commons Licence -->
<div class="ccmessage"><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img 
alt="Creative Commons Licence [Some Rights Reserved]" src="http://creativecommons.org/images/public/somerights20.gif" /></a> &nbsp; Image used on this page, &copy; Copyright {$game->image->realname|escape:'html'} and  
licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a>.</div>
<!-- /Creative Commons Licence -->

{literal}
<script type="text/javascript">
var ACFlatData = new function(){
    // Define a custom formatter function
    this.fnCustomFormatter = function(oResultItem, sQuery) {
    	//597702	SJ4583	Landrover Off Road Test Course	Sue Adair 
    	

        var aMarkup = ["<div class='sample-result'><div class='sample-info'>",
            oResultItem[2],
            "</div><tt>",
            oResultItem[0],
            "</tt> <span>",
            oResultItem[1],
            "</span></div>"];
        return (aMarkup.join(""));
    };
        
    // Instantiate one XHR DataSource and define schema as an array:
    //     ["Record Delimiter",
    //     "Field Delimiter"]
    this.oACDS = new YAHOO.widget.DS_XHR("/stuff/place-service.php", ["\n", "\t"]);
    this.oACDS.responseType = YAHOO.widget.DS_XHR.TYPE_FLAT;
    this.oACDS.maxCacheEntries = 100;
    this.oACDS.queryMatchSubset = true;



    // Instantiate AutoComplete
    this.oAutoComp1 = new YAHOO.widget.AutoComplete('ysearchinput1','ysearchcontainer1', this.oACDS);
    this.oAutoComp1.queryDelay = 0.6;
    this.oAutoComp1.maxResultsDisplayed=30;
    this.oAutoComp1.autoHighlight=false;
    this.oAutoComp1.formatResult = this.fnCustomFormatter;
    
    //define your itemSelect handler function:
    this.itemSelectHandler = function(sType, aArgs) {
    	var aData = aArgs[2]; //array of the data for the item as returned by the DataSource
    	
    	document.getElementById('grid_reference').value = aData[0];
    	document.getElementById('ysearchinput1').value = aData[1];
    	fetchMap();
    };
    
    //subscribe your handler to the event
	this.oAutoComp1.itemSelectEvent.subscribe(this.itemSelectHandler);
};
</script>

{/literal}
	<script type="text/javascript" src="{"/mapping1.js"|revision}"></script>
	<script type="text/javascript" src="{"/js/geotools2.js"|revision}"></script>
{else}
	<p>There are no images available available in the current set.</p>
	{if $game->score}<p>Don't forget to <a href="/games/score.php">save your score</a>!</a></p>{/if}
	
{/if}
{/dynamic}

{include file="_std_end.tpl"}
