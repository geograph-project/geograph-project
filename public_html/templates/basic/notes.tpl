{dynamic}
{assign var="page_title" value="View Image"}
{include file="_std_begin.tpl"}

 <script type="text/javascript" src="/notes/fnclientlib/js/fnclient.js"></script>
 <link rel="stylesheet" type="text/css" href="/notes/fnclientlib/styles/fnclient.css" />

<a name="top"></a>

<div style="float:left; position:relative; padding-right:10px;"><h2><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img
alt="Creative Commons Licence [Some Rights Reserved]" src="http://creativecommons.org/images/public/somerights20.gif" align="top" /></a> <a href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : </h2></div>

<h2 style="margin-bottom:0px" class="nowrap"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a></h2>
<div>by <a title="View profile" href="http://{$http_host}{$image->profile_link}">{$image->realname|escape:'html'}</a></div>

<br style="clear:both;"/>

<div class="interestBox">
'Edit Note' DOESN'T actually save changes permanently, it should be possible to fix later. So for now delete, then re-add the note.<br>
<b><u>For the moment</u>, it's a free for all, anyone can add and delete notes against <u>anyones images</u>!</b>
</div>

<div class="{if $image->isLandscape()}photolandscape{else}photoportrait{/if}" style="margin-left:0;text-align:left">
  <div>{$image->getFull()|replace:'<img':'<img class="fn-image"'}</div><br/><br/>

  {if $image->comment}
  <div class="caption" style="border:1px dotted lightgrey;">{$image->comment|escape:'html'|geographlinks}</div>
  {/if}
</div>



<br/><br/>
<a href="/photo/{$image->gridimage_id}">Return to photo page</a>

{/dynamic}
{include file="_std_end.tpl"}
