#!/usr/bin/perl
use File::Find;

#find all files in /var/www/geograph_live/logs that
#look recently rotated (i.e. access_log.xyz.gz)

my $basedir="/var/www/geograph_live/logs/";
my $hostname=`hostname`;
$hostname =~ s/\s//;


#find suitable logfiles and rename them with a date range and server name
find(\&scandir, $basedir);


$basedir="/var/www/geograph_static/logs/";
$hostname=`hostname`."static";
$hostname =~ s/\s//;


#find suitable logfiles and rename them with a date range and server name
find(\&scandir, $basedir);




exit;


sub scandir
{
 	if (m/^access_log\.\d+\.gz$/)
        {
                #find out stuff about the file we found
                my $full=$File::Find::name;
                my $dir=$File::Find::dir;
                my $file=$_;

		print "Found $file\n";
		rename_logfile($hostname, $basedir, $file);
	}
}


#rename an apache log based on its start/end dates
sub rename_logfile
{

my ($hostname, $dir, $filename)=@_;

my $logfile=$dir.$filename;

my $firstline=`zcat $logfile | head -n1`;
my $lastline=`zcat $logfile | tail -n1`;


#[14/Aug/2006:09:53:33 +0100] 

my ($firstdate)=$firstline =~ m/\[([^\s]+)/;
my ($lastdate)=$lastline =~ m/\[([^\s]+)/;

my ($day,$month,$year) = parsedate($firstdate);

my $fmtstart=sprintf("%04d-%02d-%02d", $year, $month, $day);

($day,$month,$year) = parsedate($lastdate);

my $fmtend=sprintf("%04d-%02d-%02d", $year, $month, $day);

my $newname=$dir."access_log_".$fmtend."_".$hostname.".gz";

#ensure new name is unique
my $idx=1;
while (-e $newname)
{
	$idx++;
	$newname=$dir."access_log_".$fmtend."_".$hostname."_".$idx.".gz";

}

`mv $logfile $newname`;

print "moved $logfile to $newname\n";

`scp $newname jam:/var/www/geograph_live/logs/`;

print "copied $newname to jam\n";

}


#yeah, we could use Date::Parse, but I want try and ensure the
#script has few dependancies for ease of installation across the
#cluster!
sub parsedate
{
	#[14/Aug/2006:09:53:33 +0100]
	
	my ($str)=@_;

	my($d,$m,$y)= $str =~ m{(\d+)/([A-Za-z]+)/(\d+)};


	%months = (
               	"Jan"=>1,
	       	"Feb"=>2,
		"Mar"=>3,
		"Apr"=>4,
		"May"=>5,
		"Jun"=>6,
		"Jul"=>7,
		"Aug"=>8,
		"Sep"=>9,
		"Oct"=>10,
		"Nov"=>11,
		"Dec"=>12
	);

	my $monthnum=$months{$m};

	return ($d, $monthnum, $y)
}
