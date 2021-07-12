<?php

class st_tableTag extends TableTag
{
	var	$nRow= -1;
	var	$nColumn= -1;

	function st_tableTag($id= null)
	{
		TableTag::TableTag("table", true, $id);
	}
	function add($value, $type= TD, $columnId= null, $rowId= null)
	{
		$this->addObj($value, $type, $columnId, $rowId);
	}
	function addObj($value, $type= TD, $columnId= null, $rowId= null)
	{
		if($this->nRow===-1)
		{
			$tr= &new RowTag($rowId);
				$td= &new ColumnTag($type, $columnId);
					$td->addObj($value);
				$tr->addObj($td);
			TableTag::addObj($tr);
			$this->nRow= 0;
			$this->nColumn= 0;
		}elseif($this->nColumn===-1)
		{
			$tr= &new RowTag($rowId);
				$td= &new ColumnTag($type, $columnId);
					$td->addObj($value);
				$tr->addObj($td);
			TableTag::addObj($tr);
			++$this->nRow;
			$this->nColumn= 0;
		}else
		{
			$td= &new ColumnTag($type, $columnId);
				$td->add($value);
			$this->inherit[$this->nRow]->addObj($td);
			++$this->nColumn;
		}
	}
	function nextRow()
	{
		$this->nColumn= -1;
	}
    function colspan($num)
    {
		if(Tag::error($this->nColumn===-1, "st_tableTag::colspan()", "row is undifined, insert first an content"))
			return;
    	$this->inherit[$this->nRow]->inherit[$this->nColumn]->colspan($num);
    }
    function rowspan($num)
    {
		if(Tag::error($this->nColumn===-1, "st_tableTag::rowspan()", "row is undifined, insert first an content"))
			return;
    	$this->inherit[$this->nRow]->inherit[$this->nColumn]->rowspan($num);
    }
	function columnAlign($value)
	{
		if(Tag::error($this->nColumn===-1, "st_tableTag::rowspan()", "row is undifined, insert first an content"))
			return;
    	$this->inherit[$this->nRow]->inherit[$this->nColumn]->align($value);
	}
	function columnValign($value)
	{
		if(Tag::error($this->nColumn===-1, "st_tableTag::rowspan()", "row is undifined, insert first an content"))
			return;
    	$this->inherit[$this->nRow]->inherit[$this->nColumn]->valign($value);
	}
	function columnWidth($width)
	{
		if(Tag::error($this->nColumn===-1, "st_tableTag::rowspan()", "row is undifined, insert first an content"))
			return;
    	$this->inherit[$this->nRow]->inherit[$this->nColumn]->width($width);
	}
	function columnHeight($height)
	{
		if(Tag::error($this->nColumn===-1, "st_tableTag::rowspan()", "row is undifined, insert first an content"))
			return;
    	$this->inherit[$this->nRow]->inherit[$this->nColumn]->height($height);
	}
	function columnBgcolor($color)
	{
		if(Tag::error($this->nColumn===-1, "st_tableTag::rowspan()", "row is undifined, insert first an content"))
			return;
    	$this->inherit[$this->nRow]->inherit[$this->nColumn]->bgcolor($color);
	}
}

?>