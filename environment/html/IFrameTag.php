<?php

class IFrameTag extends Tag
{		
		function __construct($tag= null, $class= null)
		{
			Tag::Tag("iframe", true, $class);
			$this->add($tag);
		}
		function name($vlaue)
		{
			$this->insertAttribute("name", $vlaue);
		}
		function align($align)
		{
			$this->insertAttribute("align", $align);
		}
		function src($src)
		{
			$this->insertAttribute("src", $src);
		}
		function width($width)
		{
			$this->insertAttribute("width", $width);			
		}
		function height($height)
		{
			$this->insertAttribute("height", $height);			
		}
		function frameborder($value)
		{
			if($value)
				$value= "1";
			else
				$value= "0";
			$this->insertAttribute("frameborder", $value);
		}
}

?>