{assign var="page_title" value="Guide"}
{include file="_std_begin.tpl"}

{literal}
<script language="JavaScript">
<!--
function popupOSMap(gridref)
{
        var wWidth = 740;
        var wHeight = 520;
        var wLeft = Math.round(0.5 * (screen.availWidth - wWidth));
        var wTop = Math.round(0.5 * (screen.availHeight - wHeight)) - 20;
        
        var newWin = window.open('http://getamap.ordnancesurvey.co.uk/getamap/frames.htm?mapAction=gaz&gazName=g&gazString='+gridref, 
		'gam',
		'left='+wLeft+',screenX='+wLeft+',top='+wTop+',screenY='+wTop+',width='+wWidth+',height='+wHeight+',status,scrolling=no');
}

//-->
</script>
{/literal}


<h2>Geographing Guide</h2>

<p>A good Geograph presents images and information on the main human and 
physical geographical feature(s) present in any given ordnance survey 1km 
grid square.
</p>

 

<p>To claim a square at least one photograph must be taken from within the 
grid square.</p>

<p>Photos need not be master works of art, the important thing is the human 
and physical geographical information they contain.</p>

<p>You can plan your Geograph using a hardcopy map or by visiting the 
<a title="Ordnance Survey Get-a-Map" href="http://www.ordnancesurvey.co.uk/oswebsite/getamap/">Ordnance 
Survey Get-a-Map</a> website or for really high tech geographing, you could use a 
Pocket PC or GPS equipped with mapping software (for example Memory Map).</p>

<h3>Example</h3>

<div style="float:right;width:400px;">
<img src="/templates/basic/img/guide_map.png" width="400" height="400" alt="Map of SD5300"> 
<div class="copyright">Image produced from the Ordnance Survey Get-a-map service. Image reproduced with 
kind permission of Ordnance Survey and Ordnance Survey of Northern Ireland.</div>
</div>

<p>
I entered my home village of Billinge in the search box on the 
<a title="Ordnance Survey Get-a-Map" href="javascript:popupOSMap('SD535005')">Get-a-Map</a> 
page and the result was this map (The purple spot indicates the centre of the 
SD5300 grid square).</p>




 

<p>The Getamap site also allows searching by grid reference and using this method 
you can select a grid reference from our website that hasn't yet been Geographed 
and look at its main features.</p>

 

<p>Our selected map square is primarily a residential area with a staggered cross 
road at the junction of the A571 and B5207. There is a church at the crossroads at 
533007 and a school close to the centre of the square in the heart of the residential 
area. From local knowledge, open farmland dominates the Eastern third of the grid 
square.</p>

<h3>Sample photographs for  SD5300</h3>

<p>Examples of representative photographs for the above location:</p>

<div class="photo66">
<img src="/templates/basic/img/guide1.jpg" width="422" height="317" alt="Photo of SD5300"> 
<div class="caption">B5207 junction with Wigan Road/Main Street showing the Parish Church of St Aidan</div>
</div>


<div class="photo66">
<img src="/templates/basic/img/guide2.jpg" width="422" height="317" alt="Photo of SD5300"> 
<div class="caption">entrance to St Aidans Junior and Infant school within the surrounding residential area</div>
</div>
<div class="photo66">
<img src="/templates/basic/img/guide3.jpg" width="422" height="317" alt="Photo of SD5300"> 
<div class="caption">open farmland to the East of the grid square looking South towards Blackley Hurst Hall Farm from the B5207</div>
</div>



 

{include file="_std_end.tpl"}
