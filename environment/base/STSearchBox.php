<?php

require_once($_stcategorygroup);

class STSearchBox extends STCategoryGroup
{
		var $bDoneDisplay= false; // ob execute f�r Anzeige schon ausgef�hrt wurde
		var	$aDoneTables= array(); // alle Tabellen welche bearbeitet wurden
		var	$aResultIn= array(); // shows for specific table-search where the result in aTableResult
		var $aTableResult= array(); // result of content from database defined with result-nr from aRelustIn
		var $oDb;
		var $oMsg; // klasse f�r STMessageHandling
		var	$oWhere= null;
		var	$asDBTable= array();
		
		var	$sqlEffect= MYSQL_NUM;
		var $bShowAllButton= false;
		var $showAllButtonName= "l&ouml;sche Suche";
	var	$nFirstSearchColumn= null; // ob und ab welcher Spalte die erste selectierte Spalte existiert
	var $aResult= array(); // ergebnis aus der Datenbank $aResult[<tableName>]= ergebnis
	var	$aFounded= array(); // alle Values die in der Tabelle gefunden wurden $aFounded[<tableName>][<rowNr>]= array(<founded>)
	var $bDisplayByButton= false; // searchBox wird nur �ber den DisplayButton angezeigt
	var $searchInContainer= array(); // wenn nur in einem Bestimmten Container gesucht wird,
									 // springt die SearchBox durch klicken auf den SuchenButton
									 // in diesen Container, oder Tabelle
	VAR	$getParm= array(); // alle inserts, updates und deletes bei den get-vars vor der Suche
	var	$bFks= false; // ob die Tabelle �ber verbindungen abgesucht werden soll
	var $aSearched= array();
		
