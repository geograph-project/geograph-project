{include file="_std_begin.tpl"}

<h2>Photo Exchange</h2>
<a href="{$script_name}">Make a request</a>
{dynamic}


{if $replies}

	<h3>Replies</h3>
	<hr style="clear:both"/>


	{foreach from=$replies item=reply}
		<h4 style="clear:both">{$reply.topic|escape:'html'}</h4>

		{assign var="id" value=$reply.left_gridimage_id}
		{if $id}
			{assign var="image" value=$images.$id}

			<div style="float:left; position:relative; width:130px; text-align:center">
				<a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a>
			</div>
		{/if}

		{assign var="id" value=$reply.rigth_gridimage_id}
		{if $id}
			{assign var="image" value=$images.$id}

			<div style="float:left; position:relative; width:130px; text-align:center">
				<a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a>
			</div>
		{/if}

		<div style="float:left; position:relative; width:300px;">
			{assign var="id" value=$reply.left_user_id}
			{if $id}
				{assign var="realname" value=$users.$id}
				&middot; <a href="/photo/{$id}">{$realname|escape:'html'}</a><br/>
			{/if}
			{assign var="id" value=$reply.rigth_user_id}
			{if $id}
				{assign var="realname" value=$users.$id}
				&middot; <a href="/photo/{$id}">{$realname|escape:'html'}</a><br/>
			{/if}
		</div>

		<br style="clear:both"/>
		<hr/>
	{/foreach}
	<br style="clear:both"/>
{else}
	<p>Nothing to show</p>
{/if}

{/dynamic}

{include file="_std_end.tpl"}
