{assign var="page_title" value="Query Edit"}
{include file="_std_begin.tpl"}
{dynamic}

<h2>Query Edit</h2>
    
{if $message}
	<div style="border:1px solid red; padding:10px;">{$message}</div>
{/if}
    

{if $i}
	<h3>Query Saved</h3>
	<p><a href="/search.php?i={$i}">Run query {$i} now</a></p>
	or 
	<p><a href="/admin/query.php?i={$i}">Edit Query</a></p>
{elseif $id} 

{if $user->user_id != $row.user_id}
	<div class="interestBox" style="background-color:pink; color:black; border:2px solid red; padding:10px;">
	<img src="http://{$static_host}/templates/basic/img/icon_alert.gif" alt="Alert" width="50" height="44" align="left" style="margin-right:10px"/>
	<b>This is not your search</b>, as a administrator you can edit the search, but please only use this facility with good reason.<br/>
	</div>
<br/>

{/if}	
	<form action="{$script_name}" method="post">
		<table cellpadding="3" cellspacing="0" style="font-size:0.8em">
		{foreach key=name item=value from=$row name=loop}
		  <tr onmouseover="this.style.background='#efefef'" onmouseout="this.style.background='#ffffff'">
			 <td><b>{$name}</b> {$map.$name}</td>
			 {if $desc.$name.values}
			 	<td><select name="{$name}" {$desc.$name.multiple} value="{$value|escape:'html'}">
			 	
			 		{html_options options=$desc.$name.values selected=$value} 
				
			 	</select></td>
			 
			 {else}
			 	<td><input type="text" name="{$name}" value="{$value|escape:'html'}" size="{$desc.$name.size}" maxlength="{$desc.$name.maxlength}"></td>
			 {/if}
			 <td>{$desc.$name.Type}</td>
		  </tr>
		{/foreach}
		  <tr>
			 <td>&nbsp;</td>
			 <td><input type="submit" value="Save" name="submit"></td>
		  </tr>
	</table></form>


{/if}	 
   
    
{/dynamic}    
{include file="_std_end.tpl"}
