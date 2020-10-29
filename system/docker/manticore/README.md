# Geograph Manticore Container Image

This is a Geograph-specific container image containing [Manticore Search](https://manticoresearch.com/),
a fork of [Sphinx](http://sphinxsearch.com/). This image contains the following
Geograph-specific changes and additions:

* Geograph UDF plugins (uniqueserial.so and withinfirstx.so)
* Geograph data sources and indices
* [Supercronic](https://github.com/aptible/supercronic) and daily re-indexing cron job

# Configuration

## Environment Variables

Certain environment variables need to be supplied to the running containers for
useful operation.

### `MYSQL_HOST`

The MySQL / MariaDB server to query when building indices. Default: `mysql`.

### `MYSQL_SLAVE_HOST`

The MySQL / MariaDB secondary replica server to query for building indices.
This setting is optional and defaults to the value of `MYSQL_HOST` above. Most
indices use this server as their data source rather than the primary or master
server.

### `MYSQL_USER`

The user to connect to the database with. Default: `geograph`.

### `CONF_DB_PWD`

The password for the above user. Default: `password`.

### `MYSQL_DATABASE`

The database name to connect to. Default: `geograph_live`.

### `MYSQL_PORT`

The database TCP port number. Default: `3306`.

## Configuration Mechanism

Manticore reads a single configuration file at startup, which defaults to
`/etc/sphinxsearch/sphinx.conf`. It has a trick up its sleeve, however, in that
if that file starts with a [shebang](https://en.wikipedia.org/wiki/Shebang_%28Unix%29)
it is executed and the output of that process is parsed instead. This container
image uses this mechanism to build the configuration up from many files.

Files in the image matching `/etc/sphinxsearch/sphinx.conf.d/*.conf` are
iterated, and the files concatenated. Executable files are executed and their
output used as configuration. This is used by `21-database.conf` specifically
to substitude the database configuration from the supplied environment
variables.

Note that the executable distinction is slightly different between Manticore
itself and our configuration script. Manticore looks for a shebang line but is
agnostic to the executable bit; our script uses the executable bit exclusively.

## Indices and Data Sources

The container-specific configuration is held in
[`system/docker/manticore/etc/sphinxsearch/sphinx.conf.d/`](etc/sphinxsearch/sphinx.conf.d/);

