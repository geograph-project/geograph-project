{assign var="page_title" value="Contact"}
{include file="_std_begin.tpl"}

 <h2>Contact Geograph</h2>

<p>Geograph Britain and Ireland uses a support ticket system. Please use the form below to contact the Geograph website developers and/or Image moderators.</p> 

{dynamic}

	{if !$user->registered && $image}
		<div class="interestBox" style="background-color:yellow; text-align:center; width:500px; float:left; margin-right:20px">
		<h1 style="color:red;border-bottom:2px solid red;padding-bottom:10px">Stop!</h1>
		Trying to contact <b>{$image->title|escape:'html'}</b>?<br/><br/>
		Geograph is a photo sharing website, and only has a <i>photo</i> by that title, <u>not</u> the means to contact the location photographed. <br/><br/>

		<p><small>| <a href="javascript:history.go(-1)">Back to photo page</a> | <a href="/usermsg.php?to={$image->user_id}&amp;image={$image->gridimage_id}">Contact the photographer</a> |</small></p>
		</div>

		<div style="">
			<br/>
			&middot; or <b>Looking to copy/reuse the image you where viewing?</b> <a href="/reuse.php?id={$image->gridimage_id}">See this page</a>.
			<br/><br/><hr/><br/>
			
			<span style="color:darkgreen">The Geograph Britain and Ireland project aims to collect geographically representative photographs and information for every square kilometre of Great Britain and Ireland.</span><br/><br/>
			
			We currently have <b class="nowrap">{$stats.images|thousends} photographs</b> on the site; yes <b>{$stats.millions} million</b>.
		</div>
		<br style="clear:both"/><br/>
	{/if}

<iframe src="http://www.geograph.org.uk/support/open.php?ref={$referring_page|escape:'url'}{if $user->registered}&amp;user_id={$user->user_id}&amp;t={$t}{/if}" width="100%" height="600" frameborder="0" name="content"></iframe>

{/dynamic} 

<p>Your message will become a ticket in our system, and will be answered by one of the <a href="/team.php">team</a>.</p>
   
{include file="_std_end.tpl"}
