<?php

class STDbDeleter
{
	var	$table;
	var $aWhere;
	var $bModify= true;
	var	$nErrorRowNr;
	
	// do not take by reference
	// because into table comming
	// where statements
	function __construct($oTable)
	{
	    Tag::paramCheck($oTable, 1, "STDbTable");
		$this->table= &$oTable;
	}
	function where($stwhere)
	{
		if(is_string($stwhere))
			$st_where= new STDbWhere($stwhere);
		$this->aWhere[]= &$st_where;
	}
	function execute($onError= onErrorStop)
	{
	  if(!count($this->aWhere))
		    return 0;
		$db= &$this->table->db;
		$this->nErrorRowNr= null;
		$this->table->where(null);
		$table= new STDbTable($this->table);
		$table->clearWhere();
		$db->foreignKeyModification($table);
		$modifiedWhere= $table->getWhere();
		foreach($this->aWhere as $nr=>$where)
		{		 	
			$table->clearWhere();
			if($this->bModify)
			{
				$table->where($modifiedWhere);
				$sTable= $db->isNoFkToTable($table, $where);
				if(!is_bool($sTable))
				{
					$this->error= "cannot delete this entry. foreign key from table ".$sTable." be set";
					$this->nErrorRowNr= $nr;
					break;
				}
				// clear again the where-clausel
				// because in isNoFkToTable sometimes
				// will be add the where, and some times not
				$table->clearWhere();
			}
			$table->where($modifiedWhere);
			$table->andWhere($where);
			//st_print_r($table->oWhere,20);
			$statement= $db->getDeleteStatement($table);
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
			if($this->error)
				return "FKERROR";
		}
		return $db->errno();
	}
	function getErrorString()
	{
		if($this->error)
			return "by row ".$this->nErrorRowNr.": ".$this->error;
		return "by row ".$this->nErrorRowNr.": ".$this->table->db->getError();
	}
	function modifyForeignKey($bModify)
	{
		$this->bModify= $bModify;
	}
}
?>