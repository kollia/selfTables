<?php

require_once($_stdbsqlcases);

class STDbUpdater extends STDbSqlCases
{
	/**
	 * updating content of columns
	 * for more than one row
	 * @var array columns
	 */
	var $columns= array();
	/**
	 * current row which can be filled
	 * with new content
	 * @var integer
	 */
	var $nAktRow= 0;
	
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
	            {showErrorTrace(1);echo "<br />";}
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
	    
        $where= null;
        if(isset($this->wheres[$nr]))
            $where= $this->wheres[$nr];
        $this->statement[$nr]= $this->getUpdateStatement($where, $this->columns[$nr]);
	    return $this->statement[$nr];
	}
	private function getUpdateStatement(string|STDbWhere $where= null, $values= null)
	{
	    
	    if(isset($where))
	    {
	        // alex 03/08/2005:	gib where-class in Tabelle
	        //$oTable= new STDbTable($table, $this);
	        //$bModify= $oTable->modify();
	        //$oTable->modifyForeignKey(false);
	        $this->table->andWhere($where);
	        //$oTable->modifyForeignKey($bModify);
	    }
	    $this->table->modifyQueryLimitation();
	    $oWhere= $this->table->getWhere();
	    STCheck::alert(!isset($oWhere), "STCheck::getUpdateStratement()", "no where usage for update exist");
	    //$whereAliases= $this->table->getWhereAliases();
	    $whereStatement= $this->table->getWhereStatement("where");//, $whereAliases);
	    if($whereStatement!="")
	    {
	        if(preg_match("/^(and|or)/i", $whereStatement, $ereg))
	        {
	            if($ereg[1] == "and")
	                $whereStatement= substr($where, 4);
                else
                   $whereStatement= substr($where, 3);
	        }
	        $whereStatement= " where $whereStatement";
	    }
	    
	    $update_string= "";
        $result= $this->make_sql_values($values);
        if(!count($result))
            return null;
        if(STCheck::isDebug("db.statement.update"))
        {
            $space= STCheck::echoDebug("db.statement.update", "update follow values inside database table <b>".$this->table->getName()."</b>");
            st_print_r($result,3, $space);
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
            $update_string.= $key."=".$this->add_quotes($types[$key], $value).",";
        }
        $update_string= substr($update_string, 0, strlen($update_string)-1);
        $sql="UPDATE ".$this->table->Name." set $update_string";
        $sql.= $whereStatement;
        
        STCheck::echoDebug("db.main.statement", $sql);
        return $sql;
	}
	public function execute($onError= onErrorStop)
	{
	  if(!count($this->columns))
		    return 0;
		$db= &$this->table->db;
		$this->nErrorRowNr= null;
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