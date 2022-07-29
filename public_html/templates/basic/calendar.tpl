{assign var="page_title" value="Geograph Calendar `$year`"}
{include file="_std_begin.tpl"}

<div style="float:right;width:360px;margin-right:20px;text-align:center">
	<p>{newwin href="/calendar/help.php" text="Open Help Page"} (in new window)</p>
	<p>{newwin href="/calendar/tips.php" text="Tips Page"} (in new window)</p>
</div>

<h2>Your personalised Geograph Calendar for {$year}</h2>

<div style="clear:both;float:right;width:360px;margin-right:20px;line-height:0.6;background-color:#e4e4fc;padding:10px;">

	<img src="https://media.geograph.org.uk/files/c81e728d9d4c2f636f067f89cc14862c/Cover_draftB.jpg" width=360><br><br><br>
	<img src="https://media.geograph.org.uk/files/c81e728d9d4c2f636f067f89cc14862c/photopage2B_1_.jpg" width=360>
	<img src="https://media.geograph.org.uk/files/c81e728d9d4c2f636f067f89cc14862c/month_1_.jpg" width=360 ><br><br>
</div>


<p>The calendar will be in the familiar A3 wall-hanging format, wire-bound across the middle, with a picture above a one month calendar. 
This will have space for appointments, events, etc. to be inserted on a day by day basis.

<p>There will be a separate picture for each month plus one picture for the front cover.

<p>All pictures must be ones currently appearing on Geograph and submitted by the person with the ID used when placing the order. There 
are no restrictions on when the image was taken or submitted. 
<a href="#" onclick="show_tree('spec');" id="hidespec">Full details of picture specification can be found here.</a>

<blockquote id="showspec" style="display:none">
	<h3>Picture Specification </h3>

	<img src="https://media.geograph.org.uk/files/c81e728d9d4c2f636f067f89cc14862c/photopage.jpg" width=640 height=452 align=right>


	<p>As images on Geograph are not constrained to particular proportions, the calendar will display each monthly image in the 
	same proportions as it appears on Geograph, on a white background. Image size will be adjusted, maintaining the original 
	proportions, to leave a narrow border top and bottom or left and right, as appropriate (see right). Portrait and square format 
	images will be proportionally smaller than landscape images. The submitted image will not be cropped.

	<p>The creation process will select the highest resolution version currently available on Geograph. The dimensions will be 
	shown on the 'Create Calendar' page, with a nominal dpi figure for the re-sized image. If this shows as 100 dpi or less, picture 
	print quality will be very poor. For a good quality picture you should aim for at least 200, and preferably 300dpi*. To achieve this 
	you can upload a high resolution version, which must be from the same original. (Tweaks for brightness, colour, etc and minor 
	cropping are acceptable.) This uploaded version will NOT be added to the submission on Geograph, and therefore will not be subject 
	to a Creative Commoms licence.

	<h3>Cover picture</h3>

	<p>The cover picture must be landscape format and will be printed without borders, so will be cropped.  <b>The title panel will be in the 
	position shown in the draft so your choice of image should take this into account</b>. A high resolution image is recommended. 

	<h3>Picture titles, etc</h3>

	<p>The ordering system will automatically pick up the relevant grid square, location, title, photographer name and date taken. The current title will be 
	shown as a default but you may change the title text within the specified limit of 120 characters.
  The location will also default to the image page display but can also be edited. (The gazetteer sometimes selects an inappropriate default!)

	<p>*Approx 3500 px wide or 2500 px high
  
</blockquote>

<p>As you may be submitting images of a higher resolution than those available on the Geograph 
website, the calendar will not be produced under a Creative Commons Licence, and your normal copyright will be protected.

<p>There is no restriction on how many different versions of the calendar one person may order, so selections may be tailored to suit 
intended recipients.

<p>The name that will appear on the front of the calendar will be the normal photographer name for the id used. If some of your 
chosen pictures were submitted under a different name (e.g. a relative), this can be included on the cover, provided it does not 
overrun the panel. Individual photographer names will appear on the appropriate calendar pages.

<p>You have the option to have a calendar title which will appear on the fron cover.

<p><b>Calendars are priced at &pound;8.00 each, which includes a donation to Geograph funds. There is a separate postage and packing charge of &pound;3.00 per order which may include more than one version, including the 'Best of Geograph'</b> There is a 
minimum quantity of 2 calendars per person, which can include more than one version.
<br><br>
<p>
{dynamic}{if $closed}
	<h1>Sorry, we are no longer accepting new orders</h1>
{else}
<a href="start.php" style="font-size:large;background-color:#000066;color:white;padding:10px;border-radius:10px">Create a new Calendar Now &gt; &gt;</a>
{/if}{/dynamic}

<p>{newwin href="/calendar/help.php" text="Open Help Page"} (in new window)</p>
	<p>{newwin href="/calendar/tips.php" text="Tips Page"} (in new window)</p>

{dynamic}
{if $list}
	<h3>Current Orders</h3>
	<table>
	{foreach from=$list key=index item=calendar}
		<tr>
			<td>{$calendar.title|default:'untitled calendar'}</td>
			<td>{if $calendar.status != 'processed'}<a href="edit.php?id={$calendar.calendar_id}">Review/Edit</a>{/if}
			<td>{$calendar.status} {if $calendar.quantity}x{$calendar.quantity}{/if}
			<td>{if $calendar.paid < '2' and $calendar.status!='deleted' and $calendar.status != 'processed'}<a href="order.php?id={$calendar.calendar_id}"><b>Continue and Order</b></a>{/if}
			<td>{if $calendar.status == 'new'}<a href="?delete={$calendar.calendar_id}" style="color:red">Delete</a>{/if}
		</tr>
	{/foreach}
	</table>	

	<p>You will be able to edit the order right up to the time the order is has been processed (even after payment). Processing however may happen at any time after payment.

{/if}
{/dynamic}

<br style=clear:both>

{include file="_std_end.tpl"}


