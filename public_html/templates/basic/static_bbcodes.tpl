{assign var="page_title" value="Code Help"}
{include file="_std_begin.tpl"}

<h2>Formatting and Links for use in Discussion Posts</h2>

<p>By using the code below in your post, you can help to make your post more concise. Just use the simple codes as detailed below, you can also use the buttons on the page to insert blank codes at the end of your message, ready for you to add in your own text.</p>

<h4>Formatting </h4>

<ul>
<li><tt style="color:green">[b]some text here[/b]</tt> renders as <b>some text here</b></li>
<li><tt style="color:green">[i]some text here[/i]</tt> renders as <i>some text here</i></li>
<li><tt style="color:green">[u]some text here[/u]</tt> renders as <u>some text here</u></li>
</ul>

<h4>Linking</h4>

<ul>
<li><b style="font-size:0.7em">Link to by ID direct to an Image</b><br/>
<div style="background-color:#eeeeee; padding:10px;"><tt style="color:green">[[5463]]</tt> renders as {literal}{<a href="/photo/5463" title="Geograph Image by Ben Gamble">TQ3328 : Ardingly Reservoir</a>}{/literal}</div><br/></li>

<li><b style="font-size:0.7em">Include an Image Thumbnail by ID</b><br/>
<div style="background-color:#eeeeee; padding:10px;"><tt style="color:green">[[[5463]]]</tt> renders as <a href="/photo/5463"><img alt="TQ3328 : Ardingly Reservoir" src="/photos/00/54/005463_ea60a493_120x120.jpg" width="120" height="90"/></a></div><br/></li>
</ul>
<p style="font-size:0.8em">Tip: Get the Image ID for use in building these links from the URL of the page (visible in the Address bar of your Browser) for example, to insert a link to the picture at <a href="/photo/5463">http://{$http_host}/photo/5463</a> use <tt style="color:green">[[5463]]</tt></p>
<ul>
<li><b style="font-size:0.7em">Webpage/URL</b><br/>
<div style="background-color:#eeeeee; padding:10px;"><tt style="color:green">[url=http://www.example.com]Some text[/url]</tt> renders as <a href="http://www.example.com">Some text</a></div><br/></li>
<div style="background-color:#eeeeee; padding:10px;"><tt style="color:green">http://www.example.com</tt> renders as <a href="http://www.example.com">http://www.example.com</a></div><br/></li>
</ul>
<p style="font-size:0.8em">Links will be converted to a clickable link automatically.</p>
<ul>
<li><b style="font-size:0.7em">Link to Browse page for a Square</b><br/>
<div style="background-color:#eeeeee; padding:10px;"><tt style="color:green">[[TQ7506]]</tt> renders as <a href="/gridref/TQ7506">TQ7506</a></div><br/></li>
</ul>

<p style="font-size:0.8em">Grid References in the text are identified and linked to the browse page.<br/>
To stop otherwise <i>Grid Reference Looking Text</i> being converted, either use lowercase or prefix with a !. For example <span class="nowrap">!B4567</a> won't be linked but <a href="/gridref/B4567">B4567</a> would, the ! mark is not shown on the final page.</p>

<ul>
<li><b style="font-size:0.7em">Email Address</b><br/>
<div style="background-color:#eeeeee; padding:10px;"><tt style="color:green">[email=address@example.com]contact me[/email]</tt> renders as <a href="mailto:address@example.com">contact me</a></div><br/></li>
</ul>

<h4>External Images</h4>

<ul>
<li><tt style="color:green">[img]http://www.domain.com/image.gif[/img]</tt> renders as <img src="/photos/error.jpg" width="40" height="40"/></li>
</ul>
<p>Advanced:</p>
<ul>
<li><tt style="color:green">[imgleft]http://www.domain.com/image.gif[/img]</tt> will left align the image in the flow of text.</li>

<li><tt style="color:green">[imgright]http://www.domain.com/image.gif[/img]</tt> will right align the image in the flow of text.</li>
</ul>




{include file="_std_end.tpl"}

