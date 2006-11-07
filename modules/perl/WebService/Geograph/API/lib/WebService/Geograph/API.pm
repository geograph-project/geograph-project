package WebService::Geograph::API;

use strict;
use warnings ;

use WebService::Geograph::Request ;
use WebService::Geograph::Response ;

use LWP::UserAgent ;
use Data::Dumper ;

our @ISA = qw ( LWP::UserAgent ) ;
our $VERSION = '0.01' ;


sub new {
	my $class = shift ;
	my $rh_options = shift ;
	
	unless (defined ($rh_options->{key}) and ($rh_options->{key})) {
		warn "You must obtain a valid key before using the Geograph API service.\n" .
		     "Visit http://www.geograph.org.uk/help/api for more information.\n" ;
		return undef ;
	}	
	
	# Please do not change the following parameter. 
	# It does not provide geograph.co.uk with any personal information
	# but helps then track usage of this module.
		
	my %options = ( 'agent' => 'WebService::Geograph::API' ) ;
	my $self = new LWP::UserAgent ( %options );
	$self->{key} = $rh_options->{key} ;
	
	bless $self, $class ;
	return $self ;	

}

sub lookup {
	my ($self, $mode, $args) = (@_) ;
	
	return unless ((defined $mode) && (defined $args)) ;
	return unless ref $args eq 'HASH' ;
	
	$args->{key} = $self->{key} ;
	
	my $request = new WebService::Geograph::Request (  $mode , $args  ) ;	
	
	$self->execute_request($request) ;
}

sub execute_request {
	my ($self, $request) = (@_) ;	
  my $url = $request->encode_args() ;
	
  my $response = $self->get($url) ;
	bless $response, 'WebService::Geograph::Response' ;
	
	unless ($response->{_rc} = 200) {
	  $response->set_fail(0, "API returned a non-200 status code: ($response->{_rc})") ;
		return $response ;
  }
	
	$self->create_results_node($request, $response) ;
	
}

sub create_results_node {
	my ($self, $request, $response) = (@_) ;
	
	if ($request->{mode} eq 'csv') {
		 if (defined $response->{_content}) {
				my $csv_data = $response->{_content} ;
				$response->set_success($csv_data) ;
				return $response ;	
		  }
	}
	
	elsif ($request->{mode} eq 'search') {
		if (defined $response->{_previous}->{_headers}->{location}) {
			my $location = $response->{_previous}->{_headers}->{location} ;
			$response->set_success($location) ;
			return $response ;			
		}
}
		
	
	
	
	
}

#################### main pod documentation begin ###################
## Below is the stub of documentation for your module. 
## You better edit it!


=head1 NAME

WebService::Geograph::API - Module abstract (<= 44 characters) goes here

=head1 SYNOPSIS

  use WebService::Geograph::API;
  blah blah blah


=head1 DESCRIPTION

Stub documentation for this module was created by ExtUtils::ModuleMaker.
It looks like the author of the extension was negligent enough
to leave the stub unedited.

Blah blah blah.


=head1 USAGE



=head1 BUGS



=head1 SUPPORT



=head1 AUTHOR

    Spiros Denaxas
    CPAN ID: SDEN
    Lokku Ltd
    s [dot] denaxas [@] gmail [dot]com
    http://idaru.blogspot.com

=head1 COPYRIGHT

This program is free software; you can redistribute
it and/or modify it under the same terms as Perl itself.

The full text of the license can be found in the
LICENSE file included with this module.


=head1 SEE ALSO

perl(1).

=cut

#################### main pod documentation end ###################

1;

