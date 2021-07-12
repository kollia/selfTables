<?php

class OSTCallbackClass
{
		var $tableName;
		var	$db;
		var	$sqlResult;
		var $showType;
		var $bNoShowType;
		var	$bSkip;
		var $where;
		var	$aDisabled;
		var $action;
		var $rownum;
		var $before;
		var	$aUnlink= array(); 	// wenn die Upgelodete Datei nicht gel�scht werden soll
							// ist hier der Alias-Name der Spalte eingetragen
		var	$nDisplayColumn= null; 	// wenn die ListTable in mehreren Columns dargestellt wird
									// wird die DisplayColumn hier gespeichert
		var $nDisplayColumns= 1; // wieviel Tabellen-Zeilen in einer Zeile dargestellt werdem
		var $arrangement; // ob die Tabelle bei STLIST horizontal oder vertikal dargestellt wird
		var $aAcessClusterColumns= array();//die Dynamic-Cluster der Tabelle
		var	$aTables;

		function OSTCallbackClass(&$container, $sqlResult)
		{
			$this->container= &$container;
			$this->db= &$container->getDatabase();
			$this->sqlResult= &$sqlResult;
			$this->clear();
		}
		// functionen f�r OSTTable
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
    		if(	typeof($where, "ostwhere") ||
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
		function disabled()
		{
			$this->aDisabled= "true";
		}
		function &getWhere()
		{
			return $this->where;
		}
		function countSqlResult()
		{
			if(!$this->count)
				$this->count= count($this->sqlResult);
			return $this->count;
		}
		function echoResult($fromRow= null, $limit= null)
		{
			if(!$this->resultShowen)
			{
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
		function setValue($value, $column= null, $rownum= null)
		{
			if($column===null)
				$column= $this->column;
			if($rownum===null)
				$rownum= $this->rownum;
			if($this->action==STLIST)
			{
				$columnPrefix= "";
				if($this->nDisplayColumns!=1)
					$columnPrefix= "###STcolumn".$this->nDisplayColumn."###_";
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
						//Tag::warning(!array_key_exists($columnPrefix.$column, $this->sqlResult), "STCallbackClass::setValue()",
						//						"the value for column $column is not set");
					}
					$this->sqlResult[$columnPrefix.$column][$rownum]= $value;
				}else
				{
					$this->sqlResult[$rownum][$columnPrefix.$column]= $value;
				}
			}else
			{
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
   					Tag::warning(1, "STCallbackClass::setValue()",
    										"the value for column $column in row $rownum is not set in the db-statement");
				}*/
				$this->sqlResult[$column]= $value;
			}
			//echo "values:";st_print_r($this->sqlResult);
		}
		function getValue($column= null, $rownum= null)
		{
			//echo "rownum:".$this->rownum." incomming ".$rownum."<br />";
			//echo "column:".$this->column." incomming ".$column."<br />";
			//echo "nDisplayColumn:".$this->nDisplayColumn."<br />";
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
						echo "toDo: auswertung der rownum in OSTCallbackClass::getValue()<br />";
						echo "wenn die Tabelle in mehreren Spalten angezeigt wird<br />";
						echo "und eine bestimmte Zeile gewuenscht wird";exit;
						$rownum= $rownum/$this->nDisplayColumns;

					}else
						$rownum= $this->rownum;
					$columnPrefix= "###STcolumn".$this->nDisplayColumn."###_";
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
						//Tag::warning(!array_key_exists($columnPrefix.$column, $this->sqlResult), "STCallbackClass::getValue()",
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
//    				Tag::warning(!array_key_exists($columnPrefix.$column, $this->sqlResult[$rownum]), "STCallbackClass::getValue()",
//    										"the value for column $column in row $rownum is not set");
    			}
				return $this->sqlResult[$rownum][$columnPrefix.$column];
			}
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
   				//Tag::warning(1, "STCallbackClass::setValue()",
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
		function &getDatabase()
		{
			return $this->db;
		}
}
?>