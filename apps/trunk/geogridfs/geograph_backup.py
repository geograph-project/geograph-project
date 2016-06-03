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
import json
import urllib
import urllib2
import time
import hmac

#############################################################################

import ConfigParser
configparser = ConfigParser.ConfigParser()
configparser.read(os.path.dirname(os.path.realpath(__file__))+"/geograph_backup.ini")

config = dict()
for option in configparser.options('client'):
    config[option] = configparser.get('client', option)

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

##the json interface has changed between python versions!
def json_read(inp):
    try:
        return json.read(inp) #2.4
    except AttributeError:
        return json.loads(inp) #2.7

def json_write(inp):
    try:
        return json.write(inp) #2.4
    except AttributeError:
        return json.dumps(inp) #2.7

#############################################################################

def walk_and_notify(folder = '', track_progress = True, reverify = False, sleeper = 0):
    mount = config['folder']
    mode = 'walk'
    if reverify:
        mode='rewalk'

    print mount+folder
    for root, dirs, files in os.walk(mount+folder):
        
        if files:
            if track_progress and os.path.exists(root+'/backup.done') and os.path.getmtime(root) > time.time()-1814400:
                print "done " + root
                continue

            if os.path.exists(root+'/backup.done'):
                os.unlink(root+'/backup.done')
            
            print "Processing: "+root
            
            query = "ident="+config['identity']+"&command=filelist&mode="+mode+"&folder=" + urllib.quote(string.replace(root,mount,''))+"&r="+str(random.randint(1,100000))
            
            sig = hmac.new(config['secret'], query);
            url = config['api_endpoint'] + "?" + query + "&sig="+sig.hexdigest()
            
            req = urllib2.Request(url)
            req.add_header('User-agent', urllib._urlopener.version)
            f = urllib2.urlopen(req)
            response = f.read()
            f.close()
            
            result = json_read(response)
            
            if result and 'error' in result:
                print result['error']
                sys.exit(2)

            ##SELECT file_id,filename,backups,size,md5sum,UNIX_TIMESTAMP(file_modified) AS modified
            
            notify = []
            failures = []
            
            for row in result['rows']:
                
                filename = os.path.basename(row['filename'])
                if filename in files:
                    ##We have the file, lets check we noted in replicas
                    
                    if config['identity'] in row['backups']: 
                        
                        if reverify:
                            print "Already Notified: "+row['filename']+', checking validity...'
                            stat = os.stat(root + "/" + filename)
                            if (stat.st_size > 0 and stat.st_size < 52428800):
                                md5su = md5sum(root + "/" + filename)
                            else:
                                md5su =''
                            
                            if md5su != row['md5sum']:
                                print " md5 checksum does not match '"+md5su+"' != '"+row['md5sum']+"'"
                                failures.append("md5,"+md5su+","+row['md5sum']+","+filename)
                            elif int(stat.st_size) != int(row['size']):
                                print " size does not match '"+str(stat.st_size)+"' != '"+str(row['size'])+"'"
                                failures.append("size,"+str(stat.st_size)+","+str(row['size'])+","+filename)
                        else:
                            print "Already Notified: "+row['filename']
                        
                    else:
                        print "Validating: "+row['filename']
                        
                        stat = os.stat(root + "/" + filename)
                        if (stat.st_size > 0 and stat.st_size < 52428800):
                            md5su = md5sum(root + "/" + filename)
                        else:
                            md5su =''
                        
                        if md5su != row['md5sum']:
                            print " md5 checksum does not match '"+md5su+"' != '"+row['md5sum']+"'"
                            failures.append("md5,"+md5su+","+row['md5sum']+","+filename)
                        elif int(stat.st_size) != int(row['size']):
                            print " size does not match '"+str(stat.st_size)+"' != '"+str(row['size'])+"'"
                            failures.append("size,"+str(stat.st_size)+","+str(row['size'])+","+filename)
                        #elif stat.st_mtime != row['modified']:
                        #    print " dates does not match '"+str(stat.st_mtime)+"' != '"+str(row['modified'])+"'"
                        #    failures.append("dates,"+str(stat.st_mtime)+","+str(row['modified'])+","+filename)
                        else:
                            notify.append(row['file_id'])
                    
                    files.remove(filename) ## so that any left will be new files!
                else:
                    ##there is a file on the FS, that we don't have - ignore here (the replicate function may download it later)
                    pass
            
            if files:
                for filename in files:
                    if filename != 'backup.done':
                        print "Unknown File: "+ filename
                        failures.append("unknown,"+filename)
            
            if notify:
                query = "ident="+config['identity']+"&command=notify&mode="+mode+"&folder=" + urllib.quote(string.replace(root,mount,''))+"&r="+str(random.randint(1,100000))
                
                sig = hmac.new(config['secret'], query);
                url = config['api_endpoint'] + "?" + query + "&sig="+sig.hexdigest()
                
                data = urllib.urlencode({'file_ids': ' '.join(notify)})
                
                req = urllib2.Request(url, data)
                req.add_header('User-agent', urllib._urlopener.version)
                f = urllib2.urlopen(req)
                response = f.read()
                f.close()
                
                print response
            
            if failures:
                query = "ident="+config['identity']+"&command=failures&mode="+mode+"&folder=" + urllib.quote(string.replace(root,mount,''))+"&r="+str(random.randint(1,100000))
                
                sig = hmac.new(config['secret'], query);
                url = config['api_endpoint'] + "?" + query + "&sig="+sig.hexdigest()
                
                data = urllib.urlencode({'notes': json_write(failures)})
                
                req = urllib2.Request(url, data)
                req.add_header('User-agent', urllib._urlopener.version)
                f = urllib2.urlopen(req)
                response = f.read()
                f.close()
                
                print response
            
            if track_progress:
                open(root+'/backup.done', 'w').close()
            print "-----------"
            
            if sleeper>0:
                time.sleep(sleeper)
            

