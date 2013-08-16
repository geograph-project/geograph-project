#!/usr/bin/env python

# $Project: GeoGraph $
# $Id$
__version__ = filter(str.isdigit, "$Revision$")

## Merges a number of folders into a single virtual filesyste, - tailored for Geograph use

#    Copyright (C) 2013  Barry Hunter  <geo@barryhunter.co.uk>

# Based heavily on xmp.py from the fuse-python package, which is by:

#    Copyright (C) 2001  Jeff Epler  <jepler@unpythonic.dhs.org>
#    Copyright (C) 2006  Csaba Henk  <csaba.henk@creo.hu>
#
#    This program can be distributed under the terms of the GNU LGPL.
#    See the file COPYING.
#

import os, sys
import os.path
import stat
import random
from errno import *
from stat import *
import fcntl
# pull in some spaghetti to make this stuff work without fuse-py being installed
try:
    import _find_fuse_parts
except ImportError:
    pass
import fuse
from fuse import Fuse
import config
import MySQLdb
import PySQLPool   #https://code.google.com/p/pysqlpool/wiki/Installing
import hashlib
import re
import string
import collections
import threading
from mpycache import LRUCache;


if not hasattr(fuse, '__version__'):
    raise RuntimeError, \
        "your fuse-py doesn't know of fuse.__version__, probably it's too old."

fuse.fuse_python_api = (0, 2)

fuse.feature_assert('stateful_files', 'has_init')


def flag2mode(flags):
    md = {os.O_RDONLY: 'r', os.O_WRONLY: 'w', os.O_RDWR: 'w+'}
    m = md[flags & (os.O_RDONLY | os.O_WRONLY | os.O_RDWR)]

    if flags | os.O_APPEND:
        m = m.replace('w', 'a', 1)

    return m

class MyStat(fuse.Stat):
    def __init__(self):
        self.st_mode = stat.S_IFDIR | 0755
        self.st_ino = 0
        self.st_dev = 0
        self.st_nlink = 2
        self.st_uid = os.getuid()
        self.st_gid = os.getgid()
        self.st_size = 4096
        self.st_atime = 0
        self.st_mtime = 0
        self.st_ctime = 0

class GeoGridFS(Fuse):
    connection = False
    row_cache = False
    folder_cache = False
    
    def __init__(self, *args, **kw):
        
        Fuse.__init__(self, *args, **kw)
        
        #see https://code.google.com/p/mpycache/
        self.row_cache = LRUCache(1000, 30000, 300)
        self.folder_cache = LRUCache(1000, 30000, 300)
        
        # do stuff to set up your filesystem here, if you want
        #import thread
        #thread.start_new_thread(self.mythread, ())


    def fsinit(self):
        """
        Will be called after the command line arguments are successfully
        parsed. It doesn't have to exist or do anything, but as options to the
        filesystem are not available in __init__, fsinit is more suitable for
        the mounting logic than __init__.

        To access the command line passed options and nonoption arguments, use
        cmdline.

        The mountpoint is not stored in cmdline.
        """
        
        #http://pythonhosted.org/PySQLPool/reference.html#PySQLPool.PySQLConnection
        self.connection = PySQLPool.getNewConnection(
            username=config.database['username'], 
            password=config.database['password'], 
            host=config.database['hostname'], 
            db=config.database['database'],
            connect_timeout=5,
            )

