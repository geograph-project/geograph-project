{assign var="page_title" value="Adopt Statistics"}
{include file="_std_begin.tpl"}

<h2><a href="/adopt/">Hectad Adoptions</a> - Statistics</h2>



<h3>pending</h3>

<p>We currently have <b class="nowrap">{$stats.new_count|thousends} requests</b> from <b class="nowrap">{$stats.new_users|thousends} users</b>, interested in <b class="nowrap">{$stats.new_hectads|thousends} different hectads</b>. {if $stats.new_hectads eq $stats.new_count}There are no contentions which is good.{/if}</p>





<br style="clear:both"/>


{include file="_std_end.tpl"}
