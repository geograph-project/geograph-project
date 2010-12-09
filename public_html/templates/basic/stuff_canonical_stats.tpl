{assign var="page_title" value="Canonical Categories"}
{include file="_std_begin.tpl"}

<div class="interestBox" style="background-color:pink">
        The Canonical Category project is an highly experimental attempt to create a simplified category listing. The project is ongoing.
</div>


<h2><a href="?">Canonical Category Mapping</a> :: Statistics</h2>

<h3>Categories</h3>

<blockquote><p><b>{$normal|thousends}</b> Normal categories, of which <b>{$cats|thousends}</b> have had suggestions made.</p></blockquote>

<h3>Suggestions</h3>

<blockquote><p><b>{$suggestions|thousends}</b> suggestions, made by <b>{$users}</b> users, have produced <b>{$canons|thousends}</b> preliminary canonical categories.</p></blockquote>

<h3>Canonical Categories</h3>

<p><small>A canonical category is counted as confirmed when suggested by at least three different users.</small></p>

<blockquote><p><b>{$final|thousends}</b> categories have confirmed canonical category, producing <b>{$canons_final|thousends}</b> different canonical categories.</p></blockquote>



<br/><br/>

<a href="?">Go Back</a>

<br/><br/>

{include file="_std_end.tpl"}