#############################################################################

def replicate_now(path = '',mode=False):
    mount = config['folder']
    
    if 'statvfs' in dir(os):
        s = os.statvfs(mount+'/geograph_live/')
        bytes_free = (s.f_bavail * s.f_frsize) / 1024
        gigabytes = bytes_free / (1024 * 1024)

        if gigabytes < int(filter(str.isdigit, config['keep_free_gig'])):
            print "There is only " + str(bytes_free) + " bytes free, which is less than configured keep_free_gig="+config['keep_free_gig']
            sys.exit(2)

    if not mode:
        mode=config['mode']
    
    query = "ident="+config['identity']+"&command=filelist&mode="+mode+"&r="+str(random.randint(1,100000)) #just to defeat caching
    
    sig = hmac.new(config['secret'], query);
    url = config['api_endpoint'] + "?" + query + "&sig="+sig.hexdigest()

    req = urllib2.Request(url)
    req.add_header('User-agent', urllib._urlopener.version)
    f = urllib2.urlopen(req)
    response = f.read()
    f.close()

    result = json_read(response)

    if result and 'error' in result:
        print result['error']
        sys.exit(2)
    
    ##SELECT file_id,filename,size,md5sum,UNIX_TIMESTAMP(file_modified) AS modified
    
    notify = []
    failures = []
    
    c = 0;
    for row in result['rows']:
        if '000000_' in row['filename']:
            continue
        
        #choose sever to download from
        if result['backupserver'] and result['backupserver'] in row['filename']:
            url = string.replace(row['filename'],result['backuproot'],result['backupserver'])
        else:
            if isinstance(result['server'], basestring):
                url = string.replace(row['filename'],result['docroot'],result['server'])
            else:
                server = random.choice(result['server'])
                url = string.replace(row['filename'],result['docroot'],server)
        
        filename = mount + row['filename']
        
        #if have file already check its validity
        # if not valid then archive it
        if os.path.exists(filename):
            print "have "+filename+" already"
            stat = os.stat(filename)
            if int(stat.st_size) == 0:
                os.unlink(filename)
            else:
                if (stat.st_size > 0 and stat.st_size < 52428800):
                    md5su = md5sum(filename)
                else:
                    md5su =''
                if int(stat.st_size) != int(row['size']):
                    print " size does not match '"+str(stat.st_size)+"' != '"+str(row['size'])+"'"
                    n=''
                    while os.path.exists(filename+'.old'+str(n)):
                        if not n:
                            n=1
                        else:
                            n = int(n)+1
                    os.rename(filename,filename+'.old'+str(n))
                    print " saved as "+filename+'.old'+str(n)
                    failures.append("exist_size,"+str(stat.st_size)+","+str(row['size'])+","+filename+",.old"+str(n))
                    
                elif md5su != row['md5sum']:
                    print " md5 checksum does not match '"+md5su+"' != '"+row['md5sum']+"'"
                    n=''
                    while os.path.exists(filename+'.old'+str(n)):
                        if not n:
                            n=1
                        else:
                            n = int(n)+1
                    os.rename(filename,filename+'.old'+str(n))
                    print " saved as "+filename+'.old'+str(n)
                    failures.append("exist_md5,"+md5su+","+row['md5sum']+","+filename+",.old"+str(n))
                else:
                    print " appears to be ok!"
        else:
            if not os.path.exists(os.path.dirname(filename)):
                os.makedirs(os.path.dirname(filename)) ##recursive
        
        #download as required
        if not os.path.exists(filename):
            print "download " + row['filename'] + " from "+ url
            urllib.urlretrieve(url, filename)
        
        #check if it worked
        stat = os.stat(filename)
        if stat.st_size > 0:
            os.utime(filename, (int(time.time()), int(row['modified'])) )
        if (stat.st_size > 0 and stat.st_size < 52428800):
            md5su = md5sum(filename)
        else:
            md5su =''
        if int(stat.st_size) != int(row['size']):
            print " size does not match '"+str(stat.st_size)+"' != '"+str(row['size'])+"'"
            failures.append("size,"+str(stat.st_size)+","+str(row['size'])+","+filename)
        elif md5su != row['md5sum']:
            print " md5 checksum does not match '"+md5su+"' != '"+row['md5sum']+"'"
            failures.append("md5,"+md5su+","+row['md5sum']+","+filename)
        else:
            notify.append(row['file_id'])
        
        if 'sleep' in result:
            time.sleep(result['sleep'])
    
    if notify:
        query = "ident="+config['identity']+"&command=notify&mode="+mode+"&r="+str(random.randint(1,100000))

        sig = hmac.new(config['secret'], query);
        url = config['api_endpoint'] + "?" + query + "&sig="+sig.hexdigest()

        data = urllib.urlencode({'file_ids': ' '.join(notify)})

        req = urllib2.Request(url, data)
        req.add_header('User-agent', urllib._urlopener.version)
        f = urllib2.urlopen(req)
        response = f.read()
        f.close()
        
        print response

    if failures:
        query = "ident="+config['identity']+"&command=failures&mode="+mode+"&r="+str(random.randint(1,100000))

        sig = hmac.new(config['secret'], query);
        url = config['api_endpoint'] + "?" + query + "&sig="+sig.hexdigest()

        data = urllib.urlencode({'notes': json_write(failures)})

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
    mode = False
    sleeper = 0

    try:
        opts, args = getopt.getopt(argv,"a:p:m:s:",["action=","path=","mode=","sleep="])
    except getopt.GetoptError:
        print 'replication.py -a (walk|replicate) [-p /geograph_live/rastermaps]'
        sys.exit(2)
    
    for opt, arg in opts:
        if opt in ("-a", "--action"):
            action = arg
        elif opt in ("-m", "--mode"):
            mode = arg
        elif opt in ("-p", "--path"):
            path = arg.rstrip("/")
        elif opt in ("-s", "--sleep"):
            sleeper = float(arg)
    
    if action == 'unknown':
        print 'replication.py -a (walk|replicate) [-p /geograph_live/rastermaps]'
        sys.exit(2)
    
    elif action == 'walk':
        walk_and_notify(path, True, False, sleeper)
    
    elif action == 'rewalk':
        walk_and_notify(path, False, True, sleeper)
    
    elif action == 'replicate':
        replicate_now(path,mode)


if __name__ == '__main__':
    main(sys.argv[1:])

#############################################################################

