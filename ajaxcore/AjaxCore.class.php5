<?php 
/**
 *						AjaxCore 1.2.2
 *				http://ajaxcore.sourceforge.net/
 *
 *  AjaxCore is a PHP framework that aims the ease development of rich 
 *  AJAX applications, using Prototype's JavaScript standard library.
 *  
 *  Copyright 2007 Mauro Niewolski (niewolski@users.sourceforge.net)
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */

abstract class AjaxCore
{
    private $currentfile;
    private $placeholder;
    private $method   = "get";
    private $oheaders = false;
    private $cache    = false;
    private $updating;
    private $request;
    const version  = "1.2.2";
    private $debug    = false;
    private $lastbind;
    private $JSCode   = array();

    /**
    * AjaxCore() 
    *
    * Class constructor.
    * @access protected
    */
    protected function AjaxCore ( )
    {
        $this->lookForAction();
    }

    /** 
    * setCurrentFile 
    *
    * Sets filename of the extended class that inherits of AjaxCore.
    * @access protected
    * @param string $file filename of the inherited class, where the AJAX request will be made.
    */
    protected function setCurrentFile ($file)
    {
        if (!$this->triggerOnEmpty($file,"file","setCurrentFile"))
            $this->currentfile=$file;
    }

    /**
    * setPlaceHolder
    *
    * Sets the <Div> ID that will be used as placeholder, as AjaxCore returns JavaScript code or HTML content in case JavaScript output is not undestood, it will echo it on the placeHolder.
    * @access protected
    * @param string $placeholder <Div id=""> used to return Html results.
    */
    protected function setPlaceHolder ($placeHolder)
    {
		if(!$this->triggerOnEmpty($placeHolder,"placeHolder","setPlaceHolder"))
			$this->placeholder=$placeHolder;
    }

    /**
    * setMethod
    *
    * Sets whether the method should be Get or Post.
    * @access protected
    * @param string $method get or post.
    */
    protected function setMethod ($method)
    {
        $method=strtolower($method);

        if ($method == "post" || $method == "get")
            $this->method=$method;
        else
            trigger_error("AjaxCore error setMethod expects \"get\" or \"post\" as parameter ", E_USER_ERROR);
    }

    /**
    * setOheaders
    *
    * Sets output headers, it should prevent people not using templating engine the error - Cannot modify header information-
    * @access protected
    * @param string $oheaders true or false
    */
    protected function setOutputHeaders ($oheaders)
    {
        if (is_bool($oheaders))
            $this->oheaders=$oheaders;
        else
            trigger_error("AjaxCore error setOutputHeaders expects a boolean value as parameter ", E_USER_ERROR);
    }

    /**
    * setCache
    *
    * Sets whether should use cache or not.
    * @access protected
    * @param bool $cache boolean value
    */
    protected function setCache ($cache)
    {
        if (is_bool($cache))
            $this->cache=$cache;
        else
            trigger_error("AjaxCore error setCache expects a boolean value as parameter ", E_USER_ERROR);
    }

    /**
    * setUpdating
    *
    * Sets an HTML code while the AJAX request is being made.
    * @access protected
    * @param string $code HTML code to show while making the request.
    */
    protected function setUpdating ($code)
    {
        $this->updating=$this->escapeJS($code);
    }

    /**
    * setDebug
    *
    * Set whether it should print JavaScript error when occurrs.
    * @access public
    * @param bool $debug boolean value.
    */
    public function setDebug ($debug)
    {
        if (is_bool($debug))
            $this->debug=$debug;
        else
            trigger_error("AjaxCore error setDebug expects a boolean value as parameter ", E_USER_ERROR);
    }

    /**
    * setJSCode
    * 
    * Sets specific JavaScript code to execute before and after the AJAX request is made. 
    * @access public
    * @param string $id HTML object id for binding methods, or reference id for inline bindings.
    * @param string $before JavaScript code to execute before the AJAX request is being made.
    * @param string $after JavaScript code to execute before the AJAX request is being made. 
    */
    public function setJSCode ($id, $before, $after)
    {
        $this->JSCode[$id]=array
            (
            $before,
            $after
            );
    }

