<?php

//Class specific to Geograph Project


	class FNUser {


		function FNUser() {
			GLOBAL $FN_DB,$USER;

			$this->requestUri = $_SERVER['REQUEST_URI'];
			$this->script_name = $_SERVER['SCRIPT_NAME'];
			$this->query_string = $_SERVER['QUERY_STRING'];


			$USER->mustHavePerm("basic");
		}


		function getCurrentUserId() {
			global $USER;
			return $USER->user_id;
		}


		//TODO this only works for current user right now, but the name suggests it should be extended to work for any user
		function getDisplayNameByUserID($userid) {
			global $USER;
			return $USER->nickname;
		}


		//TODO this only works for current user right now, but the name suggests it should be extended to work for any user
		function getProperNameByUserID($userid) {
			global $USER;
			return $USER->realname;
		}


	}

