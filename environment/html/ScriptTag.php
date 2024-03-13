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
		function add($tag, int $outFunc= 1)
		{
			if(STCheck::isDebug())
			{
				$msg[]= "when for an ScriptTag an src attribute is defined";
				$msg[]= "nothing should be inside this tag !!!";
				STCheck::is_error(isset($this->aNames["src"]), $msg, $outFunc);
				$outFunc++;
			}
			Tag::add($tag, ++$outFunc);
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