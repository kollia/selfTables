<?php

class HTag extends Tag
{
		function HTag($count, $class= null)
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
		function H1Tag($class= null)
		{
			HTag::HTag(1, $class);
		}
}

class H2Tag extends HTag
{
		function H2Tag($class= null)
		{
			HTag::HTag(2, $class);
		}
}

class H3Tag extends HTag
{
		function H3Tag($class= null)
		{
			HTag::HTag(3, $class);
		}
}

class H4Tag extends HTag
{
		function H4Tag($class= null)
		{
			HTag::HTag(4, $class);
		}
}

?>