#############################################################################

    def getFirstMount(self, path='/'):
    #todo!
        return config.mounts['milk']

    def getOrderedMounts(self, path='/'):
        mounts = collections.OrderedDict()
        
        # this mostly replicates how files are distributed amongst servers currently, so reads should find them in their ideal location most of the time. 
        if 'photos/' in path:
            if '_original' in path:
                mounts['jam'] = config.mounts['jam']
                mounts['cream'] = config.mounts['cream']
            elif 'photos/03/' in path:
                mounts['cream'] = config.mounts['cream']
                mounts['jam'] = config.mounts['jam']
            elif (random.random() < 0.7):
                #because we know it has a complete copy, might as well as let jam take some strain
                mounts['jam'] = config.mounts['jam']
                mounts['cream'] = config.mounts['cream']
            else:
                mounts['cream'] = config.mounts['cream']
                mounts['jam'] = config.mounts['jam']
            mounts['milk'] = config.mounts['milk']
        else:
            mounts['milk'] = config.mounts['milk']
            mounts['jam'] = config.mounts['jam']
            mounts['cream'] = config.mounts['cream']
        
        #if have metadata record, use it to promote mounts with actual replicas
        try:
            if not self.row_cache.has_key(path):
                query = PySQLPool.getNewQuery(self.connection)
                query.Query("SELECT file_id,size,UNIX_TIMESTAMP(file_created) as created,UNIX_TIMESTAMP(file_modified) as modified,UNIX_TIMESTAMP(file_accessed) as accessed,replicas FROM "+config.database['file_table']+" WHERE filename = '"+query.escape_string(path)+"' LIMIT 1")
                if query.rowcount == 1:
                    for row in query.record:
                        self.row_cache.put(path,row)
                    
            if self.row_cache.has_key(path):
                replicas = string.split(self.row_cache.get(path)['replicas'], ',')
                for short,mount in mounts.items():
                    if short not in replicas:
                        del mounts[short] 
                        mounts[short] = config.mounts[short]

                #todo, loop though relicas, add add any missing
            
        except MySQLdb.Error, e:
            if e.args[0] != 2002: # ignore connection arrors. Not the end of the universe if the file isnt in metadata
                print "Error %d: %s" % (e.args[0], e.args[1])
                sys.exit(1)
        
        return mounts.values()

    def getServerFromMount(self, mount):
        for (key,value) in config.mounts.iteritems():
            if value == mount:
                return key

    def getFolderId(self, path, create = True):
        
        if self.folder_cache.has_key(path):
            return self.folder_cache.get(path)
        
        try:
            query = PySQLPool.getNewQuery(self.connection)
            query.Query("SELECT folder_id FROM "+config.database['folder_table']+" WHERE folder = '"+query.escape_string(path)+"' LIMIT 1")
            if query.rowcount == 0:
                if not create:
                    return 0
                query.Query("INSERT INTO "+config.database['folder_table']+" SET meta_created = NOW(), folder = '"+query.escape_string(path)+"'")
                folder_id = query.lastInsertID
            else:
                for row in query.record:
                    folder_id = row['folder_id']
            
            self.folder_cache.put(path,folder_id)
            return folder_id
        
        except MySQLdb.Error, e:
            if e.args[0] != 2002: # ignore connection arrors. Not the end of the universe if the file isnt in metadata
                print "Error %d: %s" % (e.args[0], e.args[1])
                #sys.exit(1)
            
            return 0

    #http://code.activestate.com/recipes/576583-md5sum/
    def md5sum(self, path):
        file = open(path, 'rb')
        md5 = hashlib.md5()
        buffer = file.read(2 ** 20)
        while buffer:
            md5.update(buffer)
            buffer = file.read(2 ** 20)
        file.close()
        return str(md5.hexdigest())

