{assign var="page_title" value="Statistics FAQ"}
{include file="_std_begin.tpl"}
{literal}
<style type="text/css">
.helpbox { float:right;padding:5px;background:#dddddd;position:relative;font-size:0.8em;margin-left:20px;z-index:10; }
.helpbox UL { margin-top:2px;margin-bottom:0;padding:0 0 0 1em; }
.contents A { text-decoration:none; }
.contents LI { padding-bottom:2px; }
.answers h3 { padding:10px;border-top: 1px solid lightgrey;background-color:#f9f9f9; }
.answers p { padding-left:20px; }
.answers LI { padding-bottom:10px; }
div:target { background-color:orange;padding-bottom:10px; }
.top { text-align:right;font-size:0.7em; }
.top A { text-decoration:none; }
</style> {/literal}

    <h2>Statistics FAQ</h2>

<div style="position:relative" class="contents">
 <ul>
 <li><a href="#points">How do I get a Geograph <b>point</b> for my image?</a></li>
 <li><a href="#tpoints">How do <b>TPoints</b> work?</a></li>
 <li><a href="#squares">What's a 'myriad' or a 'hectad'?</a></li>
 <li><a href="#differ">What statistics do you track?</a></li>
 </ul>
</div>

<div style="position:relative" class="answers">

<div id="points">
<a name="points"></a>
<h3>How do I get a Geograph point for my image?</h3>
	<p><small><i>(copied from the main <a href="/faq.php">FAQ</a> page)</i><br/><br/></small>

	If you're the first to submit a &quot;Geograph&quot; for the grid square
	you'll get a "First Geograph" point added to your profile and the warm glow that comes
	with it.</p>

	<p>We welcome many Geograph images per square, so even if you don't get the point, you are still making a valuable contribution to the project.</p>

	<blockquote>

		<p>In addition we now award "Second Visitor" points (and Third and Fourth!) - which are given to the first Geograph the <i>second contributor</i> adds to a square. The third contributor similarly gets a "Third" point for their first Geograph to the square. </p>

		<p>So a single square can have a First, Second, Third and Fourth Visitor point, but a contributor can only get one of those per square.</p>

		<p>You can earn yourself a "Personal" point by submitting a &quot;Geograph&quot; for a square that is new to you, regardless of how many contributors have been there before.</p>
	</blockquote>
</div>

<div id="tpoints">
<a name="tpoints"></a>
<h3>How do TPoints work?</h3>

	<p>TPoint or 'Time-gap Point' (formally 'Temporal Point' or 'Temporally aware Point') is a new kind of point that takes into account when photos were taken.
	A contributor can gain a TPoint by submitting a contemporary photo to a square that hasn't had a photo for 5 years. But you can also submit historic photos, and as long as there isn't a Geograph taken with 5 years (before OR after) then it gains a TPoint.

	<p>In essence: <b>if there isn't a Geograph taken within 5 years of a Geograph you submit, you get a TPoint</b>.</p>

	<p>&middot; Squares that can get a TPoint for <b>a recent photo</b> are shown in orange on the <a href="/map/?recent=1">Recent Only coverage map</a>, or in purple on the 'TPoint Availability' layer on the <a href="/mapper/">Draggable OS</a> map.</p>

</div>

<div id="squares">
<a name="squares"></a>
<h3>What's a 'myriad' or a 'hectad'?</h3>

<p>We couldn't find unambiguous names for larger units of area, so we made our own! A hectad is 10km x 10km, whereas a myriad is 100km x 100km.</p>

<ul>
	<li>Read more: <a href="/help/squares">Names for Different-Sized Squares</a></li>
	<li>Also: <a href="/article/Geographisms">Geographisms</a> - Geograph website terminology</li>
</div>

<div id="differ">
<a name="differ"></a>
<h3>What statistics do you track?</h3>
	<ul>
	<li><b>Points</b> / <b>Firsts</b>: the first Geograph submitted for a square earns a <a href="#points"><b>First Geograph Point</b></a><br/><br/><ul>
		<li><b>Seconds</b>: a "<b>Second Visitor</b>" point is awarded to the second person to contribute a Geograph to a square</li>
		<li><b>Thirds</b>: similarly a "<b>Third Visitor</b>" point is awarded to the third person to contribute a Geograph to a square</li>
		<li><b>Fourths</b>: and therefore a "<b>Fourth Visitor</b>" point is awarded to the fourth person to contribute a Geograph to a square</li>
		<li><b>AllPoints</b>: Total of First/Second/Third/Fourth Visitor Points</li>
		<li><b>Personal Points</b>: The first Geograph image submitted by each user in a square gets a Personal point</li>
		<li><b>TPoints</b>: Awarded for a Geograph submitted to a square that doesn't already have a photo taken within 5 years<br/><small> (short for "temporally aware" - see also separate question above),</small></li>
		</ul></li>

		<li><b>Images</b>: these are all images on the site, regardless if they are Geographs or Supplementals<br/><br/><ul>
			<li><b>Geographs</b>: these are all Geograph images, even if multiples for the same grid square</li>
			<li><b>Additional Geographs</b>: these are Geograph images, excluding the First Geographs</li>
			<li><b>Supplementals</b>: the number of Supplemental images submitted</li>
		</ul></li>

		<li><b>Squares</b>: the number of different squares photographed (Geographs or Supplementals)<br/><br/><ul>
			<li><b>GeoSquares</b>: the number of different squares Geographed<br/><small> (on the All Time Leaderboard is the same as Personal Geograph Points, doesn't apply on the Weekly Leaderboard)</small></li>
			<li><b>Depth</b>: number of images divided by the number of squares.<br/> <small>Higher numbers indicate a tendency to photograph a small number of squares a lot (the opposite of points/coverage)</small></li>
			<li><b>Centisquares</b>: number of different centisquares<sup><a href="/help/squares" class="about" style="font-size:0.7em">?</a></sup> for which images have been submitted</li>
			<li><b>Hectads</b>: number of different 10k hectads<sup><a href="/help/squares" class="about" style="font-size:0.7em">?</a></sup> for which images have been submitted</li>
			<li><b>Myriads</b>: number of different 100k myriads<sup><a href="/help/squares" class="about" style="font-size:0.7em">?</a></sup> for which images have been submitted</li>
			<li><b>Spread</b>: number of hectads divided by number of images.<br><small>Higher numbers indicate a large number of hectads with few images</small></li>
			<li><b>AntiSpread</b>: number of images images divided by hectads.<br><small>Equivalent to depth score at hectad level</small></li>
			<li><b>Categories</b>: number of different image categories used in submissions</li>
			<li><b>Days</b>: number of different days over which images submitted</li>
		</ul></li>
	</ul>
</div>

 <br/>

<a name="question"></a>
<h3>I have a question, what should I do?</h3>
    <p>Please see the <a href="/faq.php">General Questions</a> or <a title="Contact Us" href="/ask.php">Contact Us</a>{if $enable_forums}, alternatively pop into the <a href="/discuss/">Discussion Forum</a>{/if}.</p>

</div>

{include file="_std_end.tpl"}
