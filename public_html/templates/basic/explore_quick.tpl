{include file='_std_begin.tpl'}

{* Tab Navigation *}
<div class="tabHolder" style="max-width:940px">
    {foreach from=$links_main_tabs item=name key=link}
        {if $link|basename == 'viewgaz4.php'}
            Places in:
        {/if}
        {if $link|basename == $smarty.server.PHP_SELF|basename}
            {if !empty($smarty.get)}
                <a class=tabSelected href={$link}>{$name}</a>
            {else}
                <a class=tabSelected>{$name}</a>
            {/if}
        {else}
            <a class=tab href={$link}>{$name}</a>
        {/if}
    {/foreach}
</div>

{* Explore Images Title *}
<div class="interestBox">
    <h2>Explore Images</h2>
</div>

{* Main Filter Form *}
<form style="background-color:#eee;padding:10px">
    List: <select name="list" onchange="this.form.submit()">
        <option></option>
        {foreach from=$filter_lists item=data key=name}
            <option value="{$name}"{if $name == ($smarty.get.list|default:'')} selected{/if}>{$data.title}</option>
        {/foreach}
    </select>

    {if !empty($smarty.get.list) && !empty($filter_lists[$smarty.get.list])}
        {$selected_list_details = $filter_lists[$smarty.get.list]}
        {$selected_list_title = $selected_list_details.title}
        {$current_filter_value = $smarty.get[$smarty.get.list]|default:''}
        {$selected_list_column_name = $selected_list_details.column}

        {$selected_list_title}: <select name="{$smarty.get.list}" onchange="this.form.submit()">
            <option></option>
            {foreach from=$selected_list_items item=row}
                <option value="{$row[$selected_list_column_name]}"{if $row[$selected_list_column_name] == $current_filter_value} selected{/if}>{$row[$selected_list_column_name]}</option>
            {/foreach}
        </select>
    {/if}

    Country: <select name="country" onchange="this.form.submit()">
        <option></option>
        {foreach from=$country_list item=row}
            <option value="{$row.country}"{if $row.country == ($smarty.get.country|default:'')} selected{/if}>{$row.country}</option>
        {/foreach}
    </select>

    {if !empty($smarty.get.country)}
        County: <select name="county" onchange="this.form.submit()">
            <option></option>
            {foreach from=$county_list item=row}
                <option value="{$row.county}"{if $row.county == ($smarty.get.county|default:'')} selected{/if}>{$row.county}</option>
            {/foreach}
        </select>
    {/if}
</form>

