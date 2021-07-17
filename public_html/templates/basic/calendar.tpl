{include file="_std_begin.tpl"}

<h2>Your personalised Geograph Calendar for 2022</h2>

<p>
Many members will have used one of the
commercial firms which allow them to
create calendars featuring their own
photographs. This year Geograph is
offering members the same opportunity,
featuring pictures that they have submitted
to Geograph.</p>

<p>The calendar will be priced very
competitively with the commercial offers,
with the bonus that a significant
proportion of the price will go to swell
Geograph&#39;s coffers. We hope that as many
members as possible will take advantage of
this offer.

<p>The calendar will be A3 hanging format, with a display
for each month consisting of an A4 landscape photo
page and an A4 landscape calendar page with ample
space to write in your appointments on a day-by-day
basis. There will be a separate photograph for each
month, presented in a similar way to the normal
Geograph photo page. (See right).

<p>We are fortunate that one of our members has had contact in the past with a local print
firm that has a calendar speciality. Further discussions with the printer mean that we can
benefit from being able to place a single bulk order even though each member&#39;s order may
only be for a small number, and each member will be providing their own set of pictures.
The bulk order does not depend on any minimum quantity of calendars, which means that
there is no financial risk to Geograph.
Geograph is grateful to the same member for making the initial proposal, including
producing the layout design, and for volunteering to do the setting out of the individual
calendars, using a professional desk-top publishing program.

<hr>

<b><a href="start.php">Create a new Calendar Order &gt; &gt;</a></b>

{dynamic}
{if $list}
	<h3>Current Orders</h3>
	<table>
	{foreach from=$list key=index item=calendar}
		<tr>
			<td>{$calendar.title|default:'untitled calendar'}</td>
			<td>{$calendar.status} {if $calendar.quantity}x{$calendar.quantity}{/if}
			<td>{if $calendar.status != 'processed'}<a href="edit.php?id={$calendar.calendar_id}">Review/Edit</a>{/if}
			<td>{if $calendar.status != 'ordered'}<a href="order.php?id={$calendar.calendar_id}"><b>Order</b></a>{/if}
			<td>{if $calendar.status == 'new'}<a href="?delete={$calendar.calendar_id}" style="color:red">Delete</a>{/if}
		</tr>
	{/foreach}
	</table>	

	<p>You will be able to edit the order right up to order has been processed (even after payment) processing however may happen at any time after payment

{/if}
{/dynamic}


{include file="_std_end.tpl"}


