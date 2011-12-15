{assign var="page_title" value="Geograph $what"}
{assign var="meta_description" value="A listing of all the $user_count $what with photos on Geograph Britain and Ireland"}
{include file="_std_begin.tpl"}

{if $where}
<div style="float:right">Switch to <a href="/credits.php?cloud&amp;where={$where}{if $whenname}&amp;when={$when}/{/if}">cloud version</a></div>
{elseif $whenname}
<div style="float:right">Switch to <a href="/credits.php?cloud{if $whenname}&amp;when={$when}{/if}">cloud version</a> or <a href="/statistics/breakdown.php?by=user{if $whenname}&amp;when={$when}{/if}">statistics version</a>.</div>
{/if}

<div align="center" class="tabHolder">
        <a href="/article/About-Geograph-page" class="tab">About Geograph</a>
        <a href="/team.php" class="tab">The Geograph Team</a>
        <span class="tabSelected">Contributors</span>
        <a href="/help/credits" class="tab">Credits</a>
        <a href="http://hub.geograph.org.uk/downloads.html" class="tab">Downloads</a>
        <a href="/contact.php" class="tab">Contact Us</a>
        <a href="/article/Get-Involved">Get Involved...</a>
</div>
<div style="position:relative;" class="interestBox">
        <h2 align="center" style="margin:0">Geograph Britain and Ireland {$what}{if !$whenname && !$where && !$filter} <small>[{$user_count|thousends} contributors]</small>{/if}</h2>

{if $whenname}
	<h3 align="center">Submitting images March 2005 though {$whenname} <small>[{$user_count|thousends} contributors]</small></h3>
{/if}
{if $where}
	<h3 align="center">Submitting in {$where} Myriad <small>[{$user_count|thousends} contributors]</small></h3>
{/if}
{if $filter}
	<h3 align="center">Beginning with {$filter} <small>[{$user_count|thousends} contributors]</small></h3>
{/if}
</div>

{if $users}
<p class="wordnet" style="font-size:0.8em;line-height:1.4em" align="center">
{foreach from=$users key=user_id item=obj}
&nbsp;<a title="{$obj.nickname|escape:'html'}, {$obj.images} images" {if $obj.images > 100} style="font-weight:bold"{/if} href="/profile/{$user_id}{if $obg.user_realname && $obj.realname ne $obj.user_realname}?a={$obj.realname|escape:'url'}{/if}">{$obj.realname|escape:'html'|replace:' ':'&middot;'}</a><small>&nbsp;[{$obj.images}]</small> &nbsp;
{/foreach}
</p>
{else}
	{if $sample}
		<div style="font-size:0.8em;width:200px;float:right;margin-left:10px;border-left:1px solid black;padding:3px;">
		<h3>Sample Listing</h3>
		{$samplename}<br/><br/>
		{foreach from=$sample key=user_id item=obj}
		<a title="{$obj.nickname|escape:'html'}, {$obj.images} images" {if $obj.images > 100} style="font-weight:bold"{/if} href="/profile/{$user_id}{if $obg.user_realname && $obj.realname ne $obj.user_realname}?a={$obj.realname|escape:'url'}{/if}">{$obj.realname|escape:'html'|replace:' ':'&middot;'}</a><small>&nbsp;[{$obj.images}]</small><br/>
		{/foreach}
		</div>
	{/if}

	We now have so many contributors, that listing everyone on one page makes for a very long page. One that takes a lot of resources.

	<p>So we have provided a few ways to browse the contributor lists easier...</p>

	<ul>
		<li><b>Contributor Search</b>:<br/>
			<form method="get" action="/finder/contributors.php">
				<label for="fcq">Keywords</label> <input type="text" name="q" id="fcq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
				<input type="submit" value="Search"/>
			</form><br/></li>

		<li><b>For a Hectad/Myriad</b>:<br/>
			<form method="get" action="/browse.php">
				<label for="gridref">Grid Ref</label> <input type="text" name="gridref" id="gridref" size="10"/>
				<input type="submit" value="Go"/><br/>
				Enter a Hectad (eg <tt>TQ75</tt> or <tt>H45</tt>) or a Myriad (eg <tt>J</tt> or <tt>NT</tt> - see myriads on <a href="/map/">map</a>)
			</form><br/></li>

		<li><b>For a Gridsquare</b>:<br/>
			<form method="get" action="/statistics/groupby.php">
				<input type="hidden" name="groupby" value="auser_id"/>
				<label for="gridsquare">Grid Ref</label> <input type="text" name="filter[agridsquare]" id="gridsquare" size="10"/>
				<input type="submit" value="Go"/><br/>
				Enter a 4figure Grid Rweference (eg <tt>TQ7351</tt>)
			</form><br/></li>

		<li><b>By First Letter</b>:<br/>
			<ol>
				{foreach from=$alphas item=alpha}
					<li value="{$alpha.count}"><a href="/credits/?filter={$alpha.alpha}" title="{$alpha.count} contributors">{$alpha.alpha}</a></li>
				{/foreach}
			</ol>
			[<a href="/credits/?filter=-">non letters</a>]
			<br/><br/></li>

		<li>We do still have a <a href="/sitemap/credits.html">single page listing everyone</a> (updated once a day)<br/>
			- but its big. About 1.1 Megabytes, so may take a while depending on your connection. <br/><br/></li>
	</ul>

	(Note: All these listings are combined listings of Contributors to <a href="/explore/places/1/">Great Britain</a> and <a href="/explore/places/2/">Ireland</a>.)

	<br style="clear:both"/>
{/if}

{include file="_std_end.tpl"}
