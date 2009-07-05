<?php

//retreived from http://geocubes.com/client-library-php
//open licence

class geocubes {

        var $buflen             = 1024;
        var $isConnected        = 0;
        var $sock               = 0;

        function __construct ($api_key = "", $api_token = "") {

                // connect

                $this->api_key          = $api_key;
                $this->api_token        = $api_token;

                $this->sock             = fsockopen("api.geocubes.com", 5000, $this->errno, $this->errstr);

                if ($this->sock) {

                        $this->gcd_version      = fgets($this->sock, $this->buflen);
                        $this->isConnected      = 1;

                        $this->_auth();

                } else {
                        print "geocubes error: failed socket";
                }

        }


        function __destruct () {

                // disconnect

                if ($this->sock)
                        fclose($this->sock);

                $this->isConnected = 0;

        }


        function _sendTo ($message, &$ret = 0) {

                $_tmp = "";
                $_tok = "";
                $ret  = array();

                fwrite($this->sock, $message . "\r\n");

                $_tmp = fgets($this->sock, $this->buflen);
                $_tmp = str_replace(array("\r\n", "\n", "\r"), "", $_tmp);

                $_tok = strtok($_tmp, " ");

                while($_tok !== false) {

                        array_push($ret, $_tok);
                        $_tok = strtok(" ");

                }

                if (substr($_tmp, 1, 2) == "OK")
                        return 1;
                else
                        return 0;

        }


        function setOption () {

        }



        function _auth() {
                print "AUTH TOK\n";
                if ($this->_sendTo("AUTH TOK", $ret) == 1) {
                        print "AUTH TOK " . $ret[1]."\n";
                        if ($this->_sendTo("AUTH TOK " . $ret[1], $ret2) == 1) {
                                print "AUTH CST " . $this->api_key . " " . $this->api_token."\n";
                                if ($this->_sendTo("AUTH CST " . $this->api_key . " " . $this->api_token, $ret) == 1) {
                                        $this->isAuth = 1;
                                } else {
                                        print "geocubes error: auth failure";
                                }
                        } else {
                                $this->isAuth = 0;
                                print "geocubes error: bounce failed";
                        }
                } else {
                        print "geocubes error: no connection";
                        $this->isAuth = 0;
                }
        }


        function addPoint ($id, $lat = 0, $lng = 0, $ft = "", $fd1 = 0, $fd2 = 0) {

                if ($this->isAuth == 1) {

                        if ($id > 0) {

                                if ($this->_sendTo("GEO ADD ID " . $id, $ret) == 0)
                                        return 0;

                                if ($this->_sendTo("GEO ADD LAT " . $lat, $ret) == 0)
                                        return 0;

                                if ($this->_sendTo("GEO ADD LNG " . $lng, $ret) == 0)
                                        return 0;

                                if ($ft && $this->_sendTo("GEO ADD FT " . $ft, $ret) == 0)
                                        return 0;

                                if ($fd1 != 0 && $this->_sendTo("GEO ADD FD1 " . $fd1, $ret) == 0)
                                        return 0;

                                if ($fd2 != 0 && $this->_sendTo("GEO ADD FD2 " . $fd2, $ret) == 0)
                                        return 0;

                                if ($this->_sendTo("GEO ADD CPL", $ret) == 1)
                                        return $ret[1];
                                else {
                                        print "geocubes error: failed add";
                                        return 0;
                                }
                        } else {
                                print "geocubes error: no id";
                                return 0;
                        }
                } else {
                        print "geocubes error: noy connected";
                        return 0;
                }
        }


        function removePoint ($id) {

                if ($this->isAuth == 1 && $id > 0) {

                        if ($this->_sendTo("GEO DEL ID " . $id, $ret) == 1)
                                return $ret[1];
                        else
                                return 0;

                } else
                        return 0;

        }

}

?>
