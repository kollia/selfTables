<?php

class ImageTag extends Tag
{		
		function ImageTag($tag= null, $class= null)
		{
			Tag::Tag("img", false, $class);
			$this->add($tag);
		}
		function name($value)
		{
			$this->insertAttribute("name", $value);
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
		function alt($value)
		{
			$this->insertAttribute("alt", $value);
		}
		function border($value)
		{
			if($value)
				$value= "1";
			else
				$value= "0";
			$this->insertAttribute("border", $value);
		}
}

?>