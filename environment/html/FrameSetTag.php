<?php

class FrameSetTag extends Tag
{		
		function __construct($class= null)
		{
			Tag::__construct("frameset", true, $class);
		}
		function frameborder($value)
		{
			if($value)
				$value= "1";
			else
				$value= "0";
			$this->insertAttribute("frameborder", $value);
		}
		function framespacing($value)
		{
		     $this->insertAttribute("framespacing", $value);
		}
}

class FrameTag extends Tag
{
    function __construct($class= null)
    {
        Tag::__construct("frame", false, $class);
    }
    function name($value)
    {
        $this->insertAttribute("name", $value);
    }
    function src($value)
    {
        $this->insertAttribute("src", $value);
    }
    function noresize()
    {
        $this->insertAttribute("noresize", "noresize");
    }
    function scrolling($value)
    {
        $this->insertAttribute("scrolling", $value);
    }
    function marginheight($value)
    {
        $this->insertAttribute("marginheight", $value);
    }
    function marginwidth($value)
    {
        $this->insertAttribute("marginwidth", $value);
    }
    function topmargin($value)
    {
        $this->insertAttribute("topmargin", $value);
    }
    function leftmargin($value)
    {
        $this->insertAttribute("leftmargin", $value);
    }
}

?>