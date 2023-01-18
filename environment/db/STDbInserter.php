<?php

require_once($_stusersession);

class STDbInserter
{
	var	$db;
	var	$table;
	var $columns;
	var $nAktRow= 0;
	var $sAccessClusterColumn;
	/**
	 * exist sql statement
	 * @var string array
	 */
	private $statements= array();

	/**
	 * Constructor
	 *
	 * @param STBaseTable $oTable object of Table
	 */
	function __construct(&$oTable)
	{
	    Tag::paramCheck($oTable, 1, "STDbTable");
		$this->table= &$oTable;
		$this->db= &$oTable->db;
	}
	function insertByPost()
	{
	}
	function fillColumn($column, $value)
	{
		Tag::paramCheck($column, 1, "string");

		if(preg_match("/^[ ]*['\"](.*)['\"][ ]*$/", $value, $preg))
			$value= $preg[1];
		$this->columns[$this->nAktRow][$column]= $value;
	}
	function fillNextRow()
	{
		++$this->nAktRow;
	}
	public function getStatement($nr= 0) : string
	{
	    if(!isset($this->statements[$nr]))
	    {
	        $this->createCluster($this->columns[$nr]);
	        $this->statement[$nr]= $this->table->db->getInsertStatement($this->table, $this->columns[$nr]);
	    }
	    return $this->statement[$nr];
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
    		//echo "$statement<br>";
			$db->query($statement, $onError);
			if($db->errno())
			{
				$this->nErrorRowNr= $nr;
				break;
			}else
			{
				$this->lastInsertID= $db->getLastInsertID();
				$this->updateCluster($columns);
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
	function getErrorString()
	{
		return "by row ".$this->nErrorRowNr." ".$this->table->db->getError();
	}
}
?>