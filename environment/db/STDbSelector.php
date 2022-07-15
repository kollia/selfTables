<?php

require_once($_stdbtable);
require_once($_stdbwhere);

class STDbSelector extends STDbTable
{
		var $selector= array();
		//var $oMainTable= null;
		var $aoToTables= array();
		var	$aNewSelects= array();
		var $columns= array();
		var $SqlResult= null;
		var $onError;
		var $search= array();
		var $fetchArrayCount= 0;
		var $count= 0;
		var	$dbCount;
		var	$defaultTyp;
		var $sqlStatement= "";
		var	$bClearSelects= false;	// for later getting table
									// by true it should also cleared

		// DbSelector spezifisch
		var $bAddedTabels= false;
		var $bAddedFkTables= false;
		var $aClearIdentifColumns= array();
		var	$bClearedByFirstSelect= false; // die selects in ->show werden gel�scht wenn ein anderer Select gew�nscht wird


		function __construct(&$oTable, $defaultTyp= MYSQL_NUM, $onError= onErrorStop)
		{
			STCheck::paramCheck($oTable, 1, "STAliasTable");
			STCheck::paramCheck($defaultTyp, 2, "check", $defaultTyp==STSQL_NUM || $defaultTyp==STSQL_ASSOC || $defaultTyp==STSQL_BOTH,
														"STSQL_NUM, STSQL_ASSOC or STSQL_BOTH");
			STCheck::paramCheck($onError, 3, "check", $onError==noErrorShow || $onError==onErrorShow || $onError==onErrorStop,
														"noErrorShow", "onErrorShow", "onErrorStop");

			$this->defaultTyp= $defaultTyp;
			$this->onError= $onError;
			$db= &$oTable->getDatabase();
			//$this->oMainTable= &$oTable;
			$this->aoToTables[$oTable->getName()]= &$oTable;
			STCheck::echoDebug("table", "copy ".get_class($oTable)."::".$oTable->getName()." into ".get_class($this)." from ID:".$oTable->ID);
			STDbTable::__construct($oTable);
			STCheck::echoDebug("table", "copy ".get_class($oTable)."::".$oTable->getName()." into ".get_class($this)." to ID:".$this->ID);
		}
		/*function getName()
		{
			if(count($this->table)==0)
				return "";
			return $this->table[0]["table"]->getName();
		}*/
		function add($table)
		{
			Tag::paramCheck($table, 1, "string", "STDbTable");
			Tag::alert($this->bAddedFkTables, "OSTDbSelector::add()", "cannot add an new table, if bevore made ::select to an other");

			if(is_string($table))
			{
				$sTableName= $table;
				$table= $this->db->getTable($sTableName);
				//$table= new STDbTable($table, $this->db, $this->onError);
			}else
				$sTableName= $table->getName();
			if(!typeof($table, "OSTDbSelector"))
				$table= new STDbSelector($table);
			$this->aoToTables[$sTableName]= &$table;
			$this->bAddedTabels= true;
			//$this->FK[$table->getName()]= array("own"=>$ownColumn, "other"=>$otherColumn, "join"=>$join);
		}
		/*protected*/function addFKTables($dbName, &$aDone, $sFromTableName)
		{
			// if sFromTableName in array aDone,
			// do not again
			if(isset($aDone[$sFromTableName]))
				return;
			$aDone[$sFromTableName]= true;
			if($this->Name==$sFromTableName)
			{
				$oTable= &$this;
				$aktDb= &$this->db;
			}else
			{
				if($dbName==$this->db->dbName)
					$aktDb= &$this->db;
				else
					$aktDb= &STBaseContainer::getContainer($dbName);
				$oTable= &$this->aoToTables[$sFromTableName];
				if(!$oTable)
				{
					$oTable= $this->container->getTable($sFromTableName);
					if(!typeof($oTable, "OSTDbSelector"))
						$oTable= new OSTDbSelector($oTable);
					if($this->bClearSelects)
						$oTable->clearIdentifColumns();
					$this->aoToTables[$sFromTableName]= &$oTable;
				}
			}

			$fks= $this->getForeignKeys();
			foreach($oTable->FK as $tableName=>$column)
			{
				$container= null;
				$db= null;
				if(isset($column["table"]))
				{
					$db= $column["table"]->getDatabase();
					$dbName= $db->getDatabaseName();
					if($dbName!==$this->db->getDatabaseName())
						$container= $column["table"]->container;
				}
				if(!$container)
					$container= $this->container;
				if(!$db)
					$db= $this->container->getDatabase();
				$fkTable= $container->getTable($tableName);
				if($fkTable)
				{
					if(!typeof($fkTable, "STDbSelector"))
						$fkTable= new STDbSelector($fkTable);
					$this->aoToTables[$tableName]= $fkTable;
					$this->addFKTables($db->dbName, $aDone, $tableName);
				}
			}
		}
		function clearTableList()
		{
			$this->aoToTables= array();
			$this->bAddedFKTables= true;
		}
		function clearSelects()
		{
			$this->bClearSelects= true;
			STDbTable::clearSelects();
			foreach($this->aoToTables as $name=>$table)
			{
				if($table)
					$this->aoToTables[$name]->clearIdentifColumns();
			}
		}
		function isSelect($tableName, $columnName= null, $aliasName= null)
		{
		 		Tag::paramCheck($tableName, 1, "string");
				Tag::paramCheck($columnName, 2, "string", "null");
				Tag::paramCheck($aliasName, 3, "string", "null");

				$tableName= $this->container->getTableName($tableName);
				if(!$this->db->isTable($tableName)) // tableName is columnName and columnName is aliasName
						return STDbTable::isSelect($tableName, $columnName);
		 		if($tableName==$this->Name)
						return STDbTable::isSelect($columnName, $aliasName);
				$oTable= &$this->getTable($tableName);
				if(!$oTable)
						return false;
				return $oTable->isIdentifColumn($columnName, $aliasName);
		}
	function &getFkTable($fromColumn, $bIsColumn= false)
	{
		STCheck::param($fromColumn, 0, "string");
		STCheck::param($bIsColumn, 1, "boolean");

		if(!$bIsColumn)
		{
			$field= $this->findAliasOrColumn($fromColumn);
			$fromColumn= $field["column"];
		}
		foreach($this->aFks as $table=>$content)
		{
			foreach($content as $columns)
			{
				if($fromColumn==$columns["own"])
					return $this->getTable($table);
			}
		}
		$nullob= new STAliasTable("NoForeignKeyTable");
		return $nullob;
	}
		function &getTable($sTableName)
		{
			Tag::paramCheck($sTableName, 1, "string");

			$sTableName= $this->container->getTableName($sTableName);
			if($this->Name==$sTableName)
				return $this;
			$oTable= &$this->aoToTables[$sTableName];
			if(!$oTable)
			{
				if(	!$this->bAddedFkTables
					and
					!$this->bAddedTabels		)
				{
					$done= array();
					$this->addFKTables($this->db->dbName, $done, $this->Name);
					$this->bAddedFkTables= true;

					$oTable= &$this->aoToTables[$sTableName];
				}
				if(!$oTable)
					$oTable= &$this->container->getTable($sTableName);
				if($oTable && !typeof($oTable, "STDbSelector"))
					$oTable= new STDbSelector($oTable);
				if($oTable)
					$this->aoToTables[$sTableName]= &$oTable;
				else
					unset($this->aoToTables[$sTableName]);
			}
			// alex 24/05/2005:	f�r aoToTables als Key den Namen eingef�hrt
			return $oTable;
			/*foreach($this->aoToTables as $aTables)
			{
				if($aTables->getName()==$sTableName)
					return $aTables;
			}
			return null;*/
		}
		// param $table hat vorrang vor tableName in where-Objekt


