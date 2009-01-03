{assign var="page_title" value="Feedback"}
{include file="_std_begin.tpl"}

<h2>Send Feedback</h2>

<p>All replies are anonymous, results will only be used aggregated form.</p>

<hr/>
{dynamic}

<form>

<p>A. First rate your expeience with the following on a scale of 1-5:</p>

<table class="report sortable" id="theTable">
	
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

<p>B. Now rate the ease of use of the following features:</p>


<table class="report sortable" id="theTable">
	
	<tbody>
	{assign var="lastcat" value="0"}
	{foreach from=$list item=row}
		{if $row.category != $lastcat}
			<thead><tr style="font-size:0.9em">
				<td><big>{$row.category}</big></td>
				<td width="70" align="center">Didn't know possible</td>
				<td width="70" align="center">Never Tried</td>
				<td width="70" align="center">Hard</td>
				<td width="70" align="center">Problematic</td>
				<td width="70" align="center">Average</td>
				<td width="70" align="center">Reasonable</td>
				<td width="70" align="center">Very Easy</td>
			</tr></thead>
			{assign var="lastcat" value=$row.category}
		{/if}
		
		{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
		<tr bgcolor="{$bgcolor}">
		<td>&nbsp;{$row.question}</td>
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

<p>C. Any other comments:</p>
<textarea name="comments" rows="7" cols="80"></textarea><br/>
(<input type="checkbox" name="nonanon"/> Identify yourself with this comment - we can then reply)

<hr/>

<p>D. <input type="submit" name="submit" value="Send it in!"/></p>
</form>


{/dynamic}    
{include file="_std_end.tpl"}
