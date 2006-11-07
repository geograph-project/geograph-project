package WebService::Geograph::Response;

use strict ;
use warnings ;

use Data::Dumper ;
use HTTP::Response ;

our @ISA = qw(HTTP::Response) ;

our $VERSION = '0.01' ;

sub new {
	my $class = shift ;
	my $self = new HTTP::Response ;
	my $options = shift ;
	bless $self, $class ;
	return $self ;
	
}

sub init_stats {
 my $self = shift ;
 $self->{results} = undef ;
 $self->{success} = 0 ;
 $self->{error_code} = 0 ;
 $self->{error_message} = 0 ;
}

sub set_fail {
	my ($self, $code, $message) = (@_) ;
	$self->{success} = 0 ;
	$self->{error_code} = $code ;
	$self->{error_message} = $message ;
}

sub set_success {
	my ($self, $data) = (@_) ;
	$self->{success} = 1 ;
	$self->{results} = $data ;
}



	



1 ;

'ERROR: no api key or email address' 