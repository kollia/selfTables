<?php

class st_tableTag extends TableTag
{
	var	$nRow= -1;
	var	$nColumn= -1;
	var $rowType;
	var $columnType;

	function __construct($class= null, $type= TR)
	{
	    switch($type)
	    {
	        case TR:
	        case TD:
	            $this->rowType= TR;
	            $this->columnType= TD;
	            break;
	        case TH:
	            $this->rowType= TR;
	            $this->columnType= TH; // can also set explicit by add method,
	                                   //if not want to set only the hole first row as headline
	            break;
	        case LU:
	        case LI:
	            $this->rowType= LU;
	            $this->columnType= LI;
	            break;
	    }
	    TableTag::__construct("table", true, $class);
	}
	function add($value, $columnType= null, $columnClass= null, $rowClass= null)
	{
	    $this->addObj($value, $columnType, $columnClass, $rowClass);
	}
	function addObj(&$value, $columnType= null, $columnClass= null, $rowClass= null)
	{
	    if(!isset($columnType))
	        $columnType= $this->columnType;
		if($this->nRow===-1)
		{
		    $tr= new RowTag($rowClass, $this->rowType);
			     $td= new ColumnTag($columnClass, $columnType);
					$td->addObj($value);
				$tr->addObj($td);
			TableTag::addObj($tr);
			$this->nRow= 0;
			$this->nColumn= 0;
		}elseif($this->nColumn===-1)
		{
		    $tr= new RowTag($rowClass);
			    $td= new ColumnTag($columnClass, $columnType);
					$td->addObj($value);
				$tr->addObj($td);
			TableTag::addObj($tr);
			++$this->nRow;
			$this->nColumn= 0;
		}else
		{
		    $td= new ColumnTag($columnClass, $columnType);
				$td->add($value);
			$this->inherit[$this->nRow]->addObj($td);
			++$this->nColumn;
		}
	}
	function nextRow()
	{
		$this->nColumn= -1;
		if($this->columnType == TH)
		    $this->columnType= TD;
	}
    function colspan($num)
    {
		if(Tag::is_error($this->nColumn===-1, "st_tableTag::colspan()", "row is undifined, insert first an content"))
			return;
    	$this->inherit[$this->nRow]->inherit[$this->nColumn]->colspan($num);
    }
    function rowspan($num)
    {
		if(Tag::is_error($this->nColumn===-1, "st_tableTag::rowspan()", "row is undifined, insert first an content"))
			return;
    	$this->inherit[$this->nRow]->inherit[$this->nColumn]->rowspan($num);
    }
	function columnAlign($value)
	{
		if(Tag::is_error($this->nColumn===-1, "st_tableTag::rowspan()", "row is undifined, insert first an content"))
			return;
    	$this->inherit[$this->nRow]->inherit[$this->nColumn]->align($value);
	}
	function columnValign($value)
	{
		if(Tag::is_error($this->nColumn===-1, "st_tableTag::rowspan()", "row is undifined, insert first an content"))
			return;
    	$this->inherit[$this->nRow]->inherit[$this->nColumn]->valign($value);
	}
	function columnWidth($width)
	{
		if(Tag::is_error($this->nColumn===-1, "st_tableTag::rowspan()", "row is undifined, insert first an content"))
			return;
    	$this->inherit[$this->nRow]->inherit[$this->nColumn]->width($width);
	}
	function columnHeight($height)
	{
		if(Tag::is_error($this->nColumn===-1, "st_tableTag::rowspan()", "row is undifined, insert first an content"))
			return;
    	$this->inherit[$this->nRow]->inherit[$this->nColumn]->height($height);
	}
	function columnBgcolor($color)
	{
		if(Tag::is_error($this->nColumn===-1, "st_tableTag::rowspan()", "row is undifined, insert first an content"))
			return;
    	$this->inherit[$this->nRow]->inherit[$this->nColumn]->bgcolor($color);
	}
	function columnBackground($image)
	{
	    if(Tag::is_error($this->nColumn===-1, "st_tableTag::rowspan()", "row is undifined, insert first an content"))
	        return;
	    $this->inherit[$this->nRow]->inherit[$this->nColumn]->background($image);
	}
	function columnStyle($value)
	{
	    if(Tag::is_error($this->nColumn===-1, "st_tableTag::rowspan()", "row is undifined, insert first an content"))
	        return;
	    $this->inherit[$this->nRow]->inherit[$this->nColumn]->style($value);
	}
	function columnClass($name)
	{
	    if(Tag::is_error($this->nColumn===-1, "st_tableTag::rowspan()", "row is undifined, insert first an content"))
	        return;
	    $this->inherit[$this->nRow]->inherit[$this->nColumn]->class($name);
	}
	function columnNowrap()
	{
	    if(Tag::is_error($this->nColumn===-1, "st_tableTag::rowspan()", "row is undifined, insert first an content"))
	        return;
	    $this->inherit[$this->nRow]->inherit[$this->nColumn]->nowrap();
	}
}

?>