#############################################################################

    def getattr(self, path):
        # use metedata server if can
        if self.row_cache.has_key(path):
            st = MyStat()
            cache = self.row_cache.get(path)
            
            st.st_atime = int(cache['accessed'])
            st.st_mtime = int(cache['modified'])
            st.st_ctime = int(cache['created'])
            st.st_mode = stat.S_IFREG | 0666
            st.st_nlink = 1
            st.st_size = int(cache['size'])
            return st
        
        for mount in self.getOrderedMounts(path):
            try:
                result = os.lstat(mount + path)
                return result
            except os.error, e:
                print "Error %d: %s" % (e.args[0], e.args[1])
                pass
        
        return os.lstat(mount + 'nonexistant')
        return -ENOENT

    def readlink(self, path):
        for mount in self.getOrderedMounts(path):
            if os.path.exists(mount + path):
                return os.readlink(mount + path)

    def readdir(self, path, offset):
        dedup = {}
        yield fuse.Direntry('.')   #os.listdir does NOT include these!
        yield fuse.Direntry('..')
        for mount in self.getOrderedMounts(path):
            if os.path.exists(mount + path):
                for e in os.listdir(mount + path):
                    if e not in dedup:
                        dedup[e] = True
                        yield fuse.Direntry(e)

    def unlink(self, path):
        query = PySQLPool.getNewQuery(self.connection)
    
        for mount in self.getOrderedMounts(path):
            if os.path.exists(mount + path):
                
                try:
                    #mark deleted in metadata
                    short = self.getServerFromMount(mount);
                    query.Query("UPDATE "+config.database['file_table']+" "+ \
                        "SET replicas = REPLACE(replicas,'"+short+"',''), replica_count=replica_count-1 "+ \
                        "WHERE filename = '"+query.escape_string(path)+"' AND replicas LIKE '%"+short+"%'")
                
                except MySQLdb.Error, e:
                    if e.args[0] != 2002: # ignore connection arrors. Not the end of the universe if the file isnt in metadata
                        print "Error %d: %s" % (e.args[0], e.args[1])
                        #sys.exit(1)
                
                os.unlink(mount + path)

    def rmdir(self, path):
        for mount in self.getOrderedMounts(path):
            if os.path.exists(mount + path):
                os.rmdir(mount + path)

    def symlink(self, path, path1):
        for mount in self.getOrderedMounts(path):
            if os.path.exists(mount + path):
                os.symlink(mount + path, mount + path1)

    def rename(self, path, path1):
        folder = False
        for mount in self.getOrderedMounts(path):
            if os.path.exists(mount + path):
                if os.path.isdir(mount + path):
                    folder = True
                os.rename(mount + path, mount + path1)
        
        try:
            query = PySQLPool.getNewQuery(self.connection)
            if folder:
                old_folder_id = self.getFolderId(path, False)

                if old_folder_id != 0:
                    new_folder_id = self.getFolderId(path1, False)
                    if new_folder_id == 0:
                        #if the new folder doesnt exist, change the folder itself
                        new_folder_id = old_folder_id
                        query.Query("UPDATE "+config.database['folder_table']+" "+ \
                                "SET folder = '"+query.escape_string(path1)+"' "+ \
                                "WHERE folder_id = "+str(old_folder_id))
                    else:
                        new_folder_id = self.getFolderId(os.path.dirname(path1))

                    #change any files DIRECTLY in the folder
                    query.Query("UPDATE "+config.database['file_table']+" "+ \
                            "SET folder_id = "+str(new_folder_id)+", filename = REPLACE(filename,'"+query.escape_string(path)+"/','"+query.escape_string(path1)+"/') "+ \
                            "WHERE folder_id = "+str(old_folder_id))
                    #todo - need to invalidate any relevent rows in row_cache!

                    #clear getFolderId's cache for old_folder_id!
                    if self.folder_cache.has_key(path):
                        self.folder_cache.erase(path)

                #todo, should ALSO check [[ FROM file WHERE filename LIKE '"+query.escape_string(path)+"/%' ]] 
                # NOT easy to do, as needs to also set folder_id, 
                # maybe needs to first SELECT folder_id,folder FROM folder WHERE folder REGEXP '^{$folder}(/|$)', then do each one in turn?
                # - maybe offload into an async process? 

            else:
                new_folder_id = self.getFolderId(os.path.dirname(path1))

                #renaming a file is easy!
                query.Query("UPDATE "+config.database['file_table']+" "+ \
                        "SET folder_id = "+str(new_folder_id)+", filename = '"+query.escape_string(path1)+"' "+ \
                        "WHERE filename = '"+query.escape_string(path)+"'")
                        
                if self.row_cache.has_key(path):
                    self.row_cache.erase(path)

        except MySQLdb.Error, e:
            if e.args[0] != 2002: # ignore connection arrors. Not the end of the universe if the file isnt in metadata
                print "Error %d: %s" % (e.args[0], e.args[1])
                #sys.exit(1)

    def link(self, path, path1):
        for mount in self.getOrderedMounts(path):
            if os.path.exists(mount + path):
                os.link(mount + path, mount + path1)

    def chmod(self, path, mode):
        for mount in self.getOrderedMounts(path):
            if os.path.exists(mount + path):
                os.chmod(mount + path, mode)

    def chown(self, path, user, group):
        for mount in self.getOrderedMounts(path):
            if os.path.exists(mount + path):
                os.chown(mount + path, user, group)

    def truncate(self, path, len):
        for mount in self.getOrderedMounts(path):
            if os.path.exists(mount + path):
                f = open(mount + path, "a")
                f.truncate(len)
                f.close()
        
        try:
            query = PySQLPool.getNewQuery(self.connection)

            query.Query("UPDATE "+config.database['file_table']+" "+ \
                        "SET size = "+str(len)+", md5sum = '' "+ \
                        "WHERE filename = '"+query.escape_string(path)+"'")

        except MySQLdb.Error, e:
            if e.args[0] != 2002: # ignore connection arrors. Not the end of the universe if the file isnt in metadata
                print "Error %d: %s" % (e.args[0], e.args[1])
                #sys.exit(1)

    def mknod(self, path, mode, dev):
        for mount in self.getOrderedMounts(path):
            if os.path.exists(mount + path):
                os.mknod(mount + path, mode, dev)

    def mkdir(self, path, mode):
        for mount in self.getOrderedMounts(path):
            os.makedirs(mount + path, mode) #so can make dirs recurivly
        
        #NOTE, we dont create the metadata.folder here, but COULD. 
        #Instead will be created as needed (with getFolderId) when a file is written there

    def utime(self, path, times):
        for mount in self.getOrderedMounts(path):
            if os.path.exists(mount + path):
                return os.utime(mount + path, times)

