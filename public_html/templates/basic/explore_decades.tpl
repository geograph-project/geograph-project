{include file='_std_begin.tpl'}

{* Top Navigation Tabs *}
<div class="tabHolder" style="max-width:940px">
    {foreach from=$links_main_tabs key=link item=name}
        {if $link|basename == 'viewgaz4.php'} Places in: {/if}
        {if $link|basename == $smarty.server.PHP_SELF|basename}
            {if !empty($smarty.get)}
                <a class=tabSelected href="{$link}">{$name}</a>
            {else}
                <a class=tabSelected>{$name}</a>
            {/if}
        {else}
            <a class=tab href="{$link}">{$name}</a>
        {/if}
    {/foreach}
</div>

{* Title *}
<div class="interestBox">
    <h2>Explore Images over time</h2>
</div>

{* Main Data Table *}
{if !empty($country_headers) && !empty($decade_matrix)}
    <table cellpadding="6" style="margin-left:auto; margin-right:auto;"> {* Added auto margins for centering as common for such tables *}
        <tr>
            <th>&nbsp;</th> {* Empty cell for the top-left corner *}
            {foreach from=$country_headers item=country_name}
                <th>{$country_name|escape:'html'}</th>
            {/foreach}
        </tr>

        {foreach from=$decade_matrix key=decade item=country_data}
            <tr>
                <th>{$decade|escape:'html'}</th>
                {foreach from=$country_headers item=country_name}
                    <td align="center">
                        {if !empty($country_data[$country_name])}
                            {$image_info = $country_data[$country_name]} {* Assuming $image_info contains both 'image_obj' and 'count' *}
                            {$image_obj = $image_info.image_obj}
                            {$image_count = $image_info.count}

                            {$decade_slug = $decade|replace:'0s':'tt'}
                            {$url = "/browser/#!/decade+"|cat:($decade_slug|urlencode)|cat:"/country+"|cat:($country_name|urlencode)|cat:"/display=date_slider"}

                            {$title_text = ($image_obj->grid_reference)|cat:" : "|cat:($image_obj->title|escape:'html')|cat:" by "|cat:($image_obj->realname|escape:'html')|cat:" [taken "|cat:($image_obj->takenyear)|cat:"]"}

                            <a title="{$title_text}" href="{$url}">
                                {$image_obj->getThumbnail($thumb_width, $thumb_height, false, true, 'loading=lazy src')}
                            </a>
                            <br>
                            <a href="{$url}">{$image_count|number_format:0} image{if $image_count != 1}s{/if}</a>
                        {else}
                            &nbsp; {* Non-breaking space for empty cells for consistent alignment *}
                        {/if}
                    </td>
                {/foreach}
            </tr>
        {/foreach}

        <tr>
            <td>&nbsp;</td> {* Empty cell for the bottom-left corner *}
            {foreach from=$country_headers item=country_name}
                <th>{$country_name|escape:'html'}</th>
            {/foreach}
        </tr>
    </table>
{else}
    <p>No data available to display.</p>
{/if}

{include file='_std_end.tpl'}
