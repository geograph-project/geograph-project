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

from math import sqrt,ceil
import re,sys
from coord import CoordsGeographD,UTM

def parseosm(file):
	# returns [ [[x11, y11],[x12,y12],...,[x11, y11]], [[x21, y21],[x22,y22],...,[x21, y21]], ... ]

	#<?xml version="1.0" encoding="UTF-8"?>					0->1				0	1
	#<osm ...>								1->2				1	2
	# <node id="20844954" lat="54.3746045" lon="10.9833318" .../>		2->3,3->3			2,3	3	1
	# <node id="20844954" lat="54.3746045" lon="10.9833318" ...>		2->4,3->4			2,3	4	1
	#   <tag k="created_by" v="almien_coastlines"/>				2>2,4>4,6>6,7>7,9>9,10>10	...	-
	# </node>								4->3				4	3
	# <way id="4096484" ...>						3->6,8->6			3,8	6	2
	#   <nd ref="21875165"/>						6,7->7				6,7	7	3
	#   <tag k="created_by" v="almien_coastlines"/>
	# </way>								7->8				7	8	4
	# <relation ...>							8,11->9				8,11	9       5
	#   <member type="way" ref="4096484" role="outer"/>			9,10->10			9,10	10	6
	# ( <member type="way" ref="4096487" role=""/>  )
	#   <member type="relation" ref="4096487" role=""/>
	#   <tag k="created_by" v="almien_coastlines"/>
	# </relation ...>							10->11				10	11	7
	#</osm>									8,11->0				8,11	0
	#EOF									0->X				0	X	X

	rexmllin = re.compile(r"^<\?xml [^>]*>$")
	reosmstr = re.compile(r"^ *<osm [^>]*>$")
	reosmend = re.compile(r"^ *</osm>$")
	rewaystr = re.compile(r"^ *<way id=\"([0-9]+)\"[^>]*>$")
	rewayend = re.compile(r"^ *</way>$")
	#rerelstr = re.compile(r"^ *<relation [^>]*>$")
	rerelstr = re.compile(r"^ *<relation id=\"([0-9]+)\"[^>]*>$")
	rerelend = re.compile(r"^ *</relation>$")
	retaglin = re.compile(r"^ *<tag [^>]*/>$")
	rendXlin = re.compile(r"^ *<nd ref=\"([0-9]+)\"/>$")
	remmblin = re.compile(r"^ *<member type=\"way\" ref=\"([0-9]+)\" role=\"([a-z]*)\"/>$")
	#remmblin = re.compile(r"^ *<member type=\"way\" ref=\"([0-9]+)\" role=\"([a-z]+)\"/>$")
	rembrlin = re.compile(r"^ *<member type=\"relation\"[^>]*>$")
	renodlin = re.compile(r"^ *<node id=\"([0-9]+)\" lat=\"([0-9.]+)\" lon=\"([0-9.]+)\"[^/>]*(/?)>$")
	renodend = re.compile(r"^ *</node>$")
	states=[
	         [[2,3],          renodlin,  4,  1],
	         [[4],            renodend,  3,  0],
	         [[6,7],          rendXlin,  7,  3],
	         [[2,4,6,7,9,10], retaglin, -1,  0],
	         [[3,8],          rewaystr,  6,  2],
	         [[7],            rewayend,  8,  4],
	         [[9,10],         remmblin, 10,  6],
	         [[9,10],         rembrlin, 10,  0],
	         [[8,11],         rerelstr,  9,  5],
	         [[10],           rerelend, 11,  7],
	         [[1],            reosmstr,  2,  0],
	         [[8,11],         reosmend,  0,  0],
	         [[0],            rexmllin,  1,  0]#,
	        #[[12],           reosmend, -1,  X],
	       ]


	nodes = {}
	ways = {}
	relations = {}
	phase = 0
	for line in file:
		for state in states:
			if phase in state[0]:
				m = re.match(state[1], line)
				if m:
					break
		else:
			print >> sys.stderr, "invalid line in phase", phase, ":", line
			sys.exit(1)
		action = state[3]
		newphase = state[2]
		if newphase != -1:
			phase = newphase
		if action == 0:
			continue
		elif action == 1:
			if m.group(4) == "/":
				phase = 3
			id = int(m.group(1))
			lat = float(m.group(2))
			lon = float(m.group(3))
			if id in nodes:
				pass
				#print >> sys.stderr, "duplicate node", id
				#sys.exit(1)
			else:
				nodes[id]=(lon, lat)
		elif action == 2:
			wayid = int(m.group(1))
			waynodes = []
			#if wayid in ways:
			#	print >> sys.stderr, "duplicate way", wayid
			#	sys.exit(1)
		elif action == 3:
			id = int(m.group(1))
			coord = nodes[id]
			waynodes.append([coord[0],coord[1],id])
		elif action == 4:
			if wayid in ways:
				pass
				#print >> sys.stderr, "duplicate way", wayid
				#sys.exit(1)
			else:
				ways[wayid] = waynodes
		elif action == 5:
			members = []
			relid = int(m.group(1))
		elif action == 6:
			id = int(m.group(1))
			role = m.group(2)
			if role == '':
				print >> sys.stderr, "warning: empty role, member", id
				role = 'outer'
			#if id not in ways or ((role != '') and (role != 'outer')):
			#if id not in ways or ((role != '') and (role != 'outer') and (role != 'inner')):
			#if id not in ways or ((role != 'outer') and (role != 'inner')):
			if (role != 'outer') and (role != 'inner'):
				print >> sys.stderr, "invalid member", id, role
				sys.exit(1)
			#members.append((id, 0 if role == '' else 1))
			members.append((id, 0 if role != 'outer' else 1)) #FIXME?
		elif action == 7:
			#relations.append(members)
			if relid in relations:
				pass
				#print >> sys.stderr, "duplicate relation", relid
				#sys.exit(1)
			else:
				#print relid
				relations[relid] = members

	if phase != 0:
		print >> sys.stderr, "input file incomplete"
		sys.exit(1)

	polygons = []
	for members in relations.itervalues():
		tmppolystart = {}
		tmppolyend = {}

		for member in members:
			if member[0] not in ways:
				print >> sys.stderr, "invalid member", member[0], member[1]
				sys.exit(1)

		for member in members:
			way = ways[member[0]][:]
			outer = member[1]
			if   way[0][2] in tmppolystart:
				curpoly = tmppolystart.pop(way[0][2])
				tmppolyend.pop(curpoly[0][-1][2])
				curpoly[0].reverse()
				curpoly[0].pop()
				curpoly[0].extend(way)
				way = curpoly[0]
				if outer != curpoly[1]:
					outer = -1
			elif way[0][2] in tmppolyend:
				curpoly = tmppolyend.pop(way[0][2])
				tmppolystart.pop(curpoly[0][0][2])
				curpoly[0].pop()
				curpoly[0].extend(way)
				way = curpoly[0]
				if outer != curpoly[1]:
					outer = -1
			if   way[-1][2] in tmppolystart:
				curpoly = tmppolystart.pop(way[-1][2])
				tmppolyend.pop(curpoly[0][-1][2])
				way.pop()
				way.extend(curpoly[0])
				if outer != curpoly[1]:
					outer = -1
			elif way[-1][2] in tmppolyend:
				curpoly = tmppolyend.pop(way[-1][2])
				tmppolystart.pop(curpoly[0][0][2])
				curpoly[0].reverse()
				way.pop()
				way.extend(curpoly[0])
				if outer != curpoly[1]:
					outer = -1

			if outer == -1:
				print >> sys.stderr, "role mismatch: ", member[0]

			poly = [ way, outer ]
			if way[0][2] == way[-1][2]:
				polygons.append(poly)
			elif way[-1][2] in tmppolyend or way[0][2] in tmppolystart:
				print >> sys.stderr, "ambiguous node", way[0][2], way[-1][2]
				sys.exit(1)
			else:
				tmppolyend[way[-1][2]] = poly
				tmppolystart[way[0][2]] = poly

		if len(tmppolyend) != 0:
			print >> sys.stderr, len(tmppolyend), "incomplete polygons:", tmppolyend.keys()
			#sys.exit(1)
		
			#for poly in tmppolyend.itervalues():
			#	polygons.append(poly)

	return polygons

