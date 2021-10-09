<?php

class HTag extends Tag
{
		function __construct($count, $class= null)
		{
			Tag::__construct("h".$count, true, $class);
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
			HTag::__construct(1, $class);
		}
}

class H2Tag extends HTag
{
		function __construct($class= null)
		{
			HTag::__construct(2, $class);
		}
}

class H3Tag extends HTag
{
		function __construct($class= null)
		{
			HTag::__construct(3, $class);
		}
}

class H4Tag extends HTag
{
		function __construct($class= null)
		{
			HTag::__construct(4, $class);
		}
}

?>