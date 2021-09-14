{assign var="page_title" value="Geograph Calendar 2022 - Step 1"}
{include file="_std_begin.tpl"}


<h2>Step 1. Create Geograph Calendar</h2>

<p>{newwin href="/calendar/help.php" text="Open Help Page"} (in new window)</p>

<div id="preview" style="float:right;max-width:40vw" class=shadow></div>

<div style="max-width:700px;">

<p>Select 13 images, the <b>first image as the Cover image</b>, and then one per month. <br>
<i>Alternatively select 12 images, you will then will be able in Step 2 to choose one to use as cover image.</i></p>

<p>The cover image <b>must</b> be a landscape format, as will be cropped to fill the page. Monthly images can be other formats, and will be 
sized appropriately to fill as much of the page as possible.</p>

<p>While you can reorder the images in the next step, it may be much easier to select/enter the images in the right order here</p>

<p>Note: you need to select 12 different monthly images; the same image cannot be used for multiple months.</p>

</div>

<form method=post style="background-color:#eee;max-width:700px;padding:20px" name=theForm>

<b>Select Images by ID</b>: (enter unique id(s), or links to images/thumbnails, separated by spaces, commas or semicolons, or even in [])<br>

<div id="markedLink">If use the marked-list function, would be able to insert directly here</div>
<textarea name=ids id=theids rows=10 cols=80 onkeyup="parseIds(this)" onpaste="parseIds(this)"></textarea><br> 