def polyarea(points):
	if len(points) == 0:
		return 0
	A = 0
	prev = points[0]
	for point in points[1:]:
		A += (point[1]+prev[1])*(prev[0]-point[0])
		prev = point
	return A/2.

def polylistarea(polygons):
	if len(polygons) == 0:
		return 0
	A = 0
	for poly in polygons:
		Ap = abs(polyarea(poly[0]))
		if poly[1] == 1:
			A = A + Ap
		else:
			A = A - Ap
	return A

def polyprint(polygons, z):
	for poly in polygons:
		for point in poly[0]:
			print point[0], point[1], z
		print

def polypointsprint(points, z):
	if len(points) == 0:
		return
	for point in points:
		print point[0], point[1], z
	point = points[0]
	print point[0], point[1], z
	print

## clipdeb = False

def polyclip(polygons, x1, y1, x2, y2, only = -1):
	# only: -1 => alles, 0 => x, 1 => y
	#FIXME ueberlappende kanten

	result = []
	for poly in polygons:
		respoly = poly[0][:-1]
		if only != 1:
			# ---- x1
			srcpoly = respoly
			respoly = []
			if len(srcpoly) != 0:
				prev = srcpoly[-1]
			for point in srcpoly:
				if prev[0] >= x1:
					if point[0] >= x1:
						respoly.append(point)
					else:
						respoly.append([x1, prev[1] + (x1 - prev[0]) * (point[1]-prev[1]) / (point[0]-prev[0]), -1])
				else:
					if point[0] >= x1:
						respoly.append([x1, prev[1] + (x1 - prev[0]) * (point[1]-prev[1]) / (point[0]-prev[0]), -1])
						respoly.append(point)
					else:
						pass
				prev = point
			## if clipdeb: polypointsprint(respoly, 1)
			# ---- x2
			srcpoly = respoly
			respoly = []
			if len(srcpoly) != 0:
				prev = srcpoly[-1]
			for point in srcpoly:
				if prev[0] <= x2:
					if point[0] <= x2:
						respoly.append(point)
					else:
						respoly.append([x2, prev[1] + (x2 - prev[0]) * (point[1]-prev[1]) / (point[0]-prev[0]), -1])
				else:
					if point[0] <= x2:
						respoly.append([x2, prev[1] + (x2 - prev[0]) * (point[1]-prev[1]) / (point[0]-prev[0]), -1])
						respoly.append(point)
					else:
						pass
				prev = point
			## if clipdeb: polypointsprint(respoly, 2)
		if only != 0:
			# ---- y1
			srcpoly = respoly
			respoly = []
			if len(srcpoly) != 0:
				prev = srcpoly[-1]
			for point in srcpoly:
				if prev[1] >= y1:
					if point[1] >= y1:
						respoly.append(point)
					else:
						respoly.append([prev[0] + (y1 - prev[1]) * (point[0]-prev[0]) / (point[1]-prev[1]), y1, -1])
				else:
					if point[1] >= y1:
						respoly.append([prev[0] + (y1 - prev[1]) * (point[0]-prev[0]) / (point[1]-prev[1]), y1, -1])
						respoly.append(point)
					else:
						pass
				prev = point
			## if clipdeb: polypointsprint(respoly, 3)
			# ---- y2
			srcpoly = respoly
			respoly = []
			if len(srcpoly) != 0:
				prev = srcpoly[-1]
			for point in srcpoly:
				if prev[1] <= y2:
					if point[1] <= y2:
						respoly.append(point)
					else:
						respoly.append([prev[0] + (y2 - prev[1]) * (point[0]-prev[0]) / (point[1]-prev[1]), y2, -1])
				else:
					if point[1] <= y2:
						respoly.append([prev[0] + (y2 - prev[1]) * (point[0]-prev[0]) / (point[1]-prev[1]), y2, -1])
						respoly.append(point)
					else:
						pass
				prev = point
			## if clipdeb: polypointsprint(respoly, 4)
		# ----
		if len(respoly) > 2:
			respoly.append(respoly[0][:])
			result.append([respoly, poly[1]])
	return result

