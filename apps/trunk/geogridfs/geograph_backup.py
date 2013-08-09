#!/usr/bin/env python

# $Project: GeoGraph $
# $Id$
__version__ = filter(str.isdigit, "$Revision$")

## Script to run on Geograph File System backup nodes. Performs two main functions:
#
#  Walk the local disk, and tells the central server about the files available
#     This is used once to first provision your node, if already have some backup files
#
#  A replication function, that asks the metadata server for a list of new files,
#     Then downloads them from the website directly.
#     This is used regually (eg once an hour) to replicate brand new files. 
##
#    Copyright (C) 2013  Barry Hunter  <geo@barryhunter.co.uk>
#
#    This program can be distributed under the terms of the GNU LGPL.
#    See the file COPYING.
#

import os, sys
import random
import hashlib
import getopt
import string
import shutil
import re
import json
import urllib
import urllib2
import time
import hmac


config = dict(
    folder = '/mnt/fake', # Set this the folder where you store files 
    keep_free = '2G', # we wont replicate if less than this disk space (only G units supported!)
    
    # Get these from Geograph Support
    mode = 'partial', 
    api_endpoint = '', 
    download_docroot = '', 
    download_endpoint = '', 
    identity = '', 
    secret = '', # don't share this secret!
)

#############################################################################

class AppURLopener(urllib.FancyURLopener):
    version = "geograph_backup.py/"+__version__+" ("+ config['identity']+")"

urllib._urlopener = AppURLopener()

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
    mount = config['folder']

    print mount+folder
    for root, dirs, files in os.walk(mount+folder):
        
        if files:
            print root
            #print dirs
            print files
            
            query = "ident="+config['identity']+"&command=filelist&folder=" + urllib.quote(string.replace(root,mount,''))+"&r="+str(random.randint(1,100000))
            
            sig = hmac.new(config['secret'], query);
            url = config['api_endpoint'] + "?" + query + "&sig="+sig.hexdigest()
            
            print url
            
            req = urllib2.Request(url)
            req.add_header('User-agent', urllib._urlopener.version)
            f = urllib2.urlopen(req)
            response = f.read()
            f.close()
            
            result = json.read(response)
            
            if result and 'error' in result:
                print result['error']
                sys.exit(2)

            ##SELECT file_id,filename,backups,size,md5sum,UNIX_TIMESTAMP(file_modified) AS modified
            
            notify = []
            
            for row in result:
                
                filename = os.path.basename(row['filename'])
                if filename in files:
                    ##We have the file, lets check we noted in replicas
                    
                    if config['identity'] in row['backups']: 
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
                        elif int(stat.st_size) != int(row['size']):
                            print "BUT size doesnt match '"+str(stat.st_size)+"' != '"+str(row['size'])+"'"
                        #elif stat.st_mtime != row['modified']:
                        #    print "BUT dates doesnt match '"+str(stat.st_mtime)+"' != '"+str(row['modified'])+"'"
                        else:
                            notify.append(row['file_id'])
                    
                    files.remove(filename) ## so that any left will be new files!
                else:
                    ##there is a file on the FS, that we don't have - ignore here (the replicate function may download it later)
                    pass
            
            if files:
                for filename in files:
                    print "We have a unknown file! "+ filename
                    
            if notify:
                query = "ident="+config['identity']+"&command=notify&folder=" + urllib.quote(string.replace(root,mount,''))+"&r="+str(random.randint(1,100000))
                
                sig = hmac.new(config['secret'], query);
                url = config['api_endpoint'] + "?" + query + "&sig="+sig.hexdigest()
                
                data = urllib.urlencode({'file_ids': ' '.join(notify)})
                
                req = urllib2.Request(url, data)
                req.add_header('User-agent', urllib._urlopener.version)
                f = urllib2.urlopen(req)
                response = f.read()
                f.close()
                
                print response

            print "-----------"

#############################################################################

def replicate_now(path = ''):
    mount = config['folder']
    
    query = "ident="+config['identity']+"&command=filelist&mode="+config['mode']+"&r="+str(random.randint(1,100000)) #just to defeat caching
    
    sig = hmac.new(config['secret'], query);
    url = config['api_endpoint'] + "?" + query + "&sig="+sig.hexdigest()

    req = urllib2.Request(url)
    req.add_header('User-agent', urllib._urlopener.version)
    f = urllib2.urlopen(req)
    response = f.read()
    f.close()

    result = json.read(response)

    if result and 'error' in result:
        print result['error']
        sys.exit(2)

    ##SELECT file_id,filename,size,md5sum,UNIX_TIMESTAMP(file_modified) AS modified

    notify = []
    
    c = 0;

    for row in result:

        url = string.replace(row['filename'],config['download_docroot'],config['download_endpoint'])
        
        print "download " + row['filename'] + " from "+ url
        
        if not os.path.exists(os.path.dirname(mount + row['filename'])):
            os.makedirs(os.path.dirname(mount + row['filename'])) ##recursive
        
        urllib.urlretrieve(url, mount + row['filename'])
        
        stat = os.stat(mount + row['filename'])
        if (stat.st_size > 0):
            os.utime(mount + row['filename'], (int(time.time()), int(row['modified'])) )
            md5su = md5sum(mount + row['filename'])
        else:
            md5su =''
        
        if md5su != row['md5sum']:
            print "BUT md5 checksum doesnt match '"+md5su+"' != '"+row['md5sum']+"'"
        elif int(stat.st_size) != int(row['size']):
            print "BUT size doesnt match"
        #elif stat.st_mtime != row['modified']:
        #    print "BUT dates doesnt match '"+str(stat.st_mtime)+"' != '"+str(row['modified'])+"'"
        else:
            notify.append(row['file_id'])

        time.sleep(2)
    
    if notify:
        query = "ident="+config['identity']+"&command=notify&mode="+config['mode']+"&r="+str(random.randint(1,100000))

        sig = hmac.new(config['secret'], query);
        url = config['api_endpoint'] + "?" + query + "&sig="+sig.hexdigest()

        data = urllib.urlencode({'file_ids': ' '.join(notify)})

        req = urllib2.Request(url, data)
        req.add_header('User-agent', urllib._urlopener.version)
        f = urllib2.urlopen(req)
        response = f.read()
        f.close()
        
        print response

#############################################################################

def main(argv):
    action = 'unknown'
    path = ''
    try:
        opts, args = getopt.getopt(argv,"a:p:",["action=","path="])
    except getopt.GetoptError:
        print 'replication.py -a (walk|replicate) [-p /geograph_live/rastermaps]'
        sys.exit(2)
    
    for opt, arg in opts:
        if opt in ("-a", "--action"):
            action = arg
        elif opt in ("-p", "--path"):
            path = arg
    
    if action == 'unknown':
        print 'replication.py -a (walk|replicate) [-p /geograph_live/rastermaps]'
        sys.exit(2)
    
    elif action == 'walk':
        walk_and_notify(path)
    
    elif action == 'replicate':
        replicate_now(path)


if __name__ == '__main__':
    main(sys.argv[1:])

#############################################################################