		// alex 13/10/2005:	herausgel�scht, alle where Komponenten
		//					sollen jetzt wie bei einer normalen Tabelle gehandhabt werden
		function where($where, $table= null)
		{//echo "function where(";st_print_r($where,0);echo ", ";st_print_r($table);echo ")<br />";
			Tag::paramCheck($where, 1, "string", "STDbWhere");
			Tag::paramCheck($table, 2, "string", "STAliasTable", "null");

			if(is_string($where))
				$where= new STDbWhere($where, $this->Name);
			if(is_string($table))
			{
				$table= $this->container->getTableName($table);
				$where->forTable($table);
				
			}elseif($table!==null)
			{// wenn $table ein Objekt von STDbTable ist
			 // wird zuerst �berpr�ft ob dieser mit add()
			 // schon hinzugef�gt wurde. Wenn ja, wird der
			 // aus der member-Variable aoToTables genommen
			 // sonst dieser hinzugef�gt
			 	$tableName= $table->getName();
				$where->forTable($tableName);
			}
			STDbTable::where($where);
		}
		function join($columnName, &$oTable, $otherColumn= null)
		{
			$this->innerJoin($columnName, $oTable, $otherColumn);
		}
		function innerJoin($columnName, &$oTable, $otherColumn= null)
		{
			Tag::paramCheck($columnName, 1, "string");
			Tag::paramCheck($oTable, 2, "STDbTable");
			Tag::paramCheck($otherColumn, 3, "string", "null");

			$bInnerJoin= true;
			if(!typeof($oTable, "OSTDbSelector"))
				$sTable= new OSTDbSelector($oTable);
			$tableName= $oTable->getName();
			$this->aoToTables[$tableName]= &$oTable;
			STDbTable::foreignKeyObj($columnName, $oTable, $otherColumn);
		}
		function outerJoin($columnName, $oTable= null, $otherColumn= null)
		{
			if($oTable===null)
				$oTable= $columnName;
			if(Tag::isDebug())
  			if(!typeof($oTable, "STDbTable", "string"))
  			{
  				echo "<b>ERROR in OSTDbSelector::outerJoin():</b> ";
					if(func_num_args()==1)
						echo "1.";
					else
						echo "2.";
  				echo " parameter must be an object of STDbTable or an string";
  				exit;
  			}
			$this->add($oTable);
			if(is_string($oTable))
				$oTable= &$this->getTable($oTable);
			$bInnerJoin= false;
			if(!typeof($oTable, "OSTDbSelector"))
				$oTable= new STDbSelector($oTable);
			//$this->aoToTables[]= &$oTable;
			STDbTable::foreignKeyObj($columnName, $oTable, $otherColumn);
		}
		function select($tableName, $column= "", $alias= null, $nextLine= true, $add= false)
		{
			if(STCheck::isDebug())
			{
				Tag::paramCheck($tableName, 1, "string");
				Tag::paramCheck($column, 2, "string", "empty(string)");
				Tag::paramCheck($alias, 3, "string", "bool", "null");
				Tag::paramCheck($nextLine, 4, "bool");
				Tag::echoDebug("selector", $this->Name.": select column $column($alias) for table $tableName");
			}
			$desc= STDbTableDescriptions::instance($this->db->getName());
			$column= $desc->getColumnName($tableName, $column);// if tableName is original function must not search
			$tableName= $desc->getTableName($tableName);
			if(STCheck::isDebug())
			{
				if($tableName===$this->Name)
					$oTable= &$this;
				else
					$oTable= &$this->db->getTable($tableName);
				STCheck::alert(!$oTable->columnExist($column), "STAliasTable::selectA()",
											"column $column not exist in table ".$tableName.
											"(".$oTable->getDisplayName().")");
			}

			//$tableName= $this->container->getTableName($tableName);
			if(is_bool($alias))
			{
			 $nextLine= $alias;
			 $alias= null;
			}
			if(!$alias)
				$alias= $column;
			//$table= &$this->getTable($tableName);
			//Tag::alert($table==null, "OSTDBSelector::select", "tablename ".$tableName." not given in database");
			/*if($table===null)
			{
			    $table= $this->container->getTable($tableName);
					$this->add($table);
			}*/

			$select= array(	"type"=>	"select",
							"column"=>	$column,
							"alias"=>	$alias,
							"next"=>	$nextLine	);
			$this->aNewSelects[$tableName][]= $select;
			/*if($tableName!=$this->Name)
			{
				//return;

				// makes all this next code in function execute
				$this->bAddedFkTables= true;
				$bSelected= false;
				foreach($this->aFks as $fkTableName=>$fk)
				{
					if($fkTableName==$tableName)
					{
						if(!$this->isSelect($this->Name, $fk["own"], $fk["own"]))
							$this->selectA($this->Name, $fk["own"], $fk["own"], $nextLine);
						$this->newIdentifColumn($fkTableName, $column, $alias);
						$this->aoToTables[$tableName]= $table;
						$bSelected= true;
					}
				}
				Tag::warning(!$bSelected, "OSTDBSelector::select", "found no foreign key from table ".$this->Name." to table $tableName");
				return;
			}*/
			if(!$this->bClearedByFirstSelect)
			{
				$this->clearSelects();
				$this->bClearedByFirstSelect= true;
			}
			$this->selectA($tableName, $column, $alias, $nextLine, $add);
		}
		function limit($start, $limit= null)
		{
			if(!$limit)
			{
				$limit= $start;
				$start= 0;
			}
			$this->limitRows= array("start"=>$start, "limit"=>$limit);
		}
		/*private*/function createNnTable()
		{
			$fk= &$this->getForeignKeys();
			$sort= array();
			$selected= array();
			// search the first selected column with foreign key
			// to set it to an right join
			// all other set to left join
			foreach($fk as $table=>$content)
			{
				foreach($content as $key=>$column)
				{
					$nr= $this->getSelectedColumnKey($column["own"]);
					if($nr!==null)
					{
						$sort[]= $nr;
						$selected[$nr]= array(	"table"=>	$table,
												"key"=>		$key	);
					}
					$fk[$table][$key]["join"]= "left";
				}
			}
			sort($sort);
			$nr= reset($sort);
			Tag::alert(count($selected)<1, "STAliasTable::nnTable()", "before use function nnTable, select leastwise an column with foreign keys", 2);
			$fk[$selected[$nr]["table"]][$selected[$nr]["key"]]["join"]= "right";
			$newFk= array();
			// make an new sort of the foreign key array
			// beacuse the right join must be the first one
			foreach($sort as $nr)
			{
				$newFk[$selected[$nr]["table"]][]= $fk[$selected[$nr]["table"]][$selected[$nr]["key"]];
			}
			$this->aFks= $newFk;
		}
		function newIdentifColumn($table, $column, $alias)
		{
			Tag::paramCheck($table, 1, "STAliasTable", "string");
			Tag::paramCheck($column, 2, "string");
			Tag::paramCheck($alias, 3, "string");

			if(is_string($table))
			{
				$tableName= $table;
				$table= &$this->getTable($tableName);
			}else
				$tableName= $table->getName();
			if(!$this->aClearIdentifColumns[$tableName])
			{
				$table->clearIdentifColumns();
				$this->aClearIdentifColumns[$tableName]= true;
			}
			$table->identifColumn($column, $alias);
		}
/*		public function allowQueryLimitation($bModify= true)
		{ $this->oMainTable->allowQueryLimitation($bModify); }
		public function clearRekursiveNoFkSelects()
		{ $this->oMainTable->clearRekursiveNoFkSelects(); }
		public function clearRekursiveGetColumns()
		{ $this->oMainTable->clearRekursiveGetColumns(); }
		public function distinct($bDistinct= true)
		{
			$this->bDistinct= $bDistinct;
		}
		function isDistinct()
		{
			return $this->bDistinct;
		}*/
		function getResult($sqlType= null) // need sqlType only to be compatible with STDbTable
		{
			return $this->SqlResult;
		}
		function &getSingleArrayResult()
		{
			$result= array();
			foreach($this->SqlResult as $row)
			{
				$each= each($row);
				$result[]= $row[$each["key"]];
			}
			return $result;
		}
		function getSingleResult($sqlType= null) // need sqlType only to be compatible with STDbTable
		{
			if(isset($this->SqlResult[0]))
				$row= $this->SqlResult[0];
			if(	!isset($row) ||
				!is_array($row)	)
			{
				$result= null;
				return $result;
			}
			$result= reset($row);
			if($result === false)
			{
				$result= null;
				return $result;
			}
			return $result;
		}
		function getRowResult($sqlType= null) // need sqlType only to be compatible with STDbTable
		{
			if($this->defaultTyp==NUM_OSTfetchArray)
			{
				$count= 0;
				$null= true;
				$Rv= array();
				foreach($this->SqlResult as $key => $value)
				{
					$Rv[$key]= $value[$this->fetchArrayCount];
					if($Rv[$key]!=null)
						$null= false;
				}
				$this->fetchArrayCount++;
				if($null)
				{
					$this->fetchArrayCount= 0;
					$Rv= null;
				}
			}else
			{//st_print_r($this->SqlResult);
				if($this->SqlResult)
				{
					$Rv= reset($this->SqlResult);//print_r($Rv);
					if($Rv==null)
						reset($this->SqlResult);
				}else
					$Rv= array();
			}
			return $Rv;
		}
		function reset()
		{
			if($this->defaultTyp==NUM_OSTfetchArray)
				$this->fetchArrayCount= 0;
			else
				reset($this->SqlResult);
		}
		function exec2()
		{
		    st_print_r($this->wait);
		    showErrorTrace();exit;
		}
		function execute($sqlType= null, $limit= null)
		{
			STCheck::param($sqlType, 0, "int", "null");
			STCheck::param($limit, 1, "int", "null");

			$bNormal= true;
			if($sqlType===null)
				$sqlType= $this->defaultTyp;
			$sqlType2= $sqlType;
			if($sqlType=="NUM_STfetchArray")
			{
				$sqlType2= MYSQL_NUM;
				$bNormal= false;
			}elseif($sqlType=="ASSOC_STfetchArray")
			{
				$sqlType2= MYSQL_ASSOC;
				$bNormal= false;
			}elseif($sqlType=="BOTH_STfetchArray")
			{
				$sqlType2= MYSQL_BOTH;
				$bNormal= false;
			}

			$statement= $this->getStatement($limit);
			$this->db->orderDates(false);
			$this->SqlResult= $this->db->fetch_array($statement, $sqlType2, $this->onError);
			$this->setSqlError($this->SqlResult);
			$this->search= array();
			if($this->SqlResult===null)
				return -1;
			$fields= $this->getSelectedFieldArray();
			$this->SqlResult= $this->db->orderDate($fields, $this->SqlResult, "need no statement");
			//echo __file__.__line__."<br />";
			//echo $statement;
			$this->db->orderDates(true);
			$this->SqlResult= $this->db->orderDate($fields, $this->SqlResult);
			if($bNormal)
			{
				$fvalue= array();
				foreach($this->SqlResult as $key=>$value)
				{
					$fvalue= $value;
					break;
				}
				reset($this->SqlResult);
				$nRv= count($fvalue);
			}else
				$nRv= count($this->SqlResult);

			if($sqlType2==MYSQL_BOTH)
				$nRv/= 2;
			return $nRv;
		}
		function getColumn($tableName, $columnName= "")
		{
			STCheck::paramCheck($tableName, 1, "string");
			STCheck::paramCheck($columnName, 2, "string");
			$nParams= func_num_args();
			STCheck::lastParam(2, $nParams);

			$desc= STDbTableDescriptions::instance($this->db->getName());
			$tableName= $desc->getTableName($tableName);
			if($tableName==$this->Name)
			{
				STDbTable::getColumn($columnName);
				return;
			}
			if(STCheck::isDebug())
				STCheck::warning(1, "OSTDbSelector::getColumn()", "toDo: function getColumn not avalibl for other tables in OSTDbSelector");
		}
		function getErrorId()
		{
			return $this->errorID;
		}
		function getErrorMessage()
		{
			return $this->errorMessage;
		}
		/*protected*/function setSqlError($sqlResult)
		{
  			if(!$sqlResult)
  			{
  				$sqlErrorMessage= $this->db->getError();
				if($this->db->errno()!=0)
				{//
					$messageId= "SQLERROR_".$this->db->errno();
					$this->errorID= $messageId;
  					$this->errorMessage= $sqlErrorMessage;
				}
  			}
		}
		function setStatement($statement)
		{ $this->sqlStatement= $statement; }
		function getStatement($limit= null, $withAlias= true)
		{

			if($this->sqlStatement != "")
				return $this->sqlStatement;

			if($this->bIsNnTable)
				$this->createNnTable();
			$aDeep= array();
			$sFirstTable= $this->Name;

			$this->sqlStatement= $this->db->getStatement($this, false, $withAlias);
			//echo $this->sqlStatement."<br />";
			if($limit)
				$this->sqlStatement.= " limit ".$limit;
			return $this->sqlStatement;
		}
		function searchValue($searchValue)
		{
			if(!isset($this->search))
			{
				echo "<b>OSTDbSelector Error:</b> Tabelle <b>".$this->table->getName()."</b><br>";
				echo "vor der Funktion ->searchValue(), ";
				echo " muss die Funktion ->search() zumindest einmal aufgerufen werden.";
				exit();
			}
			$nRow= $this->search[$searchValue];
			if($nRow==null)
				return null;
			return $this->SqlResult[$this->getColumn][$nRow];
		}
		function search($inColumn, $getColumn)
		{
			if($this->SqlResult==null)
			{
				echo "<b>OSTDbSelector Error:</b> die Funktion ->execute(), ";
				echo " f�r den Datenbank-Select in Tabelle \"<b>";
				echo $this->table->getName()."</b>\",<br>";
				echo "muss zuerst ausgef�hrt werden,";
				echo " bevor im Hash-Table gesucht werden kann!";
				exit();
			}
			if(	$this->defaultTyp!=NUM_OSTfetchArray
				and
				$this->defaultTyp!=ASSOC_OSTfetchArray
				and
				$this->defaultTyp!=BOTH_OSTfetchArray)
			{
				echo "wenn im <b>OSTDbSelector</b> eine Suche mittels Hash vorgenommen wird<br>";
				echo "muss der <b>MYSQL-Typ</b> im Konstruktor mit ";
				echo "<b>NUM_OSTfetchArray, ASSOC_OSTfetchArray</b> oder ";
				echo "<b>BOTH_OSTfetchArray</b> vordeffiniert werden";
				exit();
			}
			$flipCol= array_flip($this->columns);
			$this->inColumn= $flipCol[$inColumn];
			if(!isset($this->inColumn))
			{
				echo "<b>ERROR:</b> OSTDbSelector::search(".$inColumn.", ".$getColumn.")<br>";
				echo "im Hash-Table der Tabelle <b>".$this->table->getName()."</b> ";
				echo "gibt es keine Spalte <b>$inColumn</b>";
				exit;
			}//out($flipCol);
			$this->getColumn= $flipCol[$getColumn];
			if(!isset($this->getColumn))
			{
				echo "<b>ERROR:</b> OSTDbSelector::search(".$inColumn.", ".$getColumn.")<br>";
				echo "im Hash-Table der Tabelle <b>".$this->table->getName()."</b> ";
				echo "gibt es keine Spalte <b>$getColumn</b>";
				exit;
			}
			$array= $this->SqlResult[$flipCol[$inColumn]];
			$array= array_flip($array);
			$this->search= $array;

		}
		/*function getColumns()
		{
			return $this->show;
		}*/
		function getSelectTyp()
		{
			return $this->defaultTyp;
		}
		function getOnErrorTyp()
		{
			return $this->onError;
		}
		function clearForCount(&$table)
		{
			$selected= $table->getIdentifColumns();
			$columnString= "";
			$bHaveFk= false;
    		foreach($selected as $content)
    		{
    			if(	!$this->isIdentifColumn($content["column"])
					or
					!$this->abNewChoice["identifColumn"]		)
    			{
      					$fkTable= &$this->getFkTable($content["column"], true);
      					if($fkTable)
      					{
							$bHaveFk= true;
      						$fkTable->identifColumn($content["column"], $content["alias"]);
							$columnString.= $this->clearForCount($fkTable);
      					}
    			}
				$columnString.= $table->Name.".".$content["column"].",";
    		}
			if(!$bHaveFk)
				$table->displayIdentifs(false);
			return $columnString;
		}
		function count($column= "*", $alias= null, $add= false)
		{
			Tag::paramCheck($column, 1, "string", "STAliasTable");
			Tag::paramCheck($alias, 2, "string", "null");

			if(typeof($column, "STAliasTable"))
			{
				$selected= $column->getSelectedColumns();
				$columnString= "";
				if($column->isDistinct())
					$columnString= "distinct ";
				foreach($selected as $content)
				{
					if(	!$this->isSelect($content["column"])
						or
						!$this->abNewChoice["select"]			)
					{
    					$table= &$this->getFkTable($content["column"], true);
    					if($table)
    					{
    						STDbTable::select($content["column"]);
							$columnString.= $this->clearForCount($table);
    					}
					}
					$columnString.= $column->Name.".".$content["column"].",";
				}
				$column= substr($columnString, 0, strlen($columnString)-1);
			}
			STDbTable::count($column, $alias, $add);
		}
}

?>