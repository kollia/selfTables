<?php

		require_once($php_html_description);
	
class JavaScriptTag extends ScriptTag
{
		function __construct()
		{
			ScriptTag::__construct();
			$this->language("javascript");
			$this->type("text/javascript");
		}
}

class jsFunctionBase
{
		var $functionName;
		var $bInherit= false;
		var $inherit= array();
		
		function __construct($name, $aParams, $bInherit)
		{
			$functionName= $name."(";
			foreach($aParams as $param)
				$functionName.= $param.",";
			if(count($aParams))
			    $functionName= substr($functionName, 0, strlen($functionName)-1);
			$functionName.= ")";
			$this->functionName= $functionName;
			$this->bInherit= $bInherit;
		}
		function display()
		{
		    echo $this->getDisplayString();
		}
		function getDisplayString()
		{
			global $tag_spaces;
			global $HTML_CLASS_DEBUG_CONTENT;
			
			$displayString= "";
			if($HTML_CLASS_DEBUG_CONTENT)
				$displayString.= $this->spaces($tag_spaces);
			$displayString.= $this->functionName;	
			if(!$this->bInherit)
			{
			    $displayString.= ";";
				return;
			}
			if($HTML_CLASS_DEBUG_CONTENT)
			    $displayString.= $this->spaces($tag_spaces);
			$displayString.= "{";
			foreach($this->inherit as $function)
			{	
        	   if($HTML_CLASS_DEBUG_CONTENT)
					$tag_spaces++;
				if(is_String($function) or is_numeric($function))
				{	
            	   if($HTML_CLASS_DEBUG_CONTENT)
            	       $displayString.= $this->spaces($tag_spaces);
            	   $displayString.= $function;
				}else
				{
					if($HTML_CLASS_DEBUG_CONTENT and !is_subclass_of($function, "jsfunctionbase"))
					{
						out(get_parent_class($function));echo "\n";
						echo "\n<br><b>ERROR:</b> bei den JavaScript-Funktionen dürfen nur Strings und JavaScript-Funktionen hinzugefügt werden<br>\n";
						out($function);
						exit();
					}
					$displayString.= $tag->getDisplayString();
				}
        	if($HTML_CLASS_DEBUG_CONTENT)
					$tag_spaces--;
			}
			if($HTML_CLASS_DEBUG_CONTENT)
			    $displayString.=$this->spaces($tag_spaces);
			$displayString.= "}";
			return $displayString;
		}
		function spaces($num)
		{
		    $displayString= "\n";
			for($i= 0; $i<$num; $i++)
			    $displayString.= "  ";
			return $displayString;
		}
		function add($function)
		{
			if($function==null)
				return;
			if(!$this->bInherit)
			{
				echo "\n<br>the function <b>&lt;";
				echo $this->tag;
				echo "&gt;</b> can not inherit other functions</b><br>\n";
				exit;
			}
			$this->inherit[]= $function;
		}
}

class jsFunction extends jsFunctionBase
{
		function __construct($name, $param= null)
		{
			$args= func_get_args();
			$new_args= array();
			for($i= 1; $i<count($args); $i++)
				$new_args[]= $args[$i];
			jsFunctionBase::__construct("function $name", $new_args, true);
		}
}

?>