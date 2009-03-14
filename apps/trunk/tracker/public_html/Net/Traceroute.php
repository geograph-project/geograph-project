<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Stefan Neufeind <pear.neufeind@speedpartner.de>              |
// |                                                                      |
// | Credits:                                                             |
// | Idea and API originally based upon package Net_Ping                  |
// +----------------------------------------------------------------------+
//
// $Id: Traceroute.php,v 1.7 2007/03/27 13:51:29 neufeind Exp $

/**
* Package for handling traceroute outputs
*
* This package is an interface to the traceroute/tracert-tool most
* current OS offer. It parses the output into an easy-to-use array structure.
* Please note: The parsing is OS-dependepent! So if your OS is currently
* not supported, please let us know.
*
* @author   Stefan Neufeind <pear.neufeind@speedpartner.de>
* @package  Net_Traceroute
*/

/**
* PEAR-base-class, needed for error-handling
*/
require_once "PEAR.php";

/**
* Class for determining the OS
*
* Knowning the correct OS is really essential to choose the right parser.
*/
require_once "OS/Guess.php";

define('NET_TRACEROUTE_FAILED_MSG', 'execution of traceroute failed');
define('NET_TRACEROUTE_HOST_NOT_FOUND_MSG', 'unknown host');
define('NET_TRACEROUTE_INVALID_ARGUMENTS_MSG', 'invalid argument array');
define('NET_TRACEROUTE_CANT_LOCATE_TRACEROUTE_BINARY_MSG', 'unable to locate the traceroute binary');
define('NET_TRACEROUTE_RESULT_UNSUPPORTED_BACKEND_MSG', 'Backend not Supported');

define('NET_TRACEROUTE_FAILED',                         0);
define('NET_TRACEROUTE_HOST_NOT_FOUND',                 1);
define('NET_TRACEROUTE_INVALID_ARGUMENTS',              2);
define('NET_TRACEROUTE_CANT_LOCATE_TRACEROUTE_BINARY',  3);
define('NET_TRACEROUTE_RESULT_UNSUPPORTED_BACKEND',     4);

/**
* Wrapper class for traceroute calls
*
* @author   Stefan Neufeind <pear.neufeind@speedpartner.de>
* @package  Net_Traceroute
* @access   public
*/
class Net_Traceroute
{
    /**
    * Location where the traceroute program is stored
    *
    * @var    string
    * @access private
    */
    var $_traceroute_path = "";

    /**
    * Array with the result from the traceroute execution
    *
    * @var    array
    * @access private
    */
    var $_result = array();

    /**
    * OS_Guess->getSysname result
    *
    * @var    string
    * @access private
    */
    var $_sysname = "";

    /**
    * Traceroute command arguments
    *
    * @var    array
    * @access private
    */
    var $_args = array();

    /**
    * Indicates if an empty array was given to setArgs (not used yet)
    *
    * @var    boolean
    * @access private
    */
    var $_noArgs = true;

    /**
    * Contains the argument->option relation
    *
    * @var    array
    * @access private
    */
    var $_argRelation = array();

    /**
    * Constructor
    *
    * @access private
    */
    function Net_Traceroute($traceroute_path, $sysname)
    {
        $this->_traceroute_path = $traceroute_path;
        $this->_sysname   = $sysname;
        $this->_initArgRelation();
    }

    /**
    * Factory for Net_Traceroute
    *
    * Call this method to create a new instance of Net_Traceroute
    *
    * @return object Net_Traceroute
    * @access public
    */
    function factory()
    {
        $OS_Guess  = new OS_Guess;
        $sysname   = $OS_Guess->getSysname();
        $traceroute_path = '';

        if (($traceroute_path = Net_Traceroute::_setTraceroutePath($sysname)) == NET_TRACEROUTE_CANT_LOCATE_TRACEROUTE_BINARY) {
            return PEAR::throwError(NET_TRACEROUTE_CANT_LOCATE_TRACEROUTE_BINARY_MSG, NET_TRACEROUTE_CANT_LOCATE_TRACEROUTE_BINARY);
        } else {
            return new Net_Traceroute($traceroute_path, $sysname);
        }

    }
    /**
    * Set the arguments array
    *
    * @param  array  $args    Hash with options
    * @return mixed           true or PEAR_error
    * @access public
    */
    function setArgs($args)
    {
        if (!is_array($args)) {
            return PEAR::throwError(NET_TRACEROUTE_INVALID_ARGUMENTS_MSG, NET_TRACEROUTE_INVALID_ARGUMENTS);
        }

        /* accept empty arrays, but set flag*/
        if (0 == count($args)) {
            $this->_noArgs = true;
        } else {
           $this->_noArgs = false;
        }

        $this->_args = $args;

        return true;
    }

