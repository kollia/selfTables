<?php

class STCallbackClass
{
		var $table;
		var $container;
		var	$db;
		var	$sqlResult;
		/**
		 * current action of building box
		 * ( STLIST / STINSERT / STUPDATE / STDELETE )
		 * @var string
		 */
		public $action;
		/**
		 * defined names for update and delete columns
		 * @var array
		 */
		public $aAction= array();
		/**
		 * current column, used
		 * if user not define in some functions
		 * (maybe <code>getValue()</code> <code>setValue()</code>
		 * @var string
		 */
		public $column;
		/**
		 * current row number, used
		 * if user not define in some functions
		 * (maybe <code>getValue()</code> <code>setValue()</code>
		 * @var integer
		 */
		public $rownum;
		/**
		 * struct of all boolean behavior arguments
		 * (this time only 
		 * @var array
		 */
		private $aBehavior= array();
		/**
		 * filled array with all columns
		 * in which state (STList/STInsert/STUpdate)
		 * should be disabled by displaying on Browser
		 * @var array
		 */
		var $aDisabled= array();
		var $showType;
		var $bNoShowType;
		var	$bSkip;
		var $where;
		/**
		 * whether callback routine
		 * is running before (<code>true</code>) creating database entries
		 * or after (<code>false</code>)
		 * @var boolean
		 */
		public $before= null;
		/**
		 * whether routine running for display (<code>true</code>).<br />
		 * (all STListBox and STItemBox for showing gui element)
		 * or routine running after user action (display:<code>false</code>) for changing Database
		 * (STItemBox for INSERT or UPDATE after displaying)
		 * @var boolean
		 */
		public $display= null;
		var $MessageId;
		var $joinResult;
		/**
		 * if the uploaded file should not be deleted,
		 * the alias name of the column is entered here
		 * @var array
		 */
		var	$aUnlink= array();
		/**
		 * name of current displayed column
		 * @var string
		 */
		var	$sCurrentColumn= null;
		/**
		 * number of columns displayed in one row
		 * @var integer
		 */  	
		var $nDisplayColumns= 1;
		/**
		 * arrangement of listing table (STListBox)
		 * whether is horizontal or vertical
		 * @var enum
		 */
		var $arrangement;
		/**
		 * all dynamic clusters of table
		 */
		var $aAcessClusterColumns= array();
		var	$aTables;
		/**
		 * for debugging, whether sql result was showen the first time
		 * @var boolean
		 */
		private $resultShowen= false;
		/**
		 * if variable is -1, here will be stored the count of exiting sql rows
		 * @var integer
		 */
		private $count= -1;
		/**
		 * html content should filled into body tag
		 * @var array
		 */
		private $aHtmlContent= null;

