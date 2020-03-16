{assign var="page_title" value="Geograph i Ysgolion"}
{include file="_std_begin.tpl"}

{box colour="333" style="width:160px;float:left;margin-right:15px;"}
<div class="infobox" style="height:389px">
<h1>Map o'r lluniau</h1>
<p>Cliciwch ar y map i bori drwy'r lluniau o Ynysoedd Prydain</p>

<div class="map" style="height:{$overview2_height}px;width:{$overview2_width}px">
<div class="inner" style="position:relative;top:0px;left:0px;width:{$overview2_width}px;height:{$overview2_height}px;">

{foreach from=$overview2 key=y item=maprow}
	<div>
	{foreach from=$maprow key=x item=mapcell}
	<a href="/mapbrowse.php?o={$overview2_token}&amp;i={$x}&amp;j={$y}&amp;center=1"><img 
	alt="Map mae modd ei glicio" ismap="ismap" title="Click to zoom in" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}"/></a>
	{/foreach}

	{if $marker}
	<div style="position:absolute;top:{$marker->top-8}px;left:{$marker->left-8}px;"><a href="/photo/{$pictureoftheday.gridimage_id}" title="Lleoliad Llun y Diwrnod"><img src="{$static_host}/img/crosshairs.gif" alt="+" width="16" height="16"/></a></div>
	{/if}

	</div>
{/foreach}
</div>
</div>


</div>
{/box}

{box colour="000" style="width:409px;float:left;margin-right:12px;"}
<div class="infobox" style="height:389px"> 
<h1>Llun y diwrnod</h1>

<a href="/photo/{$pictureoftheday.gridimage_id}" title="Cliciwch i weld delwedd maint llawn">{$pictureoftheday.image->getFixedThumbnail(393,300)}</a>

<div style="float:left">
<a rel="license" title="Trwydded Creative Commons" href="http://creativecommons.org/licenses/by-sa/2.0/"><img alt="Creative Commons License" style="border-width:0" src="{$static_host}/img/80x15.png" /></a>
</div>
<div class="potdtitle"><a href="/photo/{$pictureoftheday.gridimage_id}" title="Cliciwch i weld delwedd maint llawn">{$pictureoftheday.image->title}</a>
<span class="nowrap">gan <a title="Profile" href="{$pictureoftheday.image->profile_link}">{$pictureoftheday.image->realname}</a></span>
<span class="nowrap">yn sgw&acirc;r <a href="/gridref/{$pictureoftheday.image->grid_reference}" style="color:white;text-decoration:underline gray">{$pictureoftheday.image->grid_reference}</a></span>,
<span class="nowrap">wedi'i dynnu ar <a href="/search.php?gridref={$pictureoftheday.image->grid_reference}&amp;orderby=submitted&amp;taken_start={$pictureoftheday.image->imagetaken}&amp;taken_end={$pictureoftheday.image->imagetaken}&amp;do=1">{$pictureoftheday.image->getFormattedTakenDate()}</a></span>
</div>
</div>
{/box}

{box colour="333" style="width:160px;float:left;"}

<div class="infobox" style="height:389px">
<h1>Croeso</h1>
<p>Nod prosiect Geograph Prydain ac Iwerddon yw casglu llun o bob cilometr sgwâr o Ynysoedd Prydain, a gallwch chi fod yn rhan o hynny.</p>

<p><a href="/help/more_pages" style="width:144px;height:63px;background-image: url({$static_host}/templates/charcoal_cy/img/find_out_more.png);
    display:inline-block; padding-top:24px; box-sizing:border-box; color:white; text-decoration:none; padding-left:5px;"
>Rhagor o wybodaeth...</a></p>

<div id="photocount">{$stats.images|thousends}</div>
<div id="photocount_title">llun</div>

<div id="call_to_action">

...ond mae llai na 4 llun ar gyfer {$stats.fewphotos|thousends} o'r sgwariau, <a href="/submit.php">felly ewch ati i ychwanegu'ch lluniau chi!</a>
</div>

</div>

{/box}

<br style="clear:both"/>
<br style="clear:both"/>

{box colour="333" style="margin-bottom:12px;font-size:1.3em;text-align:center"}
<div class="titlebox">&middot;
	<a href="/games/" style="color:white">Gemau a Gweithgareddau</a> &middot;
	<a href="/content/?&order=views" style="color:white">Casgliadau</a> &middot;
	<a href="/browser/" style="color:white">Porwr Delweddau</a> &middot;
	<a href="/tags/" style="color:white">Tagiau</a> &middot;
</div>
{/box}


<div style="width:370px;float:left;margin-right:14px;">

{box colour="333" style="margin-bottom:12px;"}
<div class="titlebox">
<b>Ydych chi'n Athro/Myfyriwr?</b> <small><a href="/help/education_feedback" style="color:white">Rydyn ni'n gwerthfawrogi eich barn</a></small>.
</div>
{/box}



