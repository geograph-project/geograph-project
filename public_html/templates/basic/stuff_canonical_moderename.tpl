{assign var="page_title" value="Canonical Categories"}
{include file="_std_begin.tpl"}
{dynamic}
<h2><a href="?">Canonical Category Mapping</a></h2>

{if !$canonical_old}
	<p>Congratulations. Nothing more to do right now.</p>
{else}
	<h3>Original Canonical Category</h3>
	<div class="interestBox" style="padding-left:20px;">
		<h4>{$canonical_old|escape:'html'}</h4>
	</div>
	
	<h3>New Canonical Category</h3>
	<div class="interestBox" style="padding-left:20px;">
		<h4>{$canonical_new|escape:'html'}</h4>
	</div>
	
	<br/><br/>
	
	<form method="post" action="{$script_name}?mode={$mode}" name="theForm">
		<input type="hidden" name="canonical_old" value="{$canonical_old|escape:'html'}"/>
		<input type="hidden" name="canonical_new" value="{$canonical_new|escape:'html'}"/>
		
		<div>
			Choose one:
			
			<input type="submit" name="submit" value="Agree" style="background-color:lightgreen"/>
			<input type="submit" name="submit" value="Disagree" style="background-color:pink"/>
			<input type="submit" name="submit" value="Indifferent" style="color:gray"/>
		</div>
		<br/><br/>
			
	</form>	
	
	<br/><br/>
{/if}

	<a href="?">Start over</a>

	<br/><br/>

{/dynamic}
{include file="_std_end.tpl"}
