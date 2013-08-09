#Point this to your live metadata server, should initially import the database.sql file into it. 
database = dict(
	hostname = 'localhost',
	username = 'username',
	password = 'password',
	database = 'database',
	file_table = 'file',
	folder_table = 'folder',
)

#List of /folders/mounts that make up the file system. Can either be a NFS client share to point to a remote server, or an actual folder in the case of the 'self'
#the names should match the `replicas` SET column on the metadata server
mounts = dict(
        cream = '/var/mount/cream',
        milk = '/var/mount/milk',
        jam = '/var/mount/jam'
)

#self defines the name of THIS storage node. E.g. defines the mount about that files are replicated TO. used mainly by replicator.py
server = dict(
        self = 'cream'
)

