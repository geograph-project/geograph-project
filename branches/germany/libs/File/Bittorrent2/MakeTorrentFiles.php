<?

// +----------------------------------------------------------------------+
// | Extension of File_Bittorrent2_MakeTorrent to include specific files  |
// +----------------------------------------------------------------------+
// | Copyright (C) 2007                                                   |
// |   barry hunter <geo@barryhunter.co.uk>                               |
// +----------------------------------------------------------------------+
// | This library is free software; you can redistribute it and/or        |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This library is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// |                                                                      |
// | You should have received a copy of the GNU Lesser General Public     |
// | License along with this library; if not, write to the                |
// | Free Software Foundation, Inc.                                       |
// | 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA               |
// +----------------------------------------------------------------------+

require_once 'File/Bittorrent2/MakeTorrent.php';

class File_Bittorrent2_MakeTorrentFiles extends File_Bittorrent2_MakeTorrent
{
	/**
	* @var bool Where or not we have a list of files
	*/
	protected $is_multifile = false;

	/**
	* Function to set the name for
	* the .torrent file
	*
	* @param string name
	* @return bool
	*/
	function setName($name)
	{
		$this->name = strval($name);
		return true;
	}

	/**
	* Function to add a specific list of files, 
	* pass blank to the constructor then call this function
	* 
	* @param array list of filepaths to add;
	* @param string folder containing files
	* @return bool
	*/
	function addFiles($filelist,$dir) {
		$this->is_dir = true;
		$this->is_multifile = true;
		$this->name = basename($dir);

		sort($filelist);

		foreach ($filelist as $file) {
			$filedata = $this->addFile($dir.$file);
			if ($filedata !== false) {
				$filedata['path'] = array();
				$filedata['path'][] = basename($file);
				$dirname = dirname($dir.$file);
				while (basename($dirname) != $this->name) {
					$filedata['path'][] = basename($dirname);
					$dirname = dirname($dirname);
				}
				$filedata['path'] = array_reverse($filedata['path'], false);
				$this->files[] = $filedata;
			}
		}
		return true;
	}
	
	
    /**
     * Function to build the .torrent file
     * based on the parameters you have set
     * with the set* functions.
     *
     * @return mixed false on failure or a string containing the metainfo
	 * @throws File_Bittorrent2_Exception if no file or directory is given
     */
    function buildTorrent()
    {
        if ($this->is_multifile) {
            //we already have the files added
            $metainfo = $this->encodeTorrent();
        } else if ($this->is_file) {
            if (!$info = $this->addFile($this->path)) {
                return false;
            }
            if (!$metainfo = $this->encodeTorrent($info)) {
                return false;
            }
        } else if ($this->is_dir) {
            if (!$diradd_ok = $this->addDir($this->path)) {
                return false;
            }
            $metainfo = $this->encodeTorrent();
        } else {
            throw new File_Bittorrent2_Exception('You must provide a file or directory.', File_Bittorrent2_Exception::make);
            return false;
        }
        return $metainfo;
    }

}

?>
