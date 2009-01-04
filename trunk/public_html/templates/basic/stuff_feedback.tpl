{assign var="page_title" value="Feedback"}
{include file="_std_begin.tpl"}
{dynamic}
{if $thanks}
	<h3>Thanks!</h3>
	<p>Many thanks for your feedback, it's much appreciated. Watch the Discussion area where we will publish the overall results.</p>

{else}
<h2>Let us know what you think!</h2>

<p>Please take a few minutes to answer the following questions. We will use the answers help identify what parts of the site needs improvement.</p>

<p style="color:red">All replies are anonymous, we will not link this data with your user account.</p>

<hr/>

<form method="post" action="{$script_name}">

<p><b>A. Please tell us how experienced you are with with the following on a scale of 1-5 (where 5 is very experienced):</b></p>

<table class="report sortable" id="expTable" cellpadding="5">
	
	<tbody>
	{assign var="lastcat" value="0"}
	{foreach from=$exp item=row}
		{if $row.category != $lastcat}
			<thead><tr style="font-size:0.9em">
				<td></td>
				<td align="center">1</td>
				<td align="center">2</td>
				<td align="center">3</td>
				<td align="center">4</td>
				<td align="center">5</td>
			</tr></thead>
			{assign var="lastcat" value=$row.category}
		{/if}
		
		{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
		<tr bgcolor="{$bgcolor}">
		<td>{$row.question}</td>
		<td align="center"><input type="radio" name="radio{$row.id}" value="1"/></td>
		<td align="center"><input type="radio" name="radio{$row.id}" value="2"/></td>
		<td align="center"><input type="radio" name="radio{$row.id}" value="2"/></td>
		<td align="center"><input type="radio" name="radio{$row.id}" value="3"/></td>
		<td align="center"><input type="radio" name="radio{$row.id}" value="4"/></td>
		</tr>
	{/foreach}
	</tbody>
</table>

<hr/>
<p><b>B. Now rate the ease of use of the following features:</b></p>


<table class="report sortable" id="theTable" cellpadding="5">
	
	<tbody>
	{assign var="lastcat" value="0"}
	{foreach from=$list item=row}
		{if $row.category != $lastcat}
			<thead><tr style="font-size:0.9em">
				<td valign="bottom"><big>&nbsp;&nbsp;&nbsp;{$row.category}</big></td>
				<td width="50" align="center">Didn't know possible</td>
				<td width="50" align="center">Never Tried</td>
				<td width="50" align="center">Hard</td>
				<td width="50" align="center">Problematic</td>
				<td width="50" align="center">Average</td>
				<td width="50" align="center">Reasonable</td>
				<td width="5 0" align="center">Very Easy</td>
			</tr></thead>
			{assign var="lastcat" value=$row.category}
		{/if}
		
		{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
		<tr bgcolor="{$bgcolor}">
		<td>{$row.question}</td>
		<td align="center"><input type="radio" name="radio{$row.id}" value="-2"/></td>
		<td align="center"><input type="radio" name="radio{$row.id}" value="-1"/></td>
		<td align="center"><input type="radio" name="radio{$row.id}" value="1"/></td>
		<td align="center"><input type="radio" name="radio{$row.id}" value="2"/></td>
		<td align="center"><input type="radio" name="radio{$row.id}" value="2"/></td>
		<td align="center"><input type="radio" name="radio{$row.id}" value="3"/></td>
		<td align="center"><input type="radio" name="radio{$row.id}" value="4"/></td>
		</tr>
	{/foreach}
	</tbody>
</table>

<hr/>

<p><b>C. Any other comments to add?</b></p>
<textarea name="comments" rows="7" cols="80"></textarea><br/>
{if $user->registered}
<small>(<input type="checkbox" name="nonanon"/> <i>Tick here to include your name with this comment, so we can then reply. Will not be linked with the rest of the questions</i>)</small>
{/if}
<hr/>

<p><b>D. <input type="submit" name="submit" value="Send it in!" style="font-size:1.1em"/></b></p>
</form>
{/if}

{/dynamic}    
{include file="_std_end.tpl"}
