{assign var="page_title" value="Discussion Post Formatting Summary"}
{include file="_std_begin.tpl"}
{literal}<style type="text/css">
tt { color:green; border: 1px solid lightgrey; padding:3px; background-color:white}
.bbcode {border: 1px solid gray}
.preview { padding:3px; background-color:white}
.example {background-color:#eeeeee; padding:10px;}
.tip {font-size:0.8em; padding:3px}
.explain {color: brown; font-size:0.8em; font-style:italic;}
#maincontent *{
	box-sizing:border-box;
}
</style>{/literal}

<h2>Formatting and Links for use in Discussion Posts</h2>

<p>By using the code below in your post, you can help to make your post more concise. Just use the simple codes as detailed below. You can also use the buttons on the page to insert blank codes at the end of your message, ready for you to add in your own text.</p>

<p>The formatting here is only for use in Discussion Posts. These codes do not work in other places, like image descriptions etc. Similarly Articles and blog posts, have their own similar, but not identical, formatting syntax, refer to their own reference for more information.</p>

<div class="threecolsetup">

<div class="threecolumn">

<h3>Text formatting </h3>

<h4>Bold</h4>
<div class="interestBox"><tt class="bbcode">[b]some text here[/b]</tt> shows as <span class="preview"><b>some text here</b></span> <span class="explain">(bold)</span></div>

<h4>Italic</h4>
<div class="interestBox"><tt class="bbcode">[u]some&nbsp;&nbsp;text here[/u]</tt> shows as <span class="preview"><u>some  text here</u></span> <span class="explain">(underlined)</span></div>

<h4>Underline</h4>
<div class="interestBox"><tt class="bbcode">[u]some&nbsp;&nbsp;text here[/u]</tt> shows as <span class="preview"><u>some  text here</u></span> <span class="explain">(underlined)</span></div>

<h4>Code</h4>
<div class="interestBox"><tt class="bbcode">[code]some&nbsp;&nbsp;&nbsp;text&nbsp;&nbsp;here[/code]</tt> shows as <pre class="preview">some&nbsp;&nbsp;&nbsp;text&nbsp;&nbsp;here</pre> <span class="explain">(maintains white space and uses fixed font)</span></div>

<h4>Blockquote</h4>
<div class="interestBox"><tt class="bbcode">[blockquote]some text here[/blockquote]</tt> shows as <blockquote class="preview">some text here</blockquote> <span class="explain">(indented paragraph)</span></div>



<h3>Linking and quoting forum posts</h3>

<h4>Including a posters name from the thread</h4>
<p>Clicking the name of the contributor beside their post will insert their name into the comment reply box. The contributors name will be inserted with bold formatting <tt class="bbcode">[b]Contributors name[/b]</tt>.</p>

<h4>Including a quotation from a post in the thread</h4>
<p>Parts of a post can be quoted in a reply by selecting the relevant passage of text and clicking the quote button. The quoted passage will be inserted with italicised formatting <tt class="bbcode">[i]Quoted text[/i]</tt>.</p>

<h4>Linking to a specific discussion post</h4>
<p>You can get the link for a specific post, by right clicking the # character at the beginning of a post, and selecting 'Copy Link Location' or similar. These links can be inserted using the <tt class="bbcode">[url=<i>Insert URL here</i>]<i>Insert link text here</i>[/url]</tt> tags.</p>


</div>

<div class="threecolumn">

<h3>Image links</h3>

<h4>Finding the image ID</h4>

<p>In order to insert images into posts, you will need to locate the image ID. The image ID is found in the URL (webpage address) for the photo page. For example, to insert a link to the picture at:<br><center><a href="/photo/5463">{$self_host}/photo/<b>5463</b></a></center><br> use <tt class="bbcode" style="line-height:3em">[[5463]]</tt>.</p>

<p>Other Geograph projects images can be used, with the image ID numbers prefixed with the shortcode for the project.</p>
<ul>
<li>For Germany use <b>DE:</b> before the image ID, for example <tt class="bbcode">de:1</tt> would link to image 1 on Geograph Germany.</li>
<li>For the Channel Islands use <b>CI:</b> before the image ID, for example <tt class="bbcode">ci:1</tt> would link to image 1 on Geograph Channel Islands.</li>
</ul>

<h4>Image link</h4>

<div class="interestBox"><div class="example"><tt class="bbcode">[[5463]]</tt> shows as <span class="preview">{literal}{<a href="/photo/5463" title="Geograph Image by Ben Gamble">TQ3328 : Ardingly Reservoir</a>}{/literal}</span></div></div>

<p>Other Geograph project examples: <tt class="bbcode">[[ci:1]]</tt> or <tt class="bbcode">[[de:1]]</tt></p>


<h4>Thumbnail</h4>
<div class="interestBox"><div class="example"><tt class="bbcode">[[[5463]]]</tt> shows as <span class="preview"><a href="/photo/5463"><img alt="TQ3328 : Ardingly Reservoir" src="https://s3.geograph.org.uk/photos/00/54/005463_ea60a493_120x120.jpg" width="120" height="90"/></a></span></div></div>
<p>Other Geograph project examples: <tt class="bbcode">[[[ci:1]]]</tt> or <tt class="bbcode">[[[de:1]]]</tt></p>



