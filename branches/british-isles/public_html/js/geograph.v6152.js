var IE=document.all?true:false;
function popupOSMap(a,b){if(!a&&b.length)a=b;b=Math.round(0.5*(screen.availWidth-740));var d=Math.round(0.5*(screen.availHeight-520))-20;if(a.length>0){if(a.length<7)a=a.substr(0,a.length-2)+"5"+a.substr(a.length-2,2)+"5";a=window.open("http://getamap.ordnancesurvey.co.uk/getamap/frames.htm?mapAction=gaz&gazName=g&gazString="+a,"gam","left="+b+",screenX="+b+",top="+d+",screenY="+d+",width=740,height=520,status,scrolling=no")}else window.open("http://getamap.ordnancesurvey.co.uk/getamap/frames.htm","gam",
"left="+b+",screenX="+b+",top="+d+",screenY="+d+",width=740,height=520,status,scrolling=no")}function setCaretTo(a,b){if(a.createTextRange){a=a.createTextRange();a.move("character",b);a.select()}else if(a.selectionStart){a.focus();a.setSelectionRange(b,b)}}function tabClick(a,b,d,e){for(var f=1;f<=e;f++){document.getElementById(a+f).className=d==f?"tabSelected":"tab";if(b!="")document.getElementById(b+f).style.display=d==f?"":"none"}}
function autoDisable(a){a.value="Submitting... Please wait...";name="document."+a.form.name+"."+a.name;setTimeout(name+".disabled = true",100);setTimeout(name+".value="+name+".defaultValue; "+name+".disabled = false",3E4);return true}function record_vote(a,b,d){(new Image).src="/stuff/record_vote.php?t="+a+"&id="+b+"&v="+d;document.getElementById("votediv"+b).innerHTML="Thank you!"}
function star_hover(a,b,d){for(d=1;d<=b;d++)document.images["star"+d+a].src=document.images["star"+d+a].src.replace(/light/,"on")}function star_out(a,b){for(var d=1;d<=b;d++)document.images["star"+d+a].src=document.images["star"+d+a].src.replace(/-on/,"-light")}function di20(a,b){if(a=FWFindImage(document,a,0))a.src=b}
function FWFindImage(a,b,d){var e=false;if(a.getElementById)e=a.getElementById(b);if(e)return e;if(a.images)e=a.images[b];if(e)return e;if(a.layers)for(d=0;d<a.layers.length;d++)if(e=FWFindImage(a.layers[d].document,b,0))return e;return false}
function setdate(a,b,d){parts=b.split("-");parts[2]=parseInt(parts[2],10);parts[1]=parseInt(parts[1],10);ele=d.elements[a+"Year"].options;for(i=0;i<ele.length;i++)if(ele[i].value==parts[0])ele[i].selected=true;ele=d.elements[a+"Month"].options;for(i=0;i<ele.length;i++)if(parseInt(ele[i].value,10)==parts[1])ele[i].selected=true;ele=d.elements[a+"Day"].options;for(i=0;i<ele.length;i++)if(parseInt(ele[i].value,10)==parts[2])ele[i].selected=true}
function onChangeImageclass(){if(document.getElementById("otherblock")){var a=document.getElementById("imageclass");a=a.selectedIndex==a.options.length-1;document.getElementById("otherblock").style.display=a?"":"none"}}function unescapeHTML_function(){var a=document.createElement("div");a.innerHTML=this;return a.childNodes[0]?a.childNodes[0].nodeValue:""}function fakeUnescapeHTML_function(){return this}String.prototype.unescapeHTML=document.getElementById?unescapeHTML_function:fakeUnescapeHTML_function;
function populateImageclass(){var a=document.getElementById("imageclass"),b=a.options,d=a.selectedIndex,e=null;if(d>0)e=b[d].value;var f=new Option(b[0].text,b[0].value);d=new Option(b[b.length-1].text,b[b.length-1].value);for(q=b.length;q>=0;q-=1)b[q]=null;b.length=0;b[0]=f;newselected=-1;if(typeof catListUser!="undefined"&&catListUser.length>1){for(i=0;i<catListUser.length;i++)if(catListUser[i].length>0){act=catListUser[i].unescapeHTML();f=new Option(act,act);if(e==act){f.selected=true;newselected=
b.length}b[b.length]=f}f=new Option("-----","-----");b[b.length]=f}for(i=0;i<catList.length;i++)if(catList[i].length>0){act=catList[i].unescapeHTML();f=new Option(act,act);if(e==act){f.selected=true;newselected=b.length}b[b.length]=f}if(newselected<1&&e!=null){document.getElementById("imageclassother").value=e;e="Other"}else a.selectedIndex=newselected;b[b.length]=d;if(e!=null&&e=="Other")a.selectedIndex=b.length-1;onChangeImageclass()}var hasloaded=false;
function prePopulateImageclass(){if(!hasloaded){var a=document.getElementById("imageclass");a.disabled=false;var b=a.options[0].text;a.options[0].text="please wait...";populateImageclass();hasloaded=true;a.options[0].text=b;if(document.getElementById("imageclass_enable_button"))document.getElementById("imageclass_enable_button").disabled=true}}
function checkstyle(a,b,d){var e=true,f=null,g=a.value;if(g.length>1){if(g.substr(0,1).match(/[a-z]/)){e=false;f="missing initial capital"}if(g.toUpperCase()==g||g.toLowerCase()==g){e=false;f="single case"}var h=g.substr(-1),j=g.substr(-3);if(b=="title"&&h=="."&&j!="..."){e=false;f="full stop"}if(d&&!g.match(/ /)){e=false;f="very short"}if(b=="comment"&&a.form.title.value==g){e=false;f="duplicate of title"}}document.getElementById(b+"style").style.display=e?"none":"";document.getElementById(b+"stylet").innerHTML=
f?"("+f+")":"";document.getElementById("styleguidelink").style.backgroundColor=e?"":"yellow"}
function markImage(a){current=readCookie("markedImages");newtext="marked";if(current){re=new RegExp("\\b"+a+"\\b");if(current==a||current.search(re)>-1){newCookie=current.replace(re,",").commatrim();newtext="Mark"}else newCookie=current+","+a}else newCookie=a.toString();createCookie("markedImages",newCookie,10);if(document.getElementById("marked_number"))if(newCookie){splited=newCookie.commatrim().split(",");document.getElementById("marked_number").innerHTML="["+(splited.length+0)+"]"}else document.getElementById("marked_number").innerHTML=
"[0]";ele=document.getElementById("mark"+a);if(ele.innerText!=undefined)ele.innerText=newtext;else ele.textContent=newtext}function markAllImages(a){for(var b=0;b<document.links.length;b++)document.links[b].text==a&&markImage(document.links[b].id.substr(4))}String.prototype.commatrim=function(){return this.replace(/^,+|,+$/g,"").replace(/,,/g,",")};
function importToMarkedImages(){(newCookie=readCookie("markedImages"))||(newCookie=new String);if((list=prompt("Paste your current list, either comma or space separated\n or just surrounded with [[[ ]]] ",""))&&list!=""){splited=list.split(/[^\d]+/);for(i=count=0;i<splited.length;i++){image=splited[i];if(image!="")if(newCookie.search(new RegExp("\\b"+image+"\\b"))==-1){newCookie=newCookie+","+image;count+=1}}createCookie("markedImages",newCookie,10);showMarkedImages();leng=newCookie.commatrim().split(",").length;
alert("Added "+count+" image(s) to your list, now contains "+leng+" images in total.")}else alert("Nothing to add")}function displayMarkedImages(){if(current=readCookie("markedImages")){splited=current.commatrim().split(",");newstring="[[["+splited.join("]]] [[[")+"]]]";prompt("Copy and Paste the following into the forum",newstring)}else alert("You haven't marked any images yet. Or cookies are disabled")}
function returnMarkedImages(){if(current=readCookie("markedImages")){splited=current.commatrim().split(",");return"[[["+splited.join("]]] [[[")+"]]]"}else{alert("You haven't marked any images yet. Or cookies are disabled");return""}}
function showMarkedImages(){if(current=readCookie("markedImages")){splited=current.commatrim().split(",");var a=document.getElementsByTagName("body")[0].innerText!=undefined?true:false;for(i=0;i<splited.length;i++)if(document.getElementById("mark"+splited[i])){ele=document.getElementById("mark"+splited[i]);if(a)ele.innerText="marked";else ele.textContent="marked"}if(document.getElementById("marked_number"))document.getElementById("marked_number").innerHTML="["+(splited.length+0)+"]"}}
function clearMarkedImages(){if((current=readCookie("markedImages"))&&confirm("Are you sure?")){splited=current.commatrim().split(",");var a=document.getElementsByTagName("body")[0].innerText!=undefined?true:false;for(i=0;i<splited.length;i++)if(document.getElementById("mark"+splited[i])){ele=document.getElementById("mark"+splited[i]);if(a)ele.innerText="Mark";else ele.textContent="Mark"}eraseCookie("markedImages");alert("All images removed from your list");if(document.getElementById("marked_number"))document.getElementById("marked_number").innerHTML=
"[0]"}}function createCookie(a,b,d){if(d){var e=new Date;e.setTime(e.getTime()+d*24*60*60*1E3);d="; expires="+e.toGMTString()}else d="";document.cookie=a+"="+b+d+"; path=/"}function readCookie(a){for(var b=document.cookie.split(";"),d=0;d<b.length;d++){for(var e=b[d].split("="),f=e[0];f.charAt(0)==" ";)f=f.substring(1,f.length);if(f==a)return e[1]}return false}function eraseCookie(a){createCookie(a,"",-1)}
function show_tree(a){document.getElementById("show"+a).style.display="";document.getElementById("hide"+a).style.display="none"}function hide_tree(a){document.getElementById("show"+a).style.display="none";document.getElementById("hide"+a).style.display=""}
function collapseSnippets(a){var b;for(c=1;c<=a;c++)if((b=document.getElementById("snippet"+c))&&b.clientHeight>118){b.className+=" snippetcollapsed";var d=document.createElement("div");d.id="hidesnippetf"+c;d.className="snippetfooter";d.innerHTML=" ";b.appendChild(d);d=document.createElement("div");d.id="hidesnippet"+c;d.className="snippetexpander";d.innerHTML='<a href="javascript:void(expandSnippet('+c+'));">+ Further information</a>';b.appendChild(d)}}
function expandSnippet(a){var b=document.getElementById("snippet"+a);b.className=b.className.replace(/ snippetcollapsed/,"");b=document.getElementById("hidesnippet"+a);b.style.display="none";b=document.getElementById("hidesnippetf"+a);b.style.display="none";return null}var marker1left=14,marker1top=14,marker2left=14,marker2top=14;
function overlayHideMarkers(a){if(IE){tempX=event.offsetX;tempY=event.offsetY}else{tempX=a.layerX;tempY=a.layerY}a=document.getElementById("marker1");m1left=parseInt(a.style.left)+marker1left;m1top=parseInt(a.style.top)+marker1top;found=false;a.style.display=Math.abs(tempX-m1left)<marker1left&&Math.abs(tempY-m1top)<marker1top?"none":displayMarker1?"":"none";a=document.getElementById("marker2");m2left=parseInt(a.style.left)+marker2left;m2top=parseInt(a.style.top)+marker2top;a.style.display=Math.abs(tempX-
m2left)<marker2left&&Math.abs(tempY-m2top)<marker2top?"none":displayMarker2?"":"none";return false}function rawurldecode(a){var b={};a=a.toString();var d=function(e,f,g){var h=[];h=g.split(e);return h.join(f)};b["'"]="%27";b["("]="%28";b[")"]="%29";b["*"]="%2A";b["~"]="%7E";b["!"]="%21";for(replace in b){searchstr=b[replace];a=d(searchstr,replace,a)}return a=decodeURIComponent(a)}
function AttachEvent(a,b,d,e){e||(e=false);if(a.addEventListener){a.addEventListener(b,d,e);return true}else if(a.attachEvent)return a.attachEvent("on"+b,d);else{MyAttachEvent(a,b,d);a["on"+b]=function(){MyFireEvent(a,b)}}}function MyAttachEvent(a,b,d){if(!a.myEvents)a.myEvents={};a.myEvents[b]||(a.myEvents[b]=[]);a=a.myEvents[b];a[a.length]=d}function MyFireEvent(a,b){if(!(!a||!a.myEvents||!a.myEvents[b])){a=a.myEvents[b];b=0;for(var d=a.length;b<d;b++)a[b]()}};