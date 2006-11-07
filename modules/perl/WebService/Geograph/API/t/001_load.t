# -*- perl -*-
# t/001_load.t - check module loading and create testing directory

use lib "../lib" ; # FIXME: remove later on.

use Test::More ('no_plan'); 

BEGIN { use_ok( 'WebService::Geograph::API' ); }






