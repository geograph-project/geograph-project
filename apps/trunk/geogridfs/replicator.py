#!/usr/bin/env python

# $Project: GeoGraph $
# $Id$
__version__ = filter(str.isdigit, "$Revision$")

## Script to run on Geograph File System storage node servers. Performs two main functions:
#
#  Walk the local disk, and tells the metadata server about the files available
#     This is used once to first provision a none-empty folder
#
#  A auto replication function, that asks the metadata server for a list of new files,
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

def walk_and_notify(folder = '', replica = '', track_progress = True):
    if replica == '':
	replica = config.server['self']

    mount = config.mounts[replica]

    print mount+folder
    for root, dirs, files in os.walk(mount+folder):
        
        if files:
            print root

            if track_progress and os.path.exists(root+'/replicator.done'):
                continue
            
            #print dirs
            #print files
            
            folder_id = getFolderId(string.replace(root,mount,''), True)
            
            c=db.cursor(MySQLdb.cursors.DictCursor)
            cex=db.cursor()
            c.execute("SELECT file_id,filename,replicas,size,md5sum,UNIX_TIMESTAMP(file_modified) AS modified FROM "+config.database['file_table']+" WHERE folder_id = "+folder_id)
            
            while True:
                row = c.fetchone()
                if not row: break
                
                filename = os.path.basename(row['filename'])
                if filename in files:
                    ##We have the file, lets check we noted in replicas
                    
                    if replica in row['replicas']: 
                        #print "great, metadata already knows we have "+row['filename']
                        pass
                    else:
                        print "hey! we have "+row['filename']
                        specifics = ''
                        
                        stat = os.stat(root + "/" + filename)
                        if (stat.st_size > 0 and stat.st_size < 52428800):
                            md5su = md5sum(root + "/" + filename)
                        else:
                            md5su =''
                        
                        if row['md5sum'] == '' and md5su != '': #we can repair this file
                            row['md5sum'] = md5su
                            row['size'] = stat.st_size
                            specifics = "`size` = "+str(stat.st_size)+", " + \
                                "`md5sum` = '"+md5su+"', "

                        if md5su != row['md5sum']:
                            print "BUT md5 checksum doesnt match '"+md5su+"' != '"+row['md5sum']+"'"
                        elif stat.st_size != row['size']:
                            print "BUT size doesnt match"
                        #elif stat.st_mtime != row['modified']:
                        #    print "BUT dates doesnt match '"+str(stat.st_mtime)+"' != '"+str(row['modified'])+"'"
                        else:
                            print "OK SEND THE UPDATE"
                            
                            #include the replica filter, on the small offchance another task sent the same file recently. avoids double counting in replica_count!
                            cex.execute("UPDATE "+config.database['file_table']+" SET " + \
                                "replicas = CONCAT(replicas,',"+replica+"'), " + \
                                specifics + \
                                "replica_count=replica_count+1 "+ \
                                "WHERE replicas NOT like '%"+replica+"%' AND file_id = "+str(row['file_id']))
                    
                    files.remove(filename) ## so that any left will be new files!
                #else:
                #    ##there is a file on the FS, that we don't have - ignore here (the replicate function may download it later)
                #    print "skipping " + row['filename']
            
            if files:
                for filename in files:
                    print "sending new file "+ filename
                    path = string.replace(root,mount,'') + "/" + filename
                    
                    stat = os.stat(root + "/" + filename)
                    if (stat.st_size > 0 and stat.st_size < 52428800):
                        md5su = md5sum(root + "/" + filename)
                    else:
                        md5su =''
                    specifics = "`size` = "+str(stat.st_size)+", " + \
                        "`file_created` = FROM_UNIXTIME("+str(stat.st_ctime)+"), " + \
                        "`file_modified` = FROM_UNIXTIME("+str(stat.st_mtime)+"), " + \
                        "`file_accessed` = FROM_UNIXTIME("+str(stat.st_atime)+"), " + \
                        "`md5sum` = '"+md5su+"', "
                    
                    final = False
                    targets = ''
                    for pattern in config.patterns:
                        if re.search(pattern[1],path):
                            final = pattern
                            break
                    if final:
                        targets = "`class` = '"+final[0]+ "', " + \
                            "`replica_target` = "+str(final[2])+ ", " + \
                            "`backup_target` = "+str(final[3])+ ", "
                    
                    c.execute("INSERT INTO "+config.database['file_table']+" SET meta_created = NOW(), " + \
                        "filename = '"+db.escape_string(path)+"', " + \
                        "folder_id = "+str(folder_id)+", " + \
                        specifics + targets + \
                        "replicas = '"+replica+"', " + \
                        "replica_count=1")
            
            if track_progress:
                if os.path.getmtime(root) < time.time()-21600:
                    open(root+'/replicator.done', 'w').close()
            print "-----------"

