# -*- perl -*-
# t/001_load.t - check module loading and create testing directory

use lib "../lib" ; # FIXME: remove later on.

use Test::More ('no_plan'); 
use Test::Warn ;

BEGIN { use_ok( 'WebService::Geograph::API' ); }

my $api_key = WebService::Geograph::API->new ({ 'key' => 'dummy_key' });
isa_ok ($api_key, 'WebService::Geograph::API', 'created a new API instance');

my $api_no_key = WebService::Geograph::API->new ();
is ($api_no_key, undef, 'did not create API instance with no key') ;



