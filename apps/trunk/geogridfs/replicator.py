#!/usr/bin/env python

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

def walk_and_notify(folder = ''):
    mount = config.mounts[config.server['self']]

    print mount+folder
    for root, dirs, files in os.walk(mount+folder):
        
        if files:
            print root
            #print dirs
            print files
            folder_id = getFolderId(string.replace(root,mount,''), True)
            
            c=db.cursor(MySQLdb.cursors.DictCursor)
            cex=db.cursor()
            c.execute("SELECT file_id,filename,replicas,size,md5sum,FROM_UNIXTIME(file_modified) AS modified FROM "+config.database['file_table']+" WHERE folder_id = "+folder_id)
            
            while True:
                row = c.fetchone()
                if not row: break
                
                filename = os.path.basename(row['filename'])
                if filename in files:
                    ##We have the file, lets check we noted in replicas
                    
                    if config.server['self'] in row['replicas']: 
                        print "great, metadata already knows we have "+row['filename']
                    else:
                        print "hey! we have "+row['filename']
                        
                        stat = os.stat(root + "/" + filename)
                        if (stat.st_size > 0):
                            md5su = md5sum(root + "/" + filename)
                        else:
                            md5su =''
                        
                        if md5su != row['md5sum']:
                            print "BUT md5 checksum doesnt match '"+md5su+"' != '"+row['md5sum']+"'"
                        elif stat.st_size != row['size']:
                            print "BUT size doesnt match"
                        #elif stat.st_mtime != row['modified']:
                        #    print "BUT dates doesnt match '"+str(stat.st_mtime)+"' != '"+str(row['modified'])+"'"
                        else:
                            print "OK SEND THE UPDATE"
                            
                            cex.execute("UPDATE "+config.database['file_table']+" SET " + \
                                "replicas = CONCAT(replicas,',"+config.server['self']+"'), " + \
                                "replica_count=replica_count+1 "+ \
                                "WHERE file_id = "+str(row['file_id']))
                    
                    files.remove(filename) ## so that any left will be new files!
                else:
                    ##there is a file on the FS, that we don't have - ignore here (the replicate function may download it later)
                    print "skipping " + row['filename']
            
            if files:
                for filename in files:
                    print "sending new file "+ filename
                    stat = os.stat(root + "/" + filename)
                    if (stat.st_size > 0):
                        md5su = md5sum(root + "/" + filename)
                    else:
                        md5su =''
                    
                    specifics = "`size` = "+str(stat.st_size)+", " + \
                         "`file_created` = FROM_UNIXTIME("+str(stat.st_ctime)+"), " + \
                         "`file_modified` = FROM_UNIXTIME("+str(stat.st_mtime)+"), " + \
                         "`file_accessed` = FROM_UNIXTIME("+str(stat.st_atime)+"), " + \
                         "`md5sum` = '"+md5su+"', "
                    
                    path = string.replace(root,mount,'') + "/" + filename
                    
                    c.execute("INSERT INTO "+config.database['file_table']+" SET meta_created = NOW(), " + \
                         "filename = '"+db.escape_string(path)+"', " + \
                         "folder_id = "+str(folder_id)+", " + \
                         specifics + \
                         "replicas = '"+config.server['self']+"', " + \
                         "replica_count=1")

            print "-----------"

#############################################################################

def replicate_now():
    mount = config.mounts[config.server['self']]
    
    c=db.cursor(MySQLdb.cursors.DictCursor)
    cex=db.cursor()
    
    c.execute("DESCRIBE "+config.database['file_table']);
    while True:
        row = c.fetchone()
        if not row: break
        
        if row['Field'] == 'replicas':
            list = string.replace(string.replace(row['Type'],"set('",''),"')",'');
            idx = 1
            for item in string.split(list, "','"):
                if item == config.server['self']:
                    break
                idx = idx*2
            
            break
    
    print "idx = "+str(idx)
    
    c.execute("SELECT file_id,filename,replicas,size,md5sum FROM "+config.database['file_table']+" WHERE NOT replicas & "+str(idx)+" AND replica_count < replica_target ORDER BY folder_id LIMIT 10");
    while True:
        row = c.fetchone()
        if not row: break
        
        replicas = string.split(row['replicas'],',')
        
        #todo we could loop though them in case of failures, and should we tell anyone about failures?
        replica = random.choice(replicas)
        print "download " + row['filename'] + " from "+ replica
        
        if not os.path.exists(os.path.dirname(mount + row['filename'])):
            os.makedirs(os.path.dirname(mount + row['filename'])) ##recursive
        
        shutil.copy2(config.mounts[replica] + row['filename'], mount + row['filename'])
        
        stat = os.stat(mount + row['filename'])
        if (stat.st_size > 0):
            md5su = md5sum(mount + row['filename'])
        else:
            md5su =''
        
        if md5su != row['md5sum']:
            print "BUT md5 checksum doesnt match '"+md5su+"' != '"+row['md5sum']+"'"
        elif stat.st_size != row['size']:
            print "BUT size doesnt match"
        #elif stat.st_mtime != row['modified']:
        #    print "BUT dates doesnt match '"+str(stat.st_mtime)+"' != '"+str(row['modified'])+"'"
        else:
            cex.execute("UPDATE "+config.database['file_table']+" SET " + \
                "replicas = CONCAT(replicas,',"+config.server['self']+"'), " + \
                "replica_count=replica_count+1 "+ \
                "WHERE file_id = "+str(row['file_id']))

#############################################################################

def main(argv):
    action = 'unknown'
    try:
        opts, args = getopt.getopt(argv,"a:",["action="])
    except getopt.GetoptError:
        print 'replication.py -a (walk|replicate)'
        sys.exit(2)
    
    for opt, arg in opts:
        if opt in ("-a", "--action"):
            action = arg
            
    if action == 'unknown':
        print 'replication.py -a (walk|replicate)'
        sys.exit(2)
    
    elif action == 'walk':
        walk_and_notify()
    
    elif action == 'replicate':
        replicate_now()

if __name__ == '__main__':
    main(sys.argv[1:])

#############################################################################

