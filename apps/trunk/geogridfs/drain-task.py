#!/usr/bin/env python

# $Project: GeoGraph $
# $Id: replicator.py 7979 2013-08-20 13:18:46Z barry $
__version__ = filter(str.isdigit, "$Revision: 7979 $")

## Script to run on Geograph File System storage node servers.
#
# Worker Client to process 'drain_task' from the database. Deleting files (!!) as long as they are valid files. Used to clear out a replica, or simply to redistribute files
#  (The tasks themselves are generated elsewhere!)
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

def getReplicaIndex(target):
    c=db.cursor(MySQLdb.cursors.DictCursor)
    c.execute("DESCRIBE "+config.database['file_table']+" replicas");
    idx = False
    while True:
        row = c.fetchone()
        if not row: break

        if row['Field'] == 'replicas':
            list = string.replace(string.replace(row['Type'],"set('",''),"')",'');
            idx = 1
            for item in string.split(list, "','"):
                if item == target:
                    break
                idx = idx*2
          
       	    break
    return idx

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

def drain_now(path = '',target='', order = ''):
    if target == '':
        target = config.server['self']
        target = re.sub(r'[sh]\d$','',target)

    if order == '':
        order = "shard DESC,RAND()"
    if order == 'rand':
        order = "RAND()"


    c=db.cursor(MySQLdb.cursors.DictCursor)
    cex=db.cursor()

    print "SELECT * FROM drain_task WHERE target LIKE '"+target+"%' AND `executed` = '0000-00-00 00:00:00' ORDER BY "+order+" LIMIT 1"
    c.execute("SELECT * FROM drain_task WHERE target LIKE '"+target+"%' AND `executed` = '0000-00-00 00:00:00' ORDER BY "+order+" LIMIT 1")

    row = c.fetchone()
    if not row:
        print "No tasks"
        return

    #just in case it was only a partical target, change to the full target
    target = row['target']

    start = row['shard']*10000
    end = start+9999

    crit = "file_id BETWEEN "+str(start)+" AND "+str(end)+" AND "+row['clause']

    mount = config.mounts[target]

    if not os.path.exists(mount + '/geograph_live/'):
        return mount + " does not appear to be active (no geograph_live folder)"


    cex.execute("UPDATE drain_task SET executed = NOW() WHERE task_id = "+str(row['task_id']))
    
    idx = getReplicaIndex(target)
    
    c.execute("SELECT file_id,filename,replicas,size,md5sum FROM "+config.database['file_table']+" WHERE replicas & "+str(idx)+" AND replica_count > 1 AND "+crit)

    print("              / "+str(c.rowcount)+" rows\r"),
    sys.stdout.flush()

    while True:
        row = c.fetchone()
        if not row: break
        
        filename = mount + row['filename']
        
        if os.path.exists(filename):
            print "we have " + row['filename'] + " - and about to delete from "+target+" ..."
        
            stat = os.stat(filename)
            if (stat.st_size > 0 and stat.st_size < 52428800):
                md5su = md5sum(filename)
            else:
                md5su =''
        
            if md5su != row['md5sum']:
                print "BUT md5 checksum doesnt match '"+md5su+"' != '"+row['md5sum']+"'"
                #todo - report this somewhere!
            elif stat.st_size != row['size']:
                print "BUT size doesnt match"
                #todo - report this somewhere!
            #elif stat.st_mtime != row['modified']:
            #    print "BUT dates doesnt match '"+str(stat.st_mtime)+"' != '"+str(row['modified'])+"'"
            else:
                os.unlink(filename)

                cex.execute("UPDATE "+config.database['file_table']+" SET " + \
                    "replicas = REPLACE(replicas,'"+target+"',''), " + \
                    "replica_count=replica_count-1 "+ \
                    "WHERE file_id = "+str(row['file_id']))

        else:
            print "we dont have " + row['filename']

    print "\ndone"

#############################################################################

def main(argv):
    action = 'drain'
    replica = ''
    path = ''
    order = ''
    try:
        opts, args = getopt.getopt(argv,"a:p:r:o:",["action=","path=","replica=","order="])
    except getopt.GetoptError:
        print 'replication.py -a (walk|replicate) [-p /geograph_live/rastermaps]'
        sys.exit(2)
    
    for opt, arg in opts:
        if opt in ("-a", "--action"):
            action = arg
        elif opt in ("-r", "--replica"):
            replica = arg
        elif opt in ("-p", "--path"):
            path = arg.rstrip("/")
        elif opt in ("-o", "--order"):
            order = arg
    
    if action == 'unknown':
        print 'replication.py -a (walk|replicate) [-p /geograph_live/rastermaps]'
        sys.exit(2)
    
    elif action == 'drain':
        drain_now(path, replica, order)

if __name__ == '__main__':
    main(sys.argv[1:])

#############################################################################

