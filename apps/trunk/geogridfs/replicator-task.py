#!/usr/bin/env python

# $Project: GeoGraph $
# $Id: replicator.py 7979 2013-08-20 13:18:46Z barry $
__version__ = filter(str.isdigit, "$Revision: 7979 $")

## Script to run on Geograph File System storage node servers.
#
# Worker Client to process 'replica_task' from the database. Copies files from any node that has the file to a specific node
#  (The tasks themselves are generated elsewhere!)
#
##
#    Copyright (C) 2015  Barry Hunter  <geo@barryhunter.co.uk>
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
import tinys3

db=MySQLdb.connect(host=config.database['hostname'], user=config.database['username'], passwd=config.database['password'],db=config.database['database'])

#############################################################################

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

    #print "SELECT * FROM replica_task WHERE target LIKE '"+target_snub+"%' AND `executed` = '0000-00-00 00:00:00' ORDER BY "+order+" LIMIT 1"    
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

    if not os.path.exists(mount + '/geograph_live/public_html/geophotos/'):
	return mount + " does not appear to be active (no geograph_live folder)"

    if 'statvfs' in dir(os):
        s = os.statvfs(mount+'/geograph_live/')
        bytes_free = (s.f_bavail * s.f_frsize) / 1024
        gigabytes = bytes_free / (1024 * 1024)

        if gigabytes < 10:
            print "There is only " + str(bytes_free) + " bytes free, cowardly refusing to run"
            sys.exit(2)

    cex.execute("UPDATE replica_task SET executed = NOW() WHERE task_id = "+str(row['task_id']))
    
    idx = getReplicaIndex(target)

    print "SELECT file_id,filename,replicas,size,md5sum FROM "+config.database['file_table']+" WHERE NOT replicas & "+str(idx)+" AND "+crit
    
    c.execute("SELECT file_id,filename,replicas,size,md5sum FROM "+config.database['file_table']+" WHERE NOT replicas & "+str(idx)+" AND "+crit)
    print("              / "+str(c.rowcount)+" rows\r"),
    sys.stdout.flush()

    if target == 'amz' and config.amazon:
        s3conn = tinys3.Connection(config.amazon['access'],config.amazon['secret'], tls=False, endpoint=config.amazon['endpoint'])
   
    i=1
    done=0;
    while True:
        row = c.fetchone()
        if not row: break
        
        replicas = string.split(row['replicas'],',')
        replica = False

        # loop though replicas to find a file. Stop on first as SET is roughly in preference order
        for key in replicas:
            if os.path.exists(config.mounts[key] + row['filename']):
                replica = key
                break

        if not replica:
            print "SKIPPING as file NOT found on any mount"
            continue

        if target == 'amz':
            print "download " + row['filename'] + " from "+ replica
        print(str(i)+"\r"),
        if not i%13:
            sys.stdout.flush()
        
        filename = mount + row['filename']
        
        if os.path.exists(filename):
            if os.path.getsize(filename) == 0:
                os.unlink(filename)
                
            #todo, later we should allow their newer file to overwrite our older file. (to allow for new versions of files) BUT not if class = full.jpg/thumbs.jpg etc
        else:
            if not os.path.exists(os.path.dirname(filename)):
                os.makedirs(os.path.dirname(filename)) ##recursive

        if target == 'amz':
            if config.amazon:
                # if possible use a class, avoids some overhead. (s3fs does a double PUT) 

                bucket = False
                for pattern in config.buckets:
                    if pattern[0] in row['filename']:
                        bucket = pattern
                        s3name = string.replace(row['filename'],bucket[0],bucket[1])
                        break

                if not bucket:
		    print "Unknown Amazon Bucket for "+row['filename']
                    sys.exit(2);

                # the x-amz-meta-mtime is for compatiblity with s3fs
	        mtime = int(os.path.getmtime(config.mounts[replica] + row['filename']))

                f = open(config.mounts[replica] + row['filename'],'rb')
                r = s3conn.upload(s3name, f, bucket=bucket[2], expires='max', public=bucket[3], close=True, \
                         headers={'x-amz-storage-class': ('STANDARD' if row['size'] < 50000 and bucket[4] == 'STANDARD_IA' else bucket[4]), 'x-amz-meta-mtime': str(mtime)} )

                #small files, store as STANDARD rather than STANDARD_IA, as there is a minumum of 120kb. 50k is used, because IA is still 40% cost of STD.

                if str(r) == '<Response [200]>':
                    #todo, this is NOT ideal, we just pretend it worked. We could WAIT, as amazon is only eventual consistant!
                    md5su = row['md5sum']
                    size = row['size']
                else:
                    print "Got different status from Amazon: "+r
                    md5su = '???'

            else:
                #else copy it via FS, which is using s3fs etc

                if not os.path.exists(filename):
                    ##copy2, perform copy then does copystat. s2fs implents chown/touch etc as COPY operations which cost!
                    shutil.copyfile(config.mounts[replica] + row['filename'], filename)

                stat = os.stat(filename)
                size = stat.st_size
                if (stat.st_size > 0 and stat.st_size < 52428800):
                    md5su = md5sum(filename)
                else:
                    md5su =''

        else:
            shutil.copy2(config.mounts[replica] + row['filename'], filename)
        
            stat = os.stat(filename)
            size = stat.st_size
            if (stat.st_size > 0 and stat.st_size < 52428800):
                md5su = md5sum(filename)
            else:
                md5su =''
        
        if md5su != row['md5sum']:
            print "md5 mismatch '"+md5su+"' != '"+row['md5sum']+"' : "+row['filename']
            #todo - report this somewhere!
        elif size != row['size']:
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
            path = arg.rstrip("/")
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

