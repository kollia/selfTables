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
		private $errorID= 0;
		private $errorMessage= "No Error occured";
		var $search= array();
		var $fetchArrayCount= 0;
		var $count= 0;
		var	$dbCount;
		var	$defaultTyp;
		var $sqlStatement= "";
		var	$bClearSelects= false;	// for later getting table
									// by true it should also cleared
		/**
		 * whether the checkbox column
		 * for an N to N table was selected if was declared as
		 * @var boolean
		 */
		public $bNnTableColumnSelected= false;
		public $aNnTableColumn= array();

		// DbSelector spezifisch
		var $bAddedTabels= false;
		var $bAddedFkTables= false;
		var $aClearIdentifColumns= array();
		var	$bClearedByFirstSelect= false; // die selects in ->show werden gel�scht wenn ein anderer Select gew�nscht wird


		function __construct(&$oTable, $defaultTyp= STSQL_ASSOC, $onError= onErrorStop)
		{
			STCheck::param($oTable, 0, "STBaseTable");
			STCheck::param($defaultTyp, 1, "check", $defaultTyp==STSQL_NUM || $defaultTyp==STSQL_ASSOC || $defaultTyp==STSQL_BOTH,
														"STSQL_NUM, STSQL_ASSOC or STSQL_BOTH");
			STCheck::param($onError, 2, "check", $onError==noErrorShow || $onError==onErrorShow || $onError==onErrorStop,
														"noErrorShow", "onErrorShow", "onErrorStop");

			$this->defaultTyp= $defaultTyp;
			$this->onError= $onError;
			//$db= &$oTable->getDatabase();
			//$this->oMainTable= &$oTable;
			STCheck::echoDebug("table", "copy ".get_class($oTable)."::".$oTable->getName()." into ".get_class($this)." from ID:".$oTable->ID);
			$this->aoToTables[$oTable->getName()]= &$oTable;
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
			foreach($fks as $tableName=>$fields)
			{
			    foreach($fields as $column)
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
		/**
		 * fetch table from database of given or current container<br />
		 * and if table new, store a clone in own object
		 *
		 * @param string $sTableName name of the table
		 * @param string $sContainer container name from which table should fetched
		 * @return object of table or null
		 */
		public function &getTable(string $sTableName, string $sContainer= null)
		{
			STCheck::param($sTableName, 0, "string");
			STCheck::param($sContainer, 1, "string", "null");
			
			if( $sContainer != null &&
			    $sContainer != $this->container->getName() )
			{
			    $container= &STBaseContainer::getContainer($sContainer);
			}else
			    $container= &$this->container;
			$sTableName= $this->container->getTableName($sTableName);
			if($this->Name==$sTableName)
				return $this;
			if(!isset($this->aoToTables[$sTableName]))
			{
			    $oTable= null;
				if(	!$this->bAddedFkTables
					and
					!$this->bAddedTabels		)
				{
					$done= array();
					$this->addFKTables($this->db->dbName, $done, $this->Name);
					$this->bAddedFkTables= true;

					if(isset($this->aoToTables[$sTableName]))
					   $oTable= &$this->aoToTables[$sTableName];
				}
				if(!$oTable)
					$oTable= clone $container->getTable($sTableName);
				if($oTable)
					$this->aoToTables[$sTableName]= &$oTable;
				else
					unset($this->aoToTables[$sTableName]);
			}else
			    $oTable= &$this->aoToTables[$sTableName];
			// alex 24/05/2005:	f�r aoToTables als Key den Namen eingef�hrt
			return $oTable;
		}
		public function andWhere($table, $where= null)
		{
		    if(STCheck::isDebug())
		    {
		        STCheck::param($table, 0, "string", "STDbWhere", "STDbTable");
		        STCheck::param($where, 1, "string", "STDbTable", "null");
		    }
		    if(!isset($where))
		    {
		        $where= $table;
		        if(is_string($where))
		            $where= new STDbWhere($where, $this->Name);
		        if( typeof($where, "STDbWhere") &&
		            $where->sDbName == ""         )
		        {
		            $where->setDatabase($this->db->getDatabaseName());
		        }		        
		    }
		    STDbTable::where($where, "and");
		}
		public function orWhere($table, $where= null)
		{
		    if(STCheck::isDebug())
		    {
		        STCheck::param($table, 0, "string", "STDbWhere", "STDbTable");
		        STCheck::param($where, 1, "string", "STDbTable", "null");
		    }
		    if(!isset($where))
		    {
		        $where= $table;
		        if(is_string($where))
		            $where= new STDbWhere($where, $this->Name);
		            if( typeof($where, "STDbWhere") &&
		                $where->sDbName == ""         )
		            {
		                $where->setDatabase($this->db->getDatabaseName());
		            }
		    }
		    STDbTable::where($where, "or");
		}
		/*
		 * implement where rule for table
		 * 
		 * @param string|STDbTable|STDbWhere $table can be a table object, a name of a table, or an where object/statement 
		 * @param string|STDbWhere|null $where can be a where compairson (object/string) or an string operator of 'and' or 'or'
		 * @param string $operator can be the operator of 'and' or 'or', or an null string
		 */
		public function where($table, $where= null, $operator= "")
		{//echo "function where(";st_print_r($table,0);echo ", ";st_print_r($where,0);echo ", ";st_print_r($operator,0);echo ")<br />";
		    if(STCheck::isDebug())
		    {
    			STCheck::param($table, 0, "string", "STDbTable", "STDbWhere");
    			STCheck::param($where, 1, "string", "STDbWhere", "null");
    			STCheck::param($operator, 2, "string", "empty(string)");
		    }
		    
		    if( is_string($table) )
		    {
		        $sTable= $this->container->getTableName($table);
		        if($this->db->hasTable($sTable))
		        {// first string should be a table and second the where statement
		            $table= $this->getTable($sTable);
		        }else
		        {
		            // first string should be the where statement
		            STCheck::warning( (   $where != "and" &&
		                $where != "or"      ), "STDbSelector::where()",
		                "second parameter can only be a string of 'and' or 'or'", 1   );
		            if(!isset($where))
		                $where= "";
		            STDbTable::where($table, $where);
		        }
		    }elseif(typeof($table, "STDbWhere"))
		    {
		        STCheck::warning( (   isset($where) &&
                    		            (   !is_string($where) ||
                    		                (   $where != "and" &&
                    		                    $where != "or"      )   )   ), "STDbSelector::where()",
		            "if first parameter the where statement, the second can only be 'and' or 'or'", 1         );
                if(!isset($where))
                    $where= "";
                //echo __FILE__.__LINE__."<br>";
                //st_print_r($table);st_print_r($where);
                STDbTable::where($table, $where);
                return;
		    }
		    
		    if( !isset($where) ||
		        (   is_string($where) &&
		            (   $where == "" ||
		                $where == "and" ||
		                $where == "or"    )   )   )
		    {// $table should be the where statement
		        $operator= $where;
		        $where= $table;
		        STDbTable::where($where, $operator);
		        return;
		    }
		    
            $where= new STDbWhere($where);
            $where->table($table);
            //$where->setDatabase($table->db);
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
		public function orderBy($tableName, $column= true, $bASC= true)
		{
		    if(is_bool($column))
		    {// method is as normaly table orderBy
		        $bASC= $column;
		        $column= $tableName;
		        $tableName= $this->getName();
		    }
		    STDbTable::orderByI($tableName, $column, $bASC);
		}
		function select(string $tableName, $column= "", $alias= null, $nextLine= true, $add= false)
		{
			if(STCheck::isDebug())
			{
			    STCheck::param($tableName, 0, "string"); // cannot be an null string
				STCheck::param($column, 1, "string", "empty(string)");
				STCheck::param($alias, 2, "string", "bool", "null");
				STCheck::param($nextLine, 3, "bool");
				STCheck::echoDebug("selector", $this->Name.": select column $column($alias) for table $tableName");
			}
			$desc= STDbTableDescriptions::instance($this->db->getDatabaseName());
			$tableName= $desc->getTableName($tableName);
			$orgColumn= $desc->getColumnName($tableName, $column, /*warnFuncOutput*/1);// if tableName is original function must not search
			if(STCheck::isDebug())
			{
				if($tableName===$this->Name)
					$oTable= &$this;
				else
					$oTable= &$this->db->getTable($tableName);
				STCheck::alert(!$oTable->validColumnContent($column), "STBaseTable::selectA()",
											"column $column not exist in table ".$tableName.
											"(".$oTable->getDisplayName().")");
			}
			if(trim($orgColumn) == "")
			    $orgColumn= $column;

			//$tableName= $this->container->getTableName($tableName);
			if(is_bool($alias))
			{
			 $nextLine= $alias;
			 $alias= null;
			}
			if(!$alias)
			    $alias= $orgColumn;
			//$table= &$this->getTable($tableName);
			//Tag::alert($table==null, "OSTDBSelector::select", "tablename ".$tableName." not given in database");
			/*if($table===null)
			{
			    $table= $this->container->getTable($tableName);
					$this->add($table);
			}*/

			$select= array(	"type"=>	"select",
			    "column"=>	$orgColumn,
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
				STCheck::is_warning(!$bSelected, "OSTDBSelector::select", "found no foreign key from table ".$this->Name." to table $tableName");
				return;
			}*/
			if(!$this->bClearedByFirstSelect)
			{
				$this->clearSelects();
				$this->bClearedByFirstSelect= true;
			}
			$this->selectA($tableName, $orgColumn, $alias, $nextLine, $add);
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
		public function setNnTable(string $nnTableName, string $fixTableName)
		{
		    $bfixFk=  false;
		    $bJoinFk= false;
		    $this->noInsert();
		    $this->noUpdate();
		    $this->noDelete();		    
		    $fixTableName= $this->db->getTableName($fixTableName);
		    $nnTableName= $this->db->getTableName($nnTableName);
		    $nnTable= $this->getTable($nnTableName);
		    STCheck::alert(!$this->sPKColumn, "STBaseTable::nnTableColumn()", "primary key for function ::nnTableColumn() must be set in table $nnTableName");
		    $this->bIsNnTable= true;
		    $this->aNnTableColumn= array( "table" => $nnTableName,
		                                  "column" => $nnTable->sPKColumn    );
		    
		    //$this->getColumn($this->Name, $this->getPkColumnName(), "nnPK@".$this->Name.)
			// search where the foreign keys are pointed
			// to set it  all to left joins
			// and also insert getColumns to foreign keys
		    // for new inserts
		    $fks= &$nnTable->getForeignKeys();
			foreach($fks as $table=>$content)
			{
				foreach($content as $key=>$column)
				{
				    $toTable= "no";
				    if($table == $fixTableName)
				    {
				        $bfixFk= true;
				        $toTable= "fix";
				        
				    }elseif($table == $this->Name)
				    {
				        $bJoinFk= true;
				        $toTable= "join";
				    }
				    if($toTable != "no")
				    {
				        $fks[$table][$key]["join"]= "left";
				        $this->aNnTableColumn['fks'][$toTable]['table']= $table;
				        $this->aNnTableColumn['fks'][$toTable]['column']= $column['other'];
				        if($toTable == "join")
				            $this->getColumn($table, $column['other'], "join@$table@".$column['other']);
				    }
				}
			}
			STCheck::alert(!$bJoinFk, "STBaseTable::setNnTable()", "the N to N table $nnTableName have no foreign key to table ".$this->Name, 2);
			STCheck::alert(!$bfixFk, "STBaseTable::setNnTable()", "the N to N table $nnTableName have no foreign key to table $fixTableName", 2);
		}
		/**
		 * check whether given name is a valid column.<br />
		 * The column can also be a quoted string,
		 * or contain a keyword from database
		 *
		 * {@inheritDoc}
		 * @see STBaseTable::validColumnContent()
		 */
		public function validColumnContent(string $content, &$abCorrect= null, bool $bAlias= false) : bool
		{
		    $field= null;
		    if( $this->bIsNnTable &&
		        isset($this->aNnTableColumn['fks']['join']['column']) &&
		        $content == "join@".$this->aNnTableColumn['fks']['join']['column']    )
		    {
		        $content= $this->aNnTableColumn['fks']['join']['column'];
    		    $field= array();
    		    $field['columns'][]= $content;
		    }
		    $bRv= STDbTable::validColumnContent($content, $abCorrect, $bAlias);
		    if( typeof($abCorrect, "array") &&
		        isset($field)                 )
		    {
		        $abCorrect= $field;
		    }
		    return $bRv;
		}
		/**
		 * make a column selection of primary keys from N to N table with checkboxes
		 * and all possible assignments where the box is checked.<br />
		 * if the affilation is given
		 *
		 * @param string $checkBoxColumnName headline name from checkboxes
		 */
		public function nnTableCheckboxColumn(string $checkBoxColumnName)
		{
		    STCheck::alert(!$this->bIsNnTable, "STBaseTable::nnTableColumn()", "STDbSelector is not defined as N to N table");
		    
		    $this->bNnTableColumnSelected= true;
		    $this->aNnTableColumn['alias']= $checkBoxColumnName;
		    $this->select($this->aNnTableColumn['table'], $this->aNnTableColumn['column'], $checkBoxColumnName);
		    $this->checkBox($checkBoxColumnName);
		}
		function newIdentifColumn($table, $column, $alias)
		{
			Tag::paramCheck($table, 1, "STBaseTable", "string");
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
			    reset($row);
				$result[]= current($row);
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

			$statement= $this->getStatement();
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
		/**
    	 * select column, do not calculate foreign keys by statement
    	 * and not display inside STListBox or STItemBox
    	 * 
		 * @param string $tableName name of table
		 * @param string $column name of column (parameter cannot be an null string, its only defined for compatibility with STBaseTable)
    	 * @param string $alias alias name of column
		 */
		function getColumn(string $tableName, string $column= "", string $alias= "")
		{
			STCheck::param($tableName, 0, "string");
			STCheck::param($column, 1, "string");
			$nParams= func_num_args();
			STCheck::lastParam(3, $nParams);

			$this->getColumnA($tableName, array( "column"=>$column, "alias"=>$alias));
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
  			if(!is_array($sqlResult))
  			{
  				$sqlErrorMessage= $this->db->getError();
  				$this->errorID= $this->db->errno();
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
		function getStatement($limit= null, $withAlias= null)
		{
			if($this->sqlStatement != "")
				return $this->sqlStatement;
			
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
		public function count($table= "*", string $column= "*", $alias= null, $add= false)
		{
			STCheck::param($table, 0, "string", "STBaseTable");
			// param 1 $column should always a string
			STCheck::param($alias, 2, "string", "null", "bool");
			STCheck::param($add, 3, "bool");

			if($table == "*")
			{
			    if($column == "*")
			        STDbTable::countA();
			    else
			        STDbTable::countA($table, $column);
			    return;
			}
			if(typeof($table, "STBaseTable"))
			{
			    if(typeof($alias, "boolean"))
			        $add= $alias;
			    if($column != "*")
			        $alias= $column;
				$selected= $table->getSelectedColumns();
				$columnString= "";
				if($table->isDistinct())
				    $columnString= "distinct ";
				$table= $table->getName();
				foreach($selected as $content)
				{
					if(	!$this->isSelect($content["column"])
						or
						!$this->abNewChoice["select"]			)
					{
    					$oTable= &$this->getFkTable($content["column"], true);
    					if($oTable)
    					{
    						STDbTable::select($content["column"]);
							$columnString.= $this->clearForCount($oTable);
    					}
					}
					$columnString.= $column->Name.".".$content["column"].",";
				}
				$column= substr($columnString, 0, strlen($columnString)-1);
				STDbTable::count($column, $alias, $add);
				return;
			}
			$oTable= &$this->getTable($table);
			if(!isset($oTable))
			{// if table is no table name, calling is for current table
			    $oTable= &$this;
			    $column= $table;
			    $alias= $column;
			    $add= $alias;
			}
			$oTable->countA($column, $alias, $add);
		}
}

?>