def polytrans(polygons, zone, coord):
	result = []
	for poly in polygons:
		respoly = []
		for point in poly[0]:
			#p2 = ll2en(point[1],point[0],zone)
			#p2 = coord.ll2en(point[1],point[0],zone)
			#print >> sys.stderr,point[1],point[0],zone
			p2 = coord.ll_to_en(point[1],point[0],zone)
			respoly.append([p2[0]/1000., p2[1]/1000.])
		result.append([respoly, poly[1]])
	return result

def polysplit(polygons, ds):
	#FIXME Polygone mit zu geringer Zahl von Punkten
	result = []
	for poly in polygons:
		respoly = []
		srcpoly = poly[0][1:]
		prev = srcpoly[-1]
		for point in srcpoly:
			Dx = point[0] - prev[0]
			Dy = point[1] - prev[1]
			s = sqrt(Dx*Dx+Dy*Dy)
			if s <= ds:
				respoly.append(point)
			else:
				N = ceil(s / ds)
				for i in xrange(0, int(N)):
					respoly.append([prev[0]+Dx*(i+1)/N, prev[1]+Dy*(i+1)/N, -1])
			prev = point
		respoly.append(respoly[0][:])
		result.append([respoly, poly[1]])
	return result

def polycomb(polygons, eps):
	#FIXME Polygone mit zu geringer Zahl von Punkten
	result = []
	for poly in polygons:
		respoly = []
		srcpoly = poly[0][2:]
		prev = poly[0][0]
		cur = poly[0][1]
		Dxp = cur[0] - prev[0]
		Dyp = cur[1] - prev[1]
		sp = sqrt(Dxp*Dxp+Dyp*Dyp)
		respoly.append(prev)
		for point in srcpoly:
			if sp < eps:
				cur = point
				Dxp = cur[0] - prev[0]
				Dyp = cur[1] - prev[1]
				sp = sqrt(Dxp*Dxp+Dyp*Dyp)
				continue
			Dx = point[0] - cur[0]
			Dy = point[1] - cur[1]
			s = sqrt(Dx*Dx+Dy*Dy)
			if s < eps:
				continue
			cosalpha = (Dx*Dxp + Dy*Dyp)/(s*sp)
			if abs(cosalpha-1) < eps: # other eps?
				cur = point
				Dxp = cur[0] - prev[0]
				Dyp = cur[1] - prev[1]
				sp = sqrt(Dxp*Dxp+Dyp*Dyp)
				continue
			respoly.append(cur)
			prev = cur
			cur = point
			Dxp = Dx
			Dyp = Dy
			sp = s
		respoly.append(cur)
		respoly.append(respoly[0][:])
		result.append([respoly, poly[1]])
	return result

