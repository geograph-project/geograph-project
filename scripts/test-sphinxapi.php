<?

############################################

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

require_once ( "3rdparty/sphinxapi.php" );


$offset = 0;
$q = "bridge";
$index = 'gi_stemmed';


if (!empty($argv[1])) {
    $CONF['sphinx_host'] = $argv[1];
}
print("Using server: $CONF[sphinx_host]\n");


                $cl = new SphinxClient ();
                $cl->SetServer ( $CONF['sphinx_host'], $CONF['sphinx_port'] );
                $cl->SetLimits($offset,25);
                $res = $cl->Query ( $q, $CONF['sphinx_prefix'].$index );

                // --------------

                if ( $res===false )
                {
                        if ( $cl->GetLastError() )
                                print "\nError: " . $cl->GetLastError() . "\n\n";
                        print "\tQuery failed: -- please try again later.\n";
                        exit;
                } else
                {
                        if ( $cl->GetLastWarning() )
                                print "\nWARNING: " . $cl->GetLastWarning() . "\n\n";

                        $query_info = "Query '$q' retrieved ".count($res['matches'])." of $res[total_found] matches in $res[time] sec.\n";
                }

		print $query_info;
		if (!empty($res['matches'])) {
			foreach ($res['matches'] as $id => $row) {
				print "{$id} [{$row['weight']}] => ".implode(',',$row['attrs'])."\n";
			}
		}
		//print_r($res);
		print "\n\n";
