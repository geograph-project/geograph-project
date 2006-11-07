

use ExtUtils::MakeMaker;
# See lib/ExtUtils/MakeMaker.pm for details of how to influence
# the contents of the Makefile that is written.
WriteMakefile(
    NAME         => 'WebService::Geograph::API',
    VERSION_FROM => 'lib/WebService/Geograph/API.pm', # finds \$VERSION
    AUTHOR       => 'Spiros Denaxas (s [dot] denaxas [@] gmail [dot]com)',
    ABSTRACT     => 'Perl interface to the Geograph.co.uk API',
    PREREQ_PM    => {
                     'Test::Simple' => 0.44,
                    },
);