    /**
    * getJSCode
    *
    * Returns string header JavaScript code for main placeHolder.
    * @access public
    */
    public function getJSCode ( )
    {
        $code=array();
        $code[]="<script>";
        $code[]="var lastbind='load';";
        $code[]="var timers=Array();";
        $code[]="var onload;";
        $code[]="function ".$this->placeholder."Response (originalRequest)";
        $code[]="{";
        $code[]="	try{";
		$code[]="\$('".$this->placeholder."').innerHTML = '';";
        $code[]="		eval(originalRequest.responseText);";
        $code[]="	}";
        $code[]="	catch(e)";
        $code[]="	{";

        if ($this->debug)
            $code[]="alert(e.getMessage());";

        $code[]  ="	 \$('".$this->placeholder."').innerHTML = originalRequest.responseText;";
        $code[]  ="	}";
        $code[]  ="}";
        $code[]  ="</script>";
        $appended="";

        foreach ($code as $ech)
            $appended.=$ech;

        return $appended;
    }

    /**
    * lookForAction
    *
    * Determines what PHP function should be called upon each AJAX request
    * @access private
    */
    private function lookForAction ( )
    {
        $this->parseCache();
        $this->getRequest();

        if (!empty($this->request['bind']) && method_exists($this, $this->request['bind']))
        {
            $method=$this->request['bind'];
            $this->initialize();
            $this->$method();
        }
    }

    /**
    * getRequest
    *
    * Returns get or post array.
    * @access private
	* @return array string request
    */
	protected function getRequest ( )
    {
        if ($this->method == "get")
            $this->request=&$_GET;
        else
            $this->request=&$_POST;
    }

	/**
	* getValue
	*
	* Returns the value sended within the request
	* @access protected
	* @param string var is the variable name sended within the request
	* @return string the value of the var sended within the request
	*/
	protected function getValue($var)
	{
		if(empty($var))
			trigger_error("AjaxCore error getValue variable name is empty", E_USER_ERROR);
		else if(isset($this->request[$var]))
			return $this->request[$var];
		else
			trigger_error("AjaxCore error getValue cannot find variable name ".$var, E_USER_ERROR);
		
	}

    /**
    * triggerOnEmpty
    *
    * trigger error on empty
    * @access private
	* @return boolean false if not empty
    */
    private function triggerOnEmpty ($element, $elementName, $method)
    {
        if (empty($element))
            trigger_error("AjaxCore error ".$method." \"".$elementName."\" parameter is empty ", E_USER_ERROR);
        else
            return false;
    }

    /**
    * parseCache
    *
    * Parses the current cache.
    * @access private
    */
    private function parseCache ( )
    {
        if ($this->oheaders == true && $this->cache == false)
        {
            header("Cache-Control: no-cache, must-revalidate");
        }
    }

    /**
    * bind
    *
    * Does the bind between an Html object and PHP function, request will be made when appropriate JavaScript event is triggered.
    * @access public
    * @param string $id Html ID object
    * @param string $event JavaScript event that will cause AJAX request ( onfocus onblur onmouseover onmouseout onmousedown onmouseup onsubmit onclick onload onchange onkeypress onkeydown onkeyup and so! )
    * @param string $bindto PHP function that handles the AJAX request
    * @param string $params ID of the Html elements that needs to be send within the request, static values (not html elements ) should be sent as _XXX=YYY , whether XXX represents variable name, and YYY value.
    * @return string JavaScript code to handle the binding.
    */
    public function bind ($id, $event, $bindto, $params = "")
    {
        if (!$this->triggerOnEmpty($id, "id", "bind") && !$this->triggerOnEmpty($event, "event", "bind")
            && !$this->triggerOnEmpty($bindto, "bindto", "bind"))
        {
            $code=array();
            $code[]  ="<script type='text/javascript'>";
            $code[]  ="\$('$id').$event ="; 
            $code[]  =substr($this->bindInline($bindto, $params, $id), 3);
            $code[]  ="</script>";
            $appended="";

            foreach ($code as $ech)
                $appended.=$ech;

            return $appended;
        }
    }