    /**
    * Sets the system's path to the traceroute binary
    *
    * @param  string  $sysname    Systemname which identifies the OS
    * @access private
    */
    function _setTraceroutePath($sysname)
    {
        $status    = '';
        $output    = array();
        $traceroute_path = '';

        if ("windows" == $sysname) {
            return "tracert";
        } else {
            $traceroute_path = exec("which traceroute", $output, $status);
            if ($status != 0) {
                foreach(array('/usr/sbin', '/sbin', '/usr/bin', '/bin', '/usr/local/bin') as $test) {
                    if ($status != 0) {
                        $traceroute_path = $test.'/traceroute';
                        if (file_exists($traceroute_path)) {
                            $status = 0;
                        }
                    }
                }                
            }  
            if ($status != 0) {
                return NET_TRACEROUTE_CANT_LOCATE_TRACEROUTE_BINARY;
            } else {
                return $traceroute_path;
            }
        }

    }

    /**
    * Creates the argument list according to platform differences
    *
    * @return string          Argument line
    * @access private
    */
    function _createArgList()
    {
        $retval     = array();

        $numeric    = "";
        $ttl        = "";
        $deadline   = "";

        foreach($this->_args AS $option => $value) {
            if(!empty($option) && NULL != $this->_argRelation[$this->_sysname][$option]) {
                ${$option} = $this->_argRelation[$this->_sysname][$option]." ".escapeshellarg($value)." ";
             }
        }

        switch($this->_sysname) {
        case "linux":
             $retval[0] = $numeric.$ttl.$deadline." 2>&1";
             $retval[1] = "";
             break;

        case "windows":
             $retval[0] = $numeric.$ttl.$deadline;
             $retval[1] = "";
             break;

        default:
             $retval[0] = "";
             $retval[1] = "";
             break;
        }

        return($retval);
    }

    /**
    * Execute traceroute
    *
    * @param  string $host    hostname or IP of destination
    * @return object Net_Traceroute_Result
    * @access public
    */
    function traceroute($host)
    {

        $argList = $this->_createArgList();
        $cmd = $this->_traceroute_path." ".$argList[0]." ".$host." ".$argList[1];
        exec($cmd, $this->_result);

        if (!is_array($this->_result)) {
            return PEAR::throwError(NET_TRACEROUTE_FAILED_MSG, NET_TRACEROUTE_FAILED);
        }

        if (count($this->_result) == 0) {
            return PEAR::throwError(NET_TRACEROUTE_HOST_NOT_FOUND_MSG, NET_TRACEROUTE_HOST_NOT_FOUND);
        } else {
            return Net_Traceroute_Result::factory($this->_result, $this->_sysname);
        }
    }

    /**
    * Output errors with PHP trigger_error(). You can silence the errors
    * with prefixing a "@" sign to the function call: @Net_Traceroute::traceroute(..);
    *
    * @param  mixed $error    a PEAR error or a string with the error message
    * @return bool            false
    * @access private
    * @author Kai Schröder <k.schroeder@php.net>
    */
    function raiseError($error)
    {
        if (PEAR::isError($error)) {
            $error = $error->getMessage();
        }
        trigger_error($error, E_USER_WARNING);
        return false;
    }

    /**
    * Creates the argument list according to platform differences
    *
    * @return string          Argument line
    * @access private
    */
    function _initArgRelation()
    {
        $this->_argRelation = array(
                                    "linux" => array (
                                                        "numeric"   => "-n",
                                                        "ttl"       => "-m",
                                                        "deadline"  => "-w"
                                                        ),
                                    "windows" => array (
                                                        "numeric"   => "-d",
                                                        "ttl"       => "-h",
                                                        "deadline"  => "-w"
                                                        )
                               );
    }
}


