<?php

		require_once($php_html_description);
	
class JavaScriptTag extends ScriptTag
{
		function JavaScriptTag()
		{
			ScriptTag::ScriptTag();
			$this->language("javascript");
			$this->type("text/javascript");
		}
}

class jsFunctionBase
{
		var $functionName;
		var $bInherit= false;
		var $inherit= array();
		
		function jsFunctionBase($name, $aParams, $bInherit)
		{	
			$nParamsCount= count($aParams);		
			for($i= 0; $i<$nParamsCount; $i++)
				$params= $aParams[$i].",";
			if($params)
				$params= substr($params, 0, strlen($params)-1);
			$functionName= $name."(";
			foreach($aParams as $param)
				$functionName.= $param.",";
			$functionName= substr($functionName, 0, strlen($functionName)-1).")";
			$this->functionName= $functionName;
			$this->bInherit= $bInherit;
		}
		function display()
		{
			global $tag_spaces;
			global $HTML_CLASS_DEBUG_CONTENT;
			
			if($HTML_CLASS_DEBUG_CONTENT)
				$this->spaces($tag_spaces);
			echo $this->functionName;	
			if(!$this->bInherit)
			{
				echo ";";
				return;
			}
			if($HTML_CLASS_DEBUG_CONTENT)
				$this->spaces($tag_spaces);
			echo "{";
			foreach($this->inherit as $function)
			{	
        	if($HTML_CLASS_DEBUG_CONTENT)
					$tag_spaces++;
				if(is_String($function) or is_numeric($function))
				{	
            	if($HTML_CLASS_DEBUG_CONTENT)
						$this->spaces($tag_spaces);
					echo $function;
				}else
				{
					if($HTML_CLASS_DEBUG_CONTENT and !is_subclass_of($function, "jsfunctionbase"))
					{
						out(get_parent_class($function));echo "\n";
						echo "\n<br><b>ERROR:</b> bei den JavaScript-Funktionen d�rfen nur Strings und JavaScript-Funktionen hinzugef�gt werden<br>\n";
						out($function);
						exit();
					}
					$tag->display();
				}
        	if($HTML_CLASS_DEBUG_CONTENT)
					$tag_spaces--;
			}
			if($HTML_CLASS_DEBUG_CONTENT)
				$this->spaces($tag_spaces);
			echo "}";
		}
		function spaces($num)
		{
			echo "\n";
			for($i= 0; $i<$num; $i++)
				echo "  ";
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
		function jsFunction($name, $param= null)
		{
			$args= func_get_args();
			$new_args= array();
			for($i= 1; $i<count($args); $i++)
				$new_args[]= $args[$i];
			jsFunctionBase::jsFunctionBase("function $name", $new_args, true);
		}
}

?>