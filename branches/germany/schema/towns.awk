#!/usr/bin/awk -f
BEGIN {
	FS="\t";
	xl["TLT"] = 300;
	xl["ULU"] = 300;
	xl["ULV"] = 300;
	xl["TMT"] = 400;
	xl["UMU"] = 400;
	xl["UMV"] = 400;
	xl["TNT"] = 500;
	xl["UNU"] = 500;
	xl["UNV"] = 500;
	xl["TPT"] = 600;
	xl["UPU"] = 600;
	xl["UPV"] = 600;
	xl["TQT"] = 700;
	xl["UQU"] = 700;
	xl["UQV"] = 700;

	yl["TLT"] = 5200;
	yl["ULU"] = 5300;
	yl["ULV"] = 5400;
	yl["TMT"] = 5200;
	yl["UMU"] = 5300;
	yl["UMV"] = 5400;
	yl["TNT"] = 5200;
	yl["UNU"] = 5300;
	yl["UNV"] = 5400;
	yl["TPT"] = 5200;
	yl["UPU"] = 5300;
	yl["UPV"] = 5400;
	yl["TQT"] = 5200;
	yl["UQU"] = 5300;
	yl["UQV"] = 5400;

	xl["TTN"] = 200;
	xl["UTP"] = 200;
	xl["UTQ"] = 200;
	xl["TUN"] = 300;
	xl["UUP"] = 300;
	xl["UUQ"] = 300;

	yl["TTN"] = 5200;
	yl["UTP"] = 5300;
	yl["UTQ"] = 5400;
	yl["TUN"] = 5200;
	yl["UUP"] = 5300;
	yl["UUQ"] = 5400;

	xl["X"] = 0;
	yl["X"] = 0;

	dx[3] = -200;
	dx[4] = +300;
	dx[5] = -700;
	dy[3] = -5200;
	dy[4] = -5200;
	dy[5] = -5200;
}
NF!=7 {next}
!($2 in xl) { print "invalid grid prefix " $2  > "/dev/stderr";next }

{
	if ($2 == "X") {
		x=xl[$2]*1000 + 1000 * $3;
		y=yl[$2]*1000 + 1000 * $4;
	} else {
		x=xl[$2]*1000 + 10 * $3;
		y=yl[$2]*1000 + 10 * $4;
	}

	xint = x + 1000*dx[$6];
	yint = y + 1000*dy[$6];

	#printf "(%-25s %d, %d, %d, %d, %d, GeomFromText('POINT(%d %d)')),\n","'" $1 "',", x, y, $5, $6, $7, x, y;
	printf "(%-25s %d, %d, %d, %d, %d, GeomFromText('POINT(%d %d)'), %d, %d, GeomFromText('POINT(%d %d)')),\n","'" $1 "',", x, y, $5, $6, $7, x, y, xint, yint, xint, yint;
}
