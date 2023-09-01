<?php

require_once($_stdbsqlwherecases);

class STDbUpdater extends STDbSqlWhereCases
{	
	public function update(string $column, $value)
	{ $this->fillColumnContent($column, $value); }
	
	public function getStatement(int $nr= 0) : string
	{
	    $case= STCheck::increase("db.statement");
	    if(STCheck::isDebug())
	    {
	        if(STCheck::isDebug("db.statement"))
	        {
	            echo "<br /><br />";
	            echo "<hr color='black'/>";
 	            STCheck::echoDebug("db.statement", "create $case. statement for ".($nr+1).". <b>update</b> inside table ".$this->table->toString());
	            echo "<hr />";
	            //STCheck::info(1, "STDbTable::getStatement()", "called STDbTable::<b>getStatement()</b> method from:", 1);
	        }
	        if(STCheck::isDebug("db.statement.from"))
	            {showBackTrace(1);echo "<br />";}
	    }
	    if(isset($this->statements[$nr]))
	    {
	        if(STCheck::isDebug("db.statements"))
	        {
	            $arr[]= "use pre-defined ".($nr+1).". update statement in ".get_class($this)."[".$this->table."]:";
	            $arr[]= $this->statements[$nr];
	            STCheck::echoDebug("db.statements", $arr);
	        }
	        return $this->statements[$nr];
	    }
	    
        $this->statements[$nr]= $this->getUpdateStatement($nr);
	    return $this->statements[$nr];
	}
	private function getUpdateStatement(int $nr= 0) : string //string|STDbWhere $where= null, $values= null)
	{   
	    $update_string= "";
	    $result= $this->make_sql_values($this->columns[$nr]);
        if(!count($result))
            return null;
        if(STCheck::isDebug("db.statement.update"))
        {
            $space= STCheck::echoDebug("db.statement.update", "update follow values inside database table <b>".$this->table->getName()."</b>");
            st_print_r($result,3, $space);
        }
        $mainTableAlias= "";
        $tableAliases= $this->table->getWhereAliases();
        if(count($tableAliases) > 1)
        {
            $mainTableAlias= $tableAliases[$this->table->Name];
            $mainTableAlias.= ".";
        }
        $types= $this->read_inFields("type");
        foreach($result as $key => $value)
        {
            if(STCheck::isDebug("db.statement.update"))
            {
                $flags= $this->read_inFields("flags");
                STCheck::echoDebug("db.statement.update", "field <b>$key</b>:");
                STCheck::echoDebug("db.statement.update", "   from type '".$types[$key]."'");
                STCheck::echoDebug("db.statement.update", "   with flag '".$flags[$key]."'");
                STCheck::echoDebug("db.statement.update", "   and value '$value'");
                echo "<br />";
            }
            $update_string.= $mainTableAlias.$key."=".$this->add_quotes($types[$key], $value).",";
        }
        $update_string= substr($update_string, 0, strlen($update_string)-1);
        $tableStatement= $this->table->getTableStatement($tableAliases, array());
        // remove FROM word from beginning of statement
        $tableStatement= substr($tableStatement, 5);
        $whereStatement= $this->getWhereStatement($nr);
        $statement= "UPDATE $tableStatement set $update_string $whereStatement";
        STCheck::echoDebug("db.main.statement", $statement);
        STCheck::alert(!preg_match("/where/i", $statement), "STCheck::getUpdateStatement()", "no where usage for update exist");
        return $statement;
	}
	public function execute($onError= onErrorStop)
	{
	    if( empty($this->columns) &&
	        empty($this->statements)   )
	    {
	        return 0;
	    }
		$db= &$this->table->db;
		$this->nErrorRowNr= null;
		if(!empty($this->columns))
		{
    		foreach($this->columns as $nr=>$columns)
    		{
    		    $statement= $this->getStatement($nr);
    		    $db->query($statement, $onError);
    			if($db->errno())
    			{
    				$this->nErrorRowNr= $nr;
    				break;
    			}
    		}
		}else
		{
		    foreach($this->statements as $nr=>$sqlStatement)
		    {
		        $db->query($sqlStatement);
		        if($db->errno())
		        {
		            $this->nErrorRowNr= $nr;
		            break;
		        }
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
	public function getErrorId() : int
	{
	    return $this->table->db->errno();
	}
	public function getErrorString() : string
	{
		$errorString= "";
		if($this->table->db->errno())
  			$errorString= "by row ".$this->nErrorRowNr.": ";
		$errorString.= $this->table->db->getError();
		return $errorString;
	}
}
?>