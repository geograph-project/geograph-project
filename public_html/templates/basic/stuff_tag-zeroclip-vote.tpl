{include file="_std_begin.tpl"}

{if $tagInfo}
    <h2>Rate images for the tag: "{$tagInfo.tag|escape}"</h2>
    <div id='votestars_container'>
        {votestars type='tagzeroclip' id=$tagInfo.tag_id}
        <a href="?" style="margin-left: 20px; padding: 5px 10px; background-color: #ddd; text-decoration: none; border: 1px solid #ccc; border-radius: 5px;">Next</a>
    </div>
    <div style="margin: 10px 0;">
        <p><strong>How to vote:</strong></p>
        <ul>
            <li><b>1 Star</b> - not even remotely related</li>
            <li><b>2 Stars</b> - some images getting close</li>
            <li><b>3 Stars</b> - some images might be related, but not many</li>
            <li><b>4 Stars</b> - mostly good images for the tag, even if not quite all</li>
            <li><b>5 Stars</b> - very good results, all images are a strong match</li>
        </ul>
    </div>
    <div id='thumbnails'></div>

    <script>
    $(function() {
        $('#thumbnails').load('/stuff/vision-clip-query.php?inner=1&query={$tagInfo.tag|rawurlencode}');
    });
    </script>
{else}
    <p>Could not find a tag to rate.</p>
{/if}

{include file="_std_end.tpl"}