/**
* Container class for Net_Traceroute results
*
* @author   Stefan Neufeind <pear.neufeind@speedpartner.de>
* @package  Net_Traceroute
* @access   public
*/
class Net_Traceroute_Result
{
    /**
    * Hops and associated time in ms
    *
    * @var    array
    * @access private
    */
    var $_hops = array();

    /**
    * The target's IP Address
    *
    * @var    string
    * @access private
    */
    var $_target_ip;

    /**
    * The ICMP request's TTL
    *
    * @var    int
    * @access private
    */
    var $_ttl;

    /**
    * The raw Net_Traceroute::result
    *
    * @var    array
    * @access private
    */
    var $_raw_data = array();

    /**
    * The Net_Traceroute::_sysname
    *
    * @var    string
    * @access private
    */
    var $_sysname;


    /**
    * Constructor for the Class
    *
    * @access private
    */
    function Net_Traceroute_Result($result, $sysname)
    {
        $this->_raw_data = $result;
        $this->_sysname  = $sysname;

        $this->_parseResult();
    }

    /**
    * Factory for Net_Traceroute_Result
    *
    * @param  array  $result      Net_Traceroute result
    * @param  string $sysname     OS_Guess::sysname
    * @access public
    */
    function factory($result, $sysname)
    {
        if (!Net_Traceroute_Result::_prepareParseResult($sysname)) {
            return PEAR::throwError(NET_TRACEROUTE_RESULT_UNSUPPORTED_BACKEND_MSG, NET_TRACEROUTE_RESULT_UNSUPPORTED_BACKEND);
        } else {
            return new Net_Traceroute_Result($result, $sysname);
        }
    }

    /**
    * Preparation method for _parseResult
    *
    * @access private
    * @param  string $sysname     OS_Guess::sysname
    * $return bool
    */
    function _prepareParseResult($sysname)
    {
        $methods = array_values(array_map('strtolower', get_class_methods('Net_Traceroute_Result')));
        return in_array(strtolower('_parseResult'.$sysname),
                        $methods);
    }

    /**
    * Delegates the parsing routine according to $this->_sysname
    *
    * @see    _parseResultlinux()
    * @see    _parseResultwindows()
    * @access private
    */
    function _parseResult()
    {
        $this->{'_parseResult' . $this->_sysname}();
    }

    /**
    * Parses the output of Linux' traceroute command
    *
    * @see    _parseResult()
    * @access private
    */
    function _parseResultlinux()
    {
        $raw_data_len = count($this->_raw_data);
        $dataRow = 0;

        while (empty($this->_raw_data[$dataRow]) && ($dataRow<$raw_data_len)) {
          $dataRow++;
        }
        
        $tempparts        = explode(' ', $this->_raw_data[$dataRow]);
        $this->_target_ip = trim($tempparts[3], ' (),');
        $this->_ttl       = (int) $tempparts[4];
        $dataRow++;
        
        while (empty($this->_raw_data[$dataRow]) && ($dataRow<$raw_data_len)) {
          $dataRow++;
        }

        $hops = array();
        while (($dataRow < $raw_data_len) && !empty($this->_raw_data[$dataRow])) {
            $hop = array();
            $parts = explode('  ', substr($this->_raw_data[$dataRow], 4));
            
            /* if we can find a next hop it's name/ip will be here */
            if (count($parts) > 0) {
                /* get machine/ip */
                $machineparts = explode(' ', $parts[0]);
                if (count($machineparts) > 1) {
                    $hop['machine'] = $machineparts[0];
                    $hop['ip']      = trim($machineparts[1], ' ()');
                } else {
                    $hop['ip'] = $machineparts[0];
                }
                array_shift($parts);
            }

            $responsetimes = array();
            for($timeidx = 0; $timeidx < count($parts); $timeidx++) {
                $temppart=explode(' ', $parts[$timeidx]);
                if ($temppart[0] == "*") {
                    $responsetimes[] = -1; // unreachable
                } else {
                    $responsetimes[] = (float) $temppart[0];
                }
            }
            $hop['responsetimes'] = $responsetimes;
            $hops[] = $hop;
            $dataRow++;
        }
        $this->_hops = $hops;
    }

