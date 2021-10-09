<?php

class LinkTag extends Tag
{//<link rel="stylesheet" type="text/css" href="../../src/selfhtml.css">
		function __construct()
		{
			Tag::__construct("link", false);
		}
		function href($value)
		{
			$this->insertAttribute("href", $value);
		}
		function rel($value)
		{
			$this->insertAttribute("rel", $value);
		}
		function type($value)
		{
			$this->insertAttribute("type", $value);
		}
		function title($value)
		{
			$this->insertAttribute("title", $value);
		}
		function media($value)
		{
			$this->insertAttribute("media", $value);
		}
}

?>