def point_check_inside(point, polypoints, eps): # 1: inside, -1: outside, 0: near border
	#http://www.softsurfer.com/Archive/algorithm_0103/algorithm_0103.htm
	prev = polypoints[-1]
	wn = 0
	for ppoint in polypoints:
		dy = ppoint[1] - prev[1]
		dx = ppoint[0] - prev[0]
		dyc = point[1] - prev[1]
		dxc = point[0] - prev[0]
		Lsq = dy*dy + dx*dx
		L = sqrt(Lsq)
		#determine if point is close to segment
		eta = (dyc*dy + dxc*dx) / Lsq # foot of perpendicular at prev + eta(ppoint-prev)
		etaL = eta * L
		if etaL >= -eps and etaL-L <= eps: # "besides segment"
			#determine distance to segment
			dsq = dyc*dyc + dxc*dxc - Lsq * eta*eta
			if dsq <= eps*eps:
				return 0
		#update winding number
		if prev[1] <= point[1]:
			if ppoint[1] > point[1]:
				# left(prev, ppoint, point) = dx*dyc - dy*dxc
				if dx * dyc > dy * dxc:
					wn += 1
		else:
			if ppoint[1] <= point[1]:
				if dx * dyc < dy * dxc:
					wn -= 1
		prev = ppoint
	if wn == 0:
		return -1 # outside
	else:
		return 1 #inside


