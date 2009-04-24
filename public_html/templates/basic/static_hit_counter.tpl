{assign var="page_title" value="Page View Counter"}

{include file="_std_begin.tpl"}

<h2>About the page view counter</h2>

<p>We have recently added a view counter to the main photo page - bottom right of the page. The count is historic and includes views made over the last 4 years (but unfortately some data was lost).</p>

<p>This is a measure of the number of times the page has been viewed. ,b>It's almost certainly an under estimate</b>, and doesn't truely represent how much the photo itself has been viewed, as only views to the page itself. are counted.

<p>A typical photo is visible in many more places, such as wikipedia and other websites that reuse our photos, often in bulk. Also site features such as the search engine {if $enable_forums} and forum{/if} are not taken into account.</p>

<p>At some point we hope to expand the system to provide more detail - such as traffic sources, and also to make an estimation of actual visitors rather than simply hits (the same person may view the image multiple times - counting each time)</p>

<p>Contributors can get hit counts for all their images, via the CSV download accessible at the bottom of the profile page.</p>

{include file="_std_end.tpl"}
