{assign var="page_title" value="User Timeline by Date Taken"}
{assign var="extra_meta" value="<script src='http://simile.mit.edu/timeline/api/timeline-api.js' type='text/javascript'></script>"}
{include file="_std_begin.tpl"}
<h2>{$page_title}</h2>
{dynamic}
<script type="text/javascript">{literal}

var tl;
function onLoad() {
  var eventSource = new Timeline.DefaultEventSource();
  var bandInfos = [
    Timeline.createBandInfo({
        showEventText:  false,
        trackHeight:    0.5,
        trackGap:       0.2,
        eventSource:    eventSource,
        date:           "{/literal}{$smarty.now|date_format:"%b %d %Y 00:00:00 GMT"}{literal}",
        width:          "30%", 
        intervalUnit:   Timeline.DateTime.YEAR, 
        intervalPixels: 200
    }),
    Timeline.createBandInfo({
    	eventSource:    eventSource,
        trackHeight:    1.4,
        date:           "{/literal}{$smarty.now|date_format:"%b %d %Y 00:00:00 GMT"}{literal}",
        width:          "70%", 
        intervalUnit:   Timeline.DateTime.MONTH, 
        intervalPixels: 100
    })
  ];
  bandInfos[0].syncWith = 1;
  bandInfos[0].highlight = true;
  bandInfos[0].eventPainter.setLayout(bandInfos[1].eventPainter.getLayout());
  
  var tl = Timeline.create(document.getElementById("my-timeline"), bandInfos);
  Timeline.loadXML("/api/UserTimeline/{/literal}{$user_id}{literal}", function(xml, url) { eventSource.loadXML(xml, url); });
}

var resizeTimerID = null;
function onResize() {
    if (resizeTimerID == null) {
        resizeTimerID = window.setTimeout(function() {
            resizeTimerID = null;
            tl.layout();
        }, 500);
    }
}
AttachEvent(window,'load',onLoad,false);
AttachEvent(window,'resize',onResize,false);

{/literal}</script>

{/dynamic} 

<div id="my-timeline" style="height: 500px; border: 1px solid #aaa; position:relative; font-size:10px;"></div>

{include file="_std_end.tpl"}
