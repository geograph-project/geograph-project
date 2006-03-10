{assign var="page_title" value="FAQ"}
{include file="_std_begin.tpl"}

 
 <div style="float:right;padding:5px;background:#dddddd;position:relative; font-size:0.8em;margin-left:20px;">
 <b>Contents</b>
 <ul style="margin-top:0;padding:0 0 0 1em;">
 <li><a href="#points" style="text-decoration:none">How do I get a geograph point for my image?<br/> What makes a good geograph?</a></li>
 <li><a href="#supplemental" style="text-decoration:none">What is a supplemental image?</a></li>
 <li><a href="#resize" style="text-decoration:none">Do I need to resize my photos? Are there size limits?</a></li>
 <li><a href="#change" style="text-decoration:none">I disagree with the location or title of an image - what can I do?</a></li>
 <li><a href="#legal" style="text-decoration:none">What are my legal rights when taking photographs?</a></li>
 <li><a href="#aol" style="text-decoration:none">I use AOL, and the images look terrible!</a></li>
 <li><a href="#rss" style="text-decoration:none">Do you have an RSS feed?</a></li>
 <li><a href="#use" style="text-decoration:none">I would be interested in using Geograph content, is that possible?</a></li>
 <li><a href="#built" style="text-decoration:none">Who built this marvellous site?</a></li>
 <li><a href="#opensource" style="text-decoration:none">Open source? Creative Commons? What's that all about?</a></li>
 <li><a href="#commercial" style="text-decoration:none">Why must I agree to allow commercial use of my image?</a></li>
 <li><a href="#question" style="text-decoration:none">I have a question, what should I do?</a></li>
 </ul></div>
 
    <h2>FAQ</h2>
 
<h3>What is Geographing?</h3>
    <p>See our <a title="guide to geographing" href="/help/guide">guide to good geographing</a></p>


<a name="points"></a>
<h3>How do I get a geograph point for my image?<br/> What makes a good geograph?</h3>
    
    <p>If you're the first to submit a proper &quot;geograph&quot; for the grid square
    you'll get a geograph point added to your profile and the warm glow that comes
    with it. So what makes an image a genuine geograph?</p>
    <ul>
    <li>You must clearly show at close range one of the main geographical features within the square</li>
    <li>You should include a short description relating the image to the map square</li>
    </ul>
    <p>See <a title="geograph and supplemental guide" href="/help/geograph_guide">What is a geograph?</a> 
    for more details.</p>
<a name="supplemental"></a>
<h3>What is a supplemental image?</h3>
    <p>If an image doesn't quite fulfill the geograph criteria above, but is still a good
    image, we'll accept it as &quot;supplemental image&quot; - no geograph points are awarded,
    but the image will still appear on the selected grid square. A square that just contains
    supplemental images is still open to be claimed as a geograph though! See <a 
    title="geograph and supplemental guide" href="/help/geograph_guide">What is a geograph?</a> 
    for details of how photos are moderated.
    </p>
<a name="resize"></a>
<h3>Do I need to resize my photos? Are there size limits?</h3>
    <p>You can upload images of any size, portrait or landscape, but we do resize them so their
    longest dimension is 640 pixels. We do not keep your original print quality image,
    only our resized screen-quality version.</p>
    
    <p>We do preserve the EXIF headers from your original image, so it is
    advantageous to upload your original camera image if you want this information
    to be kept.</p>

<a name="change"></a>
<h3>I disagree with the location or title of an image - what can I do?</h3>
<p>Our <a href="/help/changes">"Change Request Tickets"</a> allow any registered user 
to suggest a change of grid reference or title/comment
information - simply view the full size image and click the "report a problem" link.
</p>

<a name="legal"></a>
<h3>What are my legal rights when taking photographs?</h3>

<p>Let's preface this by stating <b>We Are Not Lawyers</b>, and if you have any doubts
about your right to take picture, then you're probably better off not submitting it to us.
However, there is a 
{external href="http://www.sirimo.co.uk/ukpr.php" text="useful guide"}
available which outlines your rights in the UK fairly concisely. 
</p>


<a name="aol"></a>
<h3>I use AOL, and the images look terrible!</h3>
<p>AOL's default settings for graphics is to show &quot;Compressed Graphics Only&quot;. 
This means AOL is selectively <i>re-compressing</i> images before you see them on your
screen. This loses a lot of image quality.</p>

<p>You should reset the preferences under &quot;My AOL/Preferences/WWW&quot; to display 
&quot;Uncompressed Graphics&quot;. You will pay only a small penalty in download time to see 
this and other photo oriented sites the way everyone else can see them.</p>

