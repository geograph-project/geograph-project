{assign var="page_title" value="Chwilio lluniau"}
{include file="_std_begin.tpl"}

<style>{literal}
form.finder {
	background-color:silver;
	padding:5px;
	margin-bottom:15px;
}
#results div.thumbs {
  margin-left:10px
}
#results div.thumb {
  float:left;
  width:213px;
  height:160px;
  margin:2px;
  text-align:center;
}
#results p, #output div.clear {
  clear:both;
}
#results h3 {
  clear:both;
  background-color:black;
  color:white;
  padding:10px;
}

.shadow div.thumb {
    padding-right: 8px;
    padding-bottom: 8px;
}


#mainImage {
  color:white;
  text-align:center;
}
#mainImage a {
  color: yellow;
  text-decoration:none;
}
#mainImage div {
  padding-top:3px;
  padding-bottom:3px;
}
#mainImage img#full{
  padding:2px;
  border:1px solid #333;
  border-radius:3px;
}
#mainImage .ccmessage img {
  vertical-align: middle;
}
#mainImage .title {
  background-color: #333;
  margin-bottom:10px;
  text-align:left; /* this is deliberate as centered doesnt work with close button! */
  padding:10px;
}
#mainImage .title b {
  font-size:1.2em;
}
#mainImage .buttons {
  background-color:gray;
}
#mainImage .buttons a {
  color:cyan;
  text-decoration:none;
  white-space: nowrap;
}
#maindesc div.maindesc {
    font-size: 0.8em;
    margin-left: auto;
    margin-right: auto;
    width: 650px;
}


#lightbox-background {
 display:none;
 background:#555555;
 opacity:0.7;
 -ms-filter:"progid:DXImageTransform.Microsoft.Alpha(Opacity=70)";
 filter:alpha(opacity=70);
 zoom:1;
 position:fixed;
 top:0px;
 left:0px;
 min-width:100%;
 min-height:100%;
 z-index:99;
}
.lightbox {
  display:none;
  position:fixed !important;
  top:110px;
  width:660px;
  left:calc( 50vw - 330px );
  max-height:80vh;
  overflow:auto;
  background-color:black;
  padding:7px;
  z-index:100;
  box-shadow:         0px 0px 4px 4px gray;
}

@media only screen and (min-width: 1400px) {
   .lightbox {
      width:1360px;
      left:calc( 50vw - 660px );
   }

   .lightbox .part1, .lightbox .part2 {
      float:left;
      width:660px;
   }
}

.close_button {
  float:right;
  padding-left:20px;
  width:80px;
  text-align:right;
  margin-right:-7px;
  margin-top:-7px;
}
.close_button input {
  background-color:red;
  border:0;
  color:white;
  font-weight:bold;
  padding:7px;
  cursor:pointer;
}

{/literal}</style>

<div id="message" style="float:right"></div>

<h2>Chwilio lluniau ar Geograph</h2>
<form method="get" onsubmit="return submitSearch(this)" class="finder">
	<div style="float:left;width:310px;height:44px">
		<label for="qqq">Chwilio am Eiriau allweddol:<br/></label>
		<input type="search" name="q" id="qqq" value="" size="38" placeholder="(rhowch y geiriau allweddol yma)">
	</div>
	<div style="float:left;width:310px;height:44px">
		<label for="loc">Chwilio gerllaw:<br/></label>
		<input type="search" name="loc" id="loc" size="38" placeholder="(enw lle, cod post, neu gyfeirnod grid yma)">
	</div>
	<div style="float:left;width:110px;height:44px">
		&nbsp;<br/>
		<input type="submit" value="Rhedeg Chwiliad">
	</div>

	<div id="location_prompt" style="clear:both"></div>

	<label for="wales">Chwilio am luniau yng Nghymru yn unig? <input type="checkbox" name="wales" id="wales" checked="">(ticiwch)</label><br>

	<label for="context">Cynnwys Daearyddol</label>: <select name="context" id="context">
		<option value="">Dewis term...</option>
		{assign var="last" value=""}
		{foreach from=$context item=row}
			{if $last != $row.grouping_cy}
				{if $last}
					</optgroup>
				{/if}
				<optgroup label="{$row.grouping_cy|escape:html}">
				{assign var="last" value=$row.grouping_cy}
			{/if}
			<option value="{$row.top|escape:html}">{$row.top_cy|escape:html}</option>
		{/foreach}
		{if $last}
			</optgroup>
		{/if}
	</select>(dewisol)
</form>

<div id="results">
   <div id="contentResults"></div>
   <div id="curatedResults"></div>
   <div id="plainResults">
	<p>Defnyddiwch y dudalen hon i chwilio am ddelweddau Geograph, testun Saesneg sydd gan y rhan fwyaf o'n delweddau, felly ceir y canlyniadau gorau drwy roi geiriau allweddol Saesneg. Mae rhai testunau wedi cael eu cyfieithu felly mae modd rhoi cynnig ar eiriau Cymraeg.</p>
	<p>A/ neu gallwch chi chwilio am luniau sydd wedi cael eu tynnu ger lleoliad penodol.</p>
   </div>
   <div id="translatedResults"></div>
</div>

<hr>
<p>Rhyngwyneb chwilio sydd wedi'i symleiddio yw hwn, er mwyn gofyn cwestiynau mwy cymhleth defnyddiwch y ffurflen <a href="/search.php?form=text">Chwilio Manwl</a>.</p>


<div id="previewImage" class="lightbox">
        <div class="close_button">
          <input type="button" onclick="$('.lightbox, #lightbox-background').hide();" value="Caewch" />
        </div>
        <div id="mainImage"></div>
</div>
<div id="lightbox-background"></div>


<link type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.22/themes/ui-lightness/jquery-ui.css" rel="stylesheet"/>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js" type="text/javascript"></script>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.22/jquery-ui.min.js"></script>

<script type="text/javascript" src="{"/mapper/geotools2.js"|revision}"></script>
<script src="/js/finder.cy.js?{$smarty.now}"></script>



{include file="_std_end.tpl"}

