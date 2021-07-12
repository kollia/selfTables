<?php

define("TH", true);
define("TD", false);

class ColumnTag extends Tag
{
		function ColumnTag($headline, $class= null)
		{
			if($headline)
				$name= "th";
			else
				$name= "td";
			Tag::Tag($name, true, $class);
		}
		function align($value)
		{
			$this->insertAttribute("align", $value);
		}
		function valign($value)
		{
			$this->insertAttribute("valign", $value);
		}		
		function colspan($num)
		{
			if($num>1)
				$this->insertAttribute("colspan", $num);			
		}
		function rowspan($num)
		{
			if($num>1)
				$this->insertAttribute("rowspan", $num);			
		}	
		function width($width)
		{
			$this->insertAttribute("width", $width);			
		}
		function height($height)
		{
			$this->insertAttribute("height", $height);			
		}
		function bgcolor($color)
		{
			$this->insertAttribute("bgcolor", $color);
		}
}

class RowTag extends Tag
{
		function RowTag($class= null)
		{
			Tag::Tag("tr", true, $class);
		}
		function align($value)
		{
			$this->insertAttribute("align", $value);
		}
		function valign($value)
		{
			$this->insertAttribute("valign", $value);
		}
		// colspan nur im ColumnTag
		/*function colspan($value)
		{
			if($value>1)
				$this->insertAttribute("colspan", $value);		
		}
		function rowspan($value)
		{
			if($value>1)
				$this->insertAttribute("rowspan", $value);			
		}*/
		function width($value)
		{
			$this->insertAttribute("width", $value);			
		}
		function height($value)
		{
			$this->insertAttribute("height", $value);			
		}
		function bgcolor($value)
		{
			$this->insertAttribute("bgcolor", $value);
		}
}

class TableTag extends Tag
{
		function TableTag($class= null)
		{
			Tag::Tag("table", true, $class);
		}
		function border($show)
		{
			$border= 0;
			if($show)
				$border= 1;
			$this->insertAttribute("border", $border);
		}
		function align($align)
		{
			$this->insertAttribute("align", $align);
		}
		function valign($value)
		{
			$this->insertAttribute("valign", $value);
		}
		function width($width)
		{
			$this->insertAttribute("width", $width);			
		}
		function height($height)
		{
			$this->insertAttribute("height", $height);			
		}
		function bgcolor($color)
		{
			$this->insertAttribute("bgcolor", $color);
		}
		function style($value)
		{
			$this->insertAttribute("style", $value);
		}
		function cellspacing($value)
		{
			$this->insertAttribute("cellspacing", $value);
		}
		function cellpadding($value)
		{
			$this->insertAttribute("cellpadding", $value);
		}
}
?>