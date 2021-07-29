<?php

class HTag extends Tag
{
		function __construct($count, $class= null)
		{
			Tag::Tag("h".$count, true, $class);
		}
		function align($align)
		{
			$this->insertAttribute("align", $align);
		}
}

class H1Tag extends HTag
{
		function __construct($class= null)
		{
			HTag::HTag(1, $class);
		}
}

class H2Tag extends HTag
{
		function __construct($class= null)
		{
			HTag::HTag(2, $class);
		}
}

class H3Tag extends HTag
{
		function __construct($class= null)
		{
			HTag::HTag(3, $class);
		}
}

class H4Tag extends HTag
{
		function __construct($class= null)
		{
			HTag::HTag(4, $class);
		}
}

?>