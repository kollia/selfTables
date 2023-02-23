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
	/**
	 * where statement for all rows
	 * @var array
	 */
	//var $wheres= array();
	/**
	 * exist sql statement
	 * @var string arraytableName
	 */
	private $statements= array();
	
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
	    if(!isset($this->statements[$nr]))
	    {
	        $where= null;
	        if(isset($this->wheres[$nr]))
	            $where= $this->wheres[$nr];
	        $this->statement[$nr]= $this->getUpdateStatement($where, $this->columns[$nr]);
	    }
	    return $this->statement[$nr];
	}
	private function getUpdateStatement($where= "", $values= null)
	{
	    Tag::paramCheck($where, 2, "STDbWhere", "string", "empty(string)", "null");
	    
	    $update_string= "";
        $result= $this->make_sql_values($values);
        if(!count($result))
            return null;
        if(STCheck::isDebug("db.statement.modify"))
        {
            $space= STCheck::echoDebug("db.statement.modify", "update follow values inside database table <b>$table</b>");
            st_print_r($result,3, $space);
        }
        $types= $this->read_inFields("type");
        foreach($result as $key => $value)
        {
            if(STCheck::isDebug("db.statement.modify"))
            {
                STCheck::echoDebug("db.statement.modify", "field <b>$key</b>:");
                STCheck::echoDebug("db.statement.modify", "   from type '".$types[$key]."'");
                STCheck::echoDebug("db.statement.modify", "   with flag '".$flags[$key]."'");
                STCheck::echoDebug("db.statement.modify", "   and value '$value'");
                echo "<br />";
            }
            $update_string.= $key."=".$this->add_quotes($types[$key], $value).",";
        }
        $update_string= substr($update_string, 0, strlen($update_string)-1);
        $sql="UPDATE ".$this->table->Name." set $update_string";
        
        if($where)
        {
            // alex 03/08/2005:	gib where-class in Tabelle
            //$oTable= new STDbTable($table, $this);
            //$bModify= $oTable->modify();
            //$oTable->modifyForeignKey(false);
            $this->table->andWhere($where);
            //$oTable->modifyForeignKey($bModify);
        }
        $this->table->modifyQueryLimitation();
        $where= $this->table->getWhereStatement("where");
        if($where!="")
        {
            if(preg_match("/^(and|or)/i", $where, $ereg))
            {
                if($ereg[1] == "and")
                    $where= substr($where, 4);
                else
                    $where= substr($where, 3);
            }
            $sql.= " where $where";
        }
        STCheck::echoDebug("db.main.statement", $sql);
        return $sql;
	}
	public function execute($onError= onErrorStop)
	{
	  if(!count($this->columns))
		    return 0;
		$db= &$this->table->db;
		$this->nErrorRowNr= null;
		//st_print_r($this->columns,2);
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