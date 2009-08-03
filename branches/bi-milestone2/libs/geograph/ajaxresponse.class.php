<?php
/**
 * $Project: GeoGraph $
 * $Id: event.class.php 1430 2005-10-19 22:40:09Z barryhunter $
 * 
 * GeoGraph geographic photo archive project
 * http://geograph.sourceforge.net/
 *
 * This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

/**
* Useful class for constructing responses to rico ajax calls
* 
* Here's some sample client side code
* 
* <script src="/prototype.js" type="text/javascript"></script>
* <script src="/rico.js" type="text/javascript"></script>
* <script language="javascript">
*	function init()
*   {
* 		ajaxEngine.registerRequest('doRequest', '/foo.php');	
*		ajaxEngine.registerAjaxElement('results');
*		ajaxEngine.registerAjaxElement('status');
*	}
*
*	function doSearch()
*	{
*		var find=document.getElementById('find').value;
*		document.getElementById('status').innerHTML='<i>searching...</i>';
*		ajaxEngine.sendRequest('doSearch', 'find='+find);
*	}
* </script>
* 
* <div>
*   <input type="text" id="find" value=""/>
*   <input type="button" onclick="doSearch()" value="Go">
* </div>
* <div id="results"></div>
* <div id="status"></div>
* 
* The server side would look something like this
* 
* $response = new AjaxResponse
* $response->setElement('status', 'Complete');
* $response->setElement('results', '<ul><li>Sample result</li></ul>');
* $response->send();
* 
* @package Geograph
* @author Paul Dixon <paul@elphin.com>
* @version $Revision: 1430 $
*/


class AjaxResponse
{
	protected $pretty=false;
	protected $indent=-1;
	protected $response="";
	
	/**
	 * Constructor
	 * @param $pretty set to true for more human readable XML output
	 */
	public function AjaxResponse($pretty=false)
	{
		$this->pretty=$pretty;
		$this->_tag("<ajax-response>");
	}
	
	/*
	 * adds an XML tag to the response
	 */
	private function _tag($tag)
	{
		if ($tag{1}=='/')
			$this->indent--;
		else
			$this->indent++;
		
		if ($this->pretty)
		{
			if ($this->indent>0)
				$this->response.=str_repeat("  ", $this->indent);
			$this->response.="$tag\n";
		}
		else
		{
			$this->response.=$tag;
		
		}
	}
	
	/**
	 * Send response to client
	 */
	public function send()
	{
		header("Content-Type:text/xml");
		
		//close the response
		$this->_tag("</ajax-response>");
	
		echo $this->response;
	}
	
	/**
	 * Sets the content of a particular HTML DOM element
	 * This element id must have been registered with rico
	 */
	public function setElement($id, $html)
	{
	
		$this->_tag("<response type=\"element\" id=\"$id\">");
		
		if ($this->pretty)
			$this->response.=str_repeat("  ", $this->indent+1);
		$this->response.= $html;
		
		
		$this->_tag("</response>");
		
	}
	
	
	/**
	 * Perform a callback
	 * The callback must have been registered with rico
	 */
	public function objectCallback($id, $xml)
	{
		$this->_tag("<response type=\"object\" id=\"$id\">");
		
		if ($this->pretty)
			$this->response.=str_repeat("  ", $this->indent+1);
		$this->response.= $xml;
		
		
		$this->_tag("</response>");
	}
	
	
}

?>