def poly_check_inside(npoly, opoly, eps): # 1: n in o  -1: o in n  0: separate areas, exit on error # assume no overlap
	if npoly[1] != opoly[1]: # assume only one set of outer ways and one of inner ways (i.e. no lake on an island on a lake)
		return 0
	# FIXME empty polygons?
	for point in npoly[0]:
		npoint_inside_o = point_check_inside(point, opoly[0][:-1], eps) # 1: inside, -1: outside, 0: near border
		if npoint_inside_o == 0:
			continue
		elif npoint_inside_o == 1:
			return 1 # point of n inside opoly => npoly inside opoly
		else:
			break    # point of n outside opoly => npoly outside opoly
	for point in opoly[0]:
		opoint_inside_n = point_check_inside(point, npoly[0][:-1], eps) # 1: inside, -1: outside, 0: near border
		if opoint_inside_n == 0:
			continue
		elif opoint_inside_n == 1:
			return -1 # point of o inside npoly => opoly inside npoly
		else:
			break     # point of o outside npoly => opoly outside npoly
	if npoint_inside_o == -1 and opoint_inside_n == -1:
		return 0
	if npoint_inside_o == -1:
		return -1
	if opoint_inside_n == -1:
		return 1
	#print >> sys.stderr, "can't determine if polygon is inside the other polygon"
	#for point in npoly[0]:
	#	print point[0], point[1], -1, npoly[1]
	#print
	#for point in opoly[0]:
	#	print point[0], point[1], -2, opoly[1]
	#print
	#sys.exit(1)
	# also that happens :-/ => can we be sure, they are identical? too tired for that... compare area to be sure...
	Ao = abs(polyarea(opoly[0]))
	An = abs(polyarea(npoly[0]))
	if An > Ao:
		return -1
	else:
		return 1

def polydoubcheck(polygons, eps):
	result = []
	for npoly in polygons:
		oresult = result
		result = []
		for i,opoly in enumerate(oresult):
			inside = poly_check_inside(npoly, opoly, eps) # 1: n in o  -1: o in n  0: separate areas, exit on error
			if inside == 1: # n inside old polygon => ignore new polygon
				result = oresult
				break
			elif inside == -1: # old polygon inside n => ignore old polygon
				continue
			else:
				result.append(opoly) # old polygon ok, keep it
		else:
			result.append(npoly) # add new polygon, as it is not inside any of the old ones
	return result


#def order(polygons):
#	for poly in polygons:
#		A = polyarea(poly[0])
#		if A < 0:
#			poly[0].reverse()

