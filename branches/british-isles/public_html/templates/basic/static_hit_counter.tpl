{assign var="page_title" value="Page View Counter"}

{include file="_std_begin.tpl"}

<h2>About the page view counter</h2>

<p>We have recently added a view counter to the main photo page. This can be found at the bottom right of each photo page.</p>

<p>This is a measure of the number of times the page has been viewed. <b>It is almost certainly an under estimate</b> as it doesn't truly represent how many times the photo itself has been viewed.  It only counts the number of times the page itself has been viewed. The count is historic and records the number of times that the page has been viewed over the last four or so years (but unfortunately some data was lost from early in the project).</p>

<p>A typical photo is visible in many more places, such as Wikipedia and other websites that reuse our photos, often in bulk. Also Geograph's own site features such as the search engine {if $enable_forums} and forum{/if} are not taken into account.</p>

<p>At some point we hope to expand the system to provide more detail, such as traffic sources, and also to make an estimation of actual visitors rather than simply hits.  At the moment the same person may view the image multiple times, each of which is counted.</p>

<p>Contributors can get hit counts for all their own images, via the CSV download accessible at the bottom of the profile page.</p>

{include file="_std_end.tpl"}
