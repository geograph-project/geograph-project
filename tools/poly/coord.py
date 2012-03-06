#!/usr/bin/python

####################################################################
#                                                                  #
#                    Copyright (c) 2008-2011 by                    #
#                  Hansjoerg Lipp <hjlipp@web.de>                  #
#                                                                  #
####################################################################
#  This program is free software; you can redistribute it and/or   #
#  modify it under the terms of the GNU General Public License as  #
#  published by the Free Software Foundation; either version 2 of  #
#  the License, or (at your option) any later version.             #
####################################################################

from math import sinh,atan,cos,sin,sqrt,tan,degrees,floor,radians,log,ceil,hypot,fabs,atan2

class Ellipsoid:
	def __init__(self, a, b):
		self.a = a
		self.b = b
		ba = b/a
		self.e2 = 1-ba*ba
		self.n = (a - b) / (a + b)
		self.n2=self.n*self.n
		self.n3=self.n2*self.n
	def llh_to_xyz(self, PHI, LAM, H):
		RadPHI = radians(PHI)
		RadLAM = radians(LAM)
		sinPHI = sin(RadPHI)
		cosPHI = cos(RadPHI)
		sinLAM = sin(RadLAM)
		cosLAM = cos(RadLAM)
		sinPHI2 = sinPHI*sinPHI
		V = self.a / sqrt(1 - self.e2*sinPHI2)
		return ((V + H) * cosPHI * cosLAM, (V + H) * cosPHI * sinLAM, (V * (1 - self.e2) + H) * sinPHI )
	def xyz_to_llh(self, X, Y, Z):
		RootXYSqr = hypot(X, Y)
		PHI = atan2 (Z , RootXYSqr * (1 - self.e2))
		while True:
			PHI1 = PHI
			sinPHI = sin(PHI)
			sinPHI2 = sinPHI*sinPHI
			V = self.a / sqrt(1 - (self.e2 * sinPHI2))
			PHI = atan2(Z + self.e2 * V * sinPHI, RootXYSqr)
			if fabs(PHI1 - PHI) <= 0.000000001:
				break
		sinPHI = sin(PHI)
		sinPHI2 = sinPHI*sinPHI
		V = self.a / sqrt(1 - (self.e2 * sinPHI2))
		return (degrees(PHI), degrees(atan2(Y, X)), RootXYSqr / cos(PHI) - V)


