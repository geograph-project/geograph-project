#!/usr/bin/env python

# $Project: GeoGraph $
# $Id: replicator.py 7978 2013-08-17 16:48:04Z barry $
__version__ = filter(str.isdigit, "$Revision: 7978 $")

## Script to run on Geograph File System storage node servers.
#
# Performs a really quick check to confirm all the mounts exist and kinda functional
#
##
#    Copyright (C) 2016  Barry Hunter  <geo@barryhunter.co.uk>
#
#    This program can be distributed under the terms of the GNU LGPL.
#    See the file COPYING.
#

import os, sys
import os.path
from errno import *
from stat import *

import config

#############################################################################

def main(argv):
	path = "/geograph_live/public_html";
	for (key,mount) in config.mounts.iteritems():
		if os.path.exists(mount + path):
			print key + " ok."
		else:
			print key + " - " + mount + path + " NOT found!"



if __name__ == '__main__':
    main(sys.argv[1:])

#############################################################################

