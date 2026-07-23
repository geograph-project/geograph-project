{assign var="page_title" value="Didnt know possible"}
{include file="_std_begin.tpl"}

<h2>I didn't know that possible!</h2>

<p>Click an item to expand...</p>

<style>{literal}

h3 {
	background-color:#eee;
	padding:10px;
	border-radius:10px;
}
.faq-view {
    padding: 10px;
}
.faq-view h2 {
    text-align:center;
}
.faq-view h4 {
    margin-top:1em;
}

.tap-prompt {
    text-align:center;
}
div.faq-item {
        padding: 15px 0;
}
button.faq-button {
        background: none; border: none; width: 100%; text-align: left; font-size: 1rem; cursor: pointer; padding: 0;
}

button.faq-button:has(+ .faq-content:not(.hidden)) {
    border-bottom: 1px dashed silver;
}

div.faq-content {
        margin-bottom: 12px; margin-left:2px; border-left:1px dashed silver; border-bottom:1px dashed silver; padding:6px; line-height: 1.5; 
	background-color:#eee;
}

div.faq-content ul, div.faq-content ol {
        padding-left: 16px;
}

.hidden {
	display:none;
}

</style>{/literal}

<div style="max-width:60em">
{assign var="last" value=""}
{foreach from=$items key=key item=item}
	{if $last != $item.category}
		<h3>{$item.category|escape:'html'}</h3>
		{assign var="last" value=$item.category}
	{/if}
            <div class="faq-item">
                <button class="faq-button" onclick="this.nextElementSibling.classList.toggle('hidden')">
                    &middot; {$item.title}
                </button>
                <div class="faq-content hidden">
                    {$item.content}
                </div>
            </div>
{/foreach}
</div>

{include file="_std_end.tpl"}