class Coords:
	def __init__(self):
		self.newel = False
		#self.el1 = Ellipsoid(6378137.000,6356752.315) # FIXME
		self.el1 = Ellipsoid(6378137.000,6378137.000*(1-1/298.257223563)) # FIXME
		self.el2 = self.el1
		self.squares = None
		self.init_proj(0.0, 0.0, 0.0, 1.0, 0.0, 0.0)
	def init_el_size(self, a, b):
		self.el2 = Ellipsoid(a, b)
		self.af0 = a * self.f0
		self.bf0 = b * self.f0
	def init_el_trans(self, DX, DY, DZ, X_Rot, Y_Rot, Z_Rot, s):
		self.newel = True
		self.DX = DX
		self.DY = DY
		self.DZ = DZ
		self.X_Rot = radians(X_Rot/3600.)
		self.Y_Rot = radians(Y_Rot/3600.)
		self.Z_Rot = radians(Z_Rot/3600.)
		self.s = s*0.000001
	def init_el_notrans(self):
		self.newel = False
	def init_proj(self, e0, n0, n0s, f0, PHI0, LAM0, zonewidth=360, zone0=-180, zoneeast=0, zonewrap=None, bandwidth=180, band0=-90):
		self.e0 = e0
		self.n0 = n0
		self.n0s = n0s
		self.f0 = f0
		self.PHI0 = PHI0
		self.LAM0 = LAM0 + zone0 + zonewidth/2.
		self.af0 = self.el2.a * f0
		self.bf0 = self.el2.b * f0
		self.RadPHI0 = radians(PHI0)
		self.RadLAM0 = radians(self.LAM0)
		self.zonewidth = zonewidth
		self.bandwidth = bandwidth
		self.zone0 = zone0
		self.band0 = band0
		self.zoneeast = zoneeast
		self.zonewrap = zonewrap
		self.Radzonewidth = radians(zonewidth)
		self.Radbandwidth = radians(bandwidth)
		self.Radzone0 = radians(zone0)
		self.Radband0 = radians(band0)
	def wgs_to_llh(self, PHI, LAM, H=0):
		if not self.newel:
			return (PHI, LAM, H)
		x, y, z = self.el1.llh_to_xyz(PHI, LAM, H)
		x2, y2, z2 = self._Helmert(x, y, z)
		return self.el2.xyz_to_llh(x2, y2, z2)
	def llh_to_wgs(self, PHI, LAM, H=0):
		if not self.newel:
			return (PHI, LAM, H)
		x, y, z = self.el2.llh_to_xyz(PHI, LAM, H)
		x2, y2, z2 = self._invHelmert(x, y, z)
		return self.el1.xyz_to_llh(x2, y2, z2)
	def in_range(self, lat, long):
		return True
	def ll_to_en(self, PHI, LAM, zone = None):
		RadPHI = radians(PHI)
		RadLAM = radians(LAM)
		sinPHI = sin(RadPHI)
		cosPHI = cos(RadPHI)
		tanPHI = sinPHI/cosPHI
		sinPHI2 = sinPHI * sinPHI
		cosPHI2 = cosPHI * cosPHI
		cosPHI3 = cosPHI2 * cosPHI
		cosPHI5 = cosPHI2 * cosPHI3
		tanPHI2 = tanPHI * tanPHI
		tanPHI4 = tanPHI2 * tanPHI2

		if zone is None:
			zone = int(floor((LAM-self.LAM0) / self.zonewidth + 0.5))
			if self.zonewrap is not None and zone < 0:
				zone += self.zonewrap
		band = int(floor((PHI-self.PHI0-self.band0) / self.bandwidth))

		nu = self.af0 / sqrt(1 - self.el2.e2 * sinPHI2)
		rho = nu * (1 - self.el2.e2) / (1 - self.el2.e2 * sinPHI2)
		eta2 = nu / rho - 1
		p = RadLAM - self.RadLAM0 - self.Radzonewidth * zone
		M = self._Marc(RadPHI)

		I = M + self.n0
		II = nu/2. * sinPHI * cosPHI
		III = nu / 24. * sinPHI * cosPHI3 * (5 - tanPHI2 + 9 * eta2)
		IIIA = nu / 720. * sinPHI * cosPHI5 * (61 - 58 * tanPHI2 + tanPHI4)
		IV = nu * cosPHI
		V = nu / 6. * cosPHI3 * (nu/rho - tanPHI2)
		VI = nu / 120. * cosPHI5 * (5 - 18*tanPHI2 + tanPHI4 + 14*eta2 - 58*tanPHI2*eta2)

		p2 = p*p
		if PHI < 0:
			dn = self.n0s
		else:
			dn = 0

		return (self.e0 + zone*self.zoneeast + p * (IV + p2 * (V + p2 * VI)), dn + I + p2 * (II + p2 * (III + p2 * IIIA)), zone, PHI < 0, band)
	def en_to_ll(self, East, North, zone=None, shem=None, band=None):
		if zone is None:
			zone = 0 # FIXME: GK: zone from easting
		if shem is None:
			if band is None:
				shem = False # FIXME warning
			else:
				phib1 = band * self.bandwidth + self.band0 + self.PHI0
				phib2 = phib1 + self.bandwidth
				if phib1 <= 0 and phib2 <= 0:
					shem = True
				elif phib1 >= 0 and phib2 >= 0:
					shem = False
				else:
					shem = False # FIXME warning
		if shem:
			dn = self.n0s
		else:
			dn = 0
		Et = East - self.e0 - zone*self.zoneeast
		Nt = North - self.n0 - dn

		PHI = Nt / self.af0 + self.RadPHI0
		M = self._Marc(PHI)

		#while fabs(Nt - M) > 0.00001:
		while True:
			PHI += (Nt - M) / self.af0
			M = self._Marc(PHI)
			if fabs(Nt - M) <= 0.00001:
				break

		sinPHI = sin(PHI)
		cosPHI = cos(PHI)
		tanPHI = sinPHI/cosPHI
		sinPHI2 = sinPHI * sinPHI
		tanPHI2 = tanPHI * tanPHI
		cosPHIr = 1. / cosPHI
		Et2 = Et * Et

		nu = self.af0 / sqrt(1 - self.el2.e2 * sinPHI2)
		rho = nu * (1 - self.el2.e2) / (1 - self.el2.e2 * sinPHI2)
		eta2 = nu / rho - 1

		nu2 = nu*nu
		nu3 = nu2 * nu
		nu5 = nu2 * nu3
		nu7 = nu2 * nu5

		VII = tanPHI / (2. * rho * nu)
		VIII = tanPHI / (24. * rho * nu3) * (5 + eta2 + tanPHI2 * (3 - 9 * eta2))
		IX = tanPHI / (720. * rho * nu5) * (61 + tanPHI2 * (90 + 45 * tanPHI2))

		X = cosPHIr / nu
		XI = cosPHIr / 6. / nu3 * (nu / rho + 2 * tanPHI2)
		XII = cosPHIr / 120. / nu5 * (5 + tanPHI2 * (28 + 24 * tanPHI2))
		XIIA = cosPHIr / 5040. / nu7 * (61 + tanPHI2 * (662 + tanPHI2 * (1320 + 720 * tanPHI2)))

		return ( degrees((PHI - Et2 * (VII - Et2 * (VIII - Et2 * IX)))),
		         degrees((self.RadLAM0 + self.Radzonewidth * zone + Et * (X - Et2 * (XI - Et2 * (XII - Et2 * XIIA))))) )
	def en_to_square(self, e, n, zone, band):
		e /= 1000
		n /= 1000
		eh=floor(e/100)
		nh=floor(n/100)
		e -= 100*eh
		n -= 100*nh
		square=self.squares[int(zone),int(eh),int(nh)]
		return (e,n,square)
	def _Helmert(self, X, Y, Z):
		return (X + X * self.s                  - Y * self.Z_Rot + Z * self.Y_Rot + self.DX,
		        Y + Y * self.s + X * self.Z_Rot                  - Z * self.X_Rot + self.DY,
		        Z + Z * self.s - X * self.Y_Rot + Y * self.X_Rot                  + self.DZ)
	def _invHelmert(self, X, Y, Z):
		return (X - X * self.s                  + Y * self.Z_Rot - Z * self.Y_Rot - self.DX,
		        Y - Y * self.s - X * self.Z_Rot                  + Z * self.X_Rot - self.DY,
		        Z - Z * self.s + X * self.Y_Rot - Y * self.X_Rot                  - self.DZ)
	def _Marc(self, RadPHI):
		sphi = RadPHI + self.RadPHI0
		dphi = RadPHI - self.RadPHI0
		return self.bf0 * (((1 + self.el2.n + 5./4 * (self.el2.n2 + self.el2.n3)) * dphi) - ((3 * (self.el2.n + self.el2.n2) + 21./8 * self.el2.n3) * sin(dphi)*cos(sphi)) + (15./8  * (self.el2.n2 + self.el2.n3)) * sin(2*dphi)*cos(2 * sphi) - (35./24 * self.el2.n3 * sin(3*dphi)*cos(3*sphi)))

