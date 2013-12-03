# Script for use with py2exe for creating geograph_backup.exe
# see http://www.py2exe.org/index.cgi/Tutorial

from distutils.core import setup
import py2exe

setup(console=['geograph_backup.py'])