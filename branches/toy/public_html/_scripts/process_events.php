<?

require_once('geograph/global.inc.php');

set_time_limit(5000);

if(extension_loaded('newrelic'))
        newrelic_background_job(true);


//in reality we have a whole event system for processing events. This is a basic processor that does nothing. Maybe we could do a carrot2 job here?

$db = GeographDatabaseConnection(false); //needs to ba master connection

$db->Execute("LOCK TABLES event WRITE");

if ($event= $db->getRow("SELECT * FROM event WHERE status = 'pending' ORDER BY priority, posted LIMIT 1")) {

	$event_id=$event['event_id'];

	$db->Execute("update event set status='in_progress' where event_id=$event_id");

        $db->Execute("UNLOCK TABLES");

	sleep(rand(2,5));

	$db->Execute("update event set status='completed',processed=now() where event_id=$event_id");
}
