#!/usr/bin/awk -f
BEGIN {
	FS="\t";
}
NF!=4 && NF!=5 && NF!=6 {next}
{
	if (NF == 4) {
		L=$3;
		R=$3+100;
		lcent="NULL,";
		bound="NULL,";
		labelminwidth=0;
	} else {
		if ($5 < 0) {
			R=100;
			L=R+$5;
		} else {
			L=0;
			R=L+$5;
		}
		lcent="'" ((L+R)/2) ",50',";
		bound="'" L ",0," L ",100," R ",100," R ",0',";
		R+=$3;
		L+=$3;
		if (NF==5) {
			labelminwidth=0;
		} else {
			labelminwidth=$6;
		}
	}
	printf "(%-10s %d, %4d, %4d, GeomFromText('POINT(%4d %4d)'), GeomFromText('POLYGON((%4d %4d, %4d %4d, %4d %4d, %4d %4d, %4d %4d))'), %-8s %-30s %3d),\n","'" $1 "',", $2, $3, $4, $3, $4, L, $4, L, $4+100, R, $4+100, R, $4, L, $4, lcent, bound, labelminwidth;
}
