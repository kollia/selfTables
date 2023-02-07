<?php

class SelectTag extends Tag
{
		function __construct($tag= null, $class= null)
		{
			Tag::__construct("select", true, $class);
			$this->add($tag);
		}
		function size($value)
		{
			$this->insertAttribute("size", $value);
		}
		function onChange($value)
		{
			$this->insertAttribute("onChange", $value);
		}
		function name($vlaue)
		{
			$this->insertAttribute("name", $vlaue);
		}
		function disabled()
		{
			$this->insertAttribute("disabled", "disabled");
		}
		function tabindex($vlaue)
		{
			$this->insertAttribute("tabindex", $vlaue);
		}
		/**
		 *  create a option tags from an array
		 *
		 *  @param: array $array an array with two columns which the option content and value hold
		 *  @param: int|string $contentKey	the key name of column for the content of options
		 *  @param: int|string $valueKey the key name of column which value the choosed option should have by sending
		 *  @param: int|string $selectedValue the value from array which should preselected.<br />If no valueKey be set it can also be the content
		 *  @Autor: Alexander Kolli
		 */
		public function createOptionArray(array $array, int|string $contentKey, int|string $valueKey= null, int|string $selectedValue= null)
		{
		    foreach($array as $row)
		    {
		        $option= new OptionTag();
		        $option->add($row[$contentKey]);
		        if(isset($valueKey))
		            $option->value($row[$valueKey]);
		        //echo "'".$selectedValue."'=='".$row[$contentKey]."'<br>";
		        if(isset($selectedValue))
		        {
		            if(isset($valueKey))
		            {
		                if($selectedValue==$row[$valueKey])
        		            $option->selected();
		            }else
		            {
		                if($selectedValue==$row[$contentKey])
		                    $option->selected();
		            }
		        }
		        $this->add($option);
		    }
		}
}

class OptionTag extends Tag
{
		function __construct()
		{
			Tag::__construct("option", true);
		}
		function value($vlaue)
		{
			$this->insertAttribute("value", $vlaue);
		}
		function selected($set= true)
		{
			if($set)
				$this->insertAttribute("selected", "selected");
		}
}
?>