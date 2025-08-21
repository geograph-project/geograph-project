{include file="_std_begin.tpl"}

{dynamic}
{if $tagInfo}
    <h2>Please rate the visual quality of the search results for the primary subject "{$tagInfo.tag|escape}"</h2>
    <div style="margin: 10px 0; max-width:60em">
	<p>Your rating should focus on how well the images visually represent the search term, even if they aren't a perfect, literal match. We are looking for plausible results.</p>

	<p><b>Low score</b>: The images are visually unrelated (e.g., getting pictures of cityscapes for the term "rolling hills").

	<p><b>High score</b>: The images visually make sense, even if they aren't perfectly accurate (e.g., getting pictures of green fields or distant mountains for "rolling hills").
	Similally, for the term "school", a picture of a building that is now a "former school" is still a great visual match.

	<p><i>Some tags can have multiple meanings. Please rate based on the common interpretation within the context of British landscape and Geograph's content. For example, the tag 'lock' is typically used for canal locks, not door locks. A result of door locks should therefore be rated with a low score. Base this on your own understanding, don't need to check how the tag is actully used.</i>

	<p>We are <b>not</b> rating for variety or diversity of results. The images can all look similar as long as they represent the tag well.

        <ul>
            <li><b>1 Star</b> - All images are irrelevant (treat like zero)</li>
            <li><b>2 Stars</b> - Some images are vaguely related, but many are off-topic</li>
            <li><b>3 Stars</b> - A mix of relevant and irrelevant images</li>
            <li><b>4 Stars</b> - Mostly relevant images</li>
            <li><b>5 Stars</b> - All images are a strong, plausible match</li>
        </ul>
    </div>

    <div class="interestBox">
        <span id="votediv{$tagInfo.tag_id}">Vote for "<b>{$tagInfo.tag|escape}</b>": {votestars type='tagzeroclip' id=$tagInfo.tag_id nohelp=1} - {$notes}</span>
        <a href="?" style="margin-left: 20px; padding: 5px 10px; background-color: #ccc; text-decoration: none; border: 1px solid #ccc; border-radius: 5px;">Next</a>
    </div>

    <div id='thumbnails'> Loading - please wait! </div>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
    <script>
    {literal}
    $(function() {
        $('#thumbnails').load('/stuff/vision-clip-query.php?inner=1&query={/literal}{$tagInfo.tag|rawurlencode}{literal}');
    });
    </script>
	<style>
		div.interestBox img {
			padding-left:4px;
			padding-right:4px;
		}
	</style>
    {/literal}
{else}
    <p>Could not find a tag to rate.</p>
{/if}
{/dynamic}

{include file="_std_end.tpl"}
