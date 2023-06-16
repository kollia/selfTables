<?php

require_once($_stdbsqlcases);
require_once($_stusersession);

class STDbInserter extends STDbSqlCases
{
	var	$db;
	var	$table;
	var $columns= array();
	var $sAccessClusterColumn;
	/**
	 * last inserted primary key 
	 * @var Integer|string
	 */
	private $lastInsertID= -1;
	/**
	 * all inserted primary keys
	 * @var Integer|string
	 */
	private $aInsertIDs= array();
	

	function fillColumn(string $column, $value)
	{
		STCheck::param($value, 1, "string", "empty(string)", "int", "float");
        $this->fillColumnContent($column, $value);
	}
	public function getStatement($nr= 0) : string
	{
	    $case= STCheck::increase("db.statement");
	    if(STCheck::isDebug())
	    {
	        if(STCheck::isDebug("db.statement"))
	        {
	            echo "<br /><br />";
	            echo "<hr color='black'/>";
	            STCheck::echoDebug("db.statement", "create $case. statement for ".($nr+1).". <b>insert</b> inside table ".$this->table->toString());
	            echo "<hr />";
	            //STCheck::info(1, "STDbTable::getStatement()", "called STDbTable::<b>getStatement()</b> method from:", 1);
	        }
	        if(STCheck::isDebug("db.statement.from"))
	            {showBackTrace(1);echo "<br />";}
	    }
	    if(!isset($this->statements[$nr]))
	    {
	        $this->createCluster($this->columns[$nr]);
	        $this->statements[$nr]= $this->getInsertStatement($nr);
	    }
	    return $this->statements[$nr];
	}
	function getInsertStatement(int $nr) //$table, $values= null)
	{
	    $key_string= "";
	    $value_string= "";
	    $result= $this->make_sql_values($this->columns[$nr]);
	    $types= $this->read_inFields("type");
	    $flags= $this->read_inFields("flags");
	    $table= $this->table->getName();
	        
        if(STCheck::isDebug("db.statement.insert"))
        {
            $space= STCheck::echoDebug("db.statement.insert", "insert follow values into database table <b>$table</b>");
            st_print_r($result,3, $space);
        }
        foreach($result as $key => $value)
        {
            if(STCheck::isDebug("db.statement.insert"))
            {
                STCheck::echoDebug("db.statement.insert", "field <b>$key</b>:");
                STCheck::echoDebug("db.statement.insert", "   from type '".$types[$key]."'");
                STCheck::echoDebug("db.statement.insert", "   with flag '".$flags[$key]."'");
                STCheck::echoDebug("db.statement.insert", "   and value '$value'");
                echo "<br />";
            }
            if(!preg_match("/auto_increment/i", $flags[$key]))
            {
                $key_string.= "$key,";
                $value_string.= $this->add_quotes($types[$key], $value).",";
            }
        }
        $key_string= substr($key_string, 0, strlen($key_string)-1);
        $value_string= substr($value_string, 0, strlen($value_string)-1);
        $sql="INSERT INTO $table($key_string) VALUES($value_string)";
        return $sql;
	}
	public function execute($onError= onDebugErrorShow)
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
        		//showLine();
        		//echo "$statement<br>";
    			$db->query($statement, $onError);
    			if($db->errno())
    			{
    				$this->nErrorRowNr= $nr;
    				break;
    			}else
    			{
    				$this->lastInsertID= $db->getLastInsertID();
    				$this->aInsertIDs[]= $this->lastInsertID;
    				$this->updateCluster($columns);
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
		        }else
		        {
		            $this->lastInsertID= $db->getLastInsertID();
		            $this->aInsertIDs[]= $this->lastInsertID;
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
		return $db->errno();
	}
	function getLastInsertID()
	{
		return $this->lastInsertID;
	}
	function getInsertedIDs()
	{ return $this->aInsertIDs; }
	function createCluster(&$row)
	{
		// if it is generate an STUserManagementSession
		// and in the STBaseTable are be set columns
		// to create cluster for spezific actions
		// prodjuce this
		$error= "NOERROR";
		if(	 global_sessionGenerated()
			and
			count($this->table->sAcessClusterColumn)	)
		{
			$session= STSession::instance();
			if(typeof($session, "STUserSession"))
			{
                $identification= "";
            	foreach($this->table->identification as $identifColumn)
                {
            	   	$identif= $row[$identifColumn["column"]];
                    $identification.= $identif." - ";
                }
                if($identification)
                   	$identification= substr($identification, 0, strlen($identification)-3);
    			else
    				STCheck::is_warning(1, "STDbInserter::createCluster()", "no identif columns in table ".$this->table->getName()." are defined");
    			$this->sAccessClusterColumn= array();
    			$pkName= $this->table->getPkColumnName();
    			$tableName= $this->table->getDisplayName();
    			$error= "";
    			foreach($this->table->sAcessClusterColumn as $column)
    			{
    			    echo __file__.__LINE__."<br>";
    			    st_print_r($column,3);
    			    st_print_r($row);
    				if(!isset($row[$column["column"]]))
    				{
    					if($column["cluster"]!==$pkName)
    					{
    						$infoString= preg_replace("/@/", $identification, $column["info"]);
    						STCheck::alert(!isset($row[$column["cluster"]]), "STDbInserter::createCluster()", "column ".$column["cluster"].
    																						" not defined in result for dinamic cluster");
    						$row[$column["column"]]= $column["parent"]."_".$row[$column["cluster"]];
    						$cluster= $row[$column["cluster"]];
    						echo __file__.__LINE__."<br>";
         					$result= $session->createAccessCluster(	$column["parent"],
         															$cluster,
         															$infoString,
      																$tableName,
         					    $column["group"]	);
         					echo __file__.__LINE__."<br>";
    						if($error==="")
    							$error= $result;
    						elseif(	$result!=="NOERROR"
    								and
    								$error==="NOERROR"	)
    						{
    							$error= "NOTALLCLUSTERCREATE";
    						}
    					}else
    					{
    						$row[$column["column"]]= session_id();
    					}
    					$key= count($this->sAccessClusterColumn);
    					$this->sAccessClusterColumn[$key]= $column;
    				}
    			}
			}
		}
	}
	function updateCluster($row)
	{
		if( is_array($this->sAccessClusterColumn) &&
		    count($this->sAccessClusterColumn)        )
		{
			$this->lastInsertID= $this->getLastInsertID();

    		$_instance= &STUserSession::instance();
            $identification= "";
        	foreach($this->table->identification as $identifColumn)
            {
        	   	$identif= $row[$identifColumn["column"]];
                $identification.= $identif." - ";
            }
            if($identification)
               	$identification= substr($identification, 0, strlen($identification)-3);
			else
				STCheck::is_warning(1, "STDbInserter::createCluster()", "no identif columns in table ".$this->table->getName()." are defined");

    		/*	$pkValue= $post[$this->table->getPkColumnName()];
    			if(!$pkValue)
    			{
    			    $table= $this->table;
  					$table->clearSelects();
					$table->clearGetColumns();
  					$table->select($table->getPkColumnName());
  					$statement= $this->db->getStatement($table);
					echo $statement;exit;
  					$pkValue= $this->db->fetch_single($statement);
    			}exit;*/
			$tableName= $this->table->getDisplayName();
			$pkName= $this->table->getPkColumnName();
			$pk= $this->db->getLastInsertID();
			$updater= new STDbUpdater($this->table);
			$updater->where($pkName."=".$pk);
			$doUpdate= false;
			foreach($this->sAccessClusterColumn as $aColumnCluster)
   			{
   				if($aColumnCluster["cluster"]===$pkName)
   				{
					$infoString= preg_replace("/@/", $identification, $aColumnCluster["info"]);
					if($aColumnCluster["cluster"]===$pkName)
					{
						$cluster= "$pk";
						$updater->update($aColumnCluster["column"], $aColumnCluster["parent"]."_".$pk);
						$doUpdate= true;
					}else
						$cluster= $row[$aColumnCluster["cluster"]];
   					$result= $_instance->createAccessCluster(	$aColumnCluster["parent"],
   																$cluster,
   																$infoString,
																$tableName,
																$aColumnCluster["group"]	);
					$cluster= $aColumnCluster["parent"]."_".$cluster;
				}else
				{
					$result= "NOERROR";
					$cluster= $row[$aColumnCluster["column"]];
				}
				if($result!=="NOCLUSTERCREATE")
					$_instance->addDynamicCluster($this->table, $aColumnCluster["action"], $pk, $cluster);
			}
			if($doUpdate)
				$updater->execute();
		}
	}
	public function getErrorString() : string
	{
	    $msg= "";
	    if(count($this->columns) > 1)
		    $msg.= "by row ".$this->nErrorRowNr." ";
	    $msg.= $this->table->db->getError();
	    return $msg;
	}
	public function getErrorId() : int
	{
	    return $this->db->errno();
	}
}
?>