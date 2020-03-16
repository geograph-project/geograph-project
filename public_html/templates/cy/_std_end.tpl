</div>
</div>
<div id="nav_block" class="no_print">
 <div class="nav">
  <ul>
    <li style="font-size:1.42em"><a accesskey="1" title="Return to the Home Page" href="/?lang=cy">Hafan</a></li>
    <li>Gweld<ul>
     <li><a title="Find and locate images" href="/finder/welsh.php?lang=cy">Chwilio</a></li>
     <li id="markedLink" style="display:none"><a title="View your current Marked Images" href="/finder/marked.php">Delweddau wedi'u marcio</a></li>
     <li><a title="View map of all submissions" href="/mapper/combined.php?lang=cy">Mapiau</a></li>
     <li><a title="Interactive browser, search and map in one" href="/browser/#!start">Porwr</a></li>
     <li><a title="Explore images by theme" href="/explore/">Archwilio</a></li>
     <li><a title="Curated selection of images" href="/gallery.php">Arddangos</a></li>
    </ul></li>
    <li><ul>
     <li><a title="Submitted Pages, Galleries and Articles" href="/content/">Casgliadau</a></li>
    </ul></li>
    <li>Rhyngweithio<ul>
     <li><a title="Geographical games to play" href="/games/">Gemau</a></li>
     <li><a title="Discussion Forum" href="/discuss/">Trafodaethau</a></li>
     {dynamic}{if $user->registered}
     <li><a title="Geograph Blog" href="/blog/">Blogiau Aelodau</a></li>
     {/if}{/dynamic}
    </ul></li>
    <li>Cyfranwyr<ul>
     <li><a title="Submit your photos" href="/submit.php">Cyflwyno</a></li>
     {dynamic}{if $user->registered}
     <li><a title="Your most recent submissions" href="/submissions.php">Wedi llwytho i fyny yn ddiweddar</a></li>
     {/if}{/dynamic}
     <li><a title="Interesting facts and figures" href="/numbers.php">Ystadegau</a></li>
     <li><a title="Contributor leaderboards" href="/statistics/moversboard.php">Tabl Arweinwyr</a></li>
    </ul></li>
    <li>Cyffredinol<ul>
     <li><a title="Frequently Asked Questions" href="/faq3.php?l=0">Cwestiynau Cyffredin</a></li>
     <li><a title="Info, Guides and Tutorials" href="/content/documentation.php">Tudalennau Cymorth</a></li>
     <li><a title="View a list of all pages" href="/help/sitemap">Map o'r Safle</a></li>
     <li><a title="Contact the Geograph Team" href="/contact.php">Cysylltu &acirc; Ni</a></li>
     <li><a title="Donate to Geograph Project" href="/help/donate">Cefnogwch Ni</a></li>
    </ul></li>
  {dynamic}
  {if $is_mod || $is_admin || $is_tickmod}
    <li>Gweinyddol<ul>
     <li><a title="Admin Tools" href="/admin/">Mynegai Gweinyddol</a></li>
     {if $is_mod}
     	<li><a title="Moderation new photo submissions" href="/admin/moderation.php">Safoni </a></li>
     {/if}
     {if $is_tickmod}
     	<li><a title="Trouble Tickets" href="/admin/suggestions.php">Awgrymiadau</a></li>
     {/if}
     <li><a title="Finish Moderation for this session" href="/admin/moderation.php?abandon=1">Gorffen</a></li>
    </ul></li>
  {/if}
  {/dynamic}
  </ul>
