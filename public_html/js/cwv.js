
/*
 * https://bigcommerce.websiteadvantage.com.au/tag-rocket/articles/core-web-vitals-ga4-bigquery-data-studio/
 * v3.1
 */
function sendToServerCWV({name, delta, value, id, navigationType, rating, attribution}) {

  var debugTarget = attribution ? attribution.largestShiftTarget||attribution.element||attribution.eventTarget||'' : '(not set)';

  var data = JSON.stringify({
    // Built-in params:
    name: name,
    value: delta, // Use `delta` so the value can be summed.
    // Custom params:
    metric_id: id, // Needed to aggregate events.
    metric_value: value, // Optional.
    metric_delta: delta, // Optional.
    navigation: navigationType,

    // OPTIONAL: any additional params or debug info here.
    // See: https://web.dev/debug-web-vitals-in-the-field/
    // metric_rating: 'good' | 'needs-improvement' | 'poor'. 'needs-improvement' was 'ni'
    metric_rating: rating,
    // debug_info
    debug_target: debugTarget,
    debug_event: attribution ? attribution.eventType||'' : '',
    debug_timing: attribution ? attribution.loadState||'' : '',
    event_time: attribution ? attribution.largestShiftTime||(attribution.lcpEntry&&attribution.lcpEntry.startTime)||attribution.eventTime||'': ''
  });

  navigator.sendBeacon("/stuff/record_cwv.php", data);
}

if ('sendBeacon' in navigator) {

  var script = document.createElement('script');
  script.src = 'https://unpkg.com/web-vitals@3.0.0/dist/web-vitals.attribution.iife.js';
  script.onload = function() {
    // When loading `web-vitals` using a classic script, all the public
    // methods can be found on the `webVitals` global namespace.
    webVitals.onCLS(sendToServerCWV);
    webVitals.onFID(sendToServerCWV);
    webVitals.onLCP(sendToServerCWV);
    webVitals.onFCP(sendToServerCWV);
    webVitals.onTTFB(sendToServerCWV);
    webVitals.onINP(sendToServerCWV);
  }
  document.head.appendChild(script);

}