def convertpoly(filename, outfile, zone, x1, y1, x2, y2, cx1, cy1, cx2, cy2, ds, eps, graylow, grayhigh, epscomb, epspoly):
	coord = CoordsGeographD()
	#coord = UTM()
	print >> sys.stderr, "parse input file"
	if filename is None:
		#debugging data
		plist = [ (0,0), (1,0), (1,1), (.1,.1), (.9,1), (0,1), (0,0) ]
		points = []
		for p in plist:
			x, y = p
			points.append([9.2+x*.1,49+y*.1,-1])
		polygons = [ [ points, 1] ]
	else:
		file = open(filename, 'r')
		polygons = parseosm(file)
		file.close()

	#for poly in polygons:
	#	for point in poly[0]:
	#		print point[0], point[1], 0, poly[1]
	#	print

	polygons = polydoubcheck(polygons, epspoly)


	#for poly in polygons:
	#	poly[1] = 1      # only outer lines here, data is inaccurate # FIXME seems to work now, better data at osm

	#order(polygons)

	long1 = zone*6 - 186
	long2 = long1 + 6

	print >> sys.stderr, "clip to zone"
	polygons = polyclip(polygons, long1, 0, long2, 84) # only northern hem.
	#polyprint(polygons, 1)

	print >> sys.stderr, "split lines"
	polygons = polysplit(polygons, ds)
	#polyprint(polygons, 2)

	#for poly in polygons:
	#	for point in poly[0]:
	#		print point[0], point[1], 1, poly[1]
	#	print

	print >> sys.stderr, "coordinate transform"
	#polytrans(polygons, zone, coord)
	polygons = polytrans(polygons, zone, coord)
	###polyprint(polygons, 3)

	print >> sys.stderr, "clip to clip area"
	polygons = polyclip(polygons, cx1, cy1, cx2, cy2)
	###polyprint(polygons, 4)

	#for poly in polygons:
	#	for point in poly[0]:
	#		print point[0], point[1], 2, poly[1]
	#	print

	###print >> sys.stderr, "combine polygon segments"
	###polygons = polycomb(polygons, epscomb)
	###polyprint(polygons, 5)

	#for poly in polygons:
	#	for point in poly[0]:
	#		print point[0], point[1], 3, poly[1]
	#	print

	#for i,poly in enumerate(polygons):
	#	for point in poly[0]:
	#		print point[0], point[1], i, poly[1]
	#	print

	## polyprint(polygons, 0)
	## global clipdeb
	## clipdeb = True
	## #polygons = polyclip(polygons, cx1+100, cy1+100, cx1+101, cy1+101)
	## polygons = polyclip(polygons, 609, 5267, 610, 5268)
	## return

	#for poly in polygons:
	#	for point in poly[0]:
	#		print point[0], point[1], poly[1]
	#	print

	print >> sys.stderr, "generate output file"
	xmin = None
	xmax = None
	ymin = None
	ymax = None
	dy = y2-y1
	yhigh = dy-1
	from PIL import Image
	im = Image.new('L', (x2-x1, y2-y1), 255)
	for x in xrange(cx1, cx2):
		rowpolygons = polyclip(polygons, x, cy1, x+1, cy2, 0)
		###rowpolygons = polycomb(rowpolygons, epscomb)
		###polyprint(rowpolygons, 6)
		for y in xrange(cy1, cy2):
			#pixpolygons = polyclip(polygons, x, y, x+1, y+1)
			pixpolygons = polyclip(rowpolygons, x, y, x+1, y+1, 1)
			###polyprint(pixpolygons, 7)
			A = polylistarea(pixpolygons)
			#print >> sys.stderr, x,y,A
			if A < eps:
				val = 255
			elif 1 - A < eps:
				val = 0
			else:
				val=round(255*(1-A))
				if val < graylow:
					val = graylow
				elif val > grayhigh:
					val = grayhigh
			curx = x - x1
			cury = y - y1
			if val != 255:
				if xmin is None:
					xmin = xmax = curx
					ymin = ymax = cury
				else:
					if curx < xmin:
						xmin = curx
					elif curx > xmax:
						xmax = curx
					if cury < ymin:
						ymin = cury
					elif cury > ymax:
						ymax = cury
			im.putpixel((curx,yhigh-cury),val)
		#print >>sys.stderr, x-cx1, "/", cx2-cx1-1
		#print >> sys.stderr
	im.save(outfile)
	print >> sys.stderr, "x range: ", xmin, xmax
	print >> sys.stderr, "y range: ", ymin, ymax