    /**
    * bindInline
    *
    * Does the bind between an Html object and PHP function, no event is required as the code generated is placed on <element onclick="javascript: BINDINLINE"> wherever onclick could be any JavaScript event given.
    * @access public
    * @param string $bindto PHP function that handles the AJAX request
    * @param string $params ID of the Html elements that needs to be send within the request, static values (not html elements ) should be sent as _XXX=YYY , whether XXX represents variable name, and YYY value.
    * @param string $id is the Javascript reference ID for specific behavior defined in setJSCode
    * @return string JavaScript inline code to handle the binding.
    */
    public function bindInline ($bindto, $params = "", $id = "")
    {
        if (!$this->triggerOnEmpty($bindto, "bindto", "bindInline"))
        {
            if (strlen($params) > 0)
                $params.=",_AjaxCore=".self::version;
            else
                $params.="_AjaxCore=".self::version;

            $arrayparams=explode(",", $params);
            $code=array();
            $code[]     ="new function () {";  

            if (isset($this->JSCode[$id]))
                $code[]=$this->JSCode[$id][0]; // setJS before

            $code[]=" eval('var request= { ";

            if (!empty($params))
                foreach ($arrayparams as $param)
                    if (strpos($param, "=") != 0)
                    {
                        $const =explode("=", $param);
                        $code[]=$const[0].": \'".$const[1]."\',";
                    }
                    else
                        $code[]="$param:\$F(\'$param\'),";

            $code[]="bind: \'$bindto\'";
            $code[]="  }');";

            if ($this->lastbind)
                $code[]="lastbind = '$bindto';";

            $code[]=" var query = \$H(request);";

            if (!empty($this->updating))
                $code[]="\$('".$this->placeholder."').innerHTML = '".$this->updating."';";

            $code[]
            ="AjaxCore('".$this->currentfile."','".$this->method."',query.toQueryString(),function(originalResponse){"
                .$this->placeholder."Response(originalResponse);";

            if (isset($this->JSCode[$id]))
                $code[]=$this->JSCode[$id][1]; // setJS after

            $code[]="});";
            $code[]="};";
			$appended="";
		
            foreach ($code as $ech)
                $appended.=$ech;

            return $appended;
        }
    }

    /**
    * bindTimer
    *
    * Does the bind between an Html object and PHP function, request will be made when appropriate JavaScript event occurs and timer expires.
    * @access public
    * @param string $id Html ID object
    * @param string $event JavaScript event that will cause AJAX request ( onfocus onblur onmouseover onmouseout onmousedown onmouseup onsubmit onclick onload onchange onkeypress onkeydown onkeyup, and so )
    * @param string $bindto PHP function that handles the AJAX request
    * @param string $params ID of the Html elements that needs to be send within the request, static values (not html elements ) should be sent as _XXX=YYY , whether XXX represents variable name, and YYY value.
    * @param string $timername name of the timer
    * @param int $timerms expiration time in milliseconds 
    * @return string JavaScript code to handle the binding.
    */
    public function bindTimer ($id, $event, $bindto, $timername, $timerms, $params = "")
    {
        if (!$this->triggerOnEmpty($id, "id", "bindTimer") && !$this->triggerOnEmpty($event, "event", "bindTimer")
            && !$this->triggerOnEmpty($bindto, "bindto", "bindTimer")
            && !$this->triggerOnEmpty($timername, "timername", "bindTimer")
            && !$this->triggerOnEmpty($timerms, "timerms", "bindTimer"))
        {
            $code=array();
            $code[]  ="<script type=\"text/javascript\">";
            $code[]  ="\$('$id').$event ="; 
            $code[]  =substr($this->bindTimerInline($bindto, $timername, $timerms, $params, $id), 3);
            $code[]  ="</script>";
            $appended="";

            foreach ($code as $ech)
                $appended.=$ech;

            return $appended;
        }
    }

