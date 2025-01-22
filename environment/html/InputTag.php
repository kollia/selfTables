<?php

class InputTag extends Tag
{
		public function __construct($class= null)
		{
			Tag::__construct("input", false, $class);
		}
		public function name($vlaue)
		{
			$this->insertAttribute("name", $vlaue);
		}
		public function type($vlaue)
		{
			$this->insertAttribute("type", $vlaue);
		}
		public function min($vlaue)
		{
			$this->insertAttribute("min", $vlaue);
		}
		public function max($vlaue)
		{
			$this->insertAttribute("max", $vlaue);
		}
		public function step($vlaue)
		{
			$this->insertAttribute("step", $vlaue);
		}
		public function value($vlaue)
		{
			$this->insertAttribute("value", $vlaue);
		}
		public function oninput($vlaue)
		{
			$this->insertAttribute("oninput", $vlaue);
		}
		public function size($vlaue)
		{
			$this->insertAttribute("size", $vlaue);
		}
		public function maxlen($vlaue)
		{
			$this->insertAttribute("maxlen", $vlaue);
		}
		public function onChange($vlaue)
		{
			$this->insertAttribute("onChange", $vlaue);
		}
		public function onClick($vlaue)
		{
			$this->insertAttribute("onClick", $vlaue);
		}
		public function onFocus($vlaue)
		{
			$this->insertAttribute("onFocus", $vlaue);
		}
		public function accept($vlaue)
		{
			$this->insertAttribute("accept", $vlaue);
		}
		public function tabindex($vlaue)
		{
			$this->insertAttribute("tabindex", $vlaue);
		}
		public function autofocus($vlaue= "")
		{
		    $this->insertAttribute("autofocus", $vlaue);
		}
		public function autocomplete($vlaue= "on")
		{
			$this->insertAttribute("autocomplete", $vlaue);
		}
		public function checked($checked= true)
		{
			if($checked)
				$this->insertAttribute("checked", "checked");
		}
		public function disabled()
		{
			$this->insertAttribute("disabled", "disabled");
		}
}

?>