def mainprog(argv = None):
	if argv is None:
		argv = sys.argv
	if len(argv) < 8 or len(argv) in [9, 10, 11] or len(argv) > 18:
		print >>sys.stderr, 'USAGE: poly.py osm_xml_file png_file utm_zone xmin ymin xmax ymax [ clipxmin clipymin clipxmax clipymax [ ds [ eps [ graylow [ grayhigh [ epscomb [ epspoly ]]]]]]]'
		return 1
	filename = argv[1]
	outfile = argv[2]
	zone, x1, y1, x2, y2 = map(int, argv[3:8])
	cx1, cy1, cx2, cy2 = x1, y1, x2, y2
	ds = .01
	eps = .0001
	epscomb = .00001#.0001
	epspoly = .00001#.0001
	graylow = 2
	grayhigh = 253
	if len(argv) > 8:
		cx1, cy1, cx2, cy2 = map(int, argv[8:12])
	if len(argv) > 12:
		ds = float(argv[12])
	if len(argv) > 13:
		eps = float(argv[13])
	if len(argv) > 14:
		graylow = int(argv[14])
	if len(argv) > 15:
		graylow = int(argv[15])
	if len(argv) > 16:
		epscomb = float(argv[16])
	if len(argv) > 17:
		epspoly = float(argv[17])
	convertpoly(filename, outfile, zone, x1, y1, x2, y2, cx1, cy1, cx2, cy2, ds, eps, graylow, grayhigh, epscomb, epspoly)
	return 0

if __name__ == "__main__":
	sys.exit(mainprog())

#./poly.py land_bw__62611_full         land_bw__62611.png           32 200 5200 1000 6200  350 5250 650 5550
#./poly.py land_bayern__62549_full     land_bayern32__62549.png     32 200 5200 1000 6200  450 5220 750 5620
#./poly.py land_bayern__62549_full     land_bayern33__62549.png     33 200 5200  600 6200  250 5250 450 5620
#./poly.py land_th__62366_full         land_th32__62366.png         32 200 5200 1000 6200  550 5550 730 5750
#./poly.py land_th__62366_full         land_th33__62366.png         33 200 5200  600 6200  270 5550 350 5750
#./poly.py land_mv_62774__full         land_mv32__62774.png         32 200 5200 1000 6200  600 5870 710 6070 #ohne Meer
#./poly.py land_mv_62774__full         land_mv33__62774.png         33 200 5200  600 6200  290 5870 470 6070 #ohne Meer
#./poly.py land_mv__28322_full         land_mv32__28322.png         32 200 5200 1000 6200  600 5870 710 6070
#./poly.py land_mv_28322__full         land_mv33X__28322.png        33 200 5200  600 6200  290 5870 470 6070 #abgeschnitten
#./poly.py land_mv_28322__full         land_mv33__28322.png         33 200 5200  600 6200  290 5870 470 6085
#./poly.py land_hes__62650_full        land_hes__62650.png          32 200 5200 1000 6200  400 5450 600 5750
#./poly.py land_rp__62341_full         land_rp__62341.png           32 200 5200 1000 6200  290 5415 470 5650
#./poly.py land_nrw__62761_full        land_nrw32__62761.png        32 200 5200 1000 6200  280 5570 540 5830
#./poly.py land_nrw__62761_full        land_nrw31__62761.png        31 600 5200  800 6200  700 5625 715 5755
#./poly.py land_saar__62372_full       land_saar__62372.png         32 200 5200 1000 6200  300 5430 400 5510
#./poly.py land_sachsen__62467_full    land_sachsen33__62467.png    33 200 5200  600 6200  275 5555 510 5735
#./poly.py land_sachsen__62467_full    land_sachsen32__62467.png    32 200 5200 1000 6200  700 5575 720 5620
#./poly.py land_sachsenanh__62607_full land_sachsenanh32__62607.png 32 200 5200 1000 6200  600 5640 720 5890
#./poly.py land_sachsenanh__62607_full land_sachsenanh33__62607.png 33 200 5200  600 6200  280 5640 380 5890
#./poly.py land_bburg__62504_full      land_bburg32__62504.png      32 200 5200 1000 6200  650 5850 710 5920
#./poly.py land_bburg__62504_full      land_bburg33X__62504.png     33 200 5200  600 6200  295 5680 490 5940
#./poly.py land_berlin__62422_full     land_berlin__62422.png       33 200 5200  600 6200  365 5790 420 5840
#./poly.py land_hb_62718__full         land_hb__62718.png           32 200 5200 1000 6200  450 5870 510 5950
#./poly.py land_hb_62718__full         land_hbX__62718.png          32 200 5200 1000 6200  450 5870 510 5915
#./poly.py land_hh_62782__full         land_hhX__62782.png          32 200 5200 1000 6200  540 5910 595 5960  #ohne Neuwerk
#./poly.py land_hh_451087__full        land_hhX__451087.png         32 200 5200 1000 6200  540 5910 595 5960  #identisch zu land_hh__62782.png
#./poly.py land_hh_62782__full         land_hh__62782.png           32 200 5200 1000 6200  440 5910 595 5985
#./poly.py land_hh_451087__full        land_hh__451087.png          32 200 5200 1000 6200  440 5910 595 5985  #ohne Meer
#./poly.py land_nds__454192_full       land_ndsX__454192.png        32 200 5200 1000 6200  320 5680 690 5990
#./poly.py land_nds_62771__full        land_ndsX__62771.png         32 200 5200 1000 6200  320 5680 690 5990
#./poly.py land_sh__51529_full         land_sh__51529.png           32 200 5200 1000 6200  400 5910 680 6115
#./poly.py land_sh_62775__full         land_sh__62775.png           32 200 5200 1000 6200  400 5910 680 6115  #ohne Meer