    /**
    * bindTimerInline
    *
    * Does the bind between an Html object and PHP function, no event is required as the code generated is placed on <element onclick="javascript: BINDINLINE"> wherever onclick could be any JavaScript event given, request will be made when JavaScript event occurs and timer expires.
    * @access public
    * @param string $bindto PHP function that handles the AJAX request
    * @param string $timername name of the timer
    * @param int $timerms expiration time in milliseconds 
    * @param string $params ID of the Html elements that needs to be send within the request, static values (not html elements ) should be sent as _XXX=YYY , whether XXX represents variable name, and YYY value.
    * @param string $id is the Javascript reference ID for specific behavior defined in setJSCode
    * @return string JavaScript inline code to handle the binding.
    */
    public function bindTimerInline ($bindto, $timername, $timerms, $params = "", $id = "")
    {
        if (!$this->triggerOnEmpty($bindto, "bindto", "bindTimerInline")
            && !$this->triggerOnEmpty($timername, "timername", "bindTimerInline")
            && !$this->triggerOnEmpty($timerms, "timerms", "bindTimerInline"))
        {
            if (strlen($params) > 0)
                $params.=",_AjaxCore=".self::version;
            else
                $params.="_AjaxCore=".self::version;

            $code=array();
            $code[]     ="new function () {";                              
            $arrayparams=explode(",", $params);
            $code[]     ="timers['".$timername."Handle'] = function () {"; 

            if (isset($this->JSCode[$id]))
                $code[]=$this->JSCode[$id][0];                             // setJS before

            $code[]=" eval('var request= { ";

            if (!empty($params))
                foreach ($arrayparams as $param)
                    if (strpos($param, "=") != 0)
                    {
                        $const =explode("=", $param);
                        $code[]=$const[0].": \'".$const[1]."\',";
                    }
                    else
                        $code[]="$param:\$F(\'$param\'),";

            $code[]="bind: \'$bindto\'";
            $code[]="  }');";

            if ($this->lastbind)
                $code[]="lastbind = '$bindto';";

            $code[]=" var query = \$H(request);";

            if (!empty($this->updating))
                $code[]="\$('".$this->placeholder."').innerHTML = '".$this->updating."';";

            $code[]
            ="AjaxCore('".$this->currentfile."','".$this->method."',query.toQueryString(),function(originalResponse){"
                .$this->placeholder."Response(originalResponse);";

            if (isset($this->JSCode[$id]))
                $code[]=$this->JSCode[$id][1]; // setJS after

            $code[]="});";
            $code[]="};";
            $code[]="timers['$timername']=new AjaxCoreTimer(timers['".$timername."Handle'],$timerms);";
            $code[]=$this->startTimer($timername);
            $code[]="};";

            foreach ($code as $ech)
                $appended.=$ech;

            return $appended;
        }
    }

    /**
    * bindPeriodicalTimer
    *
    * Does the bind between an Html object and PHP function, request will be made when appropriate JavaScript event occurs and will keep repeating when timer expires.
    * @access public
    * @param string $id Html ID object
    * @param string $event JavaScript event that will cause AJAX request ( onfocus onblur onmouseover onmouseout onmousedown onmouseup onsubmit onclick onload onchange onkeypress onkeydown onkeyup, and so)
    * @param string $bindto PHP function that handles the AJAX request
    * @param string $params ID of the Html elements that needs to be send within the request, static values (not html elements ) should be sent as _XXX=YYY , whether XXX represents variable name, and YYY value.
    * @param string $timername name of the timer
    * @param int $timerms expiration time in milliseconds 
    * @return string JavaScript code to handle the binding.
    */
    public function bindPeriodicalTimer ($id, $event, $bindto, $timername, $timerms, $params = "")
    {
        if (!$this->triggerOnEmpty($id, "id", "bindPeriodicalTimer")
            && !$this->triggerOnEmpty($event, "event", "bindPeriodicalTimer")
            && !$this->triggerOnEmpty($bindto, "bindto", "bindPeriodicalTimer")
            && !$this->triggerOnEmpty($timername, "timername", "bindPeriodicalTimer")
            && !$this->triggerOnEmpty($timerms, "timerms",
                                          "bindPeriodicalTimer")) 
        {
            $code=array();
            $code[]  ="<script type=\"text/javascript\">";
            $code[]  ="\$('$id').$event ="; 
            $code[]  =substr($this->bindPeriodicalTimerInline($bindto, $timername, $timerms, $params, $id), 3);
            $code[]  ="</script>";
            $appended="";

            foreach ($code as $ech)
                $appended.=$ech;

            return $appended;
        }
    }

