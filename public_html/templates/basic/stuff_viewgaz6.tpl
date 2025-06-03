{include file='_std_begin.tpl'}

{* Top Navigation Tabs *}
<div class="tabHolder" style="max-width:940px">Places in:
    {foreach from=$nav_links key=link item=name}
        {if $link == $smarty.server.PHP_SELF|basename}
            <a class=tabSelected>{$name}</a>
        {else}
            <a class=tab href="{$link}">{$name}</a>
        {/if}
    {/foreach}
</div>

{* Title and Notes *}
<div class="interestBox">
    <h2>{$page_title|escape:'html'}</h2>
</div>
{if !empty($page_note)}
    <p>{$page_note}</p>
{/if}

{* Places List Display *}
<div style="columns: auto 24em; column-gap: 2em;">
    {assign var="last_postcode" value=null}
    {assign var="first_group" value=true}
    {foreach from=$places_data item=place}
        {if !empty($place.postcode) && $last_postcode != $place.postcode}
            {if $last_postcode !== null}
                </ul></div>
            {/if}
            {$last_postcode = $place.postcode}
            {* For the very first group, don't add top margin if we want it flush *}
            <div style="break-inside: avoid-column; page-break-inside: avoid; -webkit-column-break-inside: avoid; {if $first_group}{assign var="first_group" value=false}{else}margin-top: 1em;{/if}">
            <h4>{$place.postcode|escape:'html'}</h4>
            <ul style="margin-top: 0; padding-left: 1.5em;">
        {elseif empty($place.postcode) && $last_postcode !== 'NONE'}
             {if $last_postcode !== null}
                </ul></div>
            {/if}
            {$last_postcode = 'NONE'} {* Special key for places without postcode *}
            <div style="break-inside: avoid-column; page-break-inside: avoid; -webkit-column-break-inside: avoid; {if $first_group}{assign var="first_group" value=false}{else}margin-top: 1em;{/if}">
            {if $page_title|strstr:"Isle of Man"} {* Specific condition for IoM title based on original PHP logic for no postcode header *}
                 <h4>Other Places</h4>
            {else}
                 <h4>{$page_title|escape:'html'}</h4>
            {/if}
            <ul style="margin-top: 0; padding-left: 1.5em;">
        {/if}

        <li>
            {if $place.place_rank < 19}<b>{/if}
            <a href="{$place.url_near}">{$place.name_latin1|escape:'html'}</a>
            {if $place.place_rank < 19}</b>{/if}

            {if !empty($place.type)}
                <i style=color:gray> ({$place.type|escape:'html'})</i>
            {/if}

            {if !empty($place.images)}
                ({$place.images|number_format:0} images
                {if !empty($place.recent) && $place.recent > '1000'}
                    , last in {$place.recent|substr:0:4}
                {/if}
                )
            {else}
                <a href="/mapper/combined.php#14/{$place.lat}/{$place.lon}" title="View on map">&#128205;</a>
            {/if}
        </li>
    {/foreach}
    {if $last_postcode !== null} {* Ensure the last group is closed *}
        </ul></div>
    {/if}
</div>

{* Alphabetical Links Footer *}
<br style="clear:both;"><hr> {* Added clear:both for safety with columns *}
If you don't see the place you are looking for, you can view a list of smaller places, but you will need the first letter of the name:
<div style="text-align:center; margin-top:0.5em; margin-bottom:0.5em;"> {* Centering the alpha links *}
{foreach from=$alphabet_range item=letter}
    &nbsp; <a href="{$alpha_footer_url_base}&amp;alpha={$letter}">{$letter}</a>
{/foreach}
</div>

{include file='_std_end.tpl'}