class CoordsOSGB36(Coords):
	def in_range(self, lat, long):
		uk = lat > 49 and lat < 62 and long > -9.5 and long < 2.3
		ire = lat > 51.2 and lat < 55.73 and long > -12.2 and long < -4.8
		if not uk:
			return False
		if ire:
			pass #FIXME
		return True
	def __init__(self):
		Coords.__init__(self)
		self.init_el_size(6377563.396,6356256.910)
		self.init_el_trans(-446.448, 125.157, -542.060, -0.1502, -0.2470, -0.8421, 20.4894)
		self.init_proj(400000,-100000,0,0.999601272,49.00000,-2.00000)
		self.squares = {
			(0, 0,  0): 'SV',
			(0, 1,  0): 'SW',
			(0, 2,  0): 'SX',
			(0, 3,  0): 'SY',
			(0, 4,  0): 'SZ',
			(0, 5,  0): 'TV',
			(0, 1,  1): 'SR',
			(0, 2,  1): 'SS',
			(0, 3,  1): 'ST',
			(0, 4,  1): 'SU',
			(0, 5,  1): 'TQ',
			(0, 6,  1): 'TR',
			(0, 1,  2): 'SM',
			(0, 2,  2): 'SN',
			(0, 3,  2): 'SO',
			(0, 4,  2): 'SP',
			(0, 5,  2): 'TL',
			(0, 6,  2): 'TM',
			(0, 2,  3): 'SH',
			(0, 3,  3): 'SJ',
			(0, 4,  3): 'SK',
			(0, 5,  3): 'TF',
			(0, 6,  3): 'TG',
			(0, 2,  4): 'SC',
			(0, 3,  4): 'SD',
			(0, 4,  4): 'SE',
			(0, 5,  4): 'TA',
			(0, 1,  5): 'NW',
			(0, 2,  5): 'NX',
			(0, 3,  5): 'NY',
			(0, 4,  5): 'NZ',
			(0, 5,  5): 'OV',
			(0, 1,  6): 'NR',
			(0, 2,  6): 'NS',
			(0, 3,  6): 'NT',
			(0, 4,  6): 'NU',
			(0, 0,  7): 'NL',
			(0, 1,  7): 'NM',
			(0, 2,  7): 'NN',
			(0, 3,  7): 'NO',
			(0, 0,  8): 'NF',
			(0, 1,  8): 'NG',
			(0, 2,  8): 'NH',
			(0, 3,  8): 'NJ',
			(0, 4,  8): 'NK',
			(0, 0,  9): 'NA',
			(0, 1,  9): 'NB',
			(0, 2,  9): 'NC',
			(0, 3,  9): 'ND',
			(0, 1, 10): 'HW',
			(0, 2, 10): 'HX',
			(0, 3, 10): 'HY',
			(0, 4, 10): 'HZ',
			(0, 3, 11): 'HT',
			(0, 4, 11): 'HU',
			(0, 4, 12): 'HP',
		}