<a name="rss"></a>
<h3>Do you have an RSS feed?</h3>
    <p>RSS, or Really Simple Syndication, allows you to obtain an up-to-date listing of
    the latest geograph submissions for integration into another website or RSS reader. For more information,
    try this {external href="http://en.wikipedia.org/wiki/RSS_(protocol)" text="Wikipedia article on RSS"}.</p>
    
    
    <p>We provide an RSS1.0 feed at 
    <a title="Geograph RSS feed" href="http://{$http_host}/syndicator.php">http://{$http_host}/syndicator.php</a>
    which contains links to the latest 20 moderated images. 
    
    The feed is also available in
    <a title="Geograph RSS 0.91 feed" href="http://{$http_host}/syndicator.php?format=RSS0.91">RSS 0.91</a>,
    <a title="Geograph RSS 2.0 feed" href="http://{$http_host}/syndicator.php?format=RSS2.0">RSS 2.0</a>,
    <a title="Geograph OPML feed" href="http://{$http_host}/syndicator.php?format=OPML">OPML</a>,
    <a title="Geograph HTML feed" href="http://{$http_host}/syndicator.php?format=HTML">HTML</a>,
    <a title="Geograph JavaScript feed" href="http://{$http_host}/syndicator.php?format=JS">JavaScript</a>,
    <a title="Geograph KML (Google Earth) feed" href="http://{$http_host}/syndicator.php?format=KML">KML</a>,
    simple <a title="Geograph GeoRSS feed" href="http://{$http_host}/syndicator.php?format=GeoRSS">GeoRSS</a> and
    <a title="Geograph PhotoRSS feed" href="http://{$http_host}/syndicator.php?format=GeoPhotoRSS">(Geo) PhotoRSS</a>
    formats.</p>
    
    <p>We have recently added RSS feeds to other parts of the site. You will find an <a class="xml-rss">RSS</a> button at the bottom of search results, useful to keep updated on local images. Registered users can access RSS feed of the latest Topics in the Discussion Forum, and even subscribe to an individual Topic, just look for the <a class="xml-rss">RSS</a> button! (they also accept the format parameter like the main feed)</p>
    
    <p>If you use the {external title="Firefox Web Browser" href="http://www.mozilla.org/products/firefox/" text="Firefox"} web browser, 
    you should be able use our feeds as "live bookmarks" - simply
    click the orange button in the address bar (or in the status bar on older versions).</p>    

<a name="use"></a>
<h3>I would be interested in using Geograph content, is that possible?</h3>
    <p>If you can think of an interesting use on your own site, or for a new idea, (beyond what's available via the RSS feeds above) 
    then we would be very interested to <a title="Contact Us" href="contact.php">hear from you</a>. 
    In all likelihood we can provide a feed to suit your requirements.</p>
    
    <p>Images are licenced for re-use under a <a href="#opensource">Creative Commons Licence</a>, see
    licence details by viewing a full size image.</p>
    
<a name="built"></a>
<h3>Who built this marvellous site?</h3>
    <p>Please see the <a href="/help/credits" title="Credits Page">Credits Page</a> for
    information on all the people who made this site possible.</p>
<a name="opensource"></a>
<h3>Open source? Creative Commons? What's that all about?</h3>
    <p>Putting this together requires many people to donate their
    time or resources, and we wanted to be sure that we created a resource
    free from commercial exploitation in future. To that end, the site
    {external title="geograph source code" href="http://geograph.sourceforge.net" text="source code"} is available 
    for re-use under the terms of the GNU Public Licence (GPL).</p>
    
    <p>In addition, we require all submitters to adopt a 
    {external href="http://creativecommons.org" text="Creative Commons"} 
    licence on their photographic submissions. While our volunteer photographers
    keep copyright on their photos, they also grant the use of their photographs
    in return for attribution (take a look at a <a title="View a typical photograph" href="/photo/14">typical 
    submission</a> for more details)</p>
    
    <p>In a nutshell, we wanted to build a true community project that won't 
    leave a nasty taste in the mouth by getting sold for shedloads of cash and
    taken away from the people who contributed. Our licence terms ensure that the
    site and content can never be "taken away" from you.
    </p>
<a name="commercial"></a>
<h3>Why must I agree to allow commercial use of my image?</h3>
    <p>Running this site costs money, particularly over time as the storage
    requirements are quite large. We require commercial rights to enable us to
    support the running costs. One way we may do this is through sales of 
    montage posters once we reach a critical mass of submissions.</p>
    
    <p>Granting everyone the same rights actually protects the site community
    from exploitation (see previous FAQ entry), but do bear in mind that we only
    retain a screen-quality version of your image, and that under the terms of 
    the Creative Commons licence, you must be credited for any use of your image</p>
    
<a name="question"></a>
<h3>I have a question, what should I do?</h3>
    <p>Please <a title="Contact Us" href="contact.php">Contact Us</a></p>
    
    
{include file="_std_end.tpl"}