		function __construct(&$container, $name= "STSearchBox", $id= "STSearchBox")
		{			
			Tag::paramCheck($container, 1, "STObjectContainer");
			Tag::paramCheck($name, 2, "string");
			Tag::paramCheck($id, 3, "string");
			
			STCategoryGroup::__construct($name, $id);
			//$this->fieldset(false);
			$this->oContainer= &$container;
			$this->oDb= &$container->getDatabase();
			$this->oMsg= new STMessageHandling("STSearchBox");
			$this->oMsg->setMessageContent("BOXDISPLAY", ""); // Box wird am Bildschirm angezeigt
			$this->oMsg->setMessageContent("EMPTY_RESULT@", "kein Ergebnis fuer den Suchbegriff \"@\"");
			$this->oMsg->setMessageContent("SQL_ERROR", "Hier wird der SQL-Error Gesetzt");
			$this->oMsg->setMessageContent("NOERROR", "");
			$this->oMsg->setMessageContent("NOTODO", "");
			// create Null array for member variable
			// resultIn
		}
		function table(&$table)
		{
			Tag::paramCheck($table, 1, "STBaseTable");
			$this->asDBTable[]= &$table;
		}
		function makeButtonToShowAll()
		{
			$this->bShowAllButton= true;
		}
		function setShowAllButtonName($name)
		{
			$this->showAllButtonName= $name;
		}
		function getSearchBoxAddress($bWithNoSearchValues= true)
		{
			$params= new STQueryString();
			$params->update("stget[displaySearch]=true");
			if($bWithNoSearchValues)
				$params->delete("stget[searchbox]");
			$this->bDisplayByButton= true;
			return $params->getStringVars();
		}
		function &getDisplayButton($displayValue= "search", $bWithNoSearchValues= true, $classId= "STSearchBoxButton")
		{			
			$button= new ButtonTag($classId);
				$button->add($displayValue);
				$button->onClick("javascript:document.location.href='".$this->getSearchBoxAddress($bWithNoSearchValues)."'");
			return $button;
		}
		function inContainer($container, $table= "")
		{
			if(typeof($container, "STDbTableContainer"))
				$container= $container->getName();
			if(	$table
				and
				typeof($table, "STDbTable")	)
			{
				$table= $table->getName();
			}
			$this->searchInContainer["container"]= $container;
			if($table)
				$this->searchInContainer["table"]= $table;
		}
		function setSqlEffect($type)
		{
			$this->sqlEffect= $type;
		}
		function execute($table= null, $onError= onErrorMessage)
		{
			global	$HTTP_POST_VARS;
			
			Tag::paramCheck($table, 1, "STDbTable", "null");
			Tag::paramCheck($onError, 2, "int");
			
			Tag::echoDebug("searchBox", "starting <b>execute</b> with table ".get_class($table)."(".$table->getName().")");
			$this->oMsg->setOnErrorStatus($onError);
			
			$param= new STQueryString();
			$getParams= $param->getArrayVars();
			if($this->sMethod=="post")
				$GetPost= $HTTP_POST_VARS;
			else
				$GetPost= $getParams;
			if(isset($GetPost["stget"]["searchbox"]["searchField"]))
				$value= $GetPost["stget"]["searchbox"]["searchField"];
			else
				$value= NULL;
			
			// alex 11/10/2005:	makeBox muss vor searchValue ausgef�hrt werden
			//					da in makeBox die Values f�r die Get-Vars identifikation
			//					gesetzt wird
			if(	!$this->bDoneDisplay
				and
				(	!$this->bDisplayByButton
					or
					$getParams["stget"]["displaySearch"]=="true"	)	)
			{// damit die Box kein zweites mal erzeugt wird	
				Tag::echoDebug("searchBox", "create <b>html-tags</b> with get-params for display");
				$this->makeBox($value);
			}else
			{// in getCategoryTags will be set the get-param values.
			 // do not need the returnvalue (TableTag)
			 	Tag::echoDebug("searchBox", "create <b>get-params</b> for all CategoryGroups");
				$this->getCategoryTags();
			}		
							
			$tableName= $table->getName();
			if(	isset($value) &&
				(	!isset($this->aSearched[$tableName]) ||
					$this->aSearched[$tableName] != $value	)	)
			{
				if(Tag::isDebug())
				{
					if($table)
					{
						$tableString= "in ".get_class($table);
						$tableString.= " ".$table->getName();
					}else
						$tableString= "in all tables";
					Tag::echoDebug("searchBox", "search value '".$value."' ".$tableString);
				}	
				$this->searchValue($table, $value);
					$tr= new RowTag();
						$td= new ColumnTag(TD);
							$td->add($this->oMsg->getMessageEndScript());
						$tr->add($td);
					$this->add($tr);
				$this->aSearched[$table->getName()]= $value;
//					return $this->oMsg->getAktualMessageId();
			}
			
			if(!$this->bDoneDisplay)
			{
				$this->bDoneDisplay= true;// Box wurde erstellt
				if(!isset($value))
				{
					if(	!$this->bDisplayByButton
						or
						$getParams["stget"]["displaySearch"]=="true"	)
					{	
						$this->oMsg->setMessageId("BOXDISPLAY");
					}else
						$this->oMsg->setMessageId("NOTODO");
					$this->add($this->oMsg->getMessageEndScript());
				}				
			}	
			$messageID= $this->oMsg->getAktualMessageId();
			if($messageID=="EMPTY_RESULT@")
				$this->oWhere= null;		
			return $messageID;
		}
		function method($value)
		{
			$this->sMethod= strtolower(trim($value));
		}
		function checkBox($text, $then, $else= null)
		{		
			$this->tableCheckBox("all", $text, $then, $else);	
		}
		function getMessageContent($messageId= null)
		{
			return $this->oMsg->getMessageContent($messageId);
		}
		function withFks($fks)
		{
			Tag::paramCheck($fks, 1, "bool");
			$this->bFks= $fks;
		}
		function searchValue($oTable, $value)
		{
			if($this->bFks)
				$inColumns= $this->aIn;
			else
			{
				$columns= $this->aIn[$oTable->getName()];
				$inColumns= array();
				foreach($columns as $column)
				{
					$need= true;
					if(	isset($column["where"]) &&
						!$this->isChecked($column["where"]))
					{
						$need= false;
					}
					if($need)
						$inColumns[]= $column["column"];
				}
			}
			if($oTable)
			{
				$this->oMsg->clearMessageId();
				$this->search($oTable, $inColumns, $value, 0);
			}elseif(count($this->asDBTable))
			{
				foreach($this->asDBTable as $key=>$table)
				{
					$this->oMsg->clearMessageId();
					$this->search($table, $inColumns, $value, $key+1);
					if($this->oMsg->getAktualMessageId()!="NOERROR")
						return true;// mindestens eine Tabelle wurde bearbeitet
				}
			}else
				return false;
			
			
			$bOk= false;
			foreach($this->aResult as $result)
			{
				if($result)
					$bOk= true;
			}				
			if($bOk)
				$this->oMsg->setMessageId("NOERROR");
			else
				$this->oMsg->setMessageId("EMPTY_RESULT@", $value);//$this->oMsg->getMessageContent("EMPTY_RESULT")." \"$value\"");			
			return true;
		}
	function &getSelectedTable($tableName)
	{
		$sResultIn= $this->aResultIn[$tableName];
		if(!$sResultIn)
			return null;
		return $this->aDoneTables[$sResultIn];
	}
	function search($DBTable, $inColumn, $value, $nTable)
	{
		$where= new STDbWhere();
		$searchWhere= new STDbWhere();
			
		$orgTable= null;
		$nPkSet= -1;			
		$tableName= $DBTable->getName();
		// create an result identificator,
		// because if set it with tableName
		// the Name can be changed for joins
		$sResultIn= "s".count($this->aResultIn);
		$this->aResultIn[$tableName]= $sResultIn;
		if(count($inColumn)==0)
		{
			$inColumn= $DBTable->show;
		}else
		{
			$orgTable= $DBTable;
			if($this->bFks)
			{	
        		$needetTables= array();
				$neededTables= $inColumn;
				$count= 0;
				foreach($neededTables as $tableName=>$content)
				{
					foreach($content as $columnContent)
					{
						if(	!isset($columnContent["where"])
							or
							$this->isChecked($columnContent["where"])	)
						{
							$neededTables[$tableName]= $count;
							++$count;
						}else
							unset($neededTables[$tableName]);
					}
				}
				//if(!isset($neededTables[$DBTable->getName()]))
				//	$neededTables[$DBTable->getName()]= $count;
				$neededTables= array_flip($neededTables);
        		$firstTables= $this->oDb->getFirstSelectTableNames($neededTables);
				$show= $DBTable->show;
				$showTableName= $DBTable->getName();
				if(count($firstTables))
				{
					$oTable= $this->oContainer->getTable(reset($firstTables));
				}else
					$oTable= $DBTable;
					
		 		$DBTable= new OSTDbSelector($oTable);
				if($orgTable->isDistinct())
				{
					$DBTable->distinct();
				}
				$DBTable->clearSelects();
				foreach($show as $column)
				{
					$DBTable->select($showTableName, $column["column"], $column["alias"], $column["nextLine"]);
				}
				$DBTable->modifyForeignKey(true);
			}else
			{
				//$DBTable->clearSelects();
    			// 22/07/2006 alex:	do not konw why showTypes are deleted
				//$DBTable->showTypes= array();
				$pkName= $DBTable->sPKColumn;
				foreach($inColumn as $key=>$column)
				{
					//$DBTable->select($column);
					if($column==$pkName)
						$nPkSet= $key;
				}	
				if($nPkSet==-1)
				{// need PK for new select to the result, if self columns are defined
					if(!$DBTable->isSelect($pkName, $pkName))
					{
						$DBTable->select($pkName);
						$nPkSet= ++$key;
					}
				}	
			}
			
		}
		//echo "Wheres:";st_print_r($DBTable->getWhere(),10);
		if(Tag::isDebug("searchBox"))
		{
			Tag::echoDebug("searchBox", "search in ".($nTable+1).". table ".get_class($DBTable)."(".$DBTable->getName().")");
			echo "<b>[</b>serachBox<b>]:</b> in Columns: ";st_print_r($inColumn[$DBTable->getName()], 3, 25);echo "<br />";
		}
		$this->createSearchOuestions($DBTable, $inColumn, $value);
		$statement= $this->oDb->getStatement($DBTable);
		$this->oWhere= $DBTable->getWhere();//give the where clausl into self object for function getWhere()
		if(Tag::isDebug())
		{
		  Tag::echoDebug("searchBox", "sql-statement:");
			if(!Tag::isDebug("db.statement"))
				Tag::echoDebug("searchBox", $statement);
				
		}
		$this->aResult[$sResultIn]= $this->oDb->fetch_array($statement, $this->sqlEffect, 
															$this->oMsg->getOnErrorStatus("SQL"));
		
		if(Tag::isDebug("searchBoxResult"))
		{
			echo "<b>[</b>serachBoxResult<b>]:</b> Result is ";st_print_r($this->aResult[$tableName],2,29);echo "<br />";
		}				
		$this->aTableResult[$sResultIn]= $this->aResult[$sResultIn];
		$this->aDoneTables[$sResultIn]= &$DBTable;
		
		//echo "DBResult:";st_print_r($this->aResult[$tableName],2);if(!count($this->aResult[$tableName]))echo "<br />";				
		if(!$this->aResult[$sResultIn])
		{
			if($this->oDb->errno()!=0)
			{
				$error= $this->oDb->getError();
				if($nTable)
					$error.= "\nSTSearchBox by $nTable. table $tableName";
				$this->oMsg->setMessageId("SQL_ERROR", $error);
			}
			return;
		}
		
		$bCaseSensitive= $this->isCaseSensitiveChecked();
		$bAndWhere= $this->isAndChecked();
		$bWholeWords= $this->isWholeWordsChecked();
		
		if(	$bCaseSensitive
			or
			(	$bAndWhere
				and
				count($inColumn)>1	)
			or
			(	$bWholeWords
				and
				!$DBTable->db->getRegexpOperator()	)	)
		{// wenn in Gro�-/Kleinschreibung unterschieden werden soll,
		 // oder alle Suchstrings vorhanden sein m�ssen, wobei nicht nur in einer Spalte gesucht wird
		 //											da sonst der and-operator verwendet werden kann,
		 // oder nur nach ganzen W�rtern gesucht werden soll aber die Datenbank kennt kein REGEXP,
		 //			dann muss in den Such-Spalten beim Ergebnis
		 //			nochmals die Richtigkeit durchsucht werden
			if(!$this->aFounded[$tableName])//[<rowNr>])
				$this->aFounded[$tableName]= array();
			foreach($this->aResult[$sResultIn] as $key=>$row)
			{
				if(is_bool($bAndWhere))
					$aResult= preg_split("/ +/", $value);
				else
					$aResult= array($value);
				if($bAndWhere)// wenn eine Und-Auswahl besteht, beginne bExist mit true
					$bExist= true;
				else// bei Or mit false
					$bExist= false;
				foreach($aResult as $result)
				{
					$founded= array();
					$exist= $this->searchInResult($inColumn, $result, $row);
					if($exist)
					{
						if(!$this->aFounded[$tableName][$key])
							$this->aFounded[$tableName][$key]= array();
						$this->aFounded[$tableName][$key][]= $result;
						if(!$bAndWhere)// wenn Und-Auswahl bExist einmal auf false steht
							$bExist= true; // darf die Var niemehr auf true gesetzt werden
					}elseif($bAndWhere)
					{// bei Und-Auswahl muss alles Stimmen
						$bExist= false; // beim Ersten ist alles falsch
					}
				}
				if(!$bExist)
					unset($this->aTableResult[$sResultIn][$key]);
			}
				if(	count($this->aTableResult[$sResultIn])
					and
					count($this->aTableResult[$sResultIn][0])>$this->nFirstSearchColumn)
				{
					//l�sche wieder die Zeilen in denen gesucht wurde
					$newResult= array();
					foreach($this->aTableResult[$sResultIn] as $row)
					{
						$newRow= array();
						$count= 0;
						foreach($row as $field=>$value)
						{
							if($count==$this->nFirstSearchColumn)
								break;
							$newRow[$field]= $value;
							$count++;
						}
						$newResult[]= $newRow;
					}
					$this->aTableResult[$sResultIn]= $newResult;
				}
			//echo "Founded:";st_print_r($this->aFounded[$tableName],10);if(!count($this->aFounded[$tableName]))echo "<br />";
			//echo "finished Result:";st_print_r($this->aTableResult[$tableName],2);if(!count($this->aTableResult[$tableName]))echo "<br />";
			$this->aResult[$sResultIn]= $this->aTableResult[$sResultIn];
			
		}
		// alex: 2017/03/23
		// do not know why need this selection
		// by search() method, when no primary key was selected, again.
		// because where need the primary key?
		if(false)//$nPkSet!==-1)
		{// if nPkSet is defined,
		 // the search was not in the same columns
		 // which have the table.
		 // so make an new select on the PK's
		 // with the original columns 
		 echo __FILE__.__LINE__."<br>";
		 echo "no primary key be set, make new select<br>";
			if($this->sqlEffect==STSQL_ASSOC)
				$nPkSet= $pkName;
			$orgTable->where($pkName." ".$this->getResult_inClause($nPkSet, $tableName));
			$statement= $this->oDb->getStatement($orgTable);
			$this->aResult[$sResultIn]= $this->oDb->fetch_array($statement, $this->sqlEffect);
		}
	}
	function getWhere()
	{
		return $this->oWhere;
	}
	function searchInResult($inColumn, $value, $row)
	{
		$bCaseSensitive= $this->isCaseSensitiveChecked();
		//$bAndWhere= $this->isAndChecked();
		$bWholeWords= $this->isWholeWordsChecked();
		$value= preg_quote($value);
		$value= preg_replace("/\//", "\/", $value);
		if($bWholeWords)
			$value= "(^|[ ^�!\"�$%&/()=?`�*+~{}\[\]|;:_,.-])".$value."([ ^�!\"�$%&/()=?`�*+~{}\[\]|;:_,.-]|$)";
		$value= "/".$value."/";
		if(!$bCaseSensitive)
			$value.= "i";
		
		$bExist= false;
		if($this->bFks)
		{
		}else
		{
			foreach($inColumn as $key=>$column)
			{
				if($this->sqlEffect==MYSQL_NUM)
				{
					$result= $row[$this->nFirstSearchColumn+$key];
				}else
					$result= $row[$column];
				//echo "preg_match(\"".$value."\", \"$result\");<br />";
				$bExist= preg_match($value, $result);
				if($bExist)
					break;
			}
		}
		return $bExist;
	}
	function createSearchOuestions(&$oTable, $inColumn, $value)
	{
		$bCaseSensitive= $this->isCaseSensitiveChecked();
		$bAndWhere= $this->isAndChecked();
		$bWholeWords= $this->isWholeWordsChecked();
		$this->createWhere($oTable);

		if(	$bCaseSensitive
			or
			(	$bAndWhere
				and
				count($inColumn)>1	)
			or
			(	$bWholeWords
				and
				!$oTable->db->getRegexpOperator()	)	)
		{// wenn in Gro�-/Kleinschreibung unterschieden werden soll,
		 // oder alle Suchstrings vorhanden sein m�ssen, wobei nicht nur in einer Tabelle gesucht wird
		 //											da sonst der and-operator verwendet werden kann,
		 // oder nur nach ganzen W�rtern gesucht werden soll aber die Datenbank kennt kein REGEXP,
		 //			dann m�ssen die Spalten in denen gesucht wird
		 //			f�r sp�tere suche im Ergebnis mitselektiert werden
		 	$this->nFirstSearchColumn= count($oTable->show);
			if($this->bFks)
			{		
					foreach($inColumn as $tableName=>$content)
					{
					 		foreach($content as $forColumn)
							{
							 		$need= true;
									echo "is ".$forColumn["where"]." checked?<br />";
									if(	$forColumn["where"]
											and
											$this->isChecked($forColumn["where"]))
									{echo "yes<br />";
									 		$need= false;
									}
									if(	$need
											and
											!$oTable->isSelect($tableName, $forColumn["column"]))
									{echo "select($tableName, ".$forColumn["column"].")<br />";
											$oTable->select($tableName, $forColumn["column"]);
									}
							}
					}
			}else
			{
		 	 		foreach($inColumn as $column)
					{
					 		if(!$oTable->isSelect($column))
							{
							 		if(typeof($oTable, "OSTDbSelector"))
											$oTable->select($oTable->getName(), $column);
									else
											$oTable->select($column);
							}
					}
			}
		}
		$where= new STDbWhere();
		$where->forTable($oTable->getName());
		if(is_bool($bAndWhere))
		{
			$split= preg_split("/ +/", $value);
			foreach($split as $search)
			{
				if(trim($search))
					$this->createWhereValues($oTable->container, $where, $inColumn, $search);
			}
		}else
			$this->createWhereValues($oTable->container, $where, $inColumn, $value);
			
		$oTable->andWhere($where);
/*		{
			$newWhere= new STDbWhere();
			foreach($inColumn as $column)
			{
				if($bWholeWords)
				{
					$operator= $oTable->db->getRegexpOperator();
					if($operator)
						$value= "'(^| )".$value."( |$)'";
					else// die Datenbank hat kein regexp
						$bWholeWords= null;
				}
				if(!$bWholeWords)// kann null od. false sein
				{
					$operator= $oTable->db->getLikeOperator();
					$value= "'%".$value."%'";
				} 
				$newWhere->orWhere($column." ".$operator." ".$value);
			}
			$where= $oTable->getWhere();
			if($where && $where->isModified())
				$where->andWhere($newWhere);
			else
				$where= $newWhere;
			$oTable->where($where);
		}else
			$this->createAndOrWhere(&$oTable, $inColumn, $value);*/
	}		
	function createWhereValues(&$db, &$oWhere, $inColumn, $value)
	{
		$bAndWhere= $this->isAndChecked();
		$bWholeWords= $this->isWholeWordsChecked();
				 
		$date= $db->makeSqlDateFormat($value);
		if(!$date)
		{
			if($bWholeWords)
			{
				$operator= $db->getRegexpOperator();
				if($operator)
					$value= " '(^|[ ^�!\"�$%&/()=?`�*+~{}\[\]|;:_,.-])".$value."([ ^�!\"�$%&/()=?`�*+~{}\[\]|;:_,.-])|$)'";
				else// die Datenbank hat kein regexp
					$bWholeWords= null;
			}else // kann null od. false sein
			{
				$operator= $db->getLikeOperator();
				$value2= " '%".$value."%'";
			}
			$is= " ".$operator;//.$value;
		}else
		{
			$is= $db->getIsOperator();
			$value= $date;
			$value2= "'$date'";
		}
			
		//$aliasTable= $this->db->getAliases($this->asDBTable);
		$nSearchInColumns= 0;
		if($this->bFks)
		{		
				foreach($inColumn as $tableName=>$content)
				{
				 		foreach($content as $forColumn)
						{
						 		$need= true;
								if(	$forColumn["where"]
										and
										!$this->isChecked($forColumn["where"]))
								{
								 		$need= false;
								}
								if($need)
								{
										++$nSearchInColumns;
										$sSearchInTable= $tableName;
										$doSearch[0]= $forColumn;
								}
						}
				}
		}else
		{
				$nSearchInColumns= count($inColumn);
				$doSearch= $inColumn;
		} 
		if(	$nSearchInColumns==1
			and
			$bAndWhere==true		)
		{
			$oWhere->andWhere($inColumn[0].$is.$value2);
			if($sSearchInTable)
					$oWhere->toTable($sSearchInTable);
		}else
		{
		 	if($this->bFks)
			{
				foreach($inColumn as $tableName=>$content)
				{
						$table= &$db->getTable($tableName);
				 		$newWhere= new STDbWhere();
						$newWhere->forTable($tableName);
				 		foreach($content as $forColumn)
						{
						 		$need= true;
								$key= $table->getColumnKey($forColumn["column"]);
								$type= $table->columns[$key]["type"];
								if(	$forColumn["where"]
									and
									!$this->isChecked($forColumn["where"]))
								{// if an where clausel set, look for where clausel is checked from user
								 		$need= false;	// to need this column in search
								}
								if($need)
								{
									$is= $db->getIsOperator();
									if(	$type=="int"
										or
										$type=="real"	)
									{
										$numbers= "[0-9";
										if($type=="real")
											$numbers.= ",";
										$numbers.= "]+";
										if(preg_match("/^[ ]*(-)?(".$numbers.")[ ]*((-)[ ]*(".$numbers.")?[ ]*)?$/", $value, $preg))
										{
											//st_print_r($preg);
											if($preg[1]=="-")
											{
												$newWhere->orWhere($forColumn["column"].$db->getLowerEqualOperator().$preg[2]);
											}elseif($preg[4]=="-")
											{
												if(isset($preg[5]))
												{
													$between= new STDbWhere($forColumn["column"].$db->getGreaterEqualOperator().$preg[2]);
													$between->andWhere($forColumn["column"].$db->getLowerEqualOperator().$preg[5]);
													$newWhere->orWhere($between);
												}else
													$newWhere->orWhere($forColumn["column"].$db->getGreaterThanOperator().$preg[2]);
												
											}else
												$newWhere->orWhere($forColumn["column"].$is.$preg[2]);
										}
									}else
									{
										$newWhere->orWhere($forColumn["column"]." ".$db->getLikeOperator()." ".$value2);
									}
									
								}
						}
						$oWhere->orWhere($newWhere);
				}
			}else
			{
			 		foreach($inColumn as $column)
					{
					 		//$alias= $aliasTable[$column["table"]];
							//if($alias)				alex 29.12.2004: 	alias wird in der OSTDatabase erzeugt
							//$where.= $alias.".";						wenn n�tig
							$oWhere->orWhere($column.$is.$value2);							
					}
			}
		}
	}
		function &getResult_array($tableName= null)
		{
			if($this->aResult===NULL)
				return $this->aResult[-1];
			if(	$tableName==null
				and
				count($this->aResult)==1)
			{
				reset($this->aResult);
				$tableName= key($this->aResult);
			}
			if(!$tableName)
			{
				echo "<b>Error:</b> its are more than one table set in OSTSearchBox<br />";
				echo "       so must given tableName in function ->getResult_array()";
				exit;
			}
			if(	isset($this->aResultIn[$tableName]) )
				$resultNr= $this->aResultIn[$tableName];
			else
				$resultNr= -1;
			return $this->aResult[$resultNr];
		}
		function getResult_single_array($nRow= 0, $tableName= null)
		{
			if(STCheck::isDebug())
			{
				if($this->sqlEffect==STSQL_ASSOC)
					Tag::paramCheck($nRow, 1, "string");
				else
					Tag::paramCheck($nRow, 1, "int");
				Tag::paramCheck($tableName, 2, "string", "null");					
			}
			
			$result= &$this->getResult_array($tableName);
			if(!count($result))
				return $result;
			$aRv= array();
			foreach($result as $row)
			{
				if($nRow===0)
					$aRv[]= reset($row);
				else
					$aRv[]= $row[$nRow];
			}
			return $aRv;
		}
		function getResult_inClause($nRow= 0, $tableName= null)
		{
			if(STCheck::isDebug())
			{
				if($this->sqlEffect==STSQL_ASSOC)
					Tag::paramCheck($nRow, 1, "string");
				else
					Tag::paramCheck($nRow, 1, "int");
				Tag::paramCheck($tableName, 2, "string", "null");					
			}
			
			$result= &$this->getResult_array($tableName);
			if(!count($result))
				return $result;
			$sRv= "";
			foreach($result as $row)
			{
				if($nRow===0)
					$value= reset($row);
				elseif(isset($row[$nRow]))
					$value= $row[$nRow];
				else
					$value= "";
				if(!is_numeric($value))
					$value= "'$value'";
				$sRv.= $value.",";
			}
			$sRv= "in(".substr($sRv, 0, strlen($sRv)-1).")";
			return $sRv;			
		}
	//deprecated
	function inColumn($tableName, $column, $whereText= null)
	{
		if(	!isset($this->aIn[$tableName]) ||
			!is_array($this->aIn[$tableName])	)
		{
			$this->aIn[$tableName]= array();
		}
		$count= count($this->aIn[$tableName]);
		$this->aIn[$tableName][$count]["column"]= $column;
		if($whereText)
			$this->aIn[$tableName][$count]["where"]= $whereText; 
		/*if(!isset($this->aIn[$tableName]))
		{
			$aoTable[$tableName]= $this->oDb->getTable#$tableName);
			$aoTable[$tableName]->clearSelects();
		}
		$aoTable[$tableName]->select($column);*/
	}
	function insertBySearch($param)
	{
		$this->getParm[STINSERT][]= $param;
	}
	function updateBySearch($param)
	{
		$this->getParm[STUPDATE][]= $param;
	}
	function deleteBySearch($param)
	{
		$this->getParm[STDELETE][]= $param;
	}
		function makeBox($value)
		{
			$tableName= $this->oContainer->getTableName();
			$getHtml= new STQueryString();
			if($getHtml->defined("stget[searchbox]"))
				$getHtml->delete("stget[firstrow][".$tableName."]");
			$getHtml->delete("stget[searchbox]");
			if($this->bDisplayByButton)
				$getHtml->delete("stget[displaySearch]");
			if(count($this->searchInContainer))
			{
				$getHtml->update("stget[container]=".$this->searchInContainer["container"]);
				$getHtml->update("stget[table]=".$this->searchInContainer["table"]);
				$getHtml->update("stget[action]=".STLIST);
			}
			foreach($this->getParm as $action=>$do)
			{
				foreach($do as $param)
				{
					if($action==STINSERT)
						$getHtml->insert($param);
					elseif($action==STUPDATE)
						$getHtml->update($param);
					elseif($action==STDELETE)
						$getHtml->delete($param);
				}
			}
			$getHtml->insert("stget[firstrow][".$tableName."]=0");
			$getparams= $getHtml->getStringVars();

			$form = new FormTag();
				$form->action($getparams);
				$form->method($this->sMethod);
				$tr= new RowTag();
					$td= new ColumnTag(TD);
					if($this->sMethod=="get")
						$hidden= $getHtml->getHiddenParams();
						$td->inherit= $hidden->inherit;						
						$td->width(80);
						$td->align("right");
						$td->add("suche nach:");
					$tr->add($td); 
					$td= new ColumnTag(TD);
						$td->width(170);
						$input= new InputTag("field");
							$input->type("text");
							$input->name("stget[searchbox][searchField]");
							$input->size(28);
  							$input->value($value);
						$td->add($input);	
					$tr->add($td); 
					$td= new ColumnTag(TD);
						//$td->width(100);
						//$td->rowspan(2);
						$td->valign("top");
						$input= new InputTag("button");
							$input->type("submit");
							$input->value("suchen");
						$td->add($input);
						
			if($this->bShowAllButton)
			{// zeige Alles Button
				$getHtml->delete("stget[firstrow][".$tableName."]");
				$getHtml->delete("stget[searchbox]");
				$newUrl= $getHtml->getUrlVars();
				if($newUrl == "")
					$newUrl= "?";
						$td->add(br());
						$input= new InputTag("button");
							$input->type("button");
							$input->value($this->showAllButtonName);
							$input->onClick("javascript:location.href='$newUrl'");
						$td->add($input);
			}
					$tr->add($td);					
				//$form->add($tr);
				$table= new TableTag("STCategoryGroup");
				$table->add($tr);
				
			/*if($this->choiseWhere)
			{// checkBoxen vom member choiseWhere
			
						$tr= new RowTag();							
				$texte= array();
				foreach($this->choiseWhere as $fromTable)
				{
					foreach($fromTable as $text=>$arts)
					{
						if(!$texte[$text])
						{
							$texte[$text]= "is shown"; 
							$td= new ColumnTag(TD);
								$td->colspan(2);
								$input= new InputTag();
									$input->type("checkbox");
									$input->name("stget[searchbox][check][".$this->checkBoxNrs[$text]."]");
									$input->value("true");
							if($HTTP_GET_VARS["stget"]["searchbox"]["check"][$this->checkBoxNrs[$text]])
									$input->checked();
								$td->add($input);
								$td->add($text);
							$tr->add($td);								
						}
					}
				}
				$form->add($tr);
			}*/
			
			
			
			// alle Kategorien hinzuf�gen
			$category= $this->getCategoryTags();
			if($category)
			{
				$tr= new RowTag();
					$td= new ColumnTag(TD);
						$td->colspan(3);
						$td->add($category);
					$tr->add($td);
				$table->add($tr);
			}
				
			$tr= new RowTag();
			$td= new ColumnTag(TD);
			$td->add($table);
			$tr->add($td);
			$form->add($tr);
			$this->add($form);			
		}
}


?>