#main('full', 'out.png', 32, 200, 5200, 800, 6200, 600, 5200, 700, 5300, 0.01)
#main('full', 'repro32.png', 32, 200, 5200, 1000, 6200, 326, 5200, 800, 5470, 0.01)
#main('full', 'comp33sued.png', 33, 200, 5200, 600, 6200, 250, 5200, 500, 5470, 0.01)
#main('full', 'repro32lim.png', 32, 200, 5200, 1000, 6200, 326, 5200, 800, 5470, 0.01, 0.0001, 2, 253)
#main('full', 'comp33suedlim.png', 33, 200, 5200, 600, 6200, 250, 5200, 500, 5470, 0.01, 0.0001, 2, 253)
#main('full', 'comp33sued2lim.png', 33, 200, 5200, 600, 6200, 250, 5470, 500, 5600, 0.01, 0.0001, 2, 253)
#main('full', 'comp32sued2lim.png', 32, 200, 5200, 1000, 6200, 250, 5470, 800, 5600, 0.01, 0.0001, 2, 253)
#main('full', 'comp32suedlim.png', 32, 200, 5200, 1000, 6200, 250, 5200, 800, 5470, 0.01, 0.0001, 2, 253)
#main('full', 'comp31lim.png', 31, 600, 5200, 800, 6200, 650, 5500, 750, 5800, 0.01, 0.0001, 2, 253)
#main('full', 'comp32nord1lim.png', 32, 200, 5200, 1000, 6200, 250, 5600, 800, 5800, 0.01, 0.0001, 2, 253)
#main('full', 'comp32nord2lim.png', 32, 200, 5200, 1000, 6200, 250, 5800, 800, 6000, 0.01, 0.0001, 2, 253)
#main('full', 'comp32nord3lim.png', 32, 200, 5200, 1000, 6200, 250, 6000, 800, 6200, 0.01, 0.0001, 2, 253)
#main('full', 'comp33nord1lim.png', 33, 200, 5200, 600,  6200, 250, 5600, 500, 5900, 0.01, 0.0001, 2, 253)
#main('full', 'comp33nord2lim.png', 33, 200, 5200, 600,  6200, 250, 5900, 500, 6200, 0.01, 0.0001, 2, 253)
#main('full', 'comp33nord1lim.png', 33, 200, 5200, 600,  6200, 250, 5600, 550, 5900, 0.01, 0.0001, 2, 253)
