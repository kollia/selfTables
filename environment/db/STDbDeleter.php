<?php

class STDbDeleter
{
	private	$table;
	private $oWhere= null;
	private $bModify= true;
	private $nErrno= 0;
	/**
	 * table names of foregn key tables
	 * which have entrys to own main table
	 * to delete
	 * @var string
	 */
	private $aFkLinkTables= "";
	
	// do not take by reference
	// because into table comming
	// where statements
	public function __construct(object $oTable)
	{
	    Tag::paramCheck($oTable, 1, "STDbTable");
		$this->table= &$oTable;
	}
	/**
	 * make for every where 
	 * @param unknown $stwhere
	 */
	public function where($stwhere)
	{
	    STCheck::param($stwhere, 0, "STDbWhere", "string");
	    
		if(is_string($stwhere))
			$stwhere= new STDbWhere($stwhere);
		$this->oWhere= &$stwhere;
	}
	public function orWhere($stwhere)
	{
	    STCheck::param($stwhere, 0, "STDbWhere", "string");
	    
	    if(isset($this->oWhere))
	        $this->oWhere->orWhere($stwhere);
	    else
	        $this->where($stwhere);
	}
	public function andWhere($stwhere)
	{
	    STCheck::param($stwhere, 0, "STDbWhere", "string");
	    
	    if(isset($this->oWhere))
	        $this->oWhere->andWhere($stwhere);
        else
            $this->where($stwhere);
	}
	function execute($onError= onDebugErrorShow)
	{
		$db= &$this->table->db;
		$this->nErrorRowNr= null;
		$this->table->where(null);
		$table= new STDbTable($this->table);
		$table->modifyQueryLimitation($this->bModify);
		$fkTables= $db->hasFkEntriesToTable($table->getName(), $this->oWhere);
		if($fkTables)
		{
		    $this->nErrno= "NODELETE_FK";
		    $this->aFkLinkTables= $fkTables;
		    return "NODELETE_FK";
		}
		if(isset($this->oWhere))
		    $table->andWhere($this->oWhere);
	    $statement= $this->getDeleteStatement($table);
	    $db->query($statement, $onError);
	    return $db->errno();
	}
	function getDeleteStatement($table)
	{
	    $nr= STCheck::increase("db.statement");
	    if(STCheck::isDebug())
	    {
	        if(STCheck::isDebug("db.statement"))
	        {
	            echo "<br /><br />";
	            echo "<hr color='black'/>";
	            $msg= "create $nr. statement for <b>delete</b> inside table ".$table;
	            STCheck::echoDebug("db.statement", $msg);
	            echo "<hr />";
	            //STCheck::info(1, "STDbTable::getStatement()", "called STDbTable::<b>getStatement()</b> method from:", 1);
	        }
	        if(STCheck::isDebug("db.statement.from"))
	        {showErrorTrace(1);echo "<br />";}
	    }
        if(is_string($table))
        {
            $tableName= $table;
            $container= &$this->getContainer();
            $table= $container->getTable($table);
        }else
            $tableName= $table->getName();
        //$whereStatement= $this->getWhereStatement($table, "");
        $table->allowFkQueryLimitation(false);
        $whereStatement= $table->getWhereStatement("where");
        $statement= "delete from ".$tableName;
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
        $statement.= " where $whereStatement";
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
		$this->table->modifyForeignKey($bModify);
	}
}
?>