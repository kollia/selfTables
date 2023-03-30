<?php

define("TR", "tr");
define("TH", "th");
define("TD", "td");
define("LU", "lu");
define("LI", "li");

class ColumnTag extends Tag
{
        function __construct($headline= TD, $class= null)
		{
			Tag::__construct($headline, true, $class);
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
		function style($value)
		{
		    $this->insertAttribute("style", $value);
		}
		function height($height)
		{
			$this->insertAttribute("height", $height);			
		}
		function bgcolor($color)
		{
			$this->insertAttribute("bgcolor", $color);
		}
		function background($image)
		{
		    $this->insertAttribute("background", $image);
		}
		function nowrap()
		{
		    $this->insertAttribute("nowrap", "nowrap");
		}
		public function showLine(int $count= 1)
		{
		    Tag::showLine($count);
		}
}

class RowTag extends Tag
{
		function __construct($class= null, $type= TR)
		{
			Tag::__construct($type, true, $class);
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
		public function showLine(int $count= 1)
		{
		    $lines= stTools::getBackTrace(1, $count);
		    $str= "";
		    foreach ($lines as $line)
		        $str.= "$line<br />";
		    $deb_col= new ColumnTag();
		    $deb_col->add($str);
		    $this->add($deb_col);
		}
}

class TableTag extends Tag
{
		function __construct($class= null)
		{
			Tag::__construct("table", true, $class);
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
		public function showLine(int $count= 1)
		{
		    $lines= stTools::getBackTrace(1, $count);
		    $str= "";
		    foreach ($lines as $line)
		        $str.= "$line<br />";
		    $deb_row= new RowTag();
		    $deb_col= new ColumnTag();
		    $deb_col->add($str);
		    $deb_row->add($deb_col);
		    $this->add($deb_row);
		}
}
?>