#############################################################################

def replicate_now(path = '',target = ''):
    if target == '':
	target = config.server['self']

    mount = config.mounts[target]

    if not os.path.exists(mount + '/geograph_live/public_html/geophotos/'):
        return mount + " does not appear to be active (no geograph_live folder)"

    if 'statvfs' in dir(os):
        s = os.statvfs(mount+'/geograph_live/') #for amazon this is ok, as its actully checking free space in cache!
        bytes_free = (s.f_bavail * s.f_frsize) / 1024
        gigabytes = bytes_free / (1024 * 1024)

        if gigabytes < 10:
            print "There is only " + str(bytes_free) + " bytes free, cowardly refusing to run"
            sys.exit(2)
    
    c=db.cursor(MySQLdb.cursors.DictCursor)
    cex=db.cursor()

    target_snub = re.sub(r'[sh]\d$','',target)
    
    if target_snub == target:
	    # in theory bit masks is a little more efficent than a LIKE match 
            # for now only used for single mount servers
            idx = getReplicaIndex(target)
	    crit = "NOT replicas & "+str(idx)
    else:
	    crit = "replicas NOT LIKE '%"+target_snub+"%'"

    crit = crit + " AND replica_count > 0 AND replica_count < replica_target AND file_id > (select max(file_id) from file)-10000 "

    if target == 'amz':
	crit = crit + " AND class IN ('full.jpg','original.jpg')"

    print crit
    
    c.execute("SELECT file_id,filename,replicas,size,md5sum FROM "+config.database['file_table']+" WHERE "+crit+" ORDER BY folder_id DESC LIMIT 400")

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
                
        filename = mount + row['filename']
        
        if os.path.exists(filename):
            if os.path.getsize(filename) == 0:
                os.unlink(filename)
                
            #todo, later we should allow their newer file to overwrite our older file. (to allow for new versions of files) BUT not if class = full.jpg/thumbs.jpg etc
        else:
            if not os.path.exists(os.path.dirname(filename)):
                os.makedirs(os.path.dirname(filename)) ##recursive

        if not os.path.exists(filename):
            if target == 'amz':
                ##copy2, perform copy then does copystat. s2fs implents chown/touch etc as COPY operations which cost! (note .copy() still does chown, hence copyfile)
                shutil.copyfile(config.mounts[replica] + row['filename'], filename)
            else:
                shutil.copy2(config.mounts[replica] + row['filename'], filename)
        
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
            #include the replica filter, on the small offchance another task sent the same file recently. avoids double counting in replica_count!
            cex.execute("UPDATE "+config.database['file_table']+" SET " + \
                "replicas = CONCAT(replicas,',"+target+"'), " + \
                "replica_count=replica_count+1 "+ \
                "WHERE replicas NOT LIKE '%"+target+"%' AND file_id = "+str(row['file_id']))

#############################################################################

def main(argv):
    action = 'unknown'
    replica = ''
    path = ''
    try:
        opts, args = getopt.getopt(argv,"a:p:r:",["action=","path=","replica="])
    except getopt.GetoptError:
        print 'replicator.py -a (walk|replicate) [-p /geograph_live/rastermaps] [-r milk]'
        sys.exit(2)
    
    for opt, arg in opts:
        if opt in ("-a", "--action"):
            action = arg
        elif opt in ("-r", "--replica"):
            replica = arg
        elif opt in ("-p", "--path"):
            path = arg.rstrip("/")
    
    if action == 'unknown':
        print 'replicator.py -a (walk|replicate) [-p /geograph_live/rastermaps] [-r milk]'
        sys.exit(2)
    
    elif action == 'walk':
        if replica == 'all':
		for (key,value) in config.mounts.iteritems():
		        walk_and_notify(path, key)
	else:
		walk_and_notify(path, replica)
    
    elif action == 'replicate':
        replicate_now(path, replica)

if __name__ == '__main__':
    main(sys.argv[1:])

#############################################################################
