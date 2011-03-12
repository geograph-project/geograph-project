{assign var="page_title" value="Statistics FAQ"}
{include file="_std_begin.tpl"}
<style type="text/css"> 
.helpbox { float:right;padding:5px;background:#dddddd;position:relative;font-size:0.8em;margin-left:20px;z-index:10; }
.helpbox UL { margin-top:2px;margin-bottom:0;padding:0 0 0 1em; }
.contents A { text-decoration:none; }
.contents LI { padding-bottom:2px; }
.answers h3 { padding:10px;border-top: 1px solid lightgrey;background-color:#f9f9f9; }
.answers p { padding-left:20px; }
div:target { background-color:orange;padding-bottom:10px; }
.top { text-align:right;font-size:0.7em; }
.top A { text-decoration:none; }
</style> 

    <h2>Statistics FAQ</h2>

<div id="points"> 
<a name="points"></a> 
<h3>How do I get a Geograph point for my image?</h3> 
	<small><i>(copied from the main <a href="/faq.php">FAQ</a> page)</i><br/><br/></small>

	<p>If you're the first to submit a &quot;Geograph&quot; for the grid square
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

If there isn't a geograph taken within 5 years of a geograph you submit, you get a TPoint.
</div>

<div id="squares">
<a name="squares"></a>
<h3>Whats a 'Myriad'? A 'hectad'?</h3>

We couldn't find unabigious names for larger units of area, so we made our own! A Hectad is 10km x 10km. Whereas a myriad is 100km x 100km. 

<ul>
	<li>Read more: <a href="/help/squares">Names for Different Sized Squares</a></li>
	<li>Also: <a href="/article/Geographisms">Geographisms</a> - geograph website terminonlogy</li>
</div>

<div id="differ">
<a name="differ"></a>
<h3>What statistics do you track?</h3>
    <ul>
	<li><b>Points</b> / <b>Firsts</b>: the first Geograph submitted for a square earns a <a href="/faq.php#points"><b>First Geograph Point</b></a><br/><br/><ul>
		<li><b>Seconds</b>: a "<b>Second Visitor</b>" point is awarded for the second person to contribute a Geograph to a square</li>
		<li><b>Thirds</b>: similarlly a "<b>Third Visitor</b>" point is awarded for the third person to contribute a Geograph to a square</li>
		<li><b>Fourths</b>: and therefore a "<b>Fourth Visitor</b>" point is awarded for the fourth person to contribute a Geograph to a square</li>
		<li><b>AllPoints</b>: Total of First/Second/Third/Fourth Visitor Points</li>
		<li><b>Personal Points</b>: The first Geograph image submitted by each user in a square gets a Personal Point</li>
	    	<li><b>TPoints</b>: (temporally aware) - awarded for a image submitted to a square, that doesnt already have a photo taken within a 5 year window</li>
    	</ul></li>
    	<li><b>Images</b>: these are all images on the site, regardless if they Geograph or supplemental<br/><br/><ul>
    		<li><b>Geographs</b>: these are all Geograph images, even if multiple for the same grid square</li>
    		<li><b>Additional Geographs</b>: these are Geograph images, excluding the First Geographs</li>
    		<li><b>Supplementals</b>: the number of Supplemental images submitted</li>
    	</ul></li>
    	<li><b>Squares</b>: the number of different squares photographed (Geograph or supplemental)<br/><br/><ul>
    		<li><b>GeoSquares</b>: the number of different squares Geographed<br/><small> (on the All Time Leaderboard is the same as Personal Geograph Points, doesn't apply on the Weekly Leaderboard)</small></li>
    		<li><b>Depth</b>: number of images divided by the number of squares.<br/> <small>Higher numbers indicate a tendency to photograph a small number of squares very well. (opposite to points/coverage)</small></li>
    		<li><b>Centisquares</b>: number of different centisquares<sup><a href="/help/squares" class="about" style="font-size:0.7em">?</a></sup> for which images have been submitted</li>
    		<li><b>Hectads</b>: number of different 10k hectads<sup><a href="/help/squares" class="about" style="font-size:0.7em">?</a></sup> for which images have been submitted</li>
    		<li><b>Myriads</b>: number of different 100k myriads<sup><a href="/help/squares" class="about" style="font-size:0.7em">?</a></sup> for which images have been submitted</li>
    		<li><b>Spread</b>: number of hectads divided by number of images<br><small>Higher numbers indicate a large number of hectads, with few images</small></li>
    		<li><b>AntiSpread</b>: number of images images divided by hectads<br><small>Equivalent to depth score at hectad level</small></li>
    		<li><b>Categories</b>: number of different image categories used in submissions</li>
    		<li><b>Days</b>: number of different days over which images submitted</li>
    	</ul></li>
    </ul>
</div>

 <br/>

<a name="question"></a>
<h3>I have a question, what should I do?</h3>
    <p>Please see the <a href="/faq.php">General Questions</a> or <a title="Contact Us" href="/contact.php">Contact Us</a>{if $enable_forums}, alternatively pop into the <a href="/discuss/">Discussion Forum</a>{/if}.</p>


{include file="_std_end.tpl"}
