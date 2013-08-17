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

#the replication classes, the Regex that defines them, the replica_target and the backup_target
# - these are precessed in ORDER, so its recommended to put popular AND more specific ones first. eg the unspecific others class will catch all files not defined above it
#NOTE: if change the targets, should possible update the meta data eg ( UPDATE file SET replica_target = 4 WHERE class = 'base.png' )
patterns = [
	('full.jpg',      r'/\d{6,}_\w{8}\.jpg$',    3, 4),
	('original.jpg',  r'_original\.jpg$',        2, 4),
	('thumb.jpg',     r'_\d+[xX]+\d+\.jpg$',     3, 0),
	('detail.png',    r'/detail_[\w\.-]+\.png$', 1, 0),
	('detail.jpg',    r'/detail_[\w\.-]+\.jpg$', 1, 0),
	('base.png',      r'/base_[\w\.-]+\.png$',   2, 0),
	('thumb.gd',      r'_\d+x\d+\.gd$',          1, 0),
	('preview.jpg',   r'_preview\.jpg$',         2, 0),
	('pending.jpg',   r'_pending\.jpg$',         3, 0),
	('tile.png',      r'/\d+-\d+\.png$',         2, 0),
	('tile.tif',      r'/\w+\d+\.TIF$',          3, 0),
	('kml',           r'/kml/',                  2, 0),
	('sitemap.gz',    r'/sitemap/root/',         1, 0),
	('sitemap.html',  r'/sitemap/',              2, 0),
	('torrents',      r'/torrent/',              1, 0),
	('templates',     r'/templates/',            1, 0),
	('rss',           r'/rss/',                  1, 0),
	('.others',       r'/.*/',                   2, 0),
]
