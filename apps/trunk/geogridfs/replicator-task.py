#!/usr/bin/env python

# $Project: GeoGraph $
# $Id: replicator.py 7979 2013-08-20 13:18:46Z barry $
__version__ = filter(str.isdigit, "$Revision: 7979 $")

## Script to run on Geograph File System storage node servers. Performs two main functions:
#
#  Walk the local disk, and tells the metadata server about the files available
#     This is used once to first provision a none-empty folder
#
#  A replication function, that asks the metadata server for a list of new files,
#     Then copies them directly from other storage nodes
#     This is used regually (eg once a minute) to replicate brand new files. 
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
import sys

db=MySQLdb.connect(host=config.database['hostname'], user=config.database['username'], passwd=config.database['password'],db=config.database['database'])

#############################################################################

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

def replicate_now(path = '', target = '', order = ''):
    if target == '':
	target = config.server['self']

    if order == '':
	order = "shard DESC,RAND()"
    if order == 'rand':
        order = "RAND()"

    target_snub = re.sub(r'[sh]\d$','',target)

    c=db.cursor(MySQLdb.cursors.DictCursor)
    cex=db.cursor()

    print "SELECT * FROM replica_task WHERE target LIKE '"+target_snub+"%' AND `executed` = '0000-00-00 00:00:00' ORDER BY "+order+" LIMIT 1"    
    c.execute("SELECT * FROM replica_task WHERE target LIKE '"+target_snub+"%' AND `executed` = '0000-00-00 00:00:00' ORDER BY "+order+" LIMIT 1")
    row = c.fetchone()
    if not row:
        print "No tasks"
	return

    #print row

    target = row['target']

    start = row['shard']*10000
    end = start+9999

    crit = "file_id BETWEEN "+str(start)+" AND "+str(end)+" AND "+row['clause']+" AND replica_count > 0"
	## AND replicas NOT LIKE '%"+target_snub+"%'"

    mount = config.mounts[target]

    if not os.path.exists(mount + '/geograph_live/'):
	return mount + " does not appear to be active (no geograph_live folder)"

    if 'statvfs' in dir(os):
        s = os.statvfs(mount+'/geograph_live/')
        bytes_free = (s.f_bavail * s.f_frsize) / 1024
        gigabytes = bytes_free / (1024 * 1024)

        if gigabytes < 10:
            print "There is only " + str(bytes_free) + " bytes free, cowardly refusing to run"
            sys.exit(2)

    cex.execute("UPDATE replica_task SET executed = NOW() WHERE task_id = "+str(row['task_id']))
    
    c.execute("DESCRIBE "+config.database['file_table']);
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
    
    #print "idx = "+str(idx)

    print "SELECT file_id,filename,replicas,size,md5sum FROM "+config.database['file_table']+" WHERE NOT replicas & "+str(idx)+" AND "+crit
    
    c.execute("SELECT file_id,filename,replicas,size,md5sum FROM "+config.database['file_table']+" WHERE NOT replicas & "+str(idx)+" AND "+crit)
    print("              / "+str(c.rowcount)+" rows\r"),
    sys.stdout.flush()

   
    i=1
    done=0;
    while True:
        row = c.fetchone()
        if not row: break
        
        replicas = string.split(row['replicas'],',')
        
        #todo we could loop though them in case of failures, and should we tell anyone about failures?
        replica = random.choice(replicas)

        #print "download " + row['filename'] + " from "+ replica
        print(str(i)+"\r"),
        if not i%13:
           sys.stdout.flush()
        
        filename = mount + row['filename']
        
        if os.path.exists(filename):
            if os.path.getsize(filename) == 0:
                unlink(filename)
                
            #todo, later we should allow their newer file to overwrite our older file. (to allow for new versions of files) BUT not if class = full.jpg/thumbs.jpg etc
        else:
            if not os.path.exists(os.path.dirname(filename)):
                os.makedirs(os.path.dirname(filename)) ##recursive

        if not os.path.exists(filename):
            shutil.copy2(config.mounts[replica] + row['filename'], filename)
        
        stat = os.stat(filename)
        if (stat.st_size > 0 and stat.st_size < 52428800):
            md5su = md5sum(filename)
        else:
            md5su =''
        
        if md5su != row['md5sum']:
            print "md5 mismatch '"+md5su+"' != '"+row['md5sum']+"' : "+row['filename']
            #todo - report this somewhere!
        elif stat.st_size != row['size']:
            print "size mismatch : "+row['filename']
            #todo - report this somewhere!
        #elif stat.st_mtime != row['modified']:
        #    print "BUT dates doesnt match '"+str(stat.st_mtime)+"' != '"+str(row['modified'])+"'"
        else:
            cex.execute("UPDATE "+config.database['file_table']+" SET " + \
                "replicas = CONCAT(replicas,',"+target+"'), " + \
                "replica_count=replica_count+1 "+ \
                "WHERE file_id = "+str(row['file_id']))
            done=done+1

        i=i+1


    print "done "+str(done)

#############################################################################

def main(argv):
    action = 'replicate'
    replica = ''
    path = ''
    order = ''
    try:
        opts, args = getopt.getopt(argv,"a:p:r:o:",["action=","path=","replica=","order="])
    except getopt.GetoptError:
        print 'replication.py -a (walk|replicate) [-p /geograph_live/rastermaps] [-r milk] [-o'
        sys.exit(2)
    
    for opt, arg in opts:
        if opt in ("-a", "--action"):
            action = arg
        elif opt in ("-r", "--replica"):
            replica = arg
        elif opt in ("-p", "--path"):
            path = arg
        elif opt in ("-o", "--order"):
            order = arg
    
    if action == 'unknown':
        print 'replication.py -a (walk|replicate) [-p /geograph_live/rastermaps] [-r milk]'
        sys.exit(2)
    
    elif action == 'replicate':
        replicate_now(path, replica, order)

if __name__ == '__main__':
    main(sys.argv[1:])

#############################################################################

