<?php

class STDbTableCreator
{
    var $db;
	var $sTable;
	var $bCheck= false;
	var $asTableColumns= array();

    function __construct(&$database, $tableName)
	{
		Tag::paramCheck($database, 1, "STDatabase");
		Tag::paramCheck($tableName, 2, "string");

        $this->db= &$database;
    	$this->sTable= $tableName;
    }
    function column(string $column, string $type, $null= true, $pk= false, $fkToTable= null, $toColumn= null)
    {
        $this->asTableColumns[$column]= array(	"type"=>	$type,
    											"null"=>	$null,
    											"pk"=>		$pk		);
		if(isset($fkToTable))
			$this->foreignKey($column, $fkToTable, $toColumn);
    }
	public function default(string $column, $default)
	{
		$this->asTableColumns[$column]["default"]= $default;	
	}
	/*public*/function notNull($column)
	{
		$this->asTableColumns[$column]["null"]= false;
	}
	/*public*/function primaryKey($column, $pk= true)
	{
		$this->asTableColumns[$column]["pk"]= $pk;
	}
	/*public*/function foreignKey($column, $toTable, $identif= 1, $toColumn= null, $type= "RESTRICT")
	{
		STCheck::paramCheck($column, 1, "string");
		STCheck::paramCheck($toTable, 2, "string");
		STCheck::paramCheck($identif, 3, "int");
		STCheck::paramCheck($toColumn, 4, "string", "null");
		STCheck::paramCheck($type, 5, "check", $type==="CASCADE" || $type==="SET NULL" || $type==="NO ACTION" || $type==="RESTRICT",
											"CASCADE", "SET NULL", "NO ACTION", "RESTRICT");

		$describe= STDbTableDescriptions::instance($this->db->getDatabaseName());
		$toTable= $describe->getTableName($toTable);
		$this->asTableColumns[$column]["fk"]["table"]= $toTable;
		$this->asTableColumns[$column]["fk"]["identif"]= $identif;
		$this->asTableColumns[$column]["fk"]["type"]= $type;
		if($toColumn===null)
		{
			$toColumn= $describe->getPkColumnName($toTable);
			if($toColumn===null)
			{echo __file__.__line__."<br />";
			 echo "table not in TableDescriptions<br />";
				$table= &$this->db->getTable($toTable);
				if($table)
					$toColumn= $table->getPkColumnName();
				else
					STCheck::is_warning(1, "STDbTableCreator::foreignKey()", "table $table no exist in TableDescriptions and database");

			}
		}
		$this->asTableColumns[$column]["fk"]["column"]= $toColumn;
	}
	/*public*/function uniqueKey($column, $unique= "1", $length= null)
	{
		$this->asTableColumns[$column]["udx"]["name"]= $unique;
		if($length!==null)
			$this->asTableColumns[$column]["udx"]["length"]= $length;
	}
	/*public*/function indexKey($column, $index= 1, $length= null)
	{
		$this->asTableColumns[$column]["idx"]["name"]= $index;
		if($length!==null)
			$this->asTableColumns[$column]["idx"]["length"]= $length;
	}
	/*public*/function autoIncrement($column)
	{
		$this->asTableColumns[$column]["auto_increment"]= true;
	}
    function check()
    {
        $this->bCheck= true;
    }
    function execute()
	{
        if($this->bCheck)
  		{ 	
			if($this->db->isTable($this->sTable))
			{
				$oTable= &$this->db->getTable($this->sTable);
				$fields= $oTable->columns;
			}else
  		    	$fields= $this->db->list_fields($this->sTable, noErrorShow);
    		if($fields)
    		{
    		    $nFields= array();
    		    foreach($fields as $column)
  				{
  				    $nFields[$column["name"]]= $column;
  				}
  				$add= false;
  				foreach($this->asTableColumns as $column=>$content)
  				{
  				    if(!isset($nFields[$column]))
					{
    				    $this->add($column);
    					$add= true;
    				}
  				}
  				if($add)
  				    return "ADDCOLUMNS";
  				return "DONOTHING";
    		}

  		}
  		return $this->create();
    }
		function add($column)
		{
			$this->db->setInTableNewColumn($this->sTable, $column, $this->asTableColumns[$column]["type"]);
			//if($this->sTable==="STPartition")
			//	st_print_r($this->db->tables["STPartition"]->columns);
		    $statement= "alter table ".$this->sTable." add ".$column;
			$statement.= " ".$this->asTableColumns[$column]["type"];
			if($this->asTableColumns[$column]["pk"])
			{
			    $statement.= " PRIMARY KEY";
				$this->db->setInTableColumnNewFlags($this->sTable, $column, "PRIMARY KEY");
			}
			if(!$this->asTableColumns[$column]["null"])
			{
			    $statement.= " NOT NULL";
				$this->db->setInTableColumnNewFlags($this->sTable, $column, "NOT NULL");
			}
			if(isset($this->asTableColumns[$column]["auto_increment"]))
			{
			    $statement.= " auto_increment";
				$this->db->setInTableColumnNewFlags($this->sTable, $column, "auto_increment");
			}
			if(isset($this->asTableColumns[$column]["default"]))
			{
				$statement.= " DEFAULT ";
				if($this->asTableColumns[$column]["default"] === null)
					$statement.= "null";
				else
					$statement.= "'{$this->asTableColumns[$column]["default"]}'";
			}
			$this->db->query($statement);
		}
		function create()
		{
		    $statement= "create table ".$this->sTable."(";
			if(STCheck::isDebug("tableCreator"))
				$statement.= "\n                               ";
			$keys= array();
    		foreach($this->asTableColumns as $column=>$content)
    		{
    		    $statement.= $column." ".$content["type"];
  				if(!$content["null"])
  		        	$statement.= " NOT NULL";
				if(isset($content['auto_increment']))
					$statement.= " auto_increment";
				if(isset($content["pk"]) && $content["pk"] == true)
					$keys["pk"][]= $column;
				if(isset($content["fk"]))
				{
					$keys["fk"][$content["fk"]["identif"]][]= array(	"ownColumn"=>	$column,
																		"otherColumn"=>	$content["fk"]["column"]	);
					$keys["fk"][$content["fk"]["identif"]]["toTable"]= $content["fk"]["table"];
					$keys["fk"][$content["fk"]["identif"]]["type"]= $content["fk"]["type"];
				}
				if(isset($content["idx"]))
				{
				    $aIdx= array( "column"=>$column );
				    if(isset($content["idx"]["length"]))
				        $aIdx['legth']= $content["idx"]["length"];
					$keys["idx"][$content["idx"]["name"]][]= $aIdx;
				}
				if(isset($content["udx"]))
				{
				    $aUdx= array( "column"=>$column );
				    if(isset($content["udx"]["length"]))
				        $aIdx['legth']= $content["udx"]["length"];
					$keys["udx"][$content["udx"]["name"]][]= $aUdx;
				}
				if(isset($content["default"]))
				{
					$statement.= " DEFAULT ";
					if($content["default"] === null)
						$statement.= "null";
					else
						$statement.= "'{$content["default"]}'";
				}
  				$statement.= ",";
				if(STCheck::isDebug("tableCreator"))
					$statement.= "\n                               ";
    		}
			//st_print_r($keys,6);
			foreach($keys as $name=>$key)
			{
				if($name==="pk")
				{
					$statement.= "PRIMARY KEY (";
					foreach($key as $column)
						$statement.= $column.",";
					$statement= substr($statement, 0, strlen($statement)-1);
					$statement.= "),";
					if(STCheck::isDebug("tableCreator"))
						$statement.= "\n                               ";
				}elseif($name==="fk")
				{
					if($this->db->saveForeignKeys())
					{
						foreach($key as $content)
						{
							$statement.= "FOREIGN KEY (";
							foreach($content as $nr=>$to)
							{
								if(is_numeric($nr))
									$statement.= $to["ownColumn"].",";
							}
							$statement= substr($statement, 0, strlen($statement)-1);
							$statement.= ") REFERENCES ".$content["toTable"]."(";
							foreach($content as $nr=>$to)
							{
								if(is_numeric($nr))
									$statement.= $to["otherColumn"].",";
							}
							$statement= substr($statement, 0, strlen($statement)-1);
							$statement.= "),";
							if(STCheck::isDebug("tableCreator"))
								$statement.= "\n                               ";
						}
					}
				}else
				{
					if($name==="udx")
						$label= "UNIQUE KEY udx_";
					else
						$label= "INDEX idx_";
					foreach($key as $identif=>$columns)
					{
						$columnString= "(";
						$statement.= $label.$identif."_";
						foreach($columns as $column)
						{
							$statement.= $column["column"]."_";
							$columnString.= $column["column"];
							if(isset($column["length"]))
								$columnString.= "(".$column["length"].")";
							$columnString.= ",";
						}
						$statement= substr($statement, 0, strlen($statement)-1);
						$columnString= substr($columnString, 0, strlen($columnString)-1);
						$statement.= " ".$columnString."),";
						if(STCheck::isDebug("tableCreator"))
							$statement.= "\n                               ";
					}
				}
			}
			if(STCheck::isDebug("tableCreator"))
				$nMinus= 33;
			else
				$nMinus= 1;
    		$statement= substr($statement, 0, strlen($statement)-$nMinus);
    		$statement.= ")";
			if($this->db->saveForeignKeys())
				$statement.= " ENGINE=InnoDb";
			if(!STCheck::isDebug("db.statement"))
				STCheck::echoDebug("tableCreator", $statement);
    		if($this->db->query($statement))
			{
				$this->db->asExistTableNames[]= $this->sTable;
				return "NOERROR";
			}
			return "SQLERROR";

			/*if(count($extern))
			{
    			foreach($extern as $name=>$key)
    			{
					$statement= "create ";
    				if($name==="udx")
    					$statement.= "UNIQUE INDEX udx_";
    				else
    					$statement.= "INDEX idx_";
    				foreach($key as $identif=>$columns)
    				{
    					$columnString= "(";
    					$statement.= $identif."_";
    					foreach($columns as $column)
    					{
    						$statement.= $column."-";
    						$columnString.= $column.",";
    					}
    					$statement= substr($statement, 0, strlen($statement)-1);
    					$columnString= substr($columnString, 0, strlen($columnString)-1);
    					$statement.= " on ".$this->sTable.$columnString.")";
    					if(STCheck::isDebug("tableCreator"))
    						$statement.= "\n                               ";
    				}
					STCheck::echoDebug("tableCreator", $statement);
	    			$this->db->fetch($statement);
    			}
			}
    		return "NOERROR";*/
		}
}

?>