		public function __construct(STDbTable &$table, array &$sqlResult)
		{
		    $this->table= &$table;
			$this->container= &$table->container;
			$this->db= &$this->container->getDatabase();
			$this->sqlResult= &$sqlResult;
			$this->clear();
		}
		// functions for STListBox
		function clear()
		{
			$this->showType= null;
			$this->bNoShowType= false;
			$this->bSkip= false;
		}
		function image()
		{
			$this->showType= "image";
		}
		function imageLink()
		{
			$this->showType= "imageLink";
		}
		function link()
		{
			$this->showType= "link";
		}
		function namedLink()
		{
			$this->showType= "namedlink";
		}
		function popUpSelect()
		{
			$this->showType= "popup";
		}
		function checkBoxes()
		{
			$this->showType= "check";
		}
		function noShowType()
		{
			$this->bNoShowType= true;
		}
		function skipRow()
		{
			$this->bSkip= true;
		}
		/*public*/function setWhere($where)
		{
    		if(	typeof($where, "stwhere") ||
    			typeof($where, "STDbWhere")	)
    		{
    			$this->where= $where;
    		}else
    			$this->where= new STDbWhere($where);
		}
		function andWhere($where)
		{
			if(!$where)
				$this->setWhere($where);
			else
				$this->where->andWhere($where);
		}
		function orWhere($where)
		{
			if(!$where)
				$this->setWhere($where);
			else
				$this->where->orWhere($where);
		}
		public function argument(string $argument, string $column, int $rownum)
		{
		    if(STCheck::isDebug())
		    {
		        STCheck::param($argument, 0, "check", $argument=="enabled"||$argument=="disabled", 
		            "enabled", "disabled");
		    }
			$column= $this->setColumnToUnderlinedAliasIfNecessary($column);
		    $bRv= false;
		    if($argument == "disabled")
		    {
		        $defArgument= "enabled";
		        $bRv= true;//default
		    }else
		        $defArgument= $argument;
	        if(isset($this->aBehavior[$column][$rownum][$defArgument]))
	            $bRv= $this->aBehavior[$column][$rownum][$defArgument];
	        if($argument == "disabled")
	            $bRv= !$bRv;
		    return $bRv;
		}
		private function setBehavior(string $argument, string $column= null, int $rownum= null)
		{
		    if(!isset($column))
		        $column= $this->column;
			$column= $this->setColumnToUnderlinedAliasIfNecessary($column);
		    if(!isset($rownum))
		        $rownum= $this->rownum;
		    $bSet= true;
		    $argument= strtolower($argument);
		    if($argument == "disabled")
		    {
		        $argument= "enabled";
		        $bSet= false;
		    }
		    $this->aBehavior[$column][$rownum][$argument]= $bSet;
		}
		public function enabled(string $column, int $rownum= null)
		{
			$column= $this->setColumnToUnderlinedAliasIfNecessary($column);
		    $this->setBehavior("enabled", $column, $rownum);
		}
		public function disabled(string $column, int $rownum= null)
		{
			//$column= $this->setColumnToUnderlinedAliasIfNecessary($column);
		    $this->setBehavior("disabled", $column, $rownum);
		}
		function &getWhere()
		{
			return $this->where;
		}
		function countSqlResult()
		{
			if($this->count == -1)
				$this->count= count($this->sqlResult);
			return $this->count;
		}
		function echoResult($fromRow= null, $limit= null)
		{
			if(!$this->resultShowen)
			{
				if($this->display)
					echo "callback performed to <b>display</b> box<br />";
				else
				{
					echo "callback was executed <b>";
					if($this->before)
						echo "before";
					else
						echo "after";
					echo "</b> changes are made in the database<br />";
				}
				$countResult= $this->countSqlResult();
				if($fromRow===null)
				{
					$fromRow= 0;
					$limit= $countResult;
				}elseif($limit===null)
				{
					$limit= $fromRow;
					$fromRow= 0;
				}
				//echo "fromRow:";st_print_r($fromRow);echo "<br />";
				//echo "limit:";st_print_r($limit);echo "<br />";
				//echo "rownum:";st_print_r($this->rownum);echo "<br />";
				if($fromRow==($this->rownum))
				{
					if($limit>$countResult)
						$limit= $countResult;

					if( ( $fromRow || $limit!=$countResult ) && $limit )
					{
						$result= array();
						for($o= $fromRow; $o<($fromRow+$limit); $o++)
						{
							$result[$o]= $this->sqlResult[$o];
						}
					}else
						$result= $this->sqlResult;
					$this->resultShowen= true;
					//if($limit)
					//{
						if($this->sqlResult)
						{
							echo "<b>callbackResult:</b>";
							st_print_r($this->sqlResult, 2);
						}else
							echo "<b>no Result set in callback</b><br />";
					//}
					return true;
				}
			}
			return false;
		}
		public function noUpdate($rownum= null)
		{
			if(!isset($this->aAction['update']))
				return; // method should only for STLIST action
		    $this->setValue(null, $this->aAction['update'], $rownum);
		}
		public function noDelete($rownum= null)
		{
			if(!isset($this->aAction['delete']))
				return; // method should only for STLIST action
		    $this->setValue(null, $this->aAction['delete'], $rownum);
		}
		/**
		 * currently on April 2024 for action STINSERT or STUPDATE
		 * name of input-tags are displayed as alias columns with underlines for spaces.
		 * So do if ask for an column try to change it if necessary
		 * (It's an ugly hack I know, but fast enough for now)
		 * 
		 * @param string $column name of column in database
		 * @return string alias with underline if before exist, otherwise the column
		 */
		public function setColumnToUnderlinedAliasIfNecessary(string $column) : string
		{
			if(!isset($this->sqlResult[$column]))
			{
				$alias= $this->table->defineDocumentItemBoxName($column);
				if(isset($this->sqlResult[$alias]))
					return $alias;
			}
			return $column;
		}
		public function setValue($value, $column= null, $rownum= null)
		{
			if($column===null)
				$column= $this->column;
			if($rownum===null)
				$rownum= $this->rownum;
			if($this->action==STLIST)
			{
				$columnPrefix= "";
				if($this->nDisplayColumns!=1)
					$columnPrefix= "###STcolumn".$this->sCurrentColumn."###_";
				if($this->arrangement==STVERTICAL)
				{
					if(!array_key_exists($columnPrefix.$column, $this->sqlResult))
					{// for older versions
						foreach($this->aTables as $tableName)
						{
							if(@array_key_exists($this->sqlResult[$columnPrefix.$tableName."@".$column]))
							{
								$column= $tableName."@".$column;
								break;
							}
						}
						//STCheck::is_warning(!array_key_exists($columnPrefix.$column, $this->sqlResult), "STCallbackClass::setValue()",
						//						"the value for column $column is not set");
					}
					$this->sqlResult[$columnPrefix.$column][$rownum]= $value;
				}else
				{
					$this->sqlResult[$rownum][$columnPrefix.$column]= $value;
				}
			}else
			{
				$column= $this->setColumnToUnderlinedAliasIfNecessary($column);
    			if(!array_key_exists($column, $this->sqlResult))
    			{// for older versions
    				foreach($this->aTables as $tableName)
    				{
    					if(array_key_exists($tableName."@".$column, $this->sqlResult))
    					{
    						$column= $tableName."@".$column;
    						break;
    					}
    				}
    			}
	/*			if(	STCheck::isDebug()
					and
					!array_key_exists($column, $this->sqlResult)	)
				{
					echo "\n<br />existing keys in sqlResult:<br />";
					foreach($this->sqlResult as $key=>$value)
						echo $key."<br />";
   					STCheck::is_warning(1, "STCallbackClass::setValue()",
    										"the value for column $column in row $rownum is not set in the db-statement");
				}*/
				$this->sqlResult[$column]= $value;
			}
			//echo "values:";st_print_r($this->sqlResult);
		}
		public function addHtmlContent(array|string|Tag $content)
		{
			$this->aHtmlContent[]= $content;
		}
		public function &getHtmlContent()
		{
			return $this->aHtmlContent;
		}
		public function getAllValues() : array
		{
			return $this->sqlResult;
		}
		public function getValue($column= null, $rownum= null)
		{
			//echo "rownum:".$this->rownum." incomming ".$rownum."<br />";
			//echo "column:".$this->column." incomming ".$column."<br />";
			//echo "sCurrentColumn:".$this->sCurrentColumn."<br />";
			//echo "nDisplayColumns:".$this->nDisplayColumns."<br />";
			if($column===null)
				$column= $this->column;

			if($this->action==STLIST)
			{
				$columnPrefix= "";
				if($this->nDisplayColumns!==1)
				{
					if($rownum!==null)
					{
						echo "toDo: auswertung der rownum in STCallbackClass::getValue()<br />";
						echo "wenn die Tabelle in mehreren Spalten angezeigt wird<br />";
						echo "und eine bestimmte Zeile gewuenscht wird";exit;
						$rownum= $rownum/$this->nDisplayColumns;

					}else
						$rownum= $this->rownum;
					$columnPrefix= "###STcolumn".$this->sCurrentColumn."###_";
				}elseif($rownum===null)
					$rownum= $this->rownum;
				if($this->arrangement==STVERTICAL)
				{
					if(!array_key_exists($columnPrefix.$column, $this->sqlResult))
					{// for older versions
						foreach($this->aTables as $tableName)
						{
							if(array_key_exists($columnPrefix.$tableName."@".$column, $this->sqlResult))
							{
								$column= $tableName."@".$column;
								break;
							}
						}
						//STCheck::is_warning(!array_key_exists($columnPrefix.$column, $this->sqlResult), "STCallbackClass::getValue()",
						//						"the value for column $column in row $rownum is not set");
					}
					if(isset($this->sqlResult[$columnPrefix.$column][$rownum]))
						return $this->sqlResult[$columnPrefix.$column][$rownum];
					return null;
				}
    			if(!array_key_exists($columnPrefix.$column, $this->sqlResult[$rownum]))
    			{// for older versions
    				foreach($this->aTables as $tableName)
    				{
    					if(array_key_exists($columnPrefix.$tableName."@".$column, $this->sqlResult[$rownum]))
    					{
    						$column= $tableName."@".$column;
    						break;
    					}
    				}
//    				STCheck::is_warning(!array_key_exists($columnPrefix.$column, $this->sqlResult[$rownum]), "STCallbackClass::getValue()",
//    										"the value for column $column in row $rownum is not set");
    			}
				return $this->sqlResult[$rownum][$columnPrefix.$column];
			}
			$column= $this->setColumnToUnderlinedAliasIfNecessary($column);
  			if(	is_array($this->sqlResult) &&
  				!array_key_exists($column, $this->sqlResult)	)
  			{// for older versions
  				foreach($this->aTables as $tableName)
  				{
  					if(array_key_exists($tableName."@".$column, $this->sqlResult))
  					{
  						$column= $tableName."@".$column;
  						break;
  					}
  				}
  			}

			/*if(	STCheck::isDebug()
				and
				!array_key_exists($column, $this->sqlResult)	)
			{
				echo "\n<br />existing keys in sqlResult:<br />";
				foreach($this->sqlResult as $key=>$value)
					echo $key."<br />";
   				//STCheck::is_warning(1, "STCallbackClass::setValue()",
    			//						"the value for column $column in row $rownum is not set in the db-statement");
			}*/
  			if(isset($this->sqlResult[$column]))
  				return $this->sqlResult[$column];
			return null;
		}
		function getRowNum()
		{
			return $this->rownum;
		}
		function getAction()
		{
			return $this->action;
		}
		function isBefore()
		{
			return $this->before;
		}
		function noUnlinkData($column)
		{
			$this->aUnlink[$column]= false;
		}
		public function &getDatabase()
		{
			return $this->db;
		}
		/**
		 * get table from current container,
		 * if no name given, current table from selection will be returned
		 * 
		 * @param string $tableName name of table
		 * @return STDbTable table object
		 */
		public function getTable(string $tableName= null)
		{
		    if(!isset($tableName))
		        return $this->table;
		    return $this->db->getTable($tableName);
		}
}
?>