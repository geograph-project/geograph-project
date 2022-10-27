{include file="_std_begin.tpl"}
<!--INFOLINKS_OFF-->

<h2><a title="Grid Reference {$image->grid_reference}{if $square_count gt 1} :: {$square_count} images{/if}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : {$image->bigtitle|escape:'html'}</h2>
{if $place.distance}
	{place place=$place h3=true takenago=$takenago}
{/if}

{if $image->moderation_status eq 'rejected'}
	<div class="interestBox" style="background-color:pink; color:black; border:2px solid red; padding:10px;">
		<h3 style="color:black"><img src="{$static_host}/templates/basic/img/icon_alert.gif" alt="Modify" width="50" height="44" align="left" style="margin-right:10px"/> Rejected</h3>

		<p>This photograph has been rejected by the site moderators, and is only viewable by you.</p>

		<p>You can find any messages related to this image on the <a title="Edit title and comments" href="/editimage.php?id={$image->gridimage_id}">edit page</a>, where you can reply or raise new concerns in the "Please tell us what is wrong..." box. These will be communicated to site moderators. You may also like to read this general article on common <a href="/article/Reasons-for-rejection">reasons for rejection</a>.
	</div>
	<br/>
{/if}

<!-- ----------------------------------------------------- -->

<div>
	{if $image->original_width}
		<div><a href="/more.php?id={$image->gridimage_id}">More sizes</a></div>
	{elseif $user->user_id eq $image->user_id}
		<div><a href="/resubmit.php?id={$image->gridimage_id}">Upload a larger version</a></div>
	{/if}

	<div class="shadow shadow_large" id="mainphoto">{$image->getFull(true,true)}</div>

	<div><strong>{$image->title|escape:'html'}</strong></div>

	{if $image->comment}
		<div>{$image->comment|escape:'html'|nl2br|geographlinks:$expand}</div>
	{/if}
</div>

<br>

<!-- Creative Commons Licence -->
<div class="interestBox ccmessage"><a href="http://creativecommons.org/licenses/by-sa/2.0/"><img
alt="Creative Commons Licence [Some Rights Reserved]" src="{$static_host}/img/somerights20.gif" /></a> &nbsp; &copy; Copyright <a title="View profile" href="{$image->profile_link}" xmlns:cc="http://creativecommons.org/ns#" property="cc:attributionName" rel="cc:attributionURL dct:creator">{$image->realname|escape:'html'}</a> and
licensed for <a href="/reuse.php?id={$image->gridimage_id}">reuse</a> under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap" about="{$imageurl}" title="Creative Commons Attribution-Share Alike 2.0 Licence">Creative Commons Licence</a>.</div>
<!-- /Creative Commons Licence -->

<!-- ----------------------------------------------------- -->

{literal}
	<script type="application/ld+json">
	{
	      "@context": "https://schema.org",
	      "@type": "BreadcrumbList",
	      "itemListElement": [{
	        "@type": "ListItem",
	        "position": 1,
	        "name": "Photos",{/literal}
	        "item": "{$self_host}/" {literal}
	      },{
	        "@type": "ListItem",
	        "position": 2,{/literal}
	        "name": {"by `$image->realname`"|latin1_to_utf8|json_encode},
	        "item": "{$self_host}{$image->profile_link|escape:'javascript'}" {literal}
	      },{
	        "@type": "ListItem",
	        "position": 3,{/literal}
	        "name": {$image->title|latin1_to_utf8|json_encode} {literal}
	      }]
	}
	</script>
	<script type="application/ld+json">
	{
	      "@context": "https://schema.org/",
	      "@type": "ImageObject",{/literal}
	      "name": {$image->title|latin1_to_utf8|json_encode},
	      "contentUrl": {$imageurl|json_encode},
	      "license": "http://creativecommons.org/licenses/by-sa/2.0/",
	      "acquireLicensePage": "{$self_host}/reuse.php?id={$image->gridimage_id}",
	      "copyrightNotice": {$image->realname|latin1_to_utf8|json_encode}, {literal}
	      "creator": {{/literal}
	        "@type": "Person",
	        "name": {$image->realname|latin1_to_utf8|json_encode},
                "url": "{$self_host}{$image->profile_link|escape:'javascript'}" {literal}
	      },
	      "isFamilyFriendly": true,
	      "representativeOfPage": true
	}
	</script>
{/literal}

<!--INFOLINKS_ON-->
{include file="_std_end.tpl"}
