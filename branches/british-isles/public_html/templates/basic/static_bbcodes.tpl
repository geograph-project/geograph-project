{assign var="page_title" value="Formatting Help"}
{include file="_std_begin.tpl"}
{literal}<style type="text/css">
tt { color:green; border: 1px solid lightgrey; padding:3px; background-color:white}
.bbcode {border: 1px solid gray}
.preview { padding:3px; background-color:white}
.example {background-color:#eeeeee; padding:10px;}
.tip {font-size:0.8em; border: 1px solid red; padding:3px}
.explain {color: brown; font-size:0.8em; font-style:italic;}
.title { font-size:0.7em }
</style>{/literal}

<h2>Formatting and Links for use in Discussion Posts</h2>

<p>By using the code below in your post, you can help to make your post more concise. Just use the simple codes as detailed below, you can also use the buttons on the page to insert blank codes at the end of your message, ready for you to add in your own text.</p>

<h4>Formatting </h4>

<ul>
<li class="example"><tt class="bbcode">[b]some text here[/b]</tt> shows as <span class="preview"><b>some text here</b></span> <span class="explain">(bold)</span></li>
<li class="example"><tt class="bbcode">[i]some text here[/i]</tt> shows as <span class="preview"><i>some text here</i></span> <span class="explain">(italic)</span></li>
<li class="example"><tt class="bbcode">[u]some&nbsp;&nbsp;text here[/u]</tt> shows as <span class="preview"><u>some  text here</u></span> <span class="explain">(underlined)</span></li>
<li class="example"><tt class="bbcode">[code]some&nbsp;&nbsp;&nbsp;text&nbsp;&nbsp;here[/code]</tt> shows as <span class="explain">(maintains whitespace and used fixed font)</span>
<pre class="preview">some&nbsp;&nbsp;&nbsp;text&nbsp;&nbsp;here</pre></li>
<li class="example"><tt class="bbcode">[blockquote]some text here[/blockquote]</tt> shows as <span class="explain">(indented paragraph)</span>
<blockquote class="preview">some text here</blockquote></li>
</ul>

<h4>Linking</h4>

<blockquote class="tip">Tip: Get the Image ID for use in building these links from the URL of the page (visible in the Address bar of your Browser) for example, to insert a link to the picture at <a href="/photo/5463">http://{$http_host}/photo/<b>5463</b></a> use <tt class="bbcode" style="line-height:3em">[[5463]]</tt></blockquote>

<ul>
<li><b class="title">Link to by ID direct to an Image</b><br/>
<div class="example"><tt class="bbcode">[[5463]]</tt> shows as <span class="preview">{literal}{<a href="/photo/5463" title="Geograph Image by Ben Gamble">TQ3328 : Ardingly Reservoir</a>}{/literal}</span></div><br/></li>

<li><b class="title">Include an Image Thumbnail by ID</b><br/>
<div class="example"><tt class="bbcode">[[[5463]]]</tt> shows as <span class="preview"><a href="/photo/5463"><img alt="TQ3328 : Ardingly Reservoir" src="/photos/00/54/005463_ea60a493_120x120.jpg" width="120" height="90"/></a></span></div><br/></li>

<li><b class="title">Include an Large Thumbnail by ID</b><br/>
<div class="example"><tt class="bbcode">[image id=5463]</tt> shows as <div class="photoguide"><div style="float:left;width:213px"><a title="TQ3328 : Ardingly Reservoir by Ben Gamble - click to view full size image" href="/photo/5463"><img alt="TQ3328 : Ardingly Reservoir by Ben Gamble" src="http://s3.geograph.org.uk/photos/00/54/005463_ea60a493_213x160.jpg" width="213" height="160" /></a><div class="caption"><a title="view full size image" href="/photo/5463">Ardingly Reservoir</a> by <a href="/profile/113">Ben Gamble</a></div></div><div style="float:left;padding-left:20px; width:400px;">Sailing club at the southern end of the reservoir<div style="text-align:right;font-size:0.8em">by Ben Gamble</a></div></div><br style="clear:both"/></div></div><br/>
<div class="example">Can also use a custom description/comment: <tt class="bbcode">[image id=5463 text=This is an example of a custom description.]</tt></div><br/></li>

<li><b class="title">Webpage/URL</b><br/>
<div class="example"><tt class="bbcode">[url=http://www.example.com]Some text[/url]</tt> shows as <span class="preview"><a href="http://www.example.com">Some text</a></span></div><br/></li>
<div class="example"><tt class="bbcode">http://www.example.com</tt> shows as a clickable link, <span class="preview"><a href="http://www.example.com">http://www.example.com</a></span></div><br/></li>
</ul>

<blockquote class="tip"><b>Linking to a specific Discussion Post</b><br/>
You can get the link for a specifc post, by right clicking the # charactor at the beginning of a post, and selecting 'Copy Link Location' or similar. You can then use this in [url=...]thread[/url] tags.</blockquote>

<ul>
<li><b class="title">Link to Browse page for a Square</b><br/>
<div class="example"><tt class="bbcode">[[TQ7506]]</tt> shows as <span class="preview"><a href="/gridref/TQ7506">TQ7506</a></span></div><br/></li>
</ul>

<blockquote class="tip">Grid References in the text are identified and linked to the browse page.<br/>
To stop otherwise <i>Grid Reference Looking Text</i> being converted, either use lowercase or prefix with a !. For example <span class="nowrap">!B4567</a> won't be linked but <a href="/gridref/B4567">B4567</a> would, the ! mark is not shown on the final page.
<div class="example"><tt class="bbcode">Drive along the !B4567 road.</tt> shows as <span class="preview">Drive along the B4567 road.</span></div></blockquote>

<ul>
<li><b class="title">Email Address</b><br/>
<div class="example"><tt class="bbcode">[email=address@example.com]contact me[/email]</tt> shows as <span class="preview"><a href="mailto:address@example.com">contact me</a></span></div><br/></li>
</ul>

<!--
<h4>External Images</h4>

<ul>
<li><tt class="bbcode">[img]http://www.domain.com/image.gif[/img]</tt> shows as <img src="/photos/error.jpg" width="40" height="40"/></li>
</ul>
<p>Advanced:</p>
<ul>
<li><tt class="bbcode">[imgleft]http://www.domain.com/image.gif[/img]</tt> will left align the image in the flow of text.</li>

<li><tt class="bbcode">[imgright]http://www.domain.com/image.gif[/img]</tt> will right align the image in the flow of text.</li>
</ul>
-->



{include file="_std_end.tpl"}