    /**
    * bindPeriodicalTimerInline
    *
    * Does the bind between an Html object and PHP function, no event is required as the code generated is placed on <element onclick="javascript: BINDINLINE"> wherever onclick could be any JavaScript event given, request will be made when JavaScript event occurs and will keep repeating when timer expires.
    * @access public
    * @param string $bindto PHP function that handles the AJAX request
    * @param string $timername name of the timer
    * @param int $timerms expiration time in milliseconds 
    * @param string $params ID of the Html elements that needs to be send within the request, static values (not html elements ) should be sent as _XXX=YYY , whether XXX represents variable name, and YYY value.
    * @param string $id is the Javascript reference ID for specific behavior defined in setJSCode
    * @return string JavaScript inline code to handle the binding.
    */
    public function bindPeriodicalTimerInline ($bindto, $timername, $timerms, $params = "", $id = "")
    {
        if (!$this->triggerOnEmpty($bindto, "bindto", "bindPeriodicalTimerInline")
            && !$this->triggerOnEmpty($timername, "timername", "bindPeriodicalTimerInline")
            && !$this->triggerOnEmpty($timerms, "timerms", "bindPeriodicalTimerInline"))
        {
            if (strlen($params) > 0)
                $params.=",_AjaxCore=".self::version;
            else
                $params.="_AjaxCore=".self::version;

            $code=array();
            $code[]     ="new function () {";                              
            $arrayparams=explode(",", $params);
            $code[]     ="timers['".$timername."Handle'] = function () {"; 

            if (isset($this->JSCode[$id]))
                $code[]=$this->JSCode[$id][0];                             // setJS before

            $code[]=" eval('var request= { ";

            if (!empty($params))
                foreach ($arrayparams as $param)
                    if (strpos($param, "=") != 0)
                    {
                        $const =explode("=", $param);
                        $code[]=$const[0].": \'".$const[1]."\',";
                    }
                    else
                        $code[]="$param:\$F(\'$param\'),";

            $code[]="bind: \'$bindto\'";
            $code[]="  }');";

            if ($this->lastbind)
                $code[]="lastbind = '$bindto';";

            $code[]=" var query = \$H(request);";

            if (!empty($this->updating))
                $code[]="\$('".$this->placeholder."').innerHTML = '".$this->updating."';";

            $code[]
            ="AjaxCore('".$this->currentfile."','".$this->method."',query.toQueryString(),function(originalResponse){"
                .$this->placeholder."Response(originalResponse);";

            if (isset($this->JSCode[$id]))
                $code[]=$this->JSCode[$id][1]; // setJS after

            $code[]="});";
            $code[]=$this->startTimer($timername);
            $code[]="};";
            $code[]="timers['$timername']=new AjaxCoreTimer(timers['".$timername."Handle'],$timerms);";
            $code[]=$this->startTimer($timername);
            $code[]="};";

            foreach ($code as $ech)
                $appended.=$ech;

            return $appended;
        }
    }

    /**
    * onLoad
    *
    * Does a request to a PHP function, request will be made when onLoad JavaScript event occurs.
    * @access public
    * @param string $bindto PHP function that handles the AJAX request
    * @param string $params ID of the Html elements that needs to be send within the request, static values (not html elements ) should be sent as _XXX=YYY , whether XXX represents variable name, and YYY value.
    * @param string $request type of request, bind, bindTimer, bindPeriodicalTimer
    * @param int $timerms timer expiration time in milliseconds (only for timer requests)
    * @return string JavaScript code to handle the binding.
    */
    public function onLoad ($bindto, $params = "", $request = "bind", $timerms = 300)
    {
        if (strlen($params) > 0)
            $params.=",_AjaxCore=".self::version;
        else
            $params.="_AjaxCore=".self::version;

		if (!$this->triggerOnEmpty($bindto, "bindto", "onLoad")) 
		{
			$code=array();
			$arrayparams=explode(",", $params);
			$code[]     ="<script type='text/javascript'>";
			$code[]     ="onLoad = function () {";  
			
			if (isset($this->JSCode[$id]))
				$code[]=$this->JSCode['onLoad'][0]; // setJS before, there's no html ID, so we'll use onLoad name tag
			
			$code[]=" eval('var request= { ";
			
			if (!empty($params))
				foreach ($arrayparams as $param)
					if (strpos($param, "=") != 0)
					{
						$const =explode("=", $param);
						$code[]=$const[0].": \'".$const[1]."\',";
					}
					else
						$code[]="$param:\$F(\'$param\'),";
			
			$code[]="bind: \'$bindto\'";
			$code[]="  }');";
			
			if ($this->lastbind)
				$code[]="lastbind = '$bindto';";
			
			$code[]=" var query = \$H(request);";
			
			if (!empty($this->updating))
				$code[]="\$('".$this->placeholder."').innerHTML = '".$this->updating."';";
			
			$code[]
				="AjaxCore('".$this->currentfile."','".$this->method."',query.toQueryString(),function(originalResponse){"
				.$this->placeholder."Response(originalResponse);";
			
			if (isset($this->JSCode[$id]))
				$code[]=$this->JSCode['onLoad'][1]; // setJS before, there's no html ID, so we'll use onLoad name tag
			
			$code[]="});";
			
			if ($request == "bindPeriodicalTimer")
				$code[]="timers['onLoad'].start();";
			
			$code[]="};";
			
			if ($request == "bindTimer" || $request == "bindPeriodicalTimer")
			{
				$code[]="timers['onLoad']=new AjaxCoreTimer(onLoad,$timerms);";
			}
			
			$code[]  ="window.onload=onLoad";
			$code[]  ="</script>";
			$appended="";
			
			foreach ($code as $ech)
				$appended.=$ech;
			
			return $appended;
		}
	}
	
