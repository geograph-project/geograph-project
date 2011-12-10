{assign var="page_title" value="Guides, Tutorials"}
{assign var="meta_description" value="Geograph Information, Guides and Tutorials"}
{include file="_std_begin.tpl"}


<div class="interestBox">
<h2 style="margin:0">Information, Guides and Tutorials</h2>
</div>
<div style="float:right;margin-right:20px"><a href="/content/?docs&amp;order=updated">View by last updated</a></div>


{include file="_doc_search.tpl"}


{if $enable_forums}
<ul>
	<li>We have a new user-contributed <a href="/faq3.php">Knowledgebase</a>, please help us improve it!</li>
</ul>
{/if}


{assign var="lastid" value="0"}
{foreach from=$list item=item}
	{if $lastcat != $item.category_name}
		{if $lastcat}
			</ul>
		{/if}

		{if !$lastid || $item.category_name eq $splitcat}
			{if $lastcat}
				</div>
			{/if}
			<div style="float:left;width:46%;position:relative; padding:5px;">
		{/if}
		<h3>{$item.category_name}</h3>
		<ul class="content">
		{assign var="lastname" value=""}
	{/if}
	<li><b><a title="{$item.extract|default:'View Article'}" href="{$item.url}">{$item.title}</a></b><br/>
	<small id="att{$lastid+1}"><small style="color:lightgrey">by <a href="/profile/{$item.user_id}" title="View Geograph Profile for {$item.realname}"  style="color:#6699CC">{$item.realname}</a>

		</small></small>

	</li>
	{if $lastname == $item.realname && $user->realname != $lastname}
		<script>document.getElementById('att{$lastid}').style.display='none'</script>
	{/if}
	{assign var="lastname" value=$item.realname}
	{assign var="lastcat" value=$item.category_name}
	{assign var="lastid" value=$lastid+1}
{foreachelse}
	<li><i>There are no Articles to display at this time.</i></li>
{/foreach}

</ul>
</div>
<br style="clear:both"/>

	<div class="interestBox" style="font-size:1.3em;margin-bottom:20px">Can't find what you looking for? <a href="/ask.php">Ask us</a>!</div>

<p align="center">Geograph Project Limited is a company limited by guarantee. Registered in England and Wales, number 7473967. Registered office: 26 Cloister Road, Acton, London W3 0DE. <a href="/article/About-Geograph-page">About Geograph Project</a>.</p>



{include file="_std_end.tpl"}

