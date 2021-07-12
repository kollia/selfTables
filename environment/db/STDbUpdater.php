<?php


class STDbUpdater
{
	var	$table;
	var $columns;
	var $nAktRow= 0;
	
	function STDbUpdater(&$oTable)
	{
	    Tag::paramCheck($oTable, 1, "STDbTable");
		$this->table= &$oTable;
	}
	function update($column, $value)
	{
		Tag::paramCheck($column, 1, "string");
		
		$this->columns[$this->nAktRow][$column]= $value;
	}
	function where($where)
	{
		$this->wheres[$this->nAktRow]= $where;
	}
	function fillNextRow()
	{
		++$this->nAktRow;
	}
	function execute($onError= onErrorStop)
	{
	  if(!count($this->columns))
		    return 0;
		$db= &$this->table->db;
		$this->nErrorRowNr= null;
		//st_print_r($this->columns,2);
		foreach($this->columns as $nr=>$row)
		{
			$statement= $db->getUpdateStatement($this->table, $this->wheres[$nr], $row);
			//echo $statement."<br />";
			$db->fetch($statement, $onError);
			if($db->errno())
			{
				$this->nErrorRowNr= $nr;
				break;
			}
		}
		if($this->nErrorRowNr!==null)
		{
			$newRows= array();
			$oldCount= count($this->columns);
			for($o= $this->nErrorRowNr; $o<$oldCount; $o++)
				$newRows[]= $this->columns[$o];
			$this->columns= $newRows;
		}
		//echo "error:".$db->errno()."<br />";
		return $db->errno();
	}
	function getErrorString()
	{
		$errorString= "";
		if($this->table->db->errno())
  			$errorString= "by row ".$this->nErrorRowNr.": ";
		$errorString.= $this->table->db->getError();
		return $errorString;
	}
}
?>