class CoordsIrish(Coords):
	def in_range(self, lat, long):
		uk = lat > 49 and lat < 62 and long > -9.5 and long < 2.3
		ire = lat > 51.2 and lat < 55.73 and long > -12.2 and long < -4.8
		if not ire:
			return False
		if uk:
			pass #FIXME
		return True
	def __init__(self):
		Coords.__init__(self)
		self.init_el_size(6377340.189,6356034.447)
		self.init_el_trans(-482.538, 130.596, -564.557, -1.042,  -0.214,  -0.631,  -8.15)
		self.init_proj(200000,250000,0,1.000035,53.50000,-8.00000)
class CoordsGK(Coords):
	def __init__(self):
		Coords.__init__(self)
		self.init_el_size(6377397.155,6356078.965)
		self.init_el_trans(-582.0,   -105.0,  -414.0,    -1.04 , -0.35,   +3.08 ,  -8.3)
		self.init_proj(500000,     0,        0,1.0,     0,       0,          3, -1.5, 1000000, 120)
class CoordsUTM(Coords):
	def __init__(self):
		Coords.__init__(self)
		self.init_proj(500000,     0, 10000000,0.9996,  0,       0,          6, -186, 0, None, 8, -80)
class CoordsGeographD(CoordsUTM):
	def in_range(self, lat, long):
		ger = lat > 47 and lat < 56 and long > 4 and long < 16
		return ger
	def __init__(self):
		CoordsUTM.__init__(self)
		self.squares = {
			(32, 2, 61): 'UKG',
			(32, 2, 60): 'UKF',
			(32, 2, 59): 'UKE',
			(32, 2, 58): 'UKD',
			(32, 2, 57): 'UKC',
			(32, 2, 56): 'UKB',
			(32, 2, 55): 'UKA',
			(32, 2, 54): 'UKV',
			(32, 2, 53): 'UKU',
			(32, 2, 52): 'TKT',
			(32, 3, 61): 'ULG',
			(32, 3, 60): 'ULF',
			(32, 3, 59): 'ULE',
			(32, 3, 58): 'ULD',
			(32, 3, 57): 'ULC',
			(32, 3, 56): 'ULB',
			(32, 3, 55): 'ULA',
			(32, 3, 54): 'ULV',
			(32, 3, 53): 'ULU',
			(32, 3, 52): 'TLT',
			(32, 4, 61): 'UMG',
			(32, 4, 60): 'UMF',
			(32, 4, 59): 'UME',
			(32, 4, 58): 'UMD',
			(32, 4, 57): 'UMC',
			(32, 4, 56): 'UMB',
			(32, 4, 55): 'UMA',
			(32, 4, 54): 'UMV',
			(32, 4, 53): 'UMU',
			(32, 4, 52): 'TMT',
			(32, 5, 61): 'UNG',
			(32, 5, 60): 'UNF',
			(32, 5, 59): 'UNE',
			(32, 5, 58): 'UND',
			(32, 5, 57): 'UNC',
			(32, 5, 56): 'UNB',
			(32, 5, 55): 'UNA',
			(32, 5, 54): 'UNV',
			(32, 5, 53): 'UNU',
			(32, 5, 52): 'TNT',
			(32, 6, 61): 'UPG',
			(32, 6, 60): 'UPF',
			(32, 6, 59): 'UPE',
			(32, 6, 58): 'UPD',
			(32, 6, 57): 'UPC',
			(32, 6, 56): 'UPB',
			(32, 6, 55): 'UPA',
			(32, 6, 54): 'UPV',
			(32, 6, 53): 'UPU',
			(32, 6, 52): 'TPT',
			(32, 7, 61): 'UQG',
			(32, 7, 60): 'UQF',
			(32, 7, 59): 'UQE',
			(32, 7, 58): 'UQD',
			(32, 7, 57): 'UQC',
			(32, 7, 56): 'UQB',
			(32, 7, 55): 'UQA',
			(32, 7, 54): 'UQV',
			(32, 7, 53): 'UQU',
			(32, 7, 52): 'TQT',
			(31, 7, 61): 'UGB',
			(31, 7, 60): 'UGA',
			(31, 7, 59): 'UGV',
			(31, 7, 58): 'UGU',
			(31, 7, 57): 'UGT',
			(31, 7, 56): 'UGS',
			(31, 7, 55): 'UGR',
			(31, 7, 54): 'UGQ',
			(31, 7, 53): 'UGP',
			(31, 7, 52): 'TGN',
			(33, 2, 61): 'UTB',
			(33, 2, 60): 'UTA',
			(33, 2, 59): 'UTV',
			(33, 2, 58): 'UTU',
			(33, 2, 57): 'UTT',
			(33, 2, 56): 'UTS',
			(33, 2, 55): 'UTR',
			(33, 2, 54): 'UTQ',
			(33, 2, 53): 'UTP',
			(33, 2, 52): 'TTN',
			(33, 3, 61): 'UUB',
			(33, 3, 60): 'UUA',
			(33, 3, 59): 'UUV',
			(33, 3, 58): 'UUU',
			(33, 3, 57): 'UUT',
			(33, 3, 56): 'UUS',
			(33, 3, 55): 'UUR',
			(33, 3, 54): 'UUQ',
			(33, 3, 53): 'UUP',
			(33, 3, 52): 'TUN',
			(33, 4, 61): 'UVB',
			(33, 4, 60): 'UVA',
			(33, 4, 59): 'UVV',
			(33, 4, 58): 'UVU',
			(33, 4, 57): 'UVT',
			(33, 4, 56): 'UVS',
			(33, 4, 55): 'UVR',
			(33, 4, 54): 'UVQ',
			(33, 4, 53): 'UVP',
			(33, 4, 52): 'TVN',
			(33, 5, 61): 'UWB',
			(33, 5, 60): 'UWA',
			(33, 5, 59): 'UWV',
			(33, 5, 58): 'UWU',
			(33, 5, 57): 'UWT',
			(33, 5, 56): 'UWS',
			(33, 5, 55): 'UWR',
			(33, 5, 54): 'UWQ',
			(33, 5, 53): 'UWP',
			(33, 5, 52): 'TWN',
		}

