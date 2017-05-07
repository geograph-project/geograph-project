#!/usr/bin/env python

# $Project: GeoGraph $
# $Id: replicator.py 8277 2015-10-08 21:55:04Z barry $
__version__ = filter(str.isdigit, "$Revision: 8277 $")

## Script to run on Geograph File System storage node servers.
#
#  Walk the local disk, and moving files from one replica to another, and updating the metadata server 
#    (works instantly on a named folder recursively, moving all files) 
#
##
#    Copyright (C) 2013  Barry Hunter  <geo@barryhunter.co.uk>
#
#    This program can be distributed under the terms of the GNU LGPL.
#    See the file COPYING.
#

import os, sys
import os.path
import random
from errno import *
from stat import *
import fcntl

import config
import MySQLdb
import hashlib
import getopt
import string
import shutil
import re
import subprocess
import time

db=MySQLdb.connect(host=config.database['hostname'], user=config.database['username'], passwd=config.database['password'],db=config.database['database'])

#############################################################################

def getFolderId(path, create = False):
    c=db.cursor()
    c.execute("SELECT folder_id FROM "+config.database['folder_table']+" WHERE folder = '"+db.escape_string(path)+"'")
    if c.rowcount == 0:
        if not create:
            return 0
        c.execute("INSERT INTO "+config.database['folder_table']+" SET meta_created = NOW(), folder = '"+db.escape_string(path)+"'")
        folder_id = db.insert_id()
    else:
        folder_id = c.fetchone()[0]
    return str(folder_id)

def md5sum(path):
    file = open(path, 'rb')
    md5 = hashlib.md5()
    buffer = file.read(2 ** 20)
    while buffer:
        md5.update(buffer)
        buffer = file.read(2 ** 20)
    file.close()
    return str(md5.hexdigest())

#############################################################################

def move_files(folder = '', replica = '', dest = '', classinc =[],classexc=[]):
    if replica == '':
	replica = config.server['self']

    mount = config.mounts[replica]
    dmount = config.mounts[dest]


    print mount+folder
    for root, dirs, files in os.walk(mount+folder):
        
        if files:
            print root

            folder_id = getFolderId(string.replace(root,mount,''), True)
            
            where = "folder_id = "+folder_id
	    where = where + " AND FIND_IN_SET('"+replica+"',replicas)"
	    if classinc:
                where = where + " AND `class` IN('"+("','".join(classinc))+"')"
	    if classexc:
                where = where + " AND `class` NOT IN('"+("','".join(classexc))+"')"

            c=db.cursor(MySQLdb.cursors.DictCursor)
            cex=db.cursor()
            c.execute("SELECT file_id,filename,replicas,size,md5sum,UNIX_TIMESTAMP(file_modified) AS modified FROM "+config.database['file_table']+" WHERE "+where)
            
            while True:
                row = c.fetchone()
                if not row: break
                
                filename = os.path.basename(row['filename'])
                if filename in files:
                    ##We have the file, lets check we noted in replicas

                    if dest in row['replicas'] or os.path.exists(dmount+row['filename']):
                    	print dest+" already has "+row['filename']

                    elif replica in row['replicas']: 

                        print "hey! we have "+row['filename']
                        specifics = ''
                        
                        stat = os.stat(root + "/" + filename)
                        if (stat.st_size > 0 and stat.st_size < 52428800):
                            md5su = md5sum(root + "/" + filename)
                        else:
                            md5su =''
                        
                        if md5su != row['md5sum']:
                            print "BUT md5 checksum doesnt match '"+md5su+"' != '"+row['md5sum']+"'"
                        elif stat.st_size != row['size']:
                            print "BUT size doesnt match"
                        else:
                            print "OK Move the file... "

		            if not os.path.exists(os.path.dirname(dmount+row['filename'])):
                                os.makedirs(os.path.dirname(dmount+row['filename'])) ##recursive

			    #print "move "+root+'/'+filename
	                    #print "  to "+dmount+row['filename']

			    ##os.rename(root+'/'+filename,dmount+row['filename']) ##doesnt work accorss filesystems!
			    shutil.move(root+'/'+filename,dmount+row['filename'])


		            if os.path.exists(dmount+row['filename']):
                                stat2 = os.stat(dmount+row['filename'])
				if stat2.st_size != stat.st_size:
				        print 'SIZE CHECK FAILED'
				        sys.exit(2)

    			        print "REPLACE(replicas,'"+replica+"','"+dest+"')"
                            
                                cex.execute("UPDATE "+config.database['file_table']+" SET " + \
                                    "replicas = REPLACE(replicas,'"+replica+"','"+dest+"') " + \
                                    "WHERE file_id = "+str(row['file_id']))
                            else:
				print 'FILE NOT FOUND ON DEST'
                                sys.exit(2)

            
            print "-----------"

#############################################################################

def main(argv):
    action = 'mover'
    replica = ''
    dest = ''
    path = ''
    classinc = []
    classexc = []
    try:
        opts, args = getopt.getopt(argv,"p:r:d:i:e:",["path=","replica=","dest=","include=","exclude="])
    except getopt.GetoptError:
        print 'mover.py -p /geograph_live/geograph_live/public_html/geophotos/03/38/39 -r teas1 -d teas2'
        sys.exit(2)
    
    for opt, arg in opts:
        if opt in ("-d", "--dest"):
            dest = arg
        elif opt in ("-p", "--path"):
            path = arg.rstrip("/")
        elif opt in ("-r", "--replica"):
            replica = arg
        elif opt in ("-i", "--include"):
            classinc.append(arg)
        elif opt in ("-e", "--exclude"):
            classexc.append(arg)
    
    if path == '' or replica == '' or dest == '':
        print 'mover.py -p /geograph_live/public_html/geophotos/03/38/39 -r teas1 -d teas2'
        sys.exit(2)

    move_files(path,replica,dest,classinc,classexc)

if __name__ == '__main__':
    main(sys.argv[1:])

#############################################################################