<div class="sponsor">noddir gan <br/> <br/>
<a title="Geograph sponsored by Ordnance Survey" href="https://www.ordnancesurvey.co.uk/education/"><img src="{$static_host}/img/os-logo-p64.png" width="64" height="50" alt="Ordnance Survey"/></a></div>
{if $image && $image->collections}
	<h3 class="newstitle">This photo is linked from:</h3>
	{assign var="lasttype" value="0"}
	{foreach from=$image->collections item=item}
		{if $lasttype != $item.type}
			<div class="newsheader">{$item.type|regex_replace:"/y$/":'ie'}s</div>
		{/if}{assign var="lasttype" value=$item.type}
		<div class="collection">&middot; <a href="{$item.url}" title="{$item.type|escape:'html'}">{$item.title|escape:'html'}</a></div>
	{/foreach}
{elseif $square && $square->collections}
	<h3 class="newstitle">Collections:</h3>
	{assign var="lasttype" value="0"}
	{foreach from=$square->collections item=item}
		{if $lasttype != $item.type}
			<div class="newsheader">{$item.type|regex_replace:"/y$/":'ie'}s</div>
		{/if}{assign var="lasttype" value=$item.type}
		<div class="collection">&middot; <a href="{$item.url}" title="{$item.type|escape:'html'}"{if $item.type == $square->grid_reference} style="font-weight:bold"{/if}>{$item.title|escape:'html'}</a></div>
	{/foreach}
{/if}
{if $discuss}
	{foreach from=$discuss item=newsitem}
		<h3 class="newstitle">{$newsitem.topic_title}</h3>
		<div class="newsbody">{$newsitem.post_text}</div>
		<div class="newsfooter">
		Posted by <a href="/profile/{$newsitem.user_id}">{$newsitem.realname}</a> on {$newsitem.topic_time|date_format:"%a, %e %b %Y"}
		<a href="/discuss/index.php?action=vthread&amp;topic={$newsitem.topic_id}">({$newsitem.comments} {if $newsitem.comments eq 1}comment{else}comments{/if})</a>
		</div>
	{/foreach}
{/if}
{if $news}
	{foreach from=$news item=newsitem}
		<h3 class="newstitle">{$newsitem.topic_title}</h3>
		<div class="newsbody">{$newsitem.post_text}</div>
		<div class="newsfooter">
		Posted by <a href="/profile/{$newsitem.user_id}">{$newsitem.realname}</a> on {$newsitem.topic_time|date_format:"%a, %e %b %Y"}
		<a href="/discuss/index.php?action=vthread&amp;topic={$newsitem.topic_id}">({$newsitem.comments} {if $newsitem.comments eq 1}comment{else}comments{/if})</a>
		</div>
	{/foreach}
	{if $rss_url}
		<h3><a rel="alternate" type="application/rss+xml" title="RSS Feed" href="{$rss_url}" class="xml-rss">News RSS Feed</a></div>
	{/if}
{/if}
  </div>
</div>
<div id="search_block" class="no_print">
  <div id="search">
    <div id="searchform">
    <form method="get" action="/finder/welsh.php">
    <div id="searchfield">
    <input type="hidden" name="lang" value="cy"/>
    {dynamic}<input id="searchterm" type="text" name="q" value="{$searchq|escape:'html'}" size="10" title="geiriau allweddol, neu rhowch enw lle, cod post, neu gyfeirnod grid yma" placeholder="chwilio am luniau..." style="background:white"/>{/dynamic}
    <input id="searchbutton" type="submit" name="go" value="Chwilio"/></div>
    </form>
    </div>
  </div>
  <div id="login"><span class="nowrap">
  {dynamic}
  {if $user->registered}
  	  Wedi mewngofnodi fel {$user->realname|escape:'html'}
  	  <span class="sep">|</span>
  	  <a title="Profile" href="/profile.php">proffil</a>
  	  <span class="sep">|</span></span>
  	  <a title="Log out" href="/logout.php">allgofnodi</a>
  {else}
	  heb fewngofnodi
	  <a title="Already registered? Login in here" href="/login.php">mewngofnodi</a>
		<span class="sep">|</span></span>
	  <a title="Register to upload photos" href="/register.php">cofrestru</a>
  {/if}
  {/dynamic}
  </div>
</div>
{if $right_block}
	{include file=$right_block}
	<div class="content3" id="footer_block">
{else}
	<div class="content2" id="footer_block">
{/if}
  <div id="footer" class="no_print">
     <p style="color:#AAAABB;float:left">Diweddarwyd y dudalen am {$smarty.now|date_format:"%H:%M"}</p>
   <p><a href="/help/sitemap" title="Listing of site pages">Map o'r safle</a>
       <span class="sep">|</span>
       <a href="/article/Use-of-Cookies-on-Geograph-Website" title="How this site uses cookies">Cwcis</a>
       <span class="sep">|</span>
       <a href="/help/credits" title="Who built this and how?">Cydnabyddiaeth</a>
       <span class="sep">|</span>
       <a href="/help/terms">Telerau defnyddio</a>
       <span class="sep">|</span>
       <a href="/article/Get-Involved" title="contribute to geograph">Cymryd Rhan</a>
    </p>
    <p style="color:#777788;">Gweithredir y wefan gan
    {external title="click to visit the livetodot website" href="http://www.livetodot.com/hosting/" text="wasanaeth Lletya Livetodot" nofollow="true"}
    </p>
  </div>
</div>
{dynamic}{pagefooter}{/dynamic}
</body>
</html>