def arsinh(x):
	return log(x + sqrt(x*x+1))

class UTM: ## old implementation, only for testing
	def __init__(self):
		self.a2=6378137.
		self.a3=6356752.31425

		self.b2=self.a2**2/self.a3
		self.b3=1-(self.a3/self.a2)**2
		self.b4=1-self.a3/self.a2
		self.b5=(self.a2/self.a3)**2-1

		self.c2=1 + 3./4*self.b3 + 45./64*self.b3**2 + 175./256*self.b3**3
		self.c3=    3./4*self.b3 + 15./16*self.b3**2 + 525./512*self.b3**3
		self.c4=              15./64*self.b3**2 + 105./256*self.b3**3
		self.c5=                              35./512*self.b3**3

		self.d2=self.a2*(1-self.b3)*self.c2
		self.d3=self.a2*(1-self.b3)*self.c3/2
		self.d4=self.a2*(1-self.b3)*self.c4/4
		self.d5=self.a2*(1-self.b3)*self.c5/6

		self.e2=self.b4/(2-self.b4)
		self.e3=3./2*(self.e2-9./16*self.e2**3)
		self.e4=21./16*self.e2**2
		self.e5=151./96*self.e2**3

		self.squares = {     
			(32, 2, 61): 'UKG',
			(32, 2, 60): 'UKF',
			(32, 2, 59): 'UKE',
			(32, 2, 58): 'UKD',
			(32, 2, 57): 'UKC',
			(32, 2, 56): 'UKB',
			(32, 2, 55): 'UKA',
			(32, 2, 54): 'UKV',
			(32, 2, 53): 'UKU',
			(32, 2, 52): 'TKT',
			(32, 3, 61): 'ULG',
			(32, 3, 60): 'ULF',
			(32, 3, 59): 'ULE',
			(32, 3, 58): 'ULD',
			(32, 3, 57): 'ULC',
			(32, 3, 56): 'ULB',
			(32, 3, 55): 'ULA',
			(32, 3, 54): 'ULV',
			(32, 3, 53): 'ULU',
			(32, 3, 52): 'TLT',
			(32, 4, 61): 'UMG',
			(32, 4, 60): 'UMF',
			(32, 4, 59): 'UME',
			(32, 4, 58): 'UMD',
			(32, 4, 57): 'UMC',
			(32, 4, 56): 'UMB',
			(32, 4, 55): 'UMA',
			(32, 4, 54): 'UMV',
			(32, 4, 53): 'UMU',
			(32, 4, 52): 'TMT',
			(32, 5, 61): 'UNG',
			(32, 5, 60): 'UNF',
			(32, 5, 59): 'UNE',
			(32, 5, 58): 'UND',
			(32, 5, 57): 'UNC',
			(32, 5, 56): 'UNB',
			(32, 5, 55): 'UNA',
			(32, 5, 54): 'UNV',
			(32, 5, 53): 'UNU',
			(32, 5, 52): 'TNT',
			(32, 6, 61): 'UPG',
			(32, 6, 60): 'UPF',
			(32, 6, 59): 'UPE',
			(32, 6, 58): 'UPD',
			(32, 6, 57): 'UPC',
			(32, 6, 56): 'UPB',
			(32, 6, 55): 'UPA',
			(32, 6, 54): 'UPV',
			(32, 6, 53): 'UPU',
			(32, 6, 52): 'TPT',
			(32, 7, 61): 'UQG',
			(32, 7, 60): 'UQF',
			(32, 7, 59): 'UQE',
			(32, 7, 58): 'UQD',
			(32, 7, 57): 'UQC',
			(32, 7, 56): 'UQB',
			(32, 7, 55): 'UQA',
			(32, 7, 54): 'UQV',
			(32, 7, 53): 'UQU',
			(32, 7, 52): 'TQT',
			(31, 7, 61): 'UGB',
			(31, 7, 60): 'UGA',
			(31, 7, 59): 'UGV',
			(31, 7, 58): 'UGU',
			(31, 7, 57): 'UGT',
			(31, 7, 56): 'UGS',
			(31, 7, 55): 'UGR',
			(31, 7, 54): 'UGQ',
			(31, 7, 53): 'UGP',
			(31, 7, 52): 'TGN',
			(33, 2, 61): 'UTB',
			(33, 2, 60): 'UTA',
			(33, 2, 59): 'UTV',
			(33, 2, 58): 'UTU',
			(33, 2, 57): 'UTT',
			(33, 2, 56): 'UTS',
			(33, 2, 55): 'UTR',
			(33, 2, 54): 'UTQ',
			(33, 2, 53): 'UTP',
			(33, 2, 52): 'TTN',
			(33, 3, 61): 'UUB',
			(33, 3, 60): 'UUA',
			(33, 3, 59): 'UUV',
			(33, 3, 58): 'UUU',
			(33, 3, 57): 'UUT',
			(33, 3, 56): 'UUS',
			(33, 3, 55): 'UUR',
			(33, 3, 54): 'UUQ',
			(33, 3, 53): 'UUP',
			(33, 3, 52): 'TUN',
			(33, 4, 61): 'UVB',
			(33, 4, 60): 'UVA',
			(33, 4, 59): 'UVV',
			(33, 4, 58): 'UVU',
			(33, 4, 57): 'UVT',
			(33, 4, 56): 'UVS',
			(33, 4, 55): 'UVR',
			(33, 4, 54): 'UVQ',
			(33, 4, 53): 'UVP',
			(33, 4, 52): 'TVN',
			(33, 5, 61): 'UWB',
			(33, 5, 60): 'UWA',
			(33, 5, 59): 'UWV',
			(33, 5, 58): 'UWU',
			(33, 5, 57): 'UWT',
			(33, 5, 56): 'UWS',
			(33, 5, 55): 'UWR',
			(33, 5, 54): 'UWQ',
			(33, 5, 53): 'UWP',
			(33, 5, 52): 'TWN',
		}

	def en2mgrs(self,east,north,zone):
		global squares
		east=east/1000
		north=north/1000
		eh=floor(east/100)
		nh=floor(north/100)
		east=east-100*eh
		north=north-100*nh
		square=self.squares[int(zone),int(eh),int(nh)]
		return (east,north,square)

	def ll2en(self,lat,lng,zone=None):
		#FIXME
		#zone2=floor(lng/6.)+31 #FIXME: floor/truncate?
		##zone=zone2
		if zone is None:
			zone=int(floor(lng/6.))+31 #FIXME: floor/truncate?

		dl=radians(lng-zone*6+183)
		b=radians(lat)
		v=sqrt(1+self.b5*cos(b)**2)
		bf=atan(tan(b)/(cos(dl*v)))
		vf=sqrt(1+self.b5*cos(bf)**2)

		y=self.b2*arsinh(tan(dl)/vf*cos(bf))
		x=self.d2*bf-self.d3*sin(2*bf)+self.d4*sin(4*bf)-self.d5*sin(6*bf)

		east=0.9996*y+500000
		north=0.9996*x # FIXME  southern hem: +10000000

		return (east,north,zone)


