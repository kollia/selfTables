<?php

require_once $_stdbsqlwherecases;

class STDbDeleter extends STDbSqlWhereCases
{
	private $bModify= true;
	private $nErrno= 0;
	/**
	 * table names of foregn key tables
	 * which have entrys to own main table
	 * to delete
	 * @var string
	 */
	private $aFkLinkTables= "";
	private $aStatement= array();
	
	public function execute($onError= onDebugErrorShow)
	{
		$db= &$this->table->db;
		$this->nErrorRowNr= null;
		//$this->table->where(null);
		//$table= new STDbTable($this->table);
		$this->table->modifyQueryLimitation($this->bModify);
		$oWhere= $this->getWhereObject(0);
		$fkTables= $db->hasFkEntriesToTable($this->table->getName(), $oWhere);
		// reset where object, because it will be modified for more tables (aliasTables)
		$oWhere->reset();// inside hasFkEntriesToTable() question
		if($fkTables)
		{
		    $this->nErrno= "NODELETE_FK";
		    $this->aFkLinkTables= $fkTables;
		    return "NODELETE_FK";
		}
	    $statement= $this->getStatement();
	    $db->query($statement, $onError);
	    return $db->errno();
	}
	function getStatement()
	{
	    $nr= STCheck::increase("db.statement");
	    if(STCheck::isDebug())
	    {
	        if(STCheck::isDebug("db.statement"))
	        {
	            echo "<br /><br />";
	            echo "<hr color='black'/>";
	            $msg= "create $nr. statement for <b>delete</b> inside table ".$this->table;
	            STCheck::echoDebug("db.statement", $msg);
	            echo "<hr />";
	            //STCheck::info(1, "STDbTable::getStatement()", "called STDbTable::<b>getStatement()</b> method from:", 1);
	        }
	        if(STCheck::isDebug("db.statement.from"))
	        {showBackTrace(1);echo "<br />";}
	    }
	    if(isset($this->aStatement['full']))
	        return $this->aStatement['full'];
	    
	    $tableName= $this->table->getName();
	    $this->table->allowFkQueryLimitation(false);
	    $whereStatement= $this->getWhereStatement(0);
        $statement= "delete from ".$tableName;
        $this->aStatement['from']= $statement;
        $ereg= array();
        preg_match("/^(and|or)/i", $whereStatement, $ereg);
        if(count($ereg) != 0)
        {
            if(	isset($ereg[1]) &&
                $ereg[1] == "and"	)
            {
                $nOp= 4;
            }else
                $nOp= 3;
            $whereStatement= substr($whereStatement, $nOp);
        }
        $this->aStatement['where']= $whereStatement;
        $statement.= " $whereStatement";
        $this->aStatement['full']= $statement;
        return $statement;
	}
	public function getErrorId() : int|string
	{
	    if(is_string($this->nErrno))
	        return $this->nErrno;
	    return $this->table->db->errno();
	}
	public function getErrorString() : string
	{
		return $this->table->db->getError();
	}
	public function getFkLinkTables() : array
	{
	    return $this->aFkLinkTables;
	}
	function modifyForeignKey(bool $bModify= true)
	{
	    $this->bModify= $bModify;
		$this->table->allowQueryLimitation($bModify);
	}
}
?>