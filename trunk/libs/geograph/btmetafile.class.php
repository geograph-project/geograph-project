<?php


class BTMetafile {
    
    public  $file;
    public  $announceurl = '';
    public  $announcelist = array();
    public  $creationdate = '';
    public  $metainfostruct = array();
    
    private $pieceexp=18;
    private $encoder = 0;
    private $piececache = null;
    
    public function __construct($opts=array()) {
        $this->creationdate = time();
        if (sizeof($opts)>0) {
            foreach($opts as $name => $value) {
                if (isset($this->$name)) {
                    $this->$name = $value;
                }
            }
        }
    }
    
    
    public function makeMetafile($file=Null) {
        $this->metainfostruct['announce'] = $this->announceurl;
        $this->metainfostruct['creation date'] = $this->creationdate;
        if (sizeof($this->announcelist) > 0) {
            $this->metainfostruct['announce_list'] = $this->announcelist;
        }
         
        $info = $this->makeinfo($file);     
        ksort($info);
        $this->metainfostruct['info'] = $info;
    }
    
    
    public function saveStruct() {
        return $this->metainfostruct;
    }
    
    
    public function saveBencoded() {
        return $this->encoder->bencode($this->saveStruct());
    }
             
    
    public function saveMetafile($metafilename) {
        $bencoded = $this->saveBencoded();
        if ($bencoded != '') {
            try {
                $f = fopen($metafilename, 'wb');
                fwrite($f, $bencoded);
                fclose($f);
                return true;
            } catch (Exception $e) {
            
            }
        }
        
        return false;
    }
    
    
    private function makeinfo($file) {
        $info = array();
        if (is_dir($file)) {
            $info = $this->getDirInfo($file, $this->pieceexp);
        } elseif (is_file($file)) {
            $info = $this->getFileInfo($file, $this->pieceexp);      
        }

        return $info;
    }
    
    
    private function getDirInfo($dir, $pieceexp) {
        
        $offset=0;
        $offbytes='';
        $pieces = '';
        
        $info = array();
        $info['files'] = array();
        $info['name'] = basename($dir);
        $info['piece length'] = pow(2, $pieceexp);
        $info['pieces'] = '';
        
        $files = $this->getFiles($dir);
        foreach($files as $fstruct) {
            $fpath = (empty($fstruct[0])) ? "$dir/".$fstruct[1] : "$dir/".$fstruct[0]."/".$fstruct[1];
            $f = fopen($fpath, 'rb');
            if (!$f) continue;
                        
            $pos = 0;
            $size = filesize($fpath);
            $piecelen = pow(2, $pieceexp);
                        
            while($pos < $size) {
                $rlen = min(($piecelen - $offset), ($size - $pos));
                $binpiece = fread($f, $rlen);
                if ($rlen != $piecelen && $pos == 0) {
                    $pieces .= sha1($offbytes.$binpiece, true);
                    $offbytes = '';
                    $offset = 0;
                                
                } elseif ($rlen == $piecelen) {
                    $pieces .= sha1($binpiece, true);
                } else {
                    $offbytes = $binpiece;
                    $offset = $rlen;
                }
                            
                $pos += $rlen;
            }
            
            $pathp = (empty($fstruct[0])) ? array() : explode("/", $fstruct[0]);
            array_push($pathp, $fstruct[1]);
            array_push($info['files'], array('length'=> $size, 'path'=> $pathp));            
                
        }
        
        if ($offset > 0) {
            $pieces .= sha1($offbytes, true);
        }
        
        $info['pieces'] = $pieces;
        return $info;
    }
    
    
    private function getFileInfo($file, $pieceexp) {
        $pieces = '';
        $info = array();
        $piecelen = pow(2, $pieceexp);
        if (file_exists($file)) {
            $size = filesize($file);
            $p = 0;
            if(($f = fopen($file, 'rb'))==false) {
                return false;
            }
            
            $round=0; 
            while($p < $size) {
                $len = min($piecelen - $offset, $size - $p);
                $binpiece = fread($f, $len);
                $pieces .= sha1($binpiece, true);
                $p+=$len;
                $round+=1;
            } 
            
            fclose($f);
            
            $info['length'] = $size;
            $info['name'] = basename($file);
            $info['piece length'] = $piecelen;
            $info['pieces'] = $pieces;
        }
        
        return $info;
    }
    
    
    private function getFiles($dir, $basedir='') {
        static $files = array();
        if (is_dir($dir)) {
            if ($d = opendir($dir)) {
                while ($f = readdir($d)) {
                    if ($f == "." || $f == ".." || empty($f)) continue;
                    $fpath = "$dir/$f";
                    if (is_dir($fpath)) {
                        $b = (empty($basedir)) ? $f : "$basedir/$f";
                        $this->getFiles("$dir/$f", $b);
                    } elseif (is_file($fpath)) {
                        array_push($files, array($basedir, $f));
                    }
                }
            }
        }
        
        return $files;
    }
}


Class BTBencode {
    
    private $bencoded = array();
    private $encmethods = array();
     
    public function __construct() {
    
    }


    public function bencode($mixed) {
        $bencoded = '';
        $method = "encode".ucfirst(gettype($mixed));
        if (method_exists($this, $method)) {
            $bencoded = $this->$method($mixed);
        }
        
        return $bencoded;
    }
    
    public function bdecode($encoded) {
    
    }
    
    private function encodeString($in) {
        return sprintf("%d:%s", strlen($in), $in);
    }


    private function encodeInteger($in) {
        return sprintf("i%de", (int) $in);
    }

    private function encodeList($in) {
        $benc = "l";
        foreach($in as $elem) {
            $benc.=$this->bencode($elem);
        }
        $benc.= "e";
        return $benc;
    }
    
    
    private function encodeDictionary($in) {
        $benc = "d";
        foreach($in as $key => $value) {
            $benc.= $this->encodeString((string)$key).$this->bencode($value);
        }
        $benc.= "e";
        return $benc;
    }
    
    
    private function encodeArray($in) {
        $islist=true;
        
        foreach(array_keys($in) as $i) {
            if (!is_int($i)) {
                $islist=false;
            }
        }
            
        if ($islist) {
            return $this->encodeList($in);
        } else {
            return $this->encodeDictionary($in);
        }
    }

}



$encoder = new BTBencode();
$opts = array('encoder'=>$encoder, 'announceurl'=>'http://tracker.var.cc:7001/announce');

$m = new BTMetafile($opts);
$m->makeMetafile('/home/silvan/tmp/test/AlexandreBilobeau-Energy.mp3');
$s = $m->saveBencoded();
$m->saveMetafile('/tmp/AlexandreBilobeau-Energy.mp3.torrent');


?>