<i>(Tip: You should be able to drag thumbnails direct from Browser or search results, into the box above, and just the ID will show. 
Don't need to add spaces/lines between the links!)</i>


<br>
<input type=submit id=subutton value="Add..." disabled>
</form>

{literal}
<style>
div.thumb {
	float:left;width:130px;height:140px;
}
</style>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script type="text/javascript">
//////////////////////////////////

var lastcnt = 0;
setInterval(function() {
	current = readCookie('markedImages');
	if (lastcnt != current.length) {
		if (current && current != '') {
			splited = current.commatrim().split(',');
			$('#markedLink').html('Marked Images['+(splited.length+0)+']: <a title="Insert marked image list" href="#" onclick="useMarked()">Paste Current Marked List</a>');
		} else {
			$('#markedLink').html('If use the marked-list function, would be able to insert directly here');
		}
	}
	lastcnt = current.length;
}, 1000);

function useMarked() {
	var ele = document.getElementById('theids');
	current = readCookie('markedImages');
	if (ele.value.length) {
		ele.value = ele.value + ', '+ current.commatrim();
	} else {
		ele.value = current.commatrim();
	}
	parseIds(ele);
}

//////////////////////////////////

// special support for catching drags.
// https://stackoverflow.com/questions/7237436/how-to-listen-for-drag-and-drop-plain-text-in-textarea-with-jquery
//Looks like you must cancel the dragover (and dragenter) event to catch the drop event in Chrome.

$(function() {
	$("textarea")
	    .bind("dragover", false)
	    .bind("dragenter", false)
	    .bind("drop", function(e) {
		var str = e.originalEvent.dataTransfer.getData("text") || e.originalEvent.dataTransfer.getData("text/plain");

		str = str.replace(/[\w:\/\.]*\/(\d{6,7})_\w{8}(_\w+)?\.jpg/g,'$1'); //replace any thumbnail urls with just the id.
		str = str.replace(/[\w:\/\.]+\/photo\/(\d{1,7})$/,'$1');

		if (this.value.length) {
		        this.value = this.value + ", "+ str;
		} else {
			this.value = str;
		}

		parseIds(this);
	    return false;
	});
});

//////////////////////////////////

var last;
var debounceDelay = null;
function parseIds(that) {
	var str= that.value;
	if (!str.length) {
		$('#preview').empty();
		$('#subutton').prop('disabled',true);
		return;
	}

		str = str.replace(/[\w:\/\.]*\/(\d{6,7})_\w{8}(_\w+)?\.jpg/g,'$1'); //replace any thumbnail urls with just the id.
	        str = str.replace(/[^\d]+/g,' ').replace(/(^ +| +$)/g,'');

	if (last && last == str)
		return;
	last = str;

	if (debounceDelay)
		clearTimeout(debounceDelay);
	debounceDelay = setTimeout(function() {
		debounceDelay = null;
		$('#preview').empty();
		var ids = str.split(/ /);
		for(var i=0;i<ids.length;i++) {
			if (ids[i]) {
				var id = ids[i];
				if ($('#a'+id).length == 0) {
					$('#preview').append('<div class=thumb><a href="/photo/'+id+'" id=a'+id+' target=_blank><img src=about:blank></a></div>');
					pupulateImage(id); //call a function, so it can use function closure!
				}
			}
		}
		$('#subutton').val("Add "+ids.length+" image"+(ids.length==1?'':'s')).prop('disabled',ids.length!=13 && ids.length!=12);
	}, 400);
}
setInterval(function() {
	 parseIds(document.getElementById('theids'));	
}, 1000);

var images = new Object();
function pupulateImage(id) {
	if (images[id]) {
		value = images[id];

		$('#a'+value.id+' img').prop('src',value.thumbnail);
		 $('#a'+value.id).prop('title',value.title+' by '+value.realname);
		return;
	}

        var data = {
                select: 'id,title,grid_reference,hash,realname,user_id',
                where: 'id='+id,
                limit: 1
        };

        $.ajax('https://api.geograph.org.uk/api-facetql.php',{
                data: data,
                cache: true,
                dataType: 'json'
        }).done(function(data){
                if (data && data.rows && data.rows.length) {
                        $.each(data.rows, function(index,value) {

                                value.thumbnail = getGeographUrl(value.id, value.hash, 'small');
				images[value.id] = value;

				$('#a'+value.id+' img').prop('src',value.thumbnail);
				 $('#a'+value.id).prop('title',value.title+' by '+value.realname);
			});
		}
	});
}


//////////////////////////////////

function getGeographUrl(gridimage_id, hash, size) {

        yz=zeroFill(Math.floor(gridimage_id/1000000),2);
        ab=zeroFill(Math.floor((gridimage_id%1000000)/10000),2);
        cd=zeroFill(Math.floor((gridimage_id%10000)/100),2);
        abcdef=zeroFill(gridimage_id,6);

        if (yz == '00') {
                fullpath="/photos/"+ab+"/"+cd+"/"+abcdef+"_"+hash;
        } else {
                fullpath="/geophotos/"+yz+"/"+ab+"/"+cd+"/"+abcdef+"_"+hash;
        }

        switch(size) {
                case 'full': return "https://s0.geograph.org.uk"+fullpath+".jpg"; break;
                case 'med': return "https://s"+(gridimage_id%4)+".geograph.org.uk"+fullpath+"_213x160.jpg"; break;
                case 'small':
                default: return "https://s"+(gridimage_id%4)+".geograph.org.uk"+fullpath+"_120x120.jpg";
        }
}

function zeroFill(number, width) {
        width -= number.toString().length;
        if (width > 0) {
                return new Array(width + (/\./.test(number)?2:1)).join('0') + number;
        }
        return number + "";
}

//////////////////////////////////


function unloadMess() {
        var ele = document.forms['theForm'].elements['ids'];
        if (ele.value == ele.defaultValue) {
                return;
        }
        return "**************************\n\nYou have unsaved changes in the content box.\n\n**************************\n";
}
//this is unreliable with AttachEvent
window.onbeforeunload=unloadMess;

function cancelMess() {
        window.onbeforeunload=null;
}
function setupSubmitForm() {
        AttachEvent(document.forms['theForm'],'submit',cancelMess,false);
}
AttachEvent(window,'load',setupSubmitForm,false);


{/literal}
</script>




{include file="_std_end.tpl"}


