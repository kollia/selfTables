<?php

require_once($_stbasecontainer);
require_once($_stdbtable);
require_once($_stdbwhere);

class STDbSelector extends STDbTable implements STContainerTempl
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
		/**
		 * for later getting table
		 * by true it should also cleared selections
		 * @var boolean
		 */
		var	$bClearSelects= false;
		/**
		 * for later getting table
		 * by true it should also cleared identif columns
		 * @var boolean
		 */
		var $bClearIdendifColumns= false;
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
			STCheck::param($oTable, 0, "STDbTable", "STObjectContainer");
			STCheck::param($defaultTyp, 1, "check", $defaultTyp==STSQL_NUM || $defaultTyp==STSQL_ASSOC || $defaultTyp==STSQL_BOTH,
														"STSQL_NUM, STSQL_ASSOC or STSQL_BOTH");
			STCheck::param($onError, 2, "check", $onError==noErrorShow || $onError==onErrorShow || $onError==onErrorStop,
														"noErrorShow", "onErrorShow", "onErrorStop");

			if(typeof($oTable, "STDbTable"))
			{
			    $container= null;
			    $table= $oTable;
			    $this->aoToTables[$oTable->getName()]= &$oTable;
			    STCheck::echoDebug("table", "copy ".$oTable->toString()." into own ".$this->toString());
			}else
			{
			    $container= $oTable;
			    $table= null;
			}
			$this->defaultTyp= $defaultTyp;
			$this->onError= $onError;
			STDbTable::__construct($table, $container, $onError);			
		}
		function __clone()
		{
		    STDbTable::__clone();
		    STCheck::echoDebug("table", "clone STDbSelector::content ".$this->Name.":".$this->ID);
		}
		function add($table)
		{
			Tag::paramCheck($table, 1, "string", "STDbTable");
			Tag::alert($this->bAddedFkTables, "STDbSelector::add()", "cannot add an new table, if bevore made ::select to an other");

			if(is_string($table))
				$table= $this->getTable($table);
			$sTableName= $table->getName();
			
			if(!isset($this->aoToTables[$sTableName]))
			{
				$this->aoToTables[$sTableName]= &$table;
				$this->bAddedTabels= true;
			}
		}
		/*protected*/function addFKTables($dbName, &$aDone, $sFromTableName)
		{
			// if sFromTableName in array aDone,
		    // do not again
		    echo __FILE__.__LINE__."<br>";
		    echo "addFKTables($dbName, &";st_print_r($aDone, 1, false);echo ", $sFromTableName)<br>";
			if(isset($aDone[$sFromTableName]))
				return;
			$aDone[$sFromTableName]= true;
			if($this->Name==$sFromTableName)
			{
				$oTable= &$this;
			}else
			{
				$oTable= &$this->aoToTables[$sFromTableName];
				if(!$oTable)
				{
					$oTable= $this->getTable($sFromTableName);
					$oTable->allowQueryLimitation($this->bModifyFk);
					if($this->bClearSelects)
						$oTable->clearSelects();
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
    					$container= $this;
    				if(!$db)
    					$db= $this->container->getDatabase();
    				$fkTable= $container->getTable($tableName);
    				if($fkTable)
    				{
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
		public function clearSelects()
		{
			$this->bClearSelects= true;
			STDbTable::clearSelects();
			foreach($this->aoToTables as $name=>$table)
			{
				if( $table &&
				    $name != $this->Name    )
				{
					$this->aoToTables[$name]->clearSelects();
				}
			}
		}
		public function clearIdentifColumns()
		{
		    $this->bClearIdendifColumns= true;
		    STDbTable::clearIdentifColunns();
		    foreach($this->aoToTables as $name=>$table)
		    {
		        if( $table &&
		            $name != $this->Name    )
		        {
		            $this->aoToTables[$name]->identifColumns();
		        }
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
		public function getTableName(string $tableName= null)
		{
		    return $this->container->getTableName($tableName);
		}
		/**
		 * whether table object exist 
		 * inside parent container from which was pulled
		 * this table
		 *
		 * @param string $tableName name of table
		 * @return bool whether exist
		 */
		public function hasTable(string $tableName) : bool
		{
		    if(!isset($this->container))
		    {
		        STCheck::warning(1, "STDbSelector::hasTable()", "no container for this table ".$this->toString()." defined");
		        return false;
		    }
		    return $this->container->hasTable($tableName);
		}
		/**
		 * fetch table from database of given or current container<br />
		 * and if table new, store a clone in own object
		 *
		 * @param string $sTableName name of the table
		 * @param string $sContainer container name from which table should fetched
		 * @return object of table or null
		 */
		public function &getTable(string $sTableName= null, string $sContainer= null)
		{
			if( !isset($sTableName) ||
			    trim($sTableName == "")  )
			{
			    $sTableName= $this->getTableName();
			}
			if( $sContainer != null &&
			    $sContainer != $this->container->getName() )
			{
			    $container= &STBaseContainer::getContainer($sContainer);
			}else
			    $container= &$this->container;
			$sTableName= $container->getTableName($sTableName);
			if($this->Name==$sTableName)
				return $this;
			if(!isset($this->aoToTables[$sTableName]))
			{
			    $oTable= null;
				if(	!$this->bAddedFkTables
					and
					!$this->bAddedTabels		)
				{
					//$done= array();
					//$this->addFKTables($this->db->dbName, $done, $this->Name);
					$this->bAddedFkTables= true;

					if(isset($this->aoToTables[$sTableName]))
					   $oTable= &$this->aoToTables[$sTableName];
				}
				if(!$oTable)
				{
				    $getTable= $container->getTable($sTableName);
				    $oTable= clone $getTable;
				    $oTable->container= &$this;
				    if(STCheck::isDebug())
				    {
				        $msg= array();
				        $msg[]= "new not exist table $oTable filled with own container";
				        $msg[]= "    was cloned from $getTable";
				        STCheck::echoDebug("table", $msg);
				        STCheck::warning(!isset($oTable), "STDbSelector::getTable()", 
				            "table $sTableName do not exist inside container ".$container->getName());
				    }
				}
				if($oTable)
				{
				    $oTable->container= $this;
				    $oTable->allowQueryLimitation($this->bModifyFk);
					$this->aoToTables[$sTableName]= &$oTable;
				}else
					unset($this->aoToTables[$sTableName]);
			}else
			    $oTable= &$this->aoToTables[$sTableName];
			// alex 24/05/2005:	f�r aoToTables als Key den Namen eingef�hrt
			return $oTable;
		}
		public function allowQueryLimitation($bModify= true)
		{
		    foreach($this->aoToTables as $table)
		    {
		        if($table->Name != $this->Name)
		            $table->allowQueryLimitation($bModify);
		    }
		    STDbTable::allowQueryLimitation($bModify);
		}
		public function andWhere($table, $where= null)
		{
		    if(STCheck::isDebug())
		    {
		        STCheck::param($table, 0, "string", "STDbWhere", "STDbTable");
		        STCheck::param($where, 1, "string", "STDbWhere", "null");
		    }
		    if(!isset($where))
		    {
		        STCheck::alert(!isset($this->Name), "STDbSelector::andWhere()", "please set Before an table");
		        $where= $table;
//		        if(is_string($where))
//		            $where= new STDbWhere($where, $this->Name);
		        if( typeof($where, "STDbWhere") &&
		            $where->sDbName == ""         )
		        {
		            $where->setDatabase($this->db);
		        }		        
		    }else 
		    {
		        if(is_string($where))
		            $where= new STDbWhere($where);
		        $where->table($table);
		    }
		    STDbTable::where($where, "and");
		}
		public function orWhere($table, $where= null)
		{
		    if(STCheck::isDebug())
		    {
		        STCheck::param($table, 0, "string", "STDbWhere", "STDbTable");
		        STCheck::param($where, 1, "string", "STDbWhere", "null");
		    }
		    if(!isset($where))
		    {
		        STCheck::alert(!isset($this->Name), "STDbSelector::orWhere()", "please set Before an table");
		        $where= $table;
		        if(is_string($where))
		            $where= new STDbWhere($where, $this->Name);
		            if( typeof($where, "STDbWhere") &&
		                $where->sDbName == ""         )
		            {
		                $where->setDatabase($this->db);
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

		    if( !isset($where) ||
		        (   is_string($where) &&
		            (   $where == "" ||
		                $where == "and" ||
		                $where == "or"    )   )   )
		    {// $table should be the where statement
		        if(!isset($where))
		            $operator= "";
		        else
		            $operator= $where;
		        
	            $where= $table;
		        STDbTable::where($where, $operator);
		        return;
		    }
		    
		    if( is_string($table) )
		    {
		        $sTable= $this->container->getTableName($table);
		        STCheck::alert(!$this->db->isDbTable($sTable), "STDbSelector::where()",
		            "table '$sTable' first parameter, do not exist inside database", 1);
		        $table= $this->getTable($sTable);
		        
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
                    showLine();
                STDbTable::where($table, $where);
                return;
		    }
		    
		    if(is_string($where))
                $where= new STDbWhere($where);
            $where->table($table);
            //$where->setDatabase($table->db);
            STDbTable::where($where);
		}
		/**
		 * prepare inner join foreign key between tables
		 * 
		 * @param string|STBaseTable $fromTable set foreign key from table of this parameter
		 * @param string $fromColumn foreign key shows from this column to the other
		 * @param string|STBaseTable $toTable set foreign key to table of this parameter
		 *                                    (prototype cannot be set, only for compatibility to STBaseTable)
		 * @param string $toColumn foreign key shows from other colum to this
		 */
		function innerJoin($fromTable, $fromColumn, $toTable= null, $toColumn= null)
		{
			STCheck::param($fromTable, 0, "StBaseTable", "string");
			STCheck::param($fromColumn, 1, "string");
			STCheck::param($toTable, 2, "STBaseTable", "string");
			STCheck::param($toColumn, 3, "string", "empty(string)", "null");

			if(typeof($fromTable, "STBaseTable"))
				$fromTable= $fromTable->getName();
			else
				$fromTable= $this->getTableName($fromTable);
			if($fromTable == $this->getName())
			{
				STDbTable::innerJoin($fromColumn, $toTable, $toColumn);
				return;
			}
			if(!isset($this->aoToTables[$fromTable]))
				$this->add($fromTable);
			if(typeof($this->aoToTables[$fromTable], "STDbSelector"))
				$this->aoToTables[$fromTable]->innerJoin($fromTable, $fromColumn, $toTable, $toColumn);
			else
				$this->aoToTables[$fromTable]->innerJoin($fromColumn, $toTable, $toColumn);
		}
		/**
		 * prepare left join foreign key between tables
		 * 
		 * @param string|STBaseTable $fromTable set foreign key from table of this parameter
		 * @param string $fromColumn foreign key shows from this column to the other
		 * @param string|STBaseTable $toTable set foreign key to table of this parameter
		 *                                    (prototype cannot be set, only for compatibility to STBaseTable)
		 * @param string $toColumn foreign key shows from other colum to this
		 */
		function leftJoin($fromTable, $fromColumn, $toTable= null, $toColumn= null)
		{
			STCheck::param($fromTable, 0, "StBaseTable", "string");
			STCheck::param($fromColumn, 1, "string");
			STCheck::param($toTable, 2, "STBaseTable", "string");
			STCheck::param($toColumn, 3, "string", "empty(string)", "null");

			if(typeof($fromTable, "STBaseTable"))
				$fromTable= $fromTable->getName();
			else
				$fromTable= $this->getTableName($fromTable);
			if($fromTable == $this->getName())
			{
				STDbTable::leftJoin($fromColumn, $toTable, $toColumn);
				return;
			}
			if(!isset($this->aoToTables[$fromTable]))
				$this->add($fromTable);
			if(typeof($this->aoToTables[$fromTable], "STDbSelector"))
				$this->aoToTables[$fromTable]->leftJoin($fromTable, $fromColumn, $toTable, $toColumn);
			else
				$this->aoToTables[$fromTable]->leftJoin($fromColumn, $toTable, $toColumn);
		}
		/**
		 * prepare right join foreign key between tables
		 * 
		 * @param string|STBaseTable $fromTable set foreign key from table of this parameter
		 * @param string $fromColumn foreign key shows from this column to the other
		 * @param string|STBaseTable $toTable set foreign key to table of this parameter
		 *                                    (prototype cannot be set, only for compatibility to STBaseTable)
		 * @param string $toColumn foreign key shows from other colum to this
		 */
		function rightJoin($fromTable, $fromColumn, $toTable= null, $toColumn= null)
		{
			STCheck::param($fromTable, 0, "StBaseTable", "string");
			STCheck::param($fromColumn, 1, "string");
			STCheck::param($toTable, 2, "STBaseTable", "string");
			STCheck::param($toColumn, 3, "string", "empty(string)", "null");

			if(typeof($fromTable, "STBaseTable"))
				$fromTable= $fromTable->getName();
			else
				$fromTable= $this->getTableName($fromTable);
			if($fromTable == $this->getName())
			{
				STDbTable::rightJoin($fromColumn, $toTable, $toColumn);
				return;
			}
			if(!isset($this->aoToTables[$fromTable]))
				$this->add($fromTable);
			if(typeof($this->aoToTables[$fromTable], "STDbSelector"))
				$this->aoToTables[$fromTable]->rightJoin($fromTable, $fromColumn, $toTable, $toColumn);
			else
				$this->aoToTables[$fromTable]->rightJoin($fromColumn, $toTable, $toColumn);
		}
		public function orderBy($tableName, $column= true, $bASC= true)
		{
		    if(is_bool($column))
		    {// method is as normaly table orderBy
		        $bASC= $column;
		        $column= $tableName;
		        STDbTable::orderBy($column, $bASC);
		        return;
		    }
		    
		    $tableName= $this->container->getTableName($tableName);
		    if($tableName == $this->Name)
		    {
		        STDbTable::orderBy($column, $bASC);
		        return;
		    }
		    $table= $this->getTable($tableName);
		    $field= $table->findAliasOrColumn($column);
		    $column= $field['column'];
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
				STCheck::param($nextLine, 4, "bool");
				STCheck::echoDebug("selector", $this->Name.": select column $column($alias) for table $tableName");
			}
			$desc= STDbTableDescriptions::instance($this->db->getDatabaseName());
			$tableName= $desc->getTableName($tableName);
			$orgColumn= $desc->getColumnName($tableName, $column, /*warnFuncOutput*/1);// if tableName is original function must not search
			if(!isset($this->Name))
			{
			    $oTable= $this->db->getTable($tableName);
			    $this->createFirstOwnTable($oTable);
			}
			if(STCheck::isDebug())
			{
			    if(!isset($oTable))
			    {
    				if($tableName===$this->Name)
    					$oTable= &$this;
    				else
    					$oTable= &$this->getTable($tableName);
			    }
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
			
			$select= array(	"type"=>	"select",
			    "column"=>	$orgColumn,
							"alias"=>	$alias,
							"next"=>	$nextLine	);
			$this->aNewSelects[$tableName][]= $select;
			if(!$this->bClearedByFirstSelect)
			{
				$this->clearSelects();
				$this->bClearedByFirstSelect= true;
			}
			$this->selectA($tableName, $orgColumn, $alias, $nextLine, $add);
		}
		public function preSelect(string $tableName, $columnName, $value= null, $action= null)
		{
		    if(!isset($action))
		        $action= STINSERT;
		    $tabName= $this->getTableName($tableName);
		    if($this->Name == $tabName)
		    {
		        STDbTable::preSelect($columnName, $value, $action);
		        return;
		    }
		    $table= $this->getTable($tabName);
		    STCheck::is_error(!isset($table), "first parameter table $tableName does not exist", 2);
		    if(typeof($table, "STDbSelector"))
		        $table->preSelect($tabName, $columnName, $value, $action);
		    else
		        $table->preSelect($columnName, $value, $action);
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
		    //$pkColumnName= $this->getPkColumnName();
		    //$this->getColumn($this->Name, $pkColumnName, "nnPK@".$this->Name."@$pkColumnName");
		    
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
		 * or contain a keyword from SQL
		 *
		 * {@inheritDoc}
		 * @see STBaseTable::validColumnContent()
		 */
		public function validColumnContent($content, &$abCorrect= null, bool $bAlias= false, $aKeyword= null) : bool
		{
		    $field= null;
		    if( $this->bIsNnTable &&
		        isset($this->aNnTableColumn['fks']['join']['column']) &&
		        $content == "join@".$this->aNnTableColumn['fks']['join']['column']    )
		    {
		        $content= $this->aNnTableColumn['fks']['join']['column'];
    		    $field= array();
    		    $field['content'][]= $content;
		    }
		    $bRv= STDbTable::validColumnContent($content, $abCorrect, $bAlias, $aKeyword);
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
		public function nnTableCheckboxColumn(string $checkBoxColumnAliasName)
		{
		    STCheck::alert(!$this->bIsNnTable, "STBaseTable::nnTableColumn()", "STDbSelector is not defined as N to N table");
		    
		    $this->bNnTableColumnSelected= true;
		    $this->aNnTableColumn['alias']= $checkBoxColumnAliasName;
		    $this->select($this->aNnTableColumn['table'], $this->aNnTableColumn['column'], $checkBoxColumnAliasName);
		    STDbTable::checkBox($checkBoxColumnAliasName);
		}
		public function checkBox(string $table, $column= null, $trueValue= false)
		{
		    STCheck::param($column, 1, "string");
		    STCheck::param($trueValue, 2, "bool");
		    
		    $table= $this->getTableName($table);
		    if($table == $this->Name)
		    {
		        STDbTable::checkBox($column, $trueValue);
		        return;
		    }
		    $oTable= $this->getTable($table);
		    if(typeof($oTable, "STDbSelector"))
		        $oTable->checkBox($table, $column, $trueValue);
		    else
		        $oTable->checkBox($column, $trueValue);
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
			if($this->defaultTyp==NUM_STfetchArray)
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
			if($this->defaultTyp==NUM_STfetchArray)
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
			if($sqlType==NUM_STfetchArray)
			{
				$sqlType2= MYSQL_NUM;
				$bNormal= false;
			}elseif($sqlType==ASSOC_STfetchArray)
			{
				$sqlType2= MYSQL_ASSOC;
				$bNormal= false;
			}elseif($sqlType==BOTH_STfetchArray)
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
				return $this->getErrorId() * -1;
			$fields= $this->getSelectedFieldArray();
			$this->SqlResult= $this->db->orderDate($fields, $this->SqlResult, "need no statement");
			//echo __file__.__line__."<br />";
			//echo $statement;
			$this->db->orderDates(true);
			$this->SqlResult= $this->db->orderDate($fields, $this->SqlResult);
			if(!$bNormal)
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
			
			if(!isset($this->Name))
			{
			    $oTable= $this->db->getTable($tableName);
			    $this->createFirstOwnTable($oTable);
			}
			$this->getColumnA($tableName, array( "column"=>$column, "alias"=>$alias));
		}
		public function getErrorId() : int
		{
			return $this->errorID;
		}
		public function getErrorString() : string
		{
		    $sqlErrorMessage= $this->errorMessage;
		    return $sqlErrorMessage;
		}
		/*protected*/function setSqlError($sqlResult)
		{
  			if(!is_array($sqlResult))
  			{
  			    $this->errorID= $this->db->errno();
  			    $this->errorMessage= $this->db->getError();
  			}
		}
		function getStatement($limit= null, $withAlias= null)
		{
			$statement= STDbTable::getStatement(false, $withAlias);
			//$this->sqlStatement= $this->db->getStatement($this, false, $withAlias);
			//echo $this->sqlStatement."<br />";
			if($limit)
			    $statement.= " limit ".$limit;
			return $statement;
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
			if(	$this->defaultTyp!=NUM_STfetchArray
				and
				$this->defaultTyp!=ASSOC_STfetchArray
				and
				$this->defaultTyp!=BOTH_STfetchArray)
			{
				echo "wenn im <b>OSTDbSelector</b> eine Suche mittels Hash vorgenommen wird<br>";
				echo "muss der <b>MYSQL-Typ</b> im Konstruktor mit ";
				echo "<b>NUM_STfetchArray, ASSOC_STfetchArray</b> oder ";
				echo "<b>BOTH_STfetchArray</b> vordeffiniert werden";
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
					!$this->abOrigChoice["identif"]		)
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
						!$this->abOrigChoice["select"]			)
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