{include file='_std_begin.tpl'}

{* Top Navigation Tabs *}
<div class="tabHolder" style="max-width:940px">Places in:
    {foreach from=$nav_links key=link_url item=link_name}
        {if $link_url == $smarty.server.PHP_SELF|basename}
            {if !empty($smarty.get)} {* If there are GET params, make current tab a link to itself without params to reset *}
                <a class=tabSelected href="{$link_url}">{$link_name}</a>
            {else}
                <a class=tabSelected>{$link_name}</a>
            {/if}
        {else}
            <a class=tab href="{$link_url}">{$link_name}</a>
        {/if}
    {/foreach}
</div>

{* Title and Name Switcher Area *}
<div class="interestBox">
    <h2>{$page_title|escape:'html'}</h2>
    {if !empty($show_name_switcher) && !empty($name_switcher_options)}
        Names: &middot;
        {foreach from=$name_switcher_options key=key item=option_name}
            {if $current_name_preference == $key}
                <b>{$option_name|escape:'html'}</b>
            {else}
                <a href="{$name_switcher_base_url}&amp;name={$key|escape:'url'}">{$option_name|escape:'html'}</a>
            {/if}
            {if !$option_name@last}&middot;{/if}
        {/foreach}
    {/if}
</div>

{* Main Content based on display_mode *}
{if $display_mode == 'list_counties'}
    {* Mode 1: List Counties *}
    <p>First click a County, note the name in brackets the Island name (where we have identified non-mainland places)</p>
    <div style="columns: auto 36em; column-gap: 2em;">
        {assign var="current_country" value=null}
        {foreach from=$counties_list item=county_item}
            {if $current_country != $county_item.country}
                {if $current_country !== null}</ul>{/if}
                <h4 style="margin-bottom: 0.2em;">{$county_item.country|escape:'html'}</h4>
                <ul style="margin-top:0; padding-left: 1.5em;">
                {$current_country = $county_item.country}
            {/if}
            <li>
                <b><a href="{$county_item.url|escape:'url'}">{$county_item.display_name|escape:'html'}</a></b>
                ({$county_item.places|number_format:0} places
                {if isset($county_item.percent_photographed) && $county_item.percent_photographed < 100 && $county_item.percent_photographed >= 0}
                    , {$county_item.percent_photographed|string_format:"%.1f"}% photographed
                {/if}
                {if !empty($county_item.image_count)}
                    , around {$county_item.image_count|number_format:0} images
                {/if}
                )
            </li>
        {/foreach}
        {if $current_country !== null}</ul>{/if}
    </div>
    <br style="clear:both;"><hr>
    If you don't know the county, try the first letter of the name:
    <div style="text-align:center; margin-top:0.5em; margin-bottom:0.5em;">
    {foreach from=$alphabet_range item=letter}
        &nbsp; <a href="?alpha={$letter|escape:'url'}">{$letter}</a>
    {/foreach}
    </div>

{elseif $display_mode == 'alpha_filter'}
    {* Mode 2: List Places by Initial (alpha mode) *}
    <div style="columns: auto 24em; column-gap: 2em;">
        {assign var="current_county_group" value=null}
        {foreach from=$places_list item=place}
            {assign var="group_key" value=($place.county|default:'Unknown County')|cat:', '|cat:($place.country|default:'Unknown Country')}
            {if $current_county_group != $group_key}
                {if $current_county_group !== null}</ul></div>{/if}
                {$current_county_group = $group_key}
                <div style="break-inside: avoid-column; page-break-inside: avoid; -webkit-column-break-inside: avoid; margin-top:0.5em;">
                <h4 style="margin-bottom: 0.2em;">{$place.county|default:'Unknown County'|escape:'html'}, {$place.country|default:'Unknown Country'|escape:'html'}</h4>
                <ul style="margin-top:0; padding-left: 1.5em;">
            {/if}
            <li>
                {if !empty($place.is_major_town)}<b>{/if}
                <a href="{$place.url|escape:'url'}">{$place.display_name|escape:'html'}</a>
                {if !empty($place.is_major_town)}</b>{/if}
                {if !empty($place.image_count)}
                    ({$place.image_count|number_format:0} images
                    {if !empty($place.recent_year) && $place.recent_year > '1000'}
                        , last in {$place.recent_year}
                    {/if}
                    )
                {/if}
            </li>
        {/foreach}
        {if $current_county_group !== null}</ul></div>{/if}
    </div>

{elseif $display_mode == 'county_filter'}
    {* Mode 3: List Places in County *}
    {if !empty($county_note)}<p>{$county_note|escape:'html'}</p>{/if}
    <div style="columns: auto 28em; column-gap: 2em;">
        {assign var="current_letter_group" value=null}
        {foreach from=$places_list item=place}
            {assign var="first_letter" value=$place.display_name|truncate:1:''|upper}
            {if $current_letter_group != $first_letter}
                 {if $current_letter_group !== null}</ul>{/if}
                 {* H4 was commented out in original PHP, preserving that for now: <h4 style="margin-bottom: 0.2em;">{$first_letter}</h4> *}
                 <ul style="margin-top:0; padding-left: 1.5em; {if $current_letter_group === null && $first_letter != 'A'} margin-top:0; {else} {* attempt to align lists if no H4 *}{/if}">
                 {$current_letter_group = $first_letter}
            {/if}
            <li>
                {if !empty($place.is_major_town)}<b>{/if}
                <a href="{$place.url|escape:'url'}">{$place.display_name|escape:'html'}</a>
                {if !empty($place.is_major_town)}</b>{/if}
                {if !empty($place.image_count)}
                    ({$place.image_count|number_format:0} images
                    {if !empty($place.recent_year) && $place.recent_year > '1000'}
                        , last in {$place.recent_year}
                    {/if}
                    )
                {/if}
            </li>
        {/foreach}
        {if $current_letter_group !== null}</ul>{/if}
    </div>
{/if}

{* NI Note *}
{if !empty($show_ni_note)}
    <hr>Note: for Northern Ireland, using a list of places on 250k mapping, so may miss some smaller places. Might still find them via <a href="search.php">Search</a>, or <a href="/mapper/combined.php">Zoomable Map</a>
{/if}

{include file='_std_end.tpl'}
