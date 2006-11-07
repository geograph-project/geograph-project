# -*- perl -*-
# t/001_load.t - check module loading and create testing directory

use lib "../lib" ; # FIXME: remove later on.
use Test::More 'no_plan' ;

use WebService::Geograph::API ;

my $api = WebService::Geograph::API->new ({ 'key' => 'dummy_key' });
isa_ok ($api, 'WebService::Geograph::API' );

my $noapi = WebService::Geograph::API->new() ;
is ($noapi, undef, 'Did not create API without a key.') ;

my $rh_valid_modes = {
	'csv'    => 'CSV',
	'search' => 'custom searching',
} ;

foreach (keys %$rh_valid_modes) {
	my $lookup = $api->lookup( $_ , { 'q' => '123' } ) ;
	ok ($lookup, 'lookup works') ;
		
}