    /**
    * Parses the output of Windows' traceroute command
    *
    * @see    _parseResult()
    * @access private
    */
    function _parseResultwindows()
    {
        $raw_data_len = count($this->_raw_data);
        $dataRow = 0;
        
        while (empty($this->_raw_data[$dataRow]) && ($dataRow<$raw_data_len)) {
          $dataRow++;
        }
        
        $tempparts = explode(' ', $this->_raw_data[$dataRow]);
        $searchIdx = 0;
        while (($searchIdx < count($tempparts)) && (substr($tempparts[$searchIdx], 0, 1) != '[')) {
            $searchIdx++;
        }
        $this->_target_ip = trim($tempparts[$searchIdx], ' [],');
        while (($searchIdx < count($tempparts)) && ((int) $tempparts[$searchIdx] <= 0)) {
            $searchIdx++;
        }
        if ((int) $tempparts[$searchIdx] > 0) {
            $this->_ttl = (int) $tempparts[$searchIdx]; // TTL might be written in next line; e.g. on Windows 98
        } elseif (!empty($this->_raw_data[$dataRow+1])) {
            $dataRow++;
            $tempparts  = explode(' ', $this->_raw_data[$dataRow]);
            $searchIdx = 0;
            while (($searchIdx < count($tempparts)) && ((int) $tempparts[$searchIdx] <= 0)) {
                $searchIdx++;
            }
            if ((int) $tempparts[$searchIdx] > 0) {
                $this->_ttl       = (int) $tempparts[$searchIdx]; // TTL might be written in next line; e.g. on Windows 98
            }
        }

        while (!empty($this->_raw_data[$dataRow]) && ($dataRow<$raw_data_len)) {
            $dataRow++;
        }
        while (empty($this->_raw_data[$dataRow]) && ($dataRow<$raw_data_len)) {
            $dataRow++;
        }

        $hops=array();
        /* loop from second elment to the fifths last */
        while (($dataRow < $raw_data_len) && !empty($this->_raw_data[$dataRow])) {
            $hop=array();
            
            $responsetimes = array();
            for($timeidx = 0; $timeidx < 3; $timeidx++) {
                $temppart=trim(str_replace(' ms','',substr($this->_raw_data[$dataRow], 3+($timeidx*9), 9)));
                if ($temppart == '*') {
                    $responsetimes[] = -1; // unreachable
                } else {
                    $responsetimes[] = (float) str_replace('<', '', $temppart);
                }
            }
            $hop['responsetimes'] = $responsetimes;

            $machineparts = explode(' ', rtrim(substr($this->_raw_data[$dataRow], 32)));
            // if we can find a next hop it's name/ip will be here
            if (count($machineparts) == 1) {
                $hop['ip'] = trim($machineparts[0], ' ()[]');
            } elseif (count($machineparts) == 2) {
                $hop['machine'] = $machineparts[0];
                $hop['ip']      = trim($machineparts[1], ' ()[]');
            }
            // otherwise we've got an errormessage or something here ... like "time limit exceeded"

            $hops[] = $hop;
            $dataRow++;
        }
        $this->_hops = $hops;
    }

    /**
    * Returns a Traceroute_Result property
    *
    * @param  string $name    property name
    * @return mixed           property value
    * @access public
    */
    function getValue($name)
    {
        return isset($this->$name) ? $this->$name : '';
    }

    /**
    * Returns the target IP from parsed result
    *
    * @return string          IP address
    * @see    _target_ip
    * @access public
    */
    function getTargetIp()
    {
    	return $this->_target_ip;
    }

    /**
    * Returns hops from parsed result
    *
    * @return array           Hops
    * @see    _hops
    * @access public
    */
    function getHops()
    {
    	return $this->_hops;
    }

    /**
    * Returns TTL from parsed result
    *
    * @return int             TTL
    * @see    _ttl
    * @access public
    */
    function getTTL()
    {
    	return $this->_ttl;
    }

    /**
    * Returns raw data that was returned by traceroute
    *
    * @return array           raw data
    * @see    _raw_data
    * @access public
    */
    function getRawData()
    {
    	return $this->_raw_data;
    }

    /**
    * Returns sysname that was "guessed" (OS on which class is running)
    *
    * @return string          OS_Guess::sysname
    * @see    _sysname
    * @access public
    */
    function getSystemName()
    {
    	return $this->_sysname;
    }
}
?>
