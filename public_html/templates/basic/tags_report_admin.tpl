{include file="_std_begin.tpl"}

<div class="tabHolder">
	<a class="tab nowrap" href="?">Public Suggestion Form</a>
	<a class="tabSelected nowrap">Admin Suggestion Form</a>
	<a class="tab nowrap" href="?finder=1">Quick Tag Searcher</a>
	<a class="tab nowrap" href="?approver=1">Approve Suggestions</a>
</div>
<div class="interestBox">
	<h3>Make Tag Edit Suggestion</h3>
</div>

<div class="interestBox" style="margin:10px;margin-bottom:40px;">
<ul>
	<li>DO use this tool to fix small <i>typos</i> and <i>inconsistencies</i> in tags. Examples:<ul>
		<li><tt>Roadbidge</tt> (can change to <tt>roadbridge</tt>)</li>
		<!--li>national cycle route 68 (can change to <tt>national cycle network route 68</tt> to match other similar tags)</li-->
	</ul><br/></li>

	<li>DON'T use it change the <i>style</i> of the wording, BAD examples (including the visa versa): <ul>
		<li><tt>Church (former)</tt> to <tt>Former:church</tt></li>
		<li><tt>World War One</tt> to <tt>WWI</tt></li>
		<li><tt>road bridge</tt> to <tt>roadbridge</tt></li>
		<li><tt>footpath</tt> to <tt>path</tt></li>
	</ul><br/></li>

	<li>DON'T use it to deal with <i>bloat</i> (ie multiple tags for the same thing)<ul style="font-size:0.8em">
                <li>We have a SEPERATE feature for that - <b>synonyms</b> - we will later be adding a dedicated editor.</li>
	</ul><br/></li>

	<li>DON'T use it to edit <b>top:</b> or <b>bucket:</b> prefixed tags<br/><br/></li>

	<li>When using this form to <i>Split</i> a tag, an initial automatic suggestion is made in the 'new' box, but make sure you check that the tag is being split correctly.</li>

	<li>While you can use this tool to change the case of a tag, it's NOT recommended. In many cases the case of the tag is ignored (eg shown all lowercase). As well as been thankless, its also ineffectual!</li> 
</ul>
</div>

<form class="simpleform" method="post" name="theForm">

<fieldset style="width:800px">

{dynamic}

{if $message}
	<p>{$message}</p>
{/if}

<div class="field">
	{if $errors.tag}<div class="formerror"><p class="error">{$errors.tag}</p>{/if}

	<label for="tag">Tag:</label>
	<input type="text" name="tag" value="{$tag|escape:"html"}" size="20" onkeyup="{literal}if (this.value.length > 2) {loadTagSuggestions(this,event);} {/literal}" onpaste="loadTagSuggestions(this,event);" onmouseup="loadTagSuggestions(this,event);" oninput="loadTagSuggestions(this,event);"/>
	<input type="hidden" name="tag_id"/>
	<input type="hidden" name="tag_images"/>
	<input type="hidden" name="tag_text"/>

	<div id="tag-message" style="float:right"></div>

		<div style="position:relative;">
			<div style="position:absolute;top:0px;left:0px;background-color:lightgrey;margin-left:116px;padding-right:20px;display:none" id="tagParent">
				<div style="float:right">
					<a href="javascript:void($('#tagParent').hide())">X</a>
				</div>
				<ol id="taglist">
				</ol>
			</div>
		</div>

	<div class="fieldnotes">The tag you want to edit. If has a prefix, enter "prefix:tag"</div>

	{if $errors.tag}</div>{/if}
</div>

<div class="field">
	{if $errors.type}<div class="formerror"><p class="error">{$errors.type}</p>{/if}

	<label for="type">Type:</label>
	<select name="type" onchange=" typeChanged(this)">
	<option value=""></option>
	{html_options options=$types selected=$type}
	</select>

	<div class="fieldnotes">what type of edit is this</div>

	{if $errors.type}</div>{/if}
</div>