<h4>Large thumbnail and caption</h4>

<div class="interestBox">
<div class="example">
<tt class="bbcode">[image id=5463]</tt> shows as 

<div class="photoguide"><div style="float:left;width:213px"><a title="TQ3328 : Ardingly Reservoir by Ben Gamble - click to view full size image" href="/photo/5463"><img alt="TQ3328 : Ardingly Reservoir by Ben Gamble" src="https://s3.geograph.org.uk/photos/00/54/005463_ea60a493_213x160.jpg" width="213" height="160" /></a><div class="caption"><a href="/gridref/TQ3328">TQ3328</a> : <a title="view full size image" href="/photo/5463">Ardingly Reservoir</a> by <a href="/profile/113">Ben Gamble</a></div></div><div style="float:left;padding-left:20px; width:400px;">Sailing club at the southern end of the reservoir<div style="text-align:right;font-size:0.8em">by Ben Gamble</a></div></div><br style="clear:both"/></div>

</div>
</div>

<p>Other Geograph project examples: <tt class="bbcode">[[[image id=ci:1]]]</tt> or <tt class="bbcode">[[[image id=de:1]]]</tt></p>



<h4>Large thumbnail and custom description</h4>


<div class="interestBox">
<div class="example">
<tt class="bbcode">[image id=5463 text=This is an example of a custom description.]</tt>
shows as 

<div class="photoguide"><div style="float:left;width:213px"><a title="TQ3328 : Ardingly Reservoir by Ben Gamble - click to view full size image" href="/photo/5463"><img alt="TQ3328 : Ardingly Reservoir by Ben Gamble" src="https://s3.geograph.org.uk/photos/00/54/005463_ea60a493_213x160.jpg" width="213" height="160" /></a><div class="caption"><a href="/gridref/TQ3328">TQ3328</a> : <a title="view full size image" href="/photo/5463">Ardingly Reservoir</a> by <a href="/profile/113">Ben Gamble</a></div></div><div style="float:left;padding-left:20px; width:400px;">This is an example of a custom description.</div><br style="clear:both"/></div>


</div>
</div>

</div>

<div class="threecolumn">

<h3>Grid reference links</h3>


<h4>Link to Browse page for a Square</h4>

<div class="interestBox"><tt class="bbcode">[[TQ7506]]</tt> shows as <span class="preview"><a href="/gridref/TQ7506">TQ7506</a></span> <span class="explain">(link to the browse page)</span></div>


<p>Text that looks like a Grid Reference in Discussion Post are identified and automatically formatted when posted to include the link formatting above.<br/>
To stop text that looks like a grid reference being converted, prefix the text with a !.</p>

<p>For example <tt class="bbcode">!B4567</tt> won't be linked but <tt class="bbcode">B4567</tt> would. The ! mark is not shown on the final page.<br>The automatic grid reference matching only applies where the text part of the grid reference is in uppercase.</p>
<div class="example"><tt class="bbcode">Drive along the !B4567 road.</tt> shows as <span class="preview">Drive along the B4567 road.</span></div></blockquote>




<h3>External links</h3>

<h4>Inserting a webpage link</h4>
<p>Pasting a URL into a post will result in the URL automatically being formatted as a link once posted.</p>
<div class="interestBox"><tt class="bbcode">http://www.example.com</tt> shows as <span class="preview"><a href="http://www.example.com">http://www.example.com</a></span></div>

<h4>Turning text into a webpage link</h4>
<div class="interestBox"><tt class="bbcode">[url=http://www.example.com]Some text[/url]</tt> shows as <span class="preview"><a href="http://www.example.com">Some text</a></span></div>

<h4>Email Address</h4>
<div class="interestBox"><tt class="bbcode">[email=address@example.com]contact me[/email]</tt> shows as <span class="preview"><a href="mailto:address@example.com">contact me</a></span></div>




<h3>Inserting images</h3>

<h4>Inserting images into posts</h4>
<p>External images can be inserted into discussion posts using the code below. You may wish to upload images to the <a href="http://media.geograph.org.uk/">Geograph Media Server</a> in order to do this.</p>
<div class="interestBox"><tt class="bbcode">[img]http://www.domain.com/image.gif[/img]</tt> shows as<br><br><img src="/templates/basic/img/hamster.gif"/></div>

<h4>Aligning images</h4>

<div class="interestBox"><tt class="bbcode">[imgleft]http://www.domain.com/image.gif[/img]</tt> will left align the image in the flow of text.</div>
<div class="interestBox"><tt class="bbcode">[imgright]http://www.domain.com/image.gif[/img]</tt> will right align the image in the flow of text.</div>

<p>Note: images over 640px <i>wide</i> will be reduced in size for display purposes.</p> 

</div>


</div>
<br style="clear:both"/>

{include file="_std_end.tpl"}

