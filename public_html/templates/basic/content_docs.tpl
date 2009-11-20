{assign var="page_title" value="Guides, Tutorials"}
{assign var="meta_description" value="Geograph Information, Guides and Tutorials"}
{include file="_std_begin.tpl"}


<div class="interestBox">
<h2>Information, Guides and Tutorials</h2>
</div>



{assign var="lastid" value="0"}
{foreach from=$list item=item}
{if $lastcat != $item.category_name}
{if $lastcat}
</ul>
</div>
{cycle values=",<br style='clear:both'/>"}
{/if}
<div style="float:left;width:46%;position:relative; padding:5px;">
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
	<li><i>There are no articles to display at this time.</i></li>
{/foreach}

</ul>
</div>
<br style="clear:both"/>


{include file="_std_end.tpl"}