	/**
	* intialize
	*
	* Method that is called just before any PHP function, useful to initialize databases and so on.
	* @access protected
	*/
	protected function initialize ( ) {} 
	
	/**
	* phpArrayToJS
	*
	* Converts an array from php to JavaScript.
	* @access public
	* @param array $array php array
	* @return string JavaScript array
	*/
	public function phpArrayToJS ($array)
	{
		$items=array();
		
		foreach ($array as $key => $value)
		{
			if (is_array($value))
				$items[]=$this->phpArrayToJS($value);
			
			else if (is_int($value))
				$items[]=$value;
			
			else
				$items[]="'".$this->escapeJS($value)."'";
		}
		
		return '['.implode(',', $items).']';
	}
	
	/**
	 * escapeJS (borrowed from Smarty)
	 *
	 * Escape the string to JavaScript
	 * @access public
	 * @param string $string String unscaped
	 * @return string escaped string
	 * @link http://smarty.php.net/manual/en/language.modifier.escape.php  escape (Smarty online manual)
	 * @author Monte Ohrt <monte at ohrt dot com>
	 */
	public function escapeJS ($string)
	{
		// escape quotes and backslashes, newlines, etc.
		return strtr($string, array
				(
					'\\' => '\\\\',
					"'"  => "\\'",
					'"'  => '\\\'',
					"\r" => '\\r',
					"\n" => '',
					'</' => '<\/'
					));
	}
	
	/**
	* alert
	*
	* Return JavaScript Alert Message
	* @access public
	* @param string $message message to alert
	* @return string JavaScript alert, if die is not set, otherwise outputs die(alert)
	*/
	public function alert ($message, $die = true)
	{
		$message=$this->escapeJS($message);
		$alert  ="alert('$message');";
		
		if ($die)
			die($alert);
		else
			return $alert;
	}
	
	/**
	*  arrayToString
	*
	* Returns a sentence form an array
	* @access public
	* @param array $array of sentences
	* @return string string with sentences
	*/
	public function arrayToString ($array)
	{
		$app="";
		
		foreach ($array as $arr)
			$app.=$arr;
		
		return $app;
	}
	
	/**
	* startTimer
	*
	* Restarts a timer
	* @access public
	* @param string id is the timer id
	* @return string JavaScript code to start timer
	*/
	public function startTimer ($id)
	{
		return "timers['$id'].start();";
	}
	
	/**
	* stopTimer
	*
	* Stops a timer
	* @access public
	* @param string $id is the timer id
	* @return string JavaScript code to stop timer
	*/
	public function stopTimer ($id)
	{
		return "timers['$id'].reset();";
	}
	
	/**
	* htmlLocation
	*
	* Sets browser current location
	* @access public
	* @param string $location is the new location
	* @return string JavaScript code to set location 
	*/
	public function htmlLocation ($location)
	{
		return "window.location='$location';";
	}
	
	/**
	* htmlWindowTitle
	*
	* Sets browser current title
	* @access public
	* @param string $string is the new title
	* @return string JavaScript code to set windows title 
	*/
	public function htmlWindowTitle ($string)
	{
		return "document.title='".$this->escapeJS($string)."';";
	}
	
	/**
	* htmlDisable
	*
	* Disables an html element
	* @access public
	* @param string $element is the ID of the element
	* @return string JavaScript code to disable element 
	*/
	public function htmlDisable ($element)
	{
		return "\$('".$element."').disabled=true;";
	}
	
	/**
	* htmlEnable
	*
	* Enables an html element
	* @access public
	* @param string $element is the ID of the element
	* @return string JavaScript code to enable element 
	*/
	public function htmlEnable ($element)
	{
		return "\$('".$element."').disabled=false;";
	}
	
	/**
	* htmlInner
	*
	* Sets an html inner content
	* @access public
	* @param string $element is the ID of the element
	* @param string $value is the content to put in
	* @return string JavaScript code to set inner content 
	*/
	public function htmlInner ($element, $value)
	{
		return "\$('".$element."').innerHTML = '".$this->escapeJS($value)."';";
	}
	
	/**
	* echoArray
	*
	* Outputs an array
	* @access public
	* @param array $array is the array to output
	*/
	public function echoArray ($array)
	{
		foreach ($array as $echo)
			echo $echo;
	}
}
?>