###################################################


#c = CoordsOSGB36()

#lat1 = 51.5
#lon1 = 0.0
#geo:  538919        179791         ( http://www.geograph.org.uk/latlong.php?lat=51.5&long=0&From=convert )
#prog: 538919.868554 179791.81839
#fix:  538919.868574 179791.817654


#lat1 = 51.5
#lon1 = -5.0
#geo:  191873        182164
#prog: 191873.647168 182164.457854
#fix:  191873.647138 182164.457118

#c = CoordsIrish()

#lat1 = 53.4
#lon1 = -8.1
#geo:  193423        238873
#prog: 193423.532165 238873.358331
#fix:  193423.532164 238873.357608

#c = CoordsUTM()

#lat1 = 49
#lon1 = 9.2
#geo:  514628        5427475        ( http://geo.hlipp.de/latlong.php?lat=49&long=9.2&From=convert )
#prog: 514628.500829 5427475.05011
#alt:  514628.500829 5427475.04976


#lat1 = 47.7
#lon1 = 11.9
#geo:  717569        5287031
#prog: 717569.502967 5287031.48802
#alt:  717569.502106 5287031.49455



#print lat1, lon1
#lat2, lon2, H = c.wgs_to_llh(lat1, lon1)
#print lat2, lon2
#lat3, lon3, H = c.llh_to_wgs(lat2, lon2)
#print lat3, lon3
#e, n, zone, shem, band = c.ll_to_en(lat2, lon2)
#print e, n, zone, shem, band
#lat4, lon4 = c.en_to_ll(e, n, zone, shem)
#print lat4, lon4
#e2, n2, zone2, shem2, band2 = c.ll_to_en(lat4, lon4)
#print e2, n2, zone2, shem2, band2
#
#c2 = UTM()
#e,n,zone=c2.ll2en(lat1, lon1)
#print e, n, zone
#es, ns, square = c2.en2mgrs(e,n,zone)
#print square, es, ns

#c = CoordsOSGB36()
#e1 = 191873
#n1 = 179791
#z1 = 0

#lat, lon = c.en_to_ll(e1, n1, z1, False)
#e2, n2, z2, shem, band = c.ll_to_en(lat, lon)

#print e1, n1
#print e2, n2
#print e2-e1, n2-n1




###################################################

#trans = {
#		'de': CoordsGeographD(),
#		'uk': CoordsOSGB36()
#}