{box colour="f4f4f4"}
<div class="infobox_alt">
<h2>Archwilio...</h2>
<ul>

	<li><a href="/mapper/combined.php?lang=cy" title="View the coverage Map">Archwiliwch yr ynysoedd hyn gyda'n <b>map</b></a></li>
	<li><a href="/finder/welsh.php?lang=cy" title="Image Search"><b>Chwiliwch</b> am leoedd neu nodweddion</a></li>
	<li><a href="/explore/" title="Themed Exploring">Chwiliwch yn &ocirc;l <b>thema</b></a></li>
	<li><a href="/content/" title="Submitted Content">Darllenwch <b>gynnwys</b> sydd wedi cael ei gyflwyno gan aelodau</b></a></li>
	<li><a href="/help/sitemap">Gweld map cyfan o'r safle</a></li>
</ul>


<h2>Defnyddiwch ac ail-ddefnyddiwch ein delweddau!</h2>
<ul>
	<li><a href="/kml.php" title="KML Exports">Geograph gyda <b>Google Earth</b></a></li>
	<li><a href="/help/sitemap#software" title="Geograph Page List"><b>Ffyrdd eraill</b> o ddefnyddio'r adnodd arbennig hwn</a></li>
	<li><a href="/activities/" title="Activites">Gweld delweddau yn ein hadran <b>Weithgareddau</b></a></li>
	<li><a href="/teachers/" title="Education Area">Geograph ar gyfer <b>athrawon</b></a><br/><br/></li>
	
	<li>Mae ein lluniau ar gael i'w hail-ddefnyddio o dan <b>{external href="http://creativecommons.org/licenses/by-sa/2.0/" text="Drwydded Creative Commons"}</b>. <a href="/help/freedom" title="">Rhagor o wybodaeth</a></li>
</ul>


<h2>Cymerwch ran...</h2>
<ul>

	<li><a href="/games/" title="educational games">Rhowch gynnig ar <b>gemau</b> yn defnyddio ein lluniau a.n mapiau</a></li>
	<li><a href="/submit.php" title="">Ychwanegwch <b>eich lluniau eich hun</b></a></li>
	<li><a href="/article/edit.php" title="">Ysgrifennwch <b>erthygl</b></a></li>
	{if $enable_forums}
	<li><a href="/discuss/" title="">Trafodaethau</a></li>
	{/if}
	<li><a href="/help/guide" title="">Gweld ein <b>meini prawf cyflwyno</b></a></li>

</ul>

</div>
{/box}

</div>

<div style="width:370px;float:left;">

{box colour="333" style="margin-bottom:12px;"}
<div class="titlebox">
<h1>Canllaw i'r Safle</h1>
</div>
{/box}

	{box colour="f4f4f4"}
	<div class="infobox_alt">



	<h2>Ydych chi'n hoffi ystadegau?</h2>
	<ul>

		<li><a href="/numbers.php" title="">Gweld <b>crynodeb</b></a></li>
		<li><a href="/statistics.php" title=""><b>Ystadegau</b> mwy manwl</a></li>
		<li><a href="/help/sitemap#stats" title="">Rhagor o Ystadegau</a></li>
		<li><a href="/statistics/moversboard.php" title="">Gweld rhestr o <b>bwy sydd ar y blaen</b></a></li>
	</ul>


	<h2>Angen Help?</h2>
	<ul>

		<li><a href="/faq3.php?l=0" title="">Gweld ein Cwestiynau Cyffredin</a></li>
		<li><a href="/help/credits" title="">Pwy sy'n rhedeg y safle</a></li>
		<li><a href="/contact.php" title="">Cysylltu &acirc; ni</a></li>

	</ul>
	</div>
	{/box}
	
	
	<br/><br/><br/>
	{box colour="f4f4f4"}
	<div class="infobox_alt" style="font-size:0.7em; text-align:center;">
	<b class="nowrap">Mae {$stats.users|thousends} o ddefnyddwyr</b> wedi cyfrannu 
	<span class="nowrap"><b>{$stats.images|thousends}</b> o luniau</span>
	ar gyfer <span  class="nowrap"><b>{$stats.squares|thousends}</b> o sgwariau'r grid</span>,
	sy'n <b class="nowrap">{$stats.percentage}%</b> o gyfanswm y sgwariau.<br/>

	Hectadau a gafodd eu cwblhau.n ddiweddar:
	{foreach from=$hectads key=id item=obj}
	<a title="View Mosaic for {$obj.hectad}, completed {$obj.last_submitted}" href="/maplarge.php?t={$obj.largemap_token}">{$obj.hectad}</a>,
	{/foreach}
	<a href="/statistics/fully_geographed.php" title="Completed 10km x 10km squares">mwy...</a><br/>

	Mae llai na 4 llun ar gyfer <b>{$stats.fewphotos|thousends} o'r sgwariau</b>, <a href="/submit.php">felly ewch ati i ychwanegu'ch lluniau chi</a>!
	
	</div>
	{/box}
	
	
</div>

<br style="clear:both"/>
&nbsp;

{include file="_std_end.tpl"}