#    The following utimens method would do the same as the above utime method.
#    We can't make it better though as the Python stdlib doesn't know of
#    subsecond preciseness in acces/modify times.
#  
#    def utimens(self, path, ts_acc, ts_mod):
#      os.utime(self.getFirstMount(path) + path, (ts_acc.tv_sec, ts_mod.tv_sec))

#todo - fix this to work
    def access(self, path, mode):
        return
#        for mount in self.getOrderedMounts(path):
#            if not os.access(mount + path, mode):
#                return -EACCES

#############################################################################

    def statfs(self):
        """
        Should return an object with statvfs attributes (f_bsize, f_frsize...).
        Eg., the return value of os.statvfs() is such a thing (since py 2.2).
        If you are not reusing an existing statvfs object, start with
        fuse.StatVFS(), and define the attributes.

        To provide usable information (ie., you want sensible df(1)
        output, you are suggested to specify the following attributes:

            - f_bsize - preferred size of file blocks, in bytes
            - f_frsize - fundamental size of file blcoks, in bytes
                [if you have no idea, use the same as blocksize]
            - f_blocks - total number of blocks in the filesystem
            - f_bfree - number of free blocks
            - f_files - total number of file inodes
            - f_ffree - nunber of free file inodes
        """
        #todo total up all mounts?
        return os.statvfs(self.getFirstMount())

