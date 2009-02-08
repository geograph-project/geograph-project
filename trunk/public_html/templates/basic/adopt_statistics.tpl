{assign var="page_title" value="Adopt Statistics"}
{include file="_std_begin.tpl"}

<h2><a href="/adopt/">Hectad Adoptions</a> - Statistics</h2>


<h3>New</h3>

<p>There is <b class="nowrap">{$stats.new.count|thousends} requests</b> on the waiting list from <b class="nowrap">{$stats.new.users|thousends} users</b>, interested in <b class="nowrap">{$stats.new.hectads|thousends} different hectads</b>. {if $stats.new.hectads eq $stats.new.count}There are no contentions which is good.{/if}</p>


<h3>Pending</h3>

<p>There is <b class="nowrap">{$stats.offered.count|thousends} offers</b> to <b class="nowrap">{$stats.offered.users|thousends} users</b>, for <b class="nowrap">{$stats.offered.hectads|thousends} different hectads</b>.</p>


<h3>Accepted</h3>

<p>We currently have <b class="nowrap">{$stats.accepted.count|thousends} adopted hectads</b> by <b class="nowrap">{$stats.accepted.users|thousends} users</b>.</p>


<h3>Squares</h3>

<p><b class="nowrap">{$squares.squares|thousends} grid squares</b> have been assigned, in <b class="nowrap">{$squares.hectads|thousends} hectads</b>.</p>


<br style="clear:both"/>


{include file="_std_end.tpl"}
