<?php
#
#
# xmlhandler class
#	Author: geoffrey@google.com
#
#
################################################################################

  class xmlHandler {

	var $inTagState;
	var $curTagState;
	var $itemCounter;
	var $startTag;
	var $elementNames;
	var $xmlReturnData;
	var $xmlParser;
	var $xmlData;
	var $error;


	function setVarsDefault() {
	
		$this->inTagState     = 0;
		$this->curTagState    = '';
		$this->itemCounter    = 0;
		$this->xmlReturnData = array();
	}

	function setElementNames($arrayNames) {

		$this->elementNames = $arrayNames;
	}
	
	function setStartTag($sTag) {

		$this->startTag = $sTag;
	}

	function startElementHandler($xmlParser, $elementName, $elementAttribs) {
  	
		if($elementName == $this->startTag)
  		{
    			$this->inTagState = 1;
  		}

  		if($this->inTagState == 1)
  		{
    			$this->curTagState = $elementName;
  		}
  		else
  		{
    			$this->curTagState = '';
  		}
	}

	function endElementHandler($xmlParser, $elementName) {

  		$this->curTagState = '';
  		if($elementName == $this->startTag)
  		{
			$this->itemCounter++;
    			$this->inTagState = 0;
  		}
	}

	function characterDataHandler($xmlParser, $xmlData){

  		if($this->curTagState == '' || $this->inTagState == 0)
		{
    			return;	
		}
  		
		foreach($this->elementNames as $eNames)
		{
			if($this->curTagState == $eNames)
			{
				$strLoName = strtolower($eNames);
				$this->xmlReturnData[$this->itemCounter]["$strLoName"] = $xmlData;
			}
		}
	}

	function setXmlParser() {

		if(!($this->xmlParser = xml_parser_create()))
		{
  			xmlHandler::setErr("Couldn't create XML parser!");
		}
	}
	
	function xmlParse() {

		if(!($this->xmlParser = xml_parser_create("UTF-8")))
                {
                        xmlHandler::setErr("Couldn't create XML parser!");
                }
		xml_set_object($this->xmlParser, $this);
		xml_set_element_handler($this->xmlParser, "startElementHandler", "endElementHandler");
		xml_set_character_data_handler($this->xmlParser, "characterDataHandler");
  		if(!xml_parse($this->xmlParser, $this->xmlData))
		{
			xmlHandler::setErr("Couldn't read XML");
  		}
		
		return $this->xmlReturnData;
		xml_parser_free($this->xmlParser);

	}

	function setXmlData($data) {

		$this->xmlData = $data;
	}

	function getXmlData() {

		return $this->xmlData;
	}
	
	function setErr($err) {

		$this->error = $err;
	}

	function getErr() {

		return $this->error;
	}
  }

?>