#############################################################################

    class GeoGridFSFile(object):
        direct_io = False
        keep_cache = False
        file = False
        fd = False
        thread_lock = False
        
        def __init__(self, server, path, flags, *mode):
            self.file = False
            final_mount = False
            
            self.thread_lock = threading.Lock()
            self.thread_lock.acquire()
            try:

                #first see if can find an actual file
                for mount in server.getOrderedMounts(path):
                    if os.path.exists(mount + path):
                        final_mount = mount
                        self.file = os.fdopen(os.open(mount + path, flags, *mode), flag2mode(flags))
                        break

                #find a mount that has a folder we can use
                if self.file is False and (flags & os.O_CREAT or flags & os.O_WRONLY or flags & os.O_RDWR):
                    for mount in server.getOrderedMounts(path):
                        if os.path.exists(os.path.dirname(mount + path)):
                            final_mount = mount
                            self.file = os.fdopen(os.open(mount + path, flags, *mode), flag2mode(flags))
                            break

                    #if still not found, then just create it on the first mount
                    if not self.file: 
                        for mount in server.getOrderedMounts(path):
                            if not os.path.exists(os.path.dirname(mount + path)):
                                os.makedirs(os.path.dirname(mount + path)) # use makedirs so will also create parent dirs as required
                            final_mount = mount
                            self.file = os.fdopen(os.open(mount + path, flags, *mode), flag2mode(flags))
                            break

                if self.file is False:
                   return -EIO

                self.server = server
                self.mount = final_mount
                self.path = path
                self.flags = flags
                self.fd = self.file.fileno()
            finally:
                self.thread_lock.release()

        def read(self, length, offset):
            self.thread_lock.acquire()
            try:
                self.file.seek(offset)
                return self.file.read(length)
            finally:
                self.thread_lock.release()

        def write(self, buf, offset):
            self.thread_lock.acquire()
            try:
                self.file.seek(offset)
                self.file.write(buf)
                return len(buf)
            finally:
                self.thread_lock.release()

        def release(self, flags):
            self.thread_lock.acquire()
            try:
                self.file.close()

                if not self.flags & os.O_WRONLY and not self.flags & os.O_RDWR: #ie IS readonly
                    #todo, if this file isnt in metadata, and we can (now) connect, should add it anyway. 
                    return

                if self.server.row_cache.has_key(self.path):
                    self.server.row_cache.erase(self.path)

                try:
                    stat = os.stat(self.mount + self.path)
                    if (stat.st_size > 0 and stat.st_size < 52428800):
                        md5sum = self.server.md5sum(self.mount + self.path)
                    else:
                        md5sum =''

                    specifics = "`size` = "+str(stat.st_size)+", " + \
                             "`file_created` = FROM_UNIXTIME("+str(stat.st_ctime)+"), " + \
                             "`file_modified` = FROM_UNIXTIME("+str(stat.st_mtime)+"), " + \
                             "`file_accessed` = FROM_UNIXTIME("+str(stat.st_atime)+"), " + \
                             "`md5sum` = '"+md5sum+"', "

                    #todo 
                    #if self.path in self.server.row_cache:
                    #   file_id = 
                    #else:
                    query = PySQLPool.getNewQuery(self.server.connection)
                    query.Query("SELECT file_id FROM "+config.database['file_table']+" WHERE filename = '"+query.escape_string(self.path)+"'")

                    if query.rowcount == 0:
                        folder_id = self.server.getFolderId(os.path.dirname(self.path))

                        final = False
                        targets = ''
                        for pattern in config.patterns:
                            if re.search(pattern[1],self.path):
                                final = pattern
                                break
                        if final:
                            targets = "`class` = '"+final[0]+ "', " + \
                                "`replica_target` = "+str(final[2])+ ", " + \
                                "`backup_target` = "+str(final[3])+ ", "

                        query.Query("INSERT INTO "+config.database['file_table']+" SET meta_created = NOW(), " + \
                             "filename = '"+query.escape_string(self.path)+"', " + \
                             "folder_id = "+str(folder_id)+", " + \
                             specifics + targets + \
                             "replicas = '"+self.server.getServerFromMount(self.mount)+"', " + \
                             "replica_count=1")
                    else:
                        for row in query.record:
                            file_id = row['file_id']

                        ## here, we obliterate record of any other replicas, because they will now be outdated, their worker should pickup this 'new' file
                        query.Query("UPDATE "+config.database['file_table']+" SET " + \
                             specifics + \
                             "replicas = '"+self.server.getServerFromMount(self.mount)+"', " + \
                             "replica_count=1, "+ \
                             "backups='', "+ \
                             "backup_count= 0 "+ \
                             "WHERE file_id = "+str(file_id))


                

                except MySQLdb.Error, e:
                    if e.args[0] != 2002: # ignore connection arrors. Not the end of the universe if the file isnt in metadata
                        print "Error %d: %s" % (e.args[0], e.args[1])
                        sys.exit(1)
            finally:
                self.thread_lock.release()


        def _fflush(self):
            if 'w' in self.file.mode or 'a' in self.file.mode:
                self.file.flush()

        def fsync(self, isfsyncfile):
            self.thread_lock.acquire()
            try:
                self._fflush()
                if isfsyncfile and hasattr(os, 'fdatasync'):
                    os.fdatasync(self.fd)
                else:
                    os.fsync(self.fd)
            finally:
                self.thread_lock.release()

        def flush(self):
            self.thread_lock.acquire()
            try:
                self._fflush()
                # cf. xmp_flush() in fusexmp_fh.c
                os.close(os.dup(self.fd))
            finally:
                self.thread_lock.release()

        def fgetattr(self):
            self.thread_lock.acquire()
            try:
                return os.fstat(self.fd)
            finally:
                self.thread_lock.release()

        def ftruncate(self, len):
            self.thread_lock.acquire()
            try:
                self.file.truncate(len)
            finally:
                self.thread_lock.release()

        def lock(self, cmd, owner, **kw):
            self.thread_lock.acquire()
            try:
                pass
            finally:
                self.thread_lock.release()

#############################################################################

    def main(self, *a, **kw):

        #see http://sourceforge.net/apps/mediawiki/fuse/index.php?title=FUSE_Python_Reference#File_Class_Methods
        class wrapped_GeoGridFSFile(self.GeoGridFSFile):
            def __init__(self2, *a, **kw):
                self.GeoGridFSFile.__init__(self2, self, *a, **kw)

        self.file_class = wrapped_GeoGridFSFile

        return Fuse.main(self, *a, **kw)

#############################################################################

def main():

    usage = """
Unify a number of folders into one virtual folder. Designed for merging NFS shares. 

""" + Fuse.fusage

    server = GeoGridFS(version="%prog " + fuse.__version__,
                 usage=usage,
                 dash_s_do='setsingle')

    # Disable multithreading: if you want to use it, protect all method of
    # XmlFile class with locks, in order to prevent race conditions
    server.multithreaded = True

    server.parse(values=server, errex=1)

    server.main()


if __name__ == '__main__':
    main()

#############################################################################