{if $filters_active}
    {* Secondary Tab Navigation *}
    <div class="tabHolder" style="max-width:940px">
        {foreach from=$links_results_tabs item=name key=link}
            {if $link|basename == $smarty.server.PHP_SELF|basename && empty($smarty.get.sort) && $name == 'Preview'} {* Assuming 'Preview' is the default and has no sort param *}
                 <a class=tabSelected>{$name}</a>
            {elseif $smarty.get.sort|default:'' == $link|regex_replace:'/^.*\?sort=/':'' && $link|strstr:"sort="}
                <a class=tabSelected>{$name}</a>
            {else}
                <a class=tab href={$link}>{$name}</a>
            {/if}
        {/foreach}
    </div>

    {* Result Count Message and Thumbnail Display *}
    <div class=interestBox>{$image_count} of {$total_image_count|number_format:0} results...</div>
    <div style="columns: auto 213px; text-align:center;">
        {foreach from=$image_list item=image}
            <a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail($thumb_width, $thumb_height, false, true, 'loading=lazy src')}</a>
        {/foreach}
    </div>

    {* Footer Links and "Too many results?" Form *}
    {if $total_image_count > 10}
        <p>This is a preview of the full results. For more options use:
            {foreach from=$links_footer item=item}
                <a href="{$item.link}">{$item.name}</a>{if !$item@last} | {/if}
            {/foreach}
        </p>
        <hr>
        <h4>Too many results?</h4>
        <blockquote>
            <p>For more help, you might want to try The Advanced Browser which will allow you to add more filters to your search results. Or you could use the search page to be more specific (e.g. with dates or other keywords)</p>
        </blockquote>
        <form action="/search.php" method="get" style="background-color:#eee;padding:10px; max-width:450px">
            <input type="hidden" name="ref" value="{$smarty.server.REQUEST_URI|escape:'url'}">
            <input type="hidden" name="refTitle" value="Previous Page">
            <input type="text" name="q" value="" placeholder="Refine with keywords">
            <input type="submit" value="Search within results">
        </form>
    {/if}

    {* "Not enough results?" Section *}
    {if !empty($smarty.get.subjects)}
        <hr>
        <h4>Not enough results for {$smarty.get.subjects|escape:'html'}?</h4>
        <blockquote>
            <p>
                If you want to broaden your search, you could try the <a href="{$not_enough_results_link}">Advanced Browser</a> to remove some of your filters, or perhaps widen your search area.
                We estimate there are {$not_enough_results_estimate} images if you remove all filters other than Subject.
            </p>
        </blockquote>
    {/if}
{elseif !empty($selected_list_items) && !empty($smarty.get.list) && !empty($filter_lists[$smarty.get.list])}
    {* This block shows when a list type is selected from the form, but no actual filters are applied to show images *}
    {$selected_list_details = $filter_lists[$smarty.get.list]} {* Ensure details are available if not set by main form's specific dropdown logic *}

    {* Sorting Tab Navigation for Topic List *}
    <div class="tabHolder" style="max-width:940px">
        Sort by:
        {foreach from=$links_sort_topic_list item=name key=link_query}
            {assign var=is_selected value=false}
            {if $link_query == "" && ($smarty.server.QUERY_STRING|escape:'html' == "list={$smarty.get.list}" || $smarty.server.QUERY_STRING|escape:'html' == "list={$smarty.get.list}&alpha=0")}
                {$is_selected = true}
            {elseif $link_query != "" && $smarty.server.QUERY_STRING|escape:'html'|strstr:$link_query}
                {$is_selected = true}
            {/if}

            {if $is_selected}
                <a class=tabSelected>{$name}</a>
            {else}
                <a class=tab href="?list={$smarty.get.list|escape:'url'}{if $link_query}&{$link_query}{/if}">{$name}</a>
            {/if}
        {/foreach}
    </div>

    {* List Title *}
    <div class="interestBox">{$selected_list_details.title}</div>

    <div {if !empty($smarty.get.alpha)}style="{$list_display_style|default:'columns: auto 15em; column-gap: 2em;'}"{/if}> {* Apply multi-column style only for alpha list *}
    {if !empty($smarty.get.alpha)}
        {* Alphabetical List Display *}
        {assign var=current_letter value=''}
        {foreach from=$selected_list_items item=row}
            {$item_value = $row[$selected_list_details.column]}
            {$first_letter = $item_value|truncate:1:''|lower}
            {if $first_letter != $current_letter}
                {if $current_letter != ''}</ol></div>{/if} {* Close previous letter's list and div *}
                {assign var=current_letter value=$first_letter}
                <div style="break-inside: avoid-column; page-break-inside: avoid; -webkit-column-break-inside: avoid;"> {* Group letter and its list for column flow *}
                <h3>{$current_letter|upper}</h3>
                <ol style="padding-left:2em; margin-top:0;">
            {/if}
            <li>
                {if !empty($official_items[($item_value|lower)])}<b>{/if}
                <a href="{linktoself name=$smarty.get.list value=$item_value}">{$item_value|escape:'html'} ({$row.c|default:0})</a>
                {if !empty($official_items[($item_value|lower)])}</b>{/if}
            </li>
        {/foreach}
        {if $current_letter != ''}</ol></div>{/if} {* Close the last letter's list and div *}
    {else}
        {* Simple List Display (Ordered by Count) *}
        <ol style="padding-left:5em">
            {foreach from=$selected_list_items item=row}
                {$item_value = $row[$selected_list_details.column]}
                <li>
                    {if !empty($official_items[($item_value|lower)])}<b>{/if}
                    <a href="{linktoself name=$smarty.get.list value=$item_value}">{$item_value|escape:'html'} ({$row.c|default:0})</a>
                    {if !empty($official_items[($item_value|lower)])}</b>{/if}
                </li>
            {/foreach}
        </ol>
    {/if}
    </div>
{/if}

{include file='_std_end.tpl'}
