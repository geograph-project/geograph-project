{assign var="page_title" value="Hectad Adoptions"}
{include file="_std_begin.tpl"}

<h2>Hectad Adoptions <sup style="color:red">Alpha</sup></h2>
 
<p>This section is new and quickly evolving, we don't even know ourselves how this section is going to operate{if $enable_forums}, to offer feedback please visit <a href="/discuss/index.php?&action=vtopic&amp;forum=12">the forum</a>{/if}.</p>


<h3>Pre Registration</h3>

<ul>
	<li><a href="/adopt/choose.php">Register your interest in adopting hectads!</a></li>
	<li><a href="/adopt/statistics.php">View some basic statistics about pre-registrations</a></li>
</ul>


{dynamic}
{if $hectads}
	<h3>Adoptions</h3>
 
	{if $message}
		<p>{$message}</p>
	{/if}

	<ul>

	{foreach from=$hectads item=item}
		<li><tt style="background-color:#eeeeee;font-size:1.5em;padding:2px">{$item.hectad}</tt> - <small>
			{if $item.status eq 'offered'}
				<b>Available for adoption</b> {if $item.indate}until {$item.expiry}{/if}
				[<a href="?accept={$item.hectad}">Accept Offer</a>]
			{/if}
			{if $item.status eq 'accepted'}
				<b>Active</b> 
				[<a href="/adopt/edit.php?hectad={$item.hectad}">Select Images</a>]
				<small>until {$item.expiry}</small>
			{/if}
		</li></small>
	{/foreach}
	</ul>
{/if}
{/dynamic}

{include file="_std_end.tpl"}
