{assign var="page_title" value="Code Help"}
{include file="_std_begin.tpl"}

<h2>Formatting and Links for use in Discussion Posts</h2>

<p>By using the code below in your post, you can help to make your post more concise. Just use the simple codes as detailed below, you can also use the buttons on the page to insert blank codes at the end of your message, ready for you to add in your own text.</p>

<h4>Formatting </h4>

<ul>
<li><tt>[b]bold[/b]</tt> renders as <b>bold</b></li>
<li><tt>[i]italic[/i]</tt> renders as <i>italic</i></li>
<li><tt>[u]underlined[/u]</tt> renders as <u>underlined</u></li>
</ul>

<h4>Linking</h4>

<ul>
<li><tt>[[TQ7506]]</tt> renders as <a href="/gridref/TQ7506">TQ7506</a><br/><br/></li>
<li><tt>[[5463]]</tt> renders as {literal}{<a href="/photo/5463" title="Geograph Image by Ben Gamble">TQ3328 : Ardingly Reservoir</a>}{/literal}<br/><br/></li>
<li><tt>[[[5463]]]</tt> renders as <a href="/photo/5463"><img alt="TQ3328 : Ardingly Reservoir" src="/photos/00/54/005463_ea60a493_120x120.jpg" width="120" height="90"/></a><br/><br/></li>
<li><tt>[url=http://www.example.com]Some text[/url]</tt> renders as <a href="http://www.example.com">Some text</a><br/><br/></li>
<li><tt>[email=email@example.com]contact me[/email]</tt> renders as <a href="mailto:email@example.com">contact me</a></li>
</ul>

<p>Tip: Get the Image ID for use in building these links from the URL of the page for example, to insert a link to the picture at <a href="/photo/5463">http://$/photo/5463

<p>Note: Plain links will be converted to a clickable link automatically, also where appropriate links to Images and Grid References will also be converted automatically. (To stop otherwise 'Grid Reference Looking Text' being converted, either use lowercase or prefix with a !. For example <span class="nowrap">!B4567</a> won't be linked but <a href="/gridref/B4567">B4567</a> would, the ! mark is not shown on the final page.)</p>

<h4>Images</h4>

<ul>
<li><tt>[img]http://www.someserver.com/images/image.gif[/img]</tt> renders as <img src="/photos/error.jpg" width="40" height="40"/></li>
</ul>
<p>Advanced:</p>
<p><img src="/photos/error.jpg" width="40" height="40" align="left" style="float:left; position:relative"/><tt>[imgleft]http://www.someserver.com/images/image.gif[/img]</tt> will left align the image in the flow of text<br style="clear:left"/></p>

<p><img src="/photos/error.jpg" width="40" height="40" align="right" style="float:right; position:relative"/><tt>[imgright]http://www.someserver.com/images/image.gif[/img]</tt> will right align the image in the flow of text</p>





{include file="_std_end.tpl"}