<div class="field" id="tag2div" style="display:none">
	{if $errors.tag2}<div class="formerror"><p class="error">{$errors.tag2}</p>{/if}

	<label for="tag2">New:</label>
	<input type="text" name="tag2" value="{$tag2|escape:"html"}" size="20" onkeyup="{literal}if (this.value.length > 2) {loadTagSuggestions(this,event);} {/literal}" onpaste="loadTagSuggestions(this,event);" onmouseup="loadTagSuggestions(this,event);" oninput="loadTagSuggestions(this,event);"/>

	<input type="hidden" name="tag2_id"/>
	<input type="hidden" name="tag2_images"/>
	<input type="hidden" name="tag2_text"/>

	<div id="tag2-message" style="float:right"></div>

		<div style="position:relative;">
			<div style="position:absolute;top:0px;left:0px;background-color:lightgrey;margin-left:116px;padding-right:20px;display:none" id="tag2Parent">
				<div style="float:right">
					<a href="javascript:void($('#tag2Parent').hide())">X</a>
				</div>
				<ol id="tag2list">
				</ol>
			</div>
		</div>


	<div class="fieldnotes">What this tag will be changed to.</div>

	{if $errors.tag2}</div>{/if}
</div>

<div class="field" id="splitdiv" style="display:none">
	<label for="tag2">New:</label>

	<div id="tags-message" style="float:right;font-size:0.8em"></div>

	<textarea name="tags" rows="5" cols="50" wordwrap=off onkeyup="checkMultiTags(this,event)" onpaste="checkMultiTags(this,event)" oninput="checkMultiTags(this,event)">
	</textarea>

        <div class="fieldnotes">Enter the seperate tags ONE PER LINE.</div>

</div>

<div class="field">
	<label for="tag2">Result:</label>
	<div class="fieldnotes" id="result"></div>
</div>


</fieldset>

<p>
<input type="submit" name="submit" value="Submit report..." style="font-size:1.1em" disabled/></p>
</form>


{/dynamic}
{literal}
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js" type="text/javascript"></script>
<script>

$(function() {
                if (location.hash.length) {
                        // skip the first character, we are not interested in the "#"
                        var query = location.hash.substring(1);

                        var pairs = query.split("&");
                        for (var i=0; i<pairs.length; i++) {
				var pos = pairs[i].indexOf("=");
                                var argname = pairs[i].substring(0,pos).toLowerCase();
                                var value = decodeURIComponent(pairs[i].substring(pos+1));
				if (argname == 't') {
					document.forms['theForm'].elements['tag'].value = value;
					loadTagSuggestions(document.forms['theForm'].elements['tag'],{keyCode:0, hidesuggestions:true});					
				}
			}
		}
});	

function typeChanged(that) {
	var value = that.options[that.selectedIndex].value;
	$('#tag2div, #splitdiv').hide();

	if ( value=='split') {
		$('#splitdiv').show();
		var text = that.form.elements['tag_text'].value;

		if (text.indexOf('.') > 1) {
			text = text.replace(/\s*\.\s*/g,"\n");
		} else if (text.indexOf(':') > 1) { //could well be splitting the prefix!
			text = text.replace(/\s*:\s*/g,"\n");
		} else if (text.indexOf(' ') > 1) {
			text = text.replace(/ +/g,"\n");
		}
		that.form.elements['tags'].value = text;
		that.form.elements['tags'].rows = text.split(/\n/).length+2;
		that.form.elements['tag2'].value = '';
		
		checkMultiTags(that.form.elements['tags'],{});

	} else if (value != '') {
		$('#tag2div').show();
		that.form.elements['tags'].value = '';
		that.form.elements['tag2'].value = that.form.elements['tag_text'].value;
	}
}
function useIt(text,which) {
	var ele = document.forms['theForm'].elements[which];
	$('#'+which+'Parent').hide();
	ele.value = text;
	loadTagSuggestions(ele,{keyCode:0, hidesuggestions:true});
}

