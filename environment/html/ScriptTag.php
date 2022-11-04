<?php

class ScriptTag extends Tag
{
		function __construct($class= null)
		{
			Tag::__construct("script", true, $class);
			$this->isScript= true;
		}
		function type($value)
		{
			$this->insertAttribute("type", $value);
		}
		function getType()
		{
			return $this->aNames["type"];
		}
		function src($value)
		{
			$this->insertAttribute("src", $value);
		}
		function language($value)
		{
			$this->insertAttribute("language", $value);
		}
		function add($tag)
		{
			if(isset($this->aNames["src"]))
			{
				echo "\n<br><b>Error</b> ScriptTag::add()<b>:</b> when for an ScriptTag an src attribute is defined";
				echo "\n<br>                                      nothing should be inside this tag !!!";
				exit;
			}
			Tag::add($tag);
		}
		protected function getBevorSubTagString()
		{
			global	$tag_spaces,
					$HTML_CLASS_DEBUG_CONTENT;
			
			if($HTML_CLASS_DEBUG_CONTENT)
			{
				$this->spaces($tag_spaces);
				return "<!--";
			}
			return "";
		}
		protected function getBehindSubTagString()
		{
			global	$tag_spaces,
					$HTML_CLASS_DEBUG_CONTENT;
			
			if($HTML_CLASS_DEBUG_CONTENT)
			{
				$this->spaces($tag_spaces);
				return "//-->";
			}
			return "";
		}
}

?>