var replies = 0;
function checkMultiTags(that,event) {
	var text = that.value.replace(/\r/g,'');
	tags = text.split(/\s*\n\s*/);
	
	var div = $('#tags-message').empty();

	$('#result').html('The ['+text+'] will be removed from <b>'+that.form.elements['tag_images'].value+'</b> images, and the <span id=tagsStr></span> tags will be added instead. Afterwards the ['+text+'] will be deleted.');

	replies = 0;
	for(q=0;q<tags.length;q++) {
		div.append('<div id="result'+q+'"></div>');

		//function used to take advantage of closures!
		checkTag(that,q,tags[q],tags.length);
	}
}

	function checkTag(that,q,tag,total) {
		param = 'q='+encodeURIComponent(tag);
                $.getJSON("/tags/tag.json.php?"+param,
                function (data) {
                        if (data && data.tag_id) {

                                var text = data.tag;
                                if (data.prefix) {
                                        text = data.prefix+':'+text;
                                }
                                text = text.replace(/<[^>]*>/ig, "");
                                text = text.replace(/['"]+/ig, " ");

				str = "[<b>"+text+"</b>]";
                                if (data.images) {
                                        str = str + " "+data.images+" images";
                                }

                                if (data.users) {
                                        str = str + ", "+data.users+" users";
                                }
				$('#tagsStr').append('[<b>'+text+'</b>], ');
			} else if (data.error) {
				str = data.error;
                        } else {
				str = "<span style=color:red>[<b>"+tag+"</b>] not existing tag</span>";
				$('#tagsStr').append('[<b>'+tag+'</b>], ');
			}
			$('#result'+q).html(str);
			replies++;
			if (replies == total) {
				$('#tagsStr').html($('#tagsStr').html().replace(/, $/,'').replace(/, \[([^\[]+)\]$/,' and [$1]'));
				that.form.elements['submit'].disabled = false;
			}
		});
	}


	function loadTagSuggestions(that,event) {

		var unicode=event.keyCode? event.keyCode : event.charCode;
		if (unicode == 13) {
			$('#'+that.name+'Parent').hide();
			return;
		}

		param = 'q='+encodeURIComponent(that.value);

		if (!event.hidesuggestions)
		$.getJSON("/tags/tags.json.php?"+param+"&counts=1",

		// on search completion, process the results
		function (data) {
			var div = $('#'+that.name+'list').empty();

			if (data && data.length > 0) {
				if (data.length == 1) {
					$('#'+that.name+'Parent').hide();
					return;
				}

				$('#'+that.name+'Parent').show();
				for(var tag_id in data) {
					var text = data[tag_id].tag;
					if (data[tag_id].prefix && data[tag_id].prefix!='term' && data[tag_id].prefix!='category' && data[tag_id].prefix!='cluster' && data[tag_id].prefix!='wiki') {
						text = data[tag_id].prefix+':'+text;
					}
					text = text.replace(/<[^>]*>/ig, "");
					text = text.replace(/['"]+/ig, " ");

					div.append("<li value=\""+data[tag_id].images+"\"><a href=\"javascript:void(useIt('"+text+"','"+that.name+"'))\">"+text+"</a></li>");
				}
			} else {
				$('#'+that.name+'Parent').hide();
			}
		});


		$.getJSON("/tags/tag.json.php?"+param+((that.name == 'tag')?'&expand=1':''),

		// on search completion, process the results
		function (data) {
			var div = document.getElementById(that.name+'-message');
			that.form.elements[that.name+'_id'].value = '';

			if (data && data.tag_id) {

				var text = data.tag;
				if (data.prefix) {
					text = data.prefix+':'+text;
				}
				text = text.replace(/<[^>]*>/ig, "");
				text = text.replace(/['"]+/ig, " ");


				str = 'Found [<b><a href="/search.php?tag='+encodeURIComponent(text)+'" target="_blank">'+text+'</a></b>]';

				if (data.images) {
					str = str + " used by "+data.images+" images";
					that.form.elements[that.name+'_images'].value = data.images;
				}

				if (data.users) {
					str = str + ", by "+data.users+" users";
				}

				that.form.elements[that.name+'_id'].value = data.tag_id;
				that.form.elements[that.name+'_text'].value = text;

				if (that.name == 'tag') {
					that.form.elements['submit'].disabled = true;
					that.form.elements['type'].selectedIndex = 0;
					typeChanged(that.form.elements['type']);
				}

				if (data.tag_id) {
					$.getJSON("/tags/report.php?lookup=1&tag_id="+encodeURIComponent(data.tag_id),function(data2) {
						if (data2.length > 0) {
							var msg = '';
							for(q=0;q<data2.length;q++) {
								msg = msg + "<br/>We already have a report for '"+data2[q].tag+"' &gt; '"+data2[q].tag2+"'";
							}
							$('#'+that.name+'-message').html($('#'+that.name+'-message').html()+msg);
						}
					});
				}

			} else if (data.error) {
				if (that.name == 'tag') {
					str = data.error;
					that.form.elements['submit'].disabled = true;
				} else {
					str = 'no tags/images';
				}
			} else {
				if (that.name == 'tag') {
					str = "tag not found!";
					that.form.elements['submit'].disabled = true;
				} else {
					str = "no tags/images";
				}
			}
			$('#'+that.name+'-message').html(str);

			var elements = that.form.elements;

			if (elements['tags'].value.length > 0) {
				$('#result').text('Nothing to do here');
			} else if (elements['tag2'].value.length == 0) {
				$('#result').text('Please enter the new tag!');
			} else if (elements['tag_text'].value.toLowerCase() == elements['tag2'].value.toLowerCase()) {
				$('#result').text('Non changes made - no action taken');
			} else if (elements['tag2_id'].value.length == 0 && parseInt(elements['tag_images'].value,10) > 0) {
				$('#result').text('No Matching tag found - new ['+elements['tag2'].value+'] tag will be created, and ALL '+elements['tag_images'].value+' images will be moved to it');
				that.form.elements['submit'].disabled = false;
			} else if (elements['tag2_id'].value.length > 0 && parseInt(elements['tag_images'].value,10) > 0) {
				$('#result').text('Matching tag found - ALL '+elements['tag_images'].value+' images will be moved to existing ['+elements['tag2_text'].value+'] tag');
				that.form.elements['submit'].disabled = false;
			} else {
				$('#result').text('UNKNOWN!');
			}
			if (elements['tag_text'].value.length > 0 && elements['tag2'].value.length > 0) {
				l = levenshtein(elements['tag_text'].value,elements['tag2'].value);
				if (l > 2) {
					$('#result').append("<p style=background-color:pink><big>You are changing <b>"+l+" charactors</b> - please ONLY use this tool to correct typos etc, DON'T use it to change the meaning of a tag (<b>However tempting it may be</b>).</big></p>");

				}
			}

		});
	}

function levenshtein (s1, s2) {
  // http://kevin.vanzonneveld.net
  // +            original by: Carlos R. L. Rodrigues (http://www.jsfromhell.com)
  // +            bugfixed by: Onno Marsman
  // +             revised by: Andrea Giammarchi (http://webreflection.blogspot.com)
  // + reimplemented by: Brett Zamir (http://brett-zamir.me)
  // + reimplemented by: Alexander M Beedie
  // *                example 1: levenshtein('Kevin van Zonneveld', 'Kevin van Sommeveld');
  // *                returns 1: 3
  if (s1 == s2) {
    return 0;
  }

  var s1_len = s1.length;
  var s2_len = s2.length;
  if (s1_len === 0) {
    return s2_len;
  }
  if (s2_len === 0) {
    return s1_len;
  }

  // BEGIN STATIC
  var split = false;
  try {
    split = !('0')[0];
  } catch (e) {
    split = true; // Earlier IE may not support access by string index
  }
  // END STATIC
  if (split) {
    s1 = s1.split('');
    s2 = s2.split('');
  }

  var v0 = new Array(s1_len + 1);
  var v1 = new Array(s1_len + 1);

  var s1_idx = 0,
    s2_idx = 0,
    cost = 0;
  for (s1_idx = 0; s1_idx < s1_len + 1; s1_idx++) {
    v0[s1_idx] = s1_idx;
  }
  var char_s1 = '',
    char_s2 = '';
  for (s2_idx = 1; s2_idx <= s2_len; s2_idx++) {
    v1[0] = s2_idx;
    char_s2 = s2[s2_idx - 1];

    for (s1_idx = 0; s1_idx < s1_len; s1_idx++) {
      char_s1 = s1[s1_idx];
      cost = (char_s1 == char_s2) ? 0 : 1;
      var m_min = v0[s1_idx + 1] + 1;
      var b = v1[s1_idx] + 1;
      var c = v0[s1_idx] + cost;
      if (b < m_min) {
        m_min = b;
      }
      if (c < m_min) {
        m_min = c;
      }
      v1[s1_idx + 1] = m_min;
    }
    var v_tmp = v0;
    v0 = v1;
    v1 = v_tmp;
  }
  return v0[s1_len];
}

</script>
{/literal}

{include file="_std_end.tpl"}

