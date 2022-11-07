<?php


require_once($_stbasetablebox);
require_once($_stdbselector);


class STListBox extends STBaseTableBox
{
		var $arrangement;
		var $statement;
		var $SqlResult;
		var $aGetParams;
		var	$bGetedSearchboxResult= false;
		var $address;
		var $dbCheck;
		var $aErrorString;
		var $Error;
		var $log;
		var $checkboxes;
		var $insertStatement;
		var $deleteStatement;
		var $showTypes;
		var	$oGet; // Objekt f�r vorhandene Parameter (STQueryString)
		var $nShowFirstRow= 0;
		var $bDropIndexButton= false;
		var	$bSetLinkByNull= null;
		var	$setParams= array(); // welche Parameter in der URI gesetzt werden sollen
		var $bCaption= true; // Beschriftung (�berschrift) der Tabelle
		var $limitColumnName; // nach welcher Column limitiert wird
		var $shownLimitValues;  // hier werden alle Pks f�r die limitation aufgelistet
								// da der einsprungpunkt in die Tabelle nicht die erste ist
		var	$bContainerManagement= true; // ob die Container in das older verschoben werden soll
		var $bLinkAccess= array(); // ob auf einen Link Zugriff besteht
		var $dateIndex;
		var $aSorts= null;

		function __construct(&$container, $class= "STTable", $logTime= false)
		{
			Tag::paramCheck($container, 1, "STBaseContainer");
			Tag::paramCheck($class, 2, "string");

			STBaseTableBox::__construct($container, $class);
			$this->logTime($logTime);

			if($this->log)
			{
				global $user;
				$debug= $user->isDebug();
				$user->debug(true);
				$user->LOG("start STListBox->Constructor", 0);
				$user->debug($debug);
			}

			$this->init();

			if($this->log)
			{
				$debug= $user->isDebug();
				$user->debug(true);
				$user->LOG("end STListBox->Constructor", 0);
				$user->debug($debug);
			}
		}
		function init()
		{
			$this->arrangement= STHORIZONTAL;
			$this->SqlResult= null;
			$this->address= array();
			$this->checkboxes= array();
			$this->showTypes= array();
			$this->oGet= new STQueryString();
			$this->setParams["onActivate"]= array();
			$this->setParams["asParam"]= array();
		}
		function createMessages()
		{
			STBaseTableBox::createMessages();
			if($this->language == "de")
			{
				$this->msg->setMessageContent("NO_SOLUTION", "zuerst muss die Funktion solution() oder table() aufgerufen werden!");
				$this->msg->setMessageContent("NO_CHECKONDB", "vor dem display-Aufruf muss der Funktion checkOnDb() ein DB-Statement uebergeben werden");
				$this->msg->setMessageContent("NO_CHANGING", "beim Checkbox-Abgleich wurde nichts veraendert");
				$this->msg->setMessageContent("WRONG_INSERT", "falsche Spalten-Angabe in Funktion insert() Spalte");
				$this->msg->setMessageContent("WRONG_DELETE", "falsche Spalten-Angabe in Funktion delete() Spalte");
				$this->msg->setMessageContent("SQL_ERROR", "hier kommt der Fehler der SQL-Datenbank hinein");
				$this->msg->setMessageContent("EMPTY_SEARCHBOX_RESULT", "");
				$this->msg->setMessageContent("NNTABLEINSERT_MUCH@2", "fuer den neuen Insert der n zu n Table sind zu viele Ergebnisse fuer die Spalte \"@\", im where-statement vorhanden");
				$this->msg->setMessageContent("NNTABLEINSERT_FAULT@", "fuer den neuen Insert der n zu n Table ist die Spalte \"@\" nicht gesetzt");
				$this->msg->setMessageContent("NOPK_FORDBCHANGE@", "fuer die aenderung in der Datenbank ist der primaerere Schluessel \"@\" nicht im Ergebnis.");
				
			}else // langauge have to be english ('en')
			{
				$this->msg->setMessageContent("NO_SOLUTION", "first have to call the funktion's solution() or table()!");
				$this->msg->setMessageContent("NO_CHECKONDB", "before calling method of display(), need to define database statement with checkOnDb()");
				$this->msg->setMessageContent("NO_CHANGING", "by checkboxing wasn't any changing");
				$this->msg->setMessageContent("WRONG_INSERT", "wrong number of columns in method insert()");
				$this->msg->setMessageContent("WRONG_DELETE", "wrong number of columns in method delete()");
				$this->msg->setMessageContent("SQL_ERROR", "here is the place for the sql-error from database");
				$this->msg->setMessageContent("EMPTY_SEARCHBOX_RESULT", "");
				$this->msg->setMessageContent("NNTABLEINSERT_MUCH@2", "for the new insert of n to n table, exist to much values in column \"@\", of where-statement");
				$this->msg->setMessageContent("NNTABLEINSERT_FAULT@", "for the new insert of n to n table is column \"@\" not filled");
				$this->msg->setMessageContent("NOPK_FORDBCHANGE@", "for this changing in databse is the primary key \"@\" not in the result.");
			}
		}
		function insertParam($param, $column= STALLDEF)
		{
			$this->aGetParams[STINSERT][$column][]= $param;
		}
		function updateParam($param, $column= STALLDEF)
		{
			$this->aGetParams[STUPDATE][$column][]= $param;
		}
		function deleteParam($param, $column= STALLDEF)
		{
			$this->aGetParams[STDELETE][$column][]= $param;
		}
		function resetParams()
		{
			$this->oGet->resetParams();
		}
		function hasAccess($aliasColumnName, $access)
		{// Zugriff f�r links
			$this->bLinkAccess[$aliasColumnName]= $access;
		}
		function doContainerManagement($bManagement)
		{
			$this->bContainerManagement= $bManagement;
		}
		function logTime($bLog= true)
		{
			if($bLog)
				$this->log= true;
			else
				$this->log= false;
		}
		function checkOnDb($statement)
		{
			$this->dbCheck= $statement;
		}
		function check()
		{
			if($this->log)
			{
				global $user;
				$debug= $user->isDebug();
				$user->debug(true);
				$user->LOG("start STListBox->check", 1);
				$user->debug($debug);
			}

			if(!$this->dbCheck)
			{
				$this->msg->setMessageId("NO_CHECKONDB");
				return null;
			}
			$result= $this->db->fetch_single($this->dbCheck, $this->getOnError("SQL"));
			if(!$result)
				return null;

			if($this->log)
			{
				$debug= $user->isDebug();
				$user->debug(true);
				$user->LOG("end STListBox->check", 1);
				$user->debug($debug);
			}
			if(count($result))
				return "Y";
			return "N";
		}
		function solution($statement)
		{// veraltete Version
  		if(is_array($statement))
			{
				$this->SqlResult= $statement;
				return;
			}
			$this->statement= $statement;
		}
		function getColumn($column)
		{
			$this->showTypes[$column]= "get";
		}
		function image($column)
		{
			$this->showTypes[$column]= "image";
		}
		function imageLink($column, $address= null)
		{
			$this->showTypes[$column]= "imageLink";
			if($address)
				$this->address[$column]= $address;
		}
		function link($column, $address= null)
		{
			$this->showTypes[$column]= "link";
			if($address)
				$this->address[$column]= $address;
		}
		function namedLink($column, $address= null)
		{
			$this->showTypes[$column]= "namedlink";
			if($address)
				$this->address[$column]= $address;;
		}
		function checkBoxes($column)
		{
			$this->showTypes[$column]= "check";
			if($this->asDBTable)
				$this->asDBTable->bIsNnTable= true;
		}
		function takeTypesFromTable()
		{
			$table= &$this->getTable();
			if(!$table)
				return;
			if($this->bSetLinkByNull===null)
				$this->bSetLinkByNull= $table->bSetLinkByNull;

			// aufbau von showTypes in STDBTables ist nicht gleich
			// wie in STListBoxs, da dort mehrere Typen auf eine Column
			// kommen k�nnen. zb. 'update' und 'image'
			foreach($table->showTypes as $column=>$types)
			{
				foreach($types as $type=>$value)
				{
					// alex 19/04/2005:	entferne extra-Abfrage
					//					und ersetze sie mit
					//					if(type!="update") (wenn nicht update dann)
					//					dadurch sind weniger Abfragen
					//					und in den Funktionen ist sowiso nur 1 Befehl
					/*if($type=="image")
						$this->image($column);
					elseif($type=="imagelink")
						$this->imageLink($column);
					elseif($type=="link")
						$this->link($column);
					elseif($type=="namedlink")
						$this->namedLink($column);*/
					if(	$column!="valueColumns"	// values for showType namedcolumnlink
						and
						$type!=STUPDATE			)
					{
						$this->showTypes[$column]= $type;
					}
				}
			}
		}
	/*protected*/function makeCallback($action, &$oCallbackClass, $columnName, $rownum)
	{
		if(STCheck::isDebug())
		{
			STCheck::echoDebug("callback", "makeCallback(ACTION:'$action', CALLBACKCLASS("
									.get_class($oCallbackClass)."), COLUMN:'$columnName', ROWNUM:$rownum)");
			STCheck::param($action, 0, "string");
			STCheck::param($oCallbackClass, 1, "STCallbackClass");
			STCheck::param($columnName, 2, "string");
			STCheck::param($rownum, 3 , "int", "string");
		}

		$aliases= array();
		$this->oSelector->createAliases($aliases);
		$aliases= array_flip($aliases);
		$oCallbackClass->aTables= $aliases;
		return STBaseTableBox::makeCallback($action, $oCallbackClass, $columnName, $rownum);
	}
	function createStatement()
	{
		$inTableFirstRow= 0;
		$params= new STQueryString();
		$HTTP_GET_VARS= $params->getArrayVars();

  		if(!$this->statement)
  		{
			$oTable= &$this->getTable();
			$tableName= $oTable->getName();
			$from= $oTable->getFirstRowSelect();			
			if(	$from == 0 &&
				isset($HTTP_GET_VARS["stget"]["firstrow"][$tableName])	)
			{
				$from= $HTTP_GET_VARS["stget"]["firstrow"][$tableName];
				STCheck::echoDebug("db.statements.limit", "set limit selection from row to $from");
				$oTable->setFirstRowSelect($from);
			}
  			// es wurde als Tabelle ein STDbTable angegeben
			// um die where Clausel in einer Callback-Funktion
			// neu zu generieren, erzeuge auch den IndexTable
			//$this->getIndexTable();
			// to generate new the where clausel
			if(isset($this->asDBTable->aCallbacks[STLIST]))
			{
				$callbackClass= new STCallbackClass($this->tableContainer, $this->sqlResult);
				$callbackClass->indexTable= &$this->oIndexTable;
				$callbackClass->before= true;
				$anTable= &$this->getTable();
				//modify forign key to see in the callback-function
				// and also to can set the hole where clausel after in the table
				$this->db->foreignKeyModification($anTable);
				$this->where= $anTable->oWhere;
				//$this->asDBTable->modifyForeignKey(false);
				//$callbackClass->where= $oTable->oWhere;
				$this->makeCallback(STLIST, $callbackClass, STLIST, 0);
				$this->asDBTable->oWhere= $callbackClass->getWhere();
			}

			//alex 18/09/2005:	damit immer die gleiche auflistung erzielt wird
			//					zb. fuer MaxRowSelect wenn nicht beim ersten eintrag
			//					in die Tabelle gesprungen wird
			//					mach ein order by auf den Pk
			if(!$oTable->isOrdered())
				$oTable->orderBy($oTable->getPkColumnName());
			// ueberpruefe ob alle get-Columns aus der Db selektiert werden
			// setze dabei abNewChoice, damit die zuvor selectierten Columns
			// nicht geloescht werden
			/*$oTable->abNewChoice["select"]= true;
			foreach($this->showTypes as $column=>$type)
			{
				if($type=="get")
				{
					$field= $oTable->findAliasOrColumn($column);
					if($field["type"]!="alias")
						$oTable->select($column);
				}
			}*/

			// alex 09/06/2005:	abchecken welche Rows selectiert werden
			$firstRow= 0;
			$this->nShowFirstRow= 0;
			$nMaxSelect= $oTable->getMaxRowSelect();
			if(0)//$nMaxSelect)
			{
				$tableName= $oTable->getName();
				$limit= null;
/*				if(isset($HTTP_GET_VARS["stget"]["firstrow"][$tableName]))
				{
					$limit= $HTTP_GET_VARS["stget"]["firstrow"][$tableName];
					// alex 18/09/2005: wenn f�r die aktuelle Tabelle eine Einschr�nkung gesetzt ist
					//					soll nicht mit der ersten row der Tabelle begonnen werden
					$countTable= $oTable;
					$countTable->clearSelects();
					$countTable->clearMaxRowSelect();
					$column= key($limit);
					$field= $oTable->findAliasOrColumn($column);
					$column= $field["column"];
					$countTable->select($column);
					$this->limitColumnName= $column;
					echo "count statement: ".$countTable->getStatement()."<br>";
					$this->shownLimitValues= $this->db->fetch_single_array($countTable);
					foreach($this->shownLimitValues as $row=>$value)
					{
						if($value==$limit[$column])
						{
							$aktRow= $row;
							break;
						}
					}
					if(isset($aktRow))
					{
    					$firstRow= 0;
    					while($firstRow<=$aktRow)
    						$firstRow+= $nMaxSelect;
						if($firstRow>$aktRow)
							$firstRow-= $nMaxSelect;
					}

				}*/
				if(	!isset($firstRow) &&
					isset($HTTP_GET_VARS["stget"]["firstrow"][$oTable->getName()])	)
				{
					$firstRow= $HTTP_GET_VARS["stget"]["firstrow"][$oTable->getName()];
				}
				if(isset($firstRow))
				{
					$this->nShowFirstRow= $firstRow;
					$inTableFirstRow= $oTable->getFirstRowSelect();
					$oTable->setFirstRowSelect($firstRow);
				}

			}elseif(	is_array($oTable->dateIndex) &&
						count($oTable->dateIndex)		)
			{
				echo __file__.__line__;
    			$param= $this->oGet;
    			$vars= $param->getArrayVars();

    			$timestamp= $vars["stget"]["time"];
    			if(!isset($timestamp))
    				$timestamp= time();

    			$day= date("d", $timestamp);
    			$month= date("n", $timestamp);
    			$year= date("Y", $timestamp);
				if($oTable->dateIndex["type"]==STDAY)
    			{
					$fromtime= mktime(0, 0, 0, $month, $day, $year);
					$totime= mktime(0, 0, 0, $month, ($day+1), $year);
    			}elseif($oTable->dateIndex["type"]==STWEEK)
    			{
					echo "file:".__file__." line:".__line__."<br />";
					echo "createStatement fuer STWEEK noch nicht ausprogrammiert";exit;
    			}elseif($oTable->dateIndex["type"]==STMONTH)
    			{
					$fromtime= mktime(0, 0, 0, $month, 1, $year);
					$totime= mktime(0, 0, 0, ($month+1), 1, $year);
    			}elseif($oTable->dateIndex["type"]==STYEAR)
    			{
					$fromtime= mktime(0, 0, 0, 1, 1, $year);
					$totime= mktime(0, 0, 0, 1, 1, ($year+1));
    			}
				$fromdate= $this->db->getSqlDateFromTimestamp($fromtime);
				$todate= $this->db->getSqlDateFromTimestamp($totime);
				st_print_r($oTable->dateIndex);
				echo "from:$fromdate<br />";
				echo "to  :$todate<br />";
				$fromDateColumn= $oTable->dateIndex["from"];
				$toDateColumn= $oTable->dateIndex["to"];

				if($toDateColumn)
				{
					$where=  new STDbWhere($fromDateColumn."<'".$todate."'");
					$where->andWhere($toDateColumn.">='".$fromdate."'");
				}else
				{
					$where= new STDbWhere($fromDateColumn.">='".$fromdate."'");
					$where->andWhere($fromDateColumn."<'".$todate."'");
				}
				$oTable->andWhere($where);
				echo __file__.__line__;
				st_print_r($where,10);
			}
			// alex 28/06/2005:	kontrolliere ob eine fixe Einschr�nkung vorhanden ist
			//					und gib diese dann in die Where-Clausl
			// alex 03/08/2005: kontrolle der fixen Einschr�nkung nach STDatabase verschoben

			
			$tableDb= &$oTable->getDatabase();
			if(typeof($oTable, "STDBSelector"))
			{
				$this->oSelector= &$oTable;
			}else
			{
			    $this->oSelector= new STDbSelector($oTable, MYSQL_ASSOC, $this->getOnError("SQL"));
			}
			if(	isset($firstRow)
				or
				isset($nMaxSelect)	)
			{
				if(!$firstRow)
					$firstRow= 0;
				$this->oSelector->limit($firstRow, $nMaxSelect);
			}

			// toDo: DbSelector find not the right Statement / Alias-Table
			$statement= $tableDb->getStatement($oTable);
			$this->oSelector->setStatement($statement);
  			$this->statement= $this->oSelector->getStatement();
			$aliases= array();
			$this->db->createAliases($aliases, $oTable, false);
			// $this->oSelector->createAliases($aliases);
			STCheck::echoDebug("db.main.statement", $this->statement);

			if(count($aliases)>1)
			{// if the selection has sub-tables
				$tableName= $this->oSelector->getName();
				foreach($aliases as $fromTable=>$alias)
				{
					if($fromTable!==$tableName)
					{
						$table= &$this->oSelector->getTable($fromTable);
						$otherTableName= $table->getName();
						foreach($table->showTypes as $column=>$content)
						{
							if($content["get"]==="get")
								$this->showTypes[$otherTableName."@".$column]= "get";
						}
						unset($table);
					}
				}
			}

			// alex 09/06/2005:	FirstRowSelect wieder zur�ck setzen
			if($inTableFirstRow)
				$oTable->setFirstRowSelect($inTableFirstRow);
  		}elseif(	!$this->statement
					and
					!count($this->asTable)	)
		{
			echo "<br /><b>ERROR</b> user dont create any table for object STListBox";
			exit;
		}
	}
	function makeResult()
	{
		if($this->log)
		{
			global $user;
			$debug= $user->isDebug();
			$user->debug(true);
			$user->LOG("start STListBox->makeResult", 2);
			$user->debug($debug);
		}

		$stget= $this->oGet;
		$stget= $stget->getArrayVars();
		if(isset($stget["stget"]))
			$stget= $stget["stget"];
		$bDone= false;
		$oTable= &$this->getTable();
		if(	$oTable
			and
			$oTable->oSearchBox)
		{
			$oTable->oSearchBox->setSqlEffect(MYSQL_ASSOC);
			$result= $oTable->oSearchBox->execute($oTable);
			if($result=="EMPTY_RESULT@")
			{
				$message= $oTable->oSearchBox->getMessageContent();
				$this->msg->setMessageId("EMPTY_SEARCHBOX_RESULT", $message);
			}elseif(	$result!="NOERROR"
						and
						$result!="BOXDISPLAY"
						and
						$result!="NOTODO"		)
			{
				$message= $oTable->oSearchBox->getMessageContent();
				preg_match("/^([^@]+)/", $result, $preg);
				$this->msg->setMessageId($preg[1], $message);
			}elseif($result=="NOERROR")
			{
				$this->SqlResult= $oTable->oSearchBox->getResult_array($oTable->getName());
				$bDone= true;
			}
		}
		$this->bGetedSearchboxResult= $bDone;
		if(!$bDone)
		{
			if(	$this->statement
				and
				!$this->SqlResult	)
  			{
  				Tag::echoDebug("db.statement", "create result");
				if(0)//$this->oSelector)
				{ // toDo: STDbSelector defekt

		STCheck::write("make list result");
exit();
					$this->oSelector->execute();
					$this->SqlResult= $this->oSelector->getResult();
					$this->setSqlError($this->SqlResult);

						//$messageId= $this->oSelector->getErrorId();
						//$sqlErrorMessage= $this->oSelector->getErrorMessage();
						//$this->msg->setMessageId($messageId, $sqlErrorMessage);

				}else
				{
  					$this->SqlResult= $this->db->fetch_array($this->statement, STSQL_ASSOC, $this->getOnError("SQL"));
  					$this->setSqlError($this->SqlResult);
				}

  			}else
  				$this->msg->setMessageId("NO_SOLUTION");
		}

		if($this->log)
		{
			$debug= $user->isDebug();
			$user->debug(true);
			$user->LOG("end STListBox->makeResult", 2);
			$user->debug($debug);
		}
	}
		function layout($layout)
		{
			stackErrorTrace();
			if($layout==STHORIZONTAL || $layout==STVERTICAL)
				$this->arrangement= $layout;
		}
		function address($address)
		{
			$this->address["All"]= $address;
		}
		function insert($statement)
		{
			$this->insertStatement= $statement;
		}
		function delete($statement)
		{
			$this->deleteStatement= $statement;
		}
		/*private*/function getHeadRowAddress($fromColumn)
		{
			$oGet= $this->oGet;
			$tableName= $this->getTable()->getName();
			if(!is_array($this->aSorts))
			{
				global	$HTTP_GET_VARS;

				$aSorts= null;
				if(isset($HTTP_GET_VARS["stget"]["sort"][$tableName]))
					$aSorts= $HTTP_GET_VARS["stget"]["sort"][$tableName];
				$this->aSorts= array();
				if(is_array($aSorts))
				{
					foreach($aSorts as $column=>$value)
					{
						//preg_match("/^([^_]+)_(ASC|DESC)$/", $value, $nValue);
					    preg_match("/^(.+)_(ASC|DESC)$/", $value, $nValue);
						$arr= array();
						$arr["column"]= $nValue[1];
						$arr["sort"]= $nValue[2];
						$this->aSorts[]= $arr;
					}
				}
			}
			if(	isset($this->aSorts[0]["column"]) &&
				$this->aSorts[0]["column"]==$fromColumn	)
			{
				$aSorts= $this->aSorts;
				if(	isset($aSorts[0]["sort"]) &&
					$aSorts[0]["sort"]=="ASC"	)
				{
					$aSorts[0]["sort"]= "DESC";
				}else
					$aSorts[0]["sort"]= "ASC";
			}else
			{
				$aSorts= array();
				$aSorts["column"]= $fromColumn;
				$aSorts["sort"]= "ASC";
				$aSorts= array($aSorts);
				$aSorts= array_merge($aSorts, $this->aSorts);
			}
			
			// create new implementation inside url-bar
			// and delete first the old one
			$oGet->delete("stget[sort][$tableName]");
			$oGet->insert("stget[sort][$tableName]");			
			$count= 0;
			/**
			 * variable to see which sorting parameters filld into the url
			 * because values should'nt set at second time again
			 * @var string array
			 */
			$aSet= array();
			foreach($aSorts as $value)
			{
				if(array_search($value["column"], $aSet) === false)
				{
					$oGet->update("stget[sort][$tableName][$count]=".$value["column"]."_".$value["sort"]);
					$aSet[]= $value["column"];
				}
				$count++;
			}
			return $oGet->getStringVars();
		}
		function &getIndexTable()
		{
			if(isset($this->oIndexTable))
				return $this->oIndexTable;
			$oTable= &$this->getTable();
			if(	isset($oTable->dateIndex) &&
				count($oTable->dateIndex)		)
			{
				$this->oIndexTable= &$this->getDateIndex($oTable);
			}else
				$this->oIndexTable= &$this->getNumIndex($oTable);
			// damit die Funktion kein zweites mal durchlaufen wird
			// setze auf oIndexTable , fals kein IndexTable erzeugt wurde, einen span-tag
			if(!$this->oIndexTable)
				$this->oIndexTable= new SpanTag();
			return $this->oIndexTable;
		}
		function &getDateIndex($oTable)
		{
			if(isset($this->oGet))
				$param= $this->oGet;
			else 
				$param= new StQuerryString();
			$vars= $param->getArrayVars();

			$timestamp= $vars["stget"]["time"];
			if(!isset($timestamp))
				$timestamp= time();

			$day= date("d", $timestamp);
			$week= date("W", $timestamp);
			$weekName= date("l", $timestamp);
			$month= date("n", $timestamp);
			$monthName= date("F", $timestamp);
			$year= date("Y", $timestamp);

			$mday= $day;
			$pday= $day;
			$mmonth= $month;
			$pmonth= $month;
			$myear= $year;
			$pyear= $year;
			if($oTable->dateIndex["type"]==STDAY)
			{
				--$mday;
				++$pday;
			}elseif($oTable->dateIndex["type"]==STWEEK)
			{
				$mday-= 7;
				$pday+= 7;
			}elseif($oTable->dateIndex["type"]==STMONTH)
			{
				--$mmonth;
				++$pmonth;
			}elseif($oTable->dateIndex["type"]==STYEAR)
			{
				--$myear;
				++$pyear;
			}

			$mTimestamp= mktime(0, 0, 0, $mmonth, $mday, $myear);
			$pTimestamp= mktime(0, 0, 0, $pmonth, $pday, $pyear);

			if($oTable->dateIndex["type"]==STDAY)
				$dateString= "$weekName $day $monthName $year";
			elseif($oTable->dateIndex["type"]==STWEEK)
				$dateString= date("W")." week";
			elseif($oTable->dateIndex["type"]==STMONTH)
				$dateString= $monthName." ".$year;
			elseif($oTable->dateIndex["type"]==STYEAR)
				$dateString= $year;

			$param->update("stget[time]=".$pTimestamp);
			$nextParams= $param->getStringVars();
			$param->update("stget[time]=".$mTimestamp);
			$backParams= $param->getStringVars();

			$script= "javascript:document.location.href=";
			$backParams= $script."'".$backParams."'";
			$nextParams= $script."'".$nextParams."'";

			$this->nShowFirstRow= 1;// so dont disable backButton
			// that nextButton not disabled
			// nMaxRowSelect must be set
			// and nMaxTableRows must be greater then
			// ( nMaxRowSelect + nMaxTableRows )
			$this->nMaxRowSelect= 1;
			$this->nMaxTableRows= 3;
			return $this->getIndexTableTag(null, $backParams, $dateString, $nextParams, null);
		}
		function &getNumIndex($oTable)
		{
			Tag::paramCheck($oTable, 1, "STBaseTable");

			$oNumIndex= null;
			if(!$oTable) // for old versions
				return $oNumIndex;

			Tag::paramCheck($oTable, 1, "STDbTable");

			$params= new STQueryString();
			$HTTP_GET_VARS= $params->getArrayVars();
			$tableName= $oTable->getName();
			if(isset($HTTP_GET_VARS["stget"]["firstrow"][$tableName]))
			{
				$this->nShowFirstRow= $HTTP_GET_VARS["stget"]["firstrow"][$tableName];
				if($this->nShowFirstRow===null)
					$this->nShowFirstRow= 0;
			}
			//$divTag= new DivTag();
			/*****************************************************************
			 ** alex 09/06/2005:                                            **
			 **        erste Zeile f�r index-Angabe und weiterschaltung     **
			 *****************************************************************/
			 if(isset($oTable))
			 {	// alex 15/06/2005:	wenn keine Tabelle existiert,
			 	//					wird die Auflistung auch nie mit einem
				//					Index versehen
			 	$this->nMaxRowSelect= $oTable->getMaxRowSelect();

				$needAlwaysIndex= $oTable->needAlwaysIndex();
				$tableName= $oTable->getName();
				$cTab= new STDbSelector($oTable);
				$cTab->allowQueryLimitation($oTable->modify());
				$cTab->clearRekursiveNoFkSelects();
				$cTab->clearRekursiveGetColumns();
    			if($this->bGetedSearchboxResult)
    				$cTab->andWhere($oTable->oSearchBox->getWhere());
				if($cTab->isDistinct())
				{// alex 17/10/2006:	ToDo: distinct inside from count
				 //							it's nessesary to set the table inside of the function
				 //							and if it is distinct it will be set all columns
				 //							inside from count from the select-statement
					$cTab->distinct(false);
    				$cTab->count("*");//$oTable, "count");
				}else
					$cTab->count("*");
    			$cTab->execute();
    			$nMaxTableRows= $cTab->getSingleResult();
				if(!isset($nMaxTableRows))
				    $nMaxTableRows= 0;
				$this->nMaxTableRows= $nMaxTableRows;
				if( $nMaxTableRows <= 1 &&
				    $this->arrangement == STVERTICAL    )
				{
				    return $oNumIndex; 
				}
				//$statement= "select count(*) from ".$tableName;
				//$nMaxTableRows= $this->db->fetch_single($statement);
			 }
			if($this->nMaxRowSelect===null)
				return $oNumIndex;
			// alex 15/06/2005:	nur wenn $needAlwaysIndex nicht auf false gesetzt ist
			//						$needAlwaysIndex =	false	-	nie Index anzeigen
			//											null	-	nur wenn $nMaxRowSelect gr�sser als vorhandene Zeilen
			//											true	-	immer anzeigen
			if(	(	$this->nMaxRowSelect
					and
					$this->nMaxRowSelect < $nMaxTableRows
					and
					$needAlwaysIndex!==false	)
			 	or
				$needAlwaysIndex				)
			 {
				if(	($this->nShowFirstRow+$this->nMaxRowSelect) < $nMaxTableRows
					or
					$needAlwaysIndex										)
				{
					$get= new STQueryString();//$this->oGet;
					$script= "javascript:document.location.href='";
					if($this->limitColumnName)
						$param= "stget[".$oTable->getName()."][".$this->limitColumnName."]=";
					else
						$param= "stget[firstrow][$tableName]=";
					$firstRow= 0;
					$backRow= $this->nShowFirstRow-$this->nMaxRowSelect;
					$nextRow= $this->nShowFirstRow+$this->nMaxRowSelect;
					if(	isset($nMaxTableRows) &&
						isset($this->nMaxRowSelect) &&
						$this->nMaxRowSelect > 0		)
					{
						$lastRow= $nMaxTableRows / $this->nMaxRowSelect;
						$lastRow= floor($lastRow)*$this->nMaxRowSelect;
					}else
						$lastRow= $nMaxTableRows;
					if($this->nMaxRowSelect==1)
						--$lastRow;
					if($this->limitColumnName)
					{
						$firstRow= $this->shownLimitValues[($firstRow)];
						$backRow= $this->shownLimitValues[($backRow)];
						$nextRow= $this->shownLimitValues[($nextRow)];
						$lastRow= $this->shownLimitValues[($lastRow)];
					}
					$get->update($param.$firstRow);
			 		$firstRow= $script.$get->getStringVars()."'";
					$get->update($param.$backRow);
			 		$backRow= $script.$get->getStringVars()."'";
					$get->update($param.$nextRow);
			 		$nextRow= $script.$get->getStringVars()."'";
					$get->update($param.$lastRow);
					$lastRow= $script.$get->getStringVars()."'";

					// create nameTags
					$nameTags= new SpanTag("indexName");
						$div= new SpanTag("fromRow");
							$fromRow= $this->nShowFirstRow+1;
							if(!isset($nMaxTableRows))
								$fromRow= "0";
							$div->add($fromRow);
						$nameTags->add($div);
					if($this->nMaxRowSelect!=1)
					{
						$div= new SpanTag("indexDelimiter");
							$div->add(" - ");
						$nameTags->add($div);
						$div= new SpanTag("toRow");
							$toRow= $this->nShowFirstRow+$this->nMaxRowSelect;
							if(!$toRow)
								$toRow= $nMaxTableRows;
							if($toRow>$nMaxTableRows)
								$toRow= $nMaxTableRows;
							$div->add($toRow);
						$nameTags->add($div);
					}
						$div= new SpanTag("fromMaxRowName");
							$sFrom= " from ";
							if($this->nMaxRowSelect==1)
								$sFrom= " / ";
							$div->add($sFrom);
						$nameTags->add($div);
						$div= new SpanTag("maxRow");
							$div->add($nMaxTableRows);
						$nameTags->add($div);
					return $this->getIndexTableTag($firstRow, $backRow, $nameTags, $nextRow, $lastRow);
				}
			 }else
			 	return $oNumIndex;
		}
		function &getIndexTableTag($firstParams, $backParams, &$nameTags, $nextParams, $lastParams)
		{//echo "getIndexTableTag($firstParams,\n<br /> $backParams,\n<br /> &$nameTags,\n<br /> $nextParams,\n<br /> $lastParams)<br />";
							$indexTable= new TableTag();
								$indexTable->border(0);
								$itr= new RowTag("index");
            						$itd= new ColumnTag(TD);
										$itd->align("center");
										$itd->width("50");
								if($firstParams)
								{
            							$first= new ButtonTag("indexButtons");
            								$first->add("<<");
											$first->onClick($firstParams);
									if($this->nShowFirstRow==0)
											$first->disabled();
								}
								if(	$this->nShowFirstRow
									or
									!$this->bDropIndexButton	)
								{
									if($firstParams)
									{
										$itd->addObj($first);
									$itr->add($itd);
										
            						$itd= new ColumnTag(TD);
										$itd->align("center");
										$itd->width("50");
									}
								}
								if($backParams)
								{
            							$back= new ButtonTag("indexButtons");
            								$back->add("<");
											$back->onClick($backParams);
									if($this->nShowFirstRow==0)
											$back->disabled();
								}
								if(	$this->nShowFirstRow
									or
									!$this->bDropIndexButton	)
								{
									if($backParams)
										$itd->addObj($back);
								}
								$itr->add($itd);

								$itd= new ColumnTag(TD);
									$itd->align("center");
									$itd->width("200");
								if($nameTags)
								{
										$itd->addObj($nameTags);
								}
									$itr->add($itd);
									$itd= new ColumnTag(TD);
										$itd->align("center");
										$itd->width("50");
								if($nextParams)
								{
									if(	$this->nMaxRowSelect
										and
										($this->nShowFirstRow+$this->nMaxRowSelect) < $this->nMaxTableRows	)
									{
										$bLastRow= false;
									}else
										$bLastRow= true;
            							$next= new ButtonTag("indexButtons");
            								$next->add(">");
											$next->onClick($nextParams);
										if($bLastRow)
											$next->disabled();
								}
								if(	!$this->bDropIndexButton
									or
									!$bLastRow	)
								{
									if($nextParams)
										$itd->addObj($next);
									$itr->add($itd);
										
            						$itd= new ColumnTag(TD);
										$itd->align("center");
										$itd->width("50");
								}
								if($lastParams)
								{
            							$last= new ButtonTag("indexButtons");
            								$last->add(">>");
											$last->onClick($lastParams);
										if($bLastRow)
											$last->disabled();
								}
								if(	!$this->bDropIndexButton
									or
									!$bLastRow	)
								{
									if($lastParams)
										$itd->addObj($last);
								}
								$itr->add($itd);
							$indexTable->add($itr);
			return $indexTable;
		}
		function callback($columnName, $callbackFunction, $action= STLIST)
		{
			STBaseTableBox::callback($columnName, $callbackFunction, $action);
		}
		function insertAttributes(&$tag, $element)
		{
			$aAttributes= null;
			if(isset($this->asDBTable->aAttributes[STLIST][$element]))
				$aAttributes= $this->asDBTable->aAttributes[STLIST][$element];
			if(is_array($aAttributes))
			{
				foreach($aAttributes as $attribute=>$value)
					$tag->insertAttribute($attribute, $value);
			}
		}
		/**
		 *	erzeugt den Html-Code der Tabelle
		 *
		 *	@param	onError:	gibt was bei einem Fehler geschehen soll.<br />
		 * 						onErrorStop: gibt Fehlermeldung aus und beendet<br />
		 *						onErrorShow: gibt nur Fehlermeldung aus<br />
		 *						noErrorShow: zeigt keinen Fehler an und bricht auch nicht ab<br />
		 *						onErrorMessage: zeigt ersten Fehler mittels Message-Box (default)<br />
		 *	@return	gibt 0, 1, -1, -2 oder -5 zur�ck.<br>
		 *			 0 - Die Tabelle wurde erzeugt<br>
		 *			 1 - Die Checkboxen wurden mit der Datenbank abgeglichen<br>
		 *			-1 - die solution ergab kein Ergebnis<br>
		 *			-2 - zuerst muss die Funktion solution aufgerufen werden!<br>
		 *			-5 - beim Checkbox-Abgleich wurde nichts ver�ndert<br>
		 *			(die R&uuml;ckgabe im Positiven Bereich ist kein Fehler)
		 */
		function createTags()
		{
			// wenn ein buttonText deffiniert ist
			// wird der Tabellen-Inhalt in einen Div-Tag geschrieben
			$showTypes= array_flip($this->showTypes);
			if(	isset($showTypes["check"]) ||
				$this->asDBTable->bIsNnTable	)
			{
				$hTable= new DivTag();
			}else// sonst gleich in die Tabelle =dieses Objekt($this)
				$hTable= &$this;

			//if(typeof($this->asDbTable, "STDbTable"))
			$indexTable= &$this->getIndexTable();
			if($indexTable)
			{
				$idtr= new RowTag();
					$this->insertAttributes($idtr, "tr");
					$idtd= new ColumnTag(TD);
						$this->insertAttributes($idtd, "td");
						if(isset($this->SqlResult[0]))
							$colspan= count($this->SqlResult[0]);
						else
							$colspan= 1;
						if($this->asDBTable->nDisplayColumns)
						    $colspan*= $this->asDBTable->nDisplayColumns;
						$idtd->colspan($colspan);
						$idtd->align("center");
						$idtd->addObj($indexTable);
					$idtr->addObj($idtd);
				$hTable->addObj($idtr);
			}

			/*****************************************************************
			 **        Ueberschrift-Zeile deffinieren wenn HORIZONTAL        **
			 *****************************************************************/
			if(	$this->arrangement==STHORIZONTAL
				and
				$this->bCaption					)
			{
				$tr= new RowTag();
					$this->insertAttributes($tr, "tr");
				$firstRow= $this->SqlResult[0];
	            foreach($firstRow as $key=>$value)
    	        {
					if(	!isset($this->showTypes[$key]) ||
						$this->showTypes[$key] != "get"		)
					{
						$th= new ColumnTag(TH);
							$this->insertAttributes($th, "th");
						if($this->getTable()->doTableSorting)
						{
							$a= new ATag("hline");
               				$a->add($key);
								$a->href($this->getHeadRowAddress($key));
							$th->add($a);
						}else
							$th->add($key);
						$tr->add($th);
					}
					if(	isset($this->showTypes[$key]) &&
						$this->showTypes[$key]=="check"	)
					{
						$this->checkboxes[]= $key;
					}
        	    }
				$hTable->add($tr);
			}
			/*****************************************************************/



			$Rows= &$this->SqlResult;
			$CallbackClass= new STCallbackClass($this->tableContainer, $Rows);
			$CallbackClass->before= false;
			$CallbackClass->nDisplayColumns= $this->asDBTable->nDisplayColumns;
			$CallbackClass->arrangement= $this->arrangement;

			/*****************************************************************
			 **        wenn mehrere Spalten sein sollen,    			    **
			 **			werden sie hier zusammen gelegt						**
			 *****************************************************************/
			if(	$this->asDBTable
			 	and
				$this->asDBTable->nDisplayColumns>1	)
			{
				$sqlResult= array();
				$nCount= 0;
				$displayCount= 1;
				foreach($Rows as $key=>$row)
				{
					foreach($row as $name=>$entry)
					{
						$sqlResult[$nCount]["###STcolumn".$key."###_".$name]= $entry;
					}
					++$displayCount;
					if($displayCount>$this->asDBTable->nDisplayColumns)
					{
						$displayCount=1;
						++$nCount;
					}
				}
				$Rows= $sqlResult;
			}
			/*****************************************************************/

		$_showColumnProperties= false;
		if($_showColumnProperties)
		{
			if(STCheck::isDebug())
			{
				echo "<br /><br />";
				STCheck::write("output defined to write all column properties from list");
				$msg= "current arrangement is ";
				if($this->arrangement == STHORIZONTAL)
				    $msg.= "HORICONTAL";
				else if($this->arrangement == STVERTICAL)
				    $msg.= "VERTICAL";
				else
				    $msg.= "unknown!";
				STCheck::write($msg);
				st_print_r($Rows,2);
			}else
				$_showColumnProperties= false;
		}
		$class= "Tr1";
        foreach($Rows as $rowKey=>$rowArray)
        {
        	$extraField= null;
			//*****************************************************************************************
			if(isset($this->showTypes[$rowKey]))
				$extraField= $this->showTypes[$rowKey];// diese Variablen werden je nach HORIZONTAL/VERTIKA
			if($class == "Tr1")
			    $class= "Tr0";
			else
			    $class= "Tr1";
			$row= $rowKey;
			$key= $rowKey;
			//*****************************************************************************************

			$tr= new RowTag($class);
				$this->insertAttributes($tr, "tr");
			$getColumn= false;// sagt aus das die Spalte zuvor
							  // nur zur Uebermittlung (showType=get) war

			if($_showColumnProperties)
				echo "-------------------------------------------------<br />";
			foreach($rowArray as $columnKey=>$columnValue)
          	{
  				if($this->arrangement==STVERTICAL)
  				{// erstelle als erstes Feld die Ueberschrift
  					if(	!isset($this->showTypes[$rowKey]) ||
  						$this->showTypes[$rowKey] != "get"	)
  					{
						if($this->bCaption)
						{
	  						$th= new ColumnTag(TH);
								$this->insertAttributes($th, "th");
								$th->add($rowKey);
							$tr->add($th);
						}
						$row= $columnKey;
						if($class == "Tr1")
						    $class= "Tr0";
					    else
					        $class= "Tr1";
  					}
  				}else
				{
					$key= $columnKey;
				}

				if(preg_match("/^###STcolumn([0-9]+)###_(.*)$/", $key, $preg))
				{
					$nDisplayColumn= $preg[1];
					$CallbackClass->nDisplayColumn= $nDisplayColumn;
					$createdColumn= $preg[2];
				}else
					$createdColumn= $key;
				if($this->arrangement==STHORIZONTAL)
					$createdRow= $rowKey;
				else
					$createdRow= $columnKey;
				if(isset($this->showTypes[$createdColumn]))
					$extraField= $this->showTypes[$createdColumn];

				// hier wird gesetzt ob ein Zugriff vorhanden ist
				$bHasAccess= true;
				if(isset($this->bLinkAccess[$createdColumn]))
					$bHasAccess= $this->bLinkAccess[$createdColumn];
				if($this->makeCallback(STLIST, $CallbackClass, $createdColumn, $createdRow))
				{// ein Callback vom user wurde durchgef�hrt
					if($CallbackClass->bSkip)
						break;
					if($CallbackClass->showType)
						$extraField= $CallbackClass->showType;
					if($CallbackClass->bNoShowType)
						$extraField= null;
					// aktuelles Feld wird in $columnValue nicht aktualisiert
					$columnValue= $CallbackClass->sqlResult[$rowKey][$columnKey];
				}
				if($_showColumnProperties)
				{
				// for Debug see the properties
				// from the created Vars
				// $key is always the column name
				// $createdRow is alwas the row count
				// $rowKey and $columnKey chances inside column name and row count by defined HORIZONTAL or VERTICAL
				// $createdColumn always the column
					echo "- key:$key<br />";
					echo "  createdRow:$createdRow<br />";
					echo "  rowKey:$rowKey<br />";
					echo "  columnKey:$columnKey<br />";
					echo "  createdColumn:$createdColumn<br />";
					echo "  columnValue:";st_print_r($columnValue);echo "<br />";
					echo "  field parameters: <b>";
					if(isset($extraField))
						echo $extraField;
					else
						echo "no extra parameters";
					echo "</b><br />";
				}

					if(!$getColumn)
					{
						$td= new ColumnTag(TD);
						$this->insertAttributes($td, "td");
					}else// wenn getColumn gesetzt ist wird kein neuer TD Tag erzeugt
						$getColumn= false;

				if(	isset($extraField) &&
					$extraField!=="dropdown" &&
					(	(	$columnValue!==null
							or
							!preg_match("/link/", $extraField)	)
						or
						$this->bSetLinkByNull						)	)
          		{
          			// create links for the current column
          			if(	(	!isset($extraField) ||
          					(	$extraField!=="get" &&
								$extraField!=="image" &&
								$extraField!=="check"	)	) &&
						!isset($this->address["All"]) &&
						!isset($this->address[$createdColumn])			)
          			{
							global	$HTTP_SERVER_VARS;
							
							$file=  "javascript:location='";
          					$file.= $HTTP_SERVER_VARS["SCRIPT_NAME"];

          					$alldef= [];
          					$array= [];
							$get= new STQueryString();//$this->oGet;
							if(isset($this->aGetParams[STINSERT][STALLDEF]))
							    $alldef= $this->aGetParams[STINSERT][STALLDEF];
							if(isset($this->aGetParams[STINSERT][$createdColumn]))
							    $array= $this->aGetParams[STINSERT][$createdColumn];
							if(	is_array($alldef) &&
								is_array($array)	)
							{
								$array= array_merge($alldef, $array);
								
							}elseif(is_array($alldef))
								$array= $alldef;
							if(count($array))
							{
								foreach($array as $param)
								{
									$get->insert($param);
								}
							}
							if(isset($this->aGetParams[STUPDATE][STALLDEF]))
							    $alldef= $this->aGetParams[STUPDATE][STALLDEF];
							if(isset($this->aGetParams[STUPDATE][$createdColumn]))
							    $array= $this->aGetParams[STUPDATE][$createdColumn];
							if(	is_array($alldef) &&
								is_array($array)	)
							{
								$array= array_merge($alldef, $array);
								
							}elseif(is_array($alldef))
								$array= $alldef;
							if(count($array))
							{
								foreach($array as $param)
								{
									$get->update($param);
								}
							}
							if(isset($this->aGetParams[STDELETE][STALLDEF]))
							    $alldef= $this->aGetParams[STDELETE][STALLDEF];
							if(isset($this->aGetParams[STDELETE][$createdColumn]))
							    $array= $this->aGetParams[STDELETE][$createdColumn];
							if(	is_array($alldef) &&
								is_array($array)	)
							{
								$array= array_merge($alldef, $array);
								
							}elseif(is_array($alldef))
								$array= $alldef;
							if(count($array))
							{
								foreach($array as $param)
								{
									$get->delete($param);
								}
							}
							/*if(preg_match("/^container_/", $extraField))
							{
								echo "�bergabe";
								//print_r($get);
								//st_print_r($get, 50);//$this->oGet->getArrayVars(),10);
							}*/
							// alex 02/09/2005:	wenn ein Link in der aufgelisteten Tabelle angeklickt wird
							//					sollen zus�tzlich die gew�nschten Parameter gesetzt werden
							if(isset($this->setParams["onActivate"][$columnKey]))
							{
								foreach($this->setParams["onActivate"][$columnKey] as $work)
								{
									$get->make($work["do"], $work["param"]);
								}
							}
							if(preg_match("/^container_/", $extraField))
							{//	alex 19/04/2005:	bei einem Table-Link (bei der Linkangabe wurde ein Tabellenname angegeben)
							 //						wird der Tabellenname ge�ndert und von der neuen Tabelle
							 //						der PK mit dem augenblicklichen Value verglichen
								$table= &$this->getTable();
								$tableName= $table->getName();
								// alex 28/06/2005:	suche Name der Spalte
								//					welche der Key representiert
								$represent= $createdColumn;
								foreach($table->show as $column)
								{
									if($column["alias"]==$createdColumn)
									{
										$represent= $column["column"];
										break;
									}
								}
								//echo __file__.__line__;
								//echo "columnValue:$columnValue<br />";
								//echo "createdColumn:$createdColumn<br />";
								//echo "key:$key<br />";
								$isValue= $columnValue;
								//if(preg_match("/^(container_)?namedcolumnlink$/", $extraField))
								if(isset($table->showTypes["valueColumns"][$createdColumn]))
								{// wenn bei der Angabe des showTypes ein andere Value und Column gew�nscht wurde
									//st_print_r($table->showTypes, 2);
									$represent= $table->showTypes["valueColumns"][$createdColumn];
									//echo "represent:$represent<br />";
									if($key==$createdColumn)
										$createdRepresent= $represent;
									else
										$createdRepresent= "###STcolumn".$nDisplayColumn."###_".$represent;
									//st_print_r($rowArray);
									$isValue= $rowArray[$createdRepresent];
								}
								//echo "get->update(stget[".$tableName."][".$represent."]=".$isValue.");<br />";
								$get->update("stget[".$tableName."][".$represent."]=".$isValue);


								/*if(preg_match("/^###STcolumn[0-9]+###_(.*)$/", $key, $preg))
									$fromKey= $preg[1];
								else
									$fromKey= $key;*/
								$newContainer= $table->showTypes[$createdColumn][$extraField];
								//$newTable= $this->db->getTable($newTableName);
								//$containerName= $this->tableContainer->getName();
								//$pkFromNewTable= $newTable->getPkColumnName();
								$get_vars= $get->getArrayVars();
								if($this->bContainerManagement)
									$make= STINSERT;
								else
									$make= STUPDATE;
			//echo "file:".__file__." line:".__line__."<br />";st_print_r($make);echo "<br />";
								$get->make($make, "stget[table]=");//=".$newTableName);
								$get->make($make, "stget[action]=");
								$get->make($make, "stget[container]=".$newContainer->getName());
								if($table->sDeleteLimitation=="older")
								{
									$count= 0;
									$from= $get_vars["stget"]["link"]["from"];
									if($from)
										foreach($from as $countTable)
										{
											if($countTable==$tableName)
											{// table is in array
											 // need no second insert
												$count= 0;
												break;
											}
											$count++;
										}

									if($count)
										$get->make($make, "stget[link][from][".$count."][".$tableName."]=".$represent);
									$get->make($make, "stget[link][from]=");
								}elseif($table->sDeleteLimitation=="true")
								{
									$oldlink= $get->getArrayVars();
									if(isset($oldlink["stget"]["link"]["from"]))
										$oldlink= $oldlink["stget"]["link"]["from"];
									else 
										$oldlink= null;
									$countLink= 0;
									if(	is_array($oldlink)
										and
										$make==STUPDATE	)
									{
										$countLink= count($oldlink);
									}
									if($make==STINSERT)
										$get->insert("stget[link][from]=");
									$aktParams= $get->getArrayVars();
									$existParam= false;
									if(	isset($oldlink["stget"]["link"]["from"]) &&
										is_array($aktParams["stget"]["link"]["from"])	)
									{
										foreach($aktParams["stget"]["link"]["from"] as $paramLink)
										{
											if($paramLink[$tableName]==$represent)
											{
												$existParam= true;
												break;
											}
										}
									}
									if(!$existParam)
										$get->make($make, "stget[link][from][".$countLink."][".$tableName."]=".$represent);
								}else // sDeleteLimitation ist "false"
									$get->make($make, "stget[link][from]=");
							}else
							{
								$asParam= $createdColumn;
								if(isset($this->setParams["asParam"][$createdColumn]))
									$asParam= $this->setParams["asParam"][$createdColumn];
								$get->update("stget[".$extraField."][".$asParam."]=".$columnValue);
							}
							$table= &$this->getTable();
							//echo "value is $sValue<br />";
							//echo "represent is $represent<br />";
							if(	isset($table->linkParams[$createdColumn]) &&
								is_array($table->linkParams[$createdColumn])	)
							{
								foreach($this->asDBTable->linkParams[$createdColumn] as $newAction=>$content)
								{
									foreach($content as $newParam)
									{
										$get->make($newAction, $newParam);
										/*if($newAction==STDELETE)
										{
										}else
										{
											$setparam
											$oldlink= $get->getArrayVars();
											$count= count($oldlink["stget"]["link"]["from"]);
											$get->make($newAction, "stget[link][from][".$count."][".$tableName."]=
										}*/
									}
								}
							}
							/*if(preg_match("/^container_/", $extraField))
							{
								echo "create file<br />";
								st_print_r($get->getArrayVars(),50);
							}*/

					//echo "create query-string for:<br />";
					//echo "column:$createdColumn<br />";
					//echo "extraField:$extraField<br />";
							$file.= $get->getStringVars();
							$file.= "'";

          			}else
          			{
						if(isset($this->address[$createdColumn]))
							$address= $this->address[$createdColumn];
						if(	(	!isset($address) ||
								$address == ""		) &&
							isset($this->address["All"])	)
						{
							$address= $this->address["All"];
						}
						if(	isset($address) &&
							$address != ""		)
						{
							$file= preg_replace("/%NAME%/i", $createdColumn, $address);
							$file= preg_replace("/%ROWNUM%/i", "$row", $file);
							if(preg_match("/(['\"]?)%VALUE%['\"]?/i", $file, $preg))
							{
								$sValue= $columnValue;
								if($sValue===null)
								{
									if(!$preg[1])
										$sValue= "null";
								}
	          					$file= preg_replace("/%VALUE%/i", $sValue, $file);
							}
						}
          			}	
					//echo"extraField:$extraField<br />";
						if(	isset($this->asDBTable->aActiveLink["column"]) &&
							$this->asDBTable->aActiveLink["column"]===$createdColumn	)
						{
							$tableName= $this->asDBTable->getName();
							$query= new STQueryString();
							$limitation= $query->getLimitation($tableName);
							if($limitation)
								$this->asDBTable->aActiveLink["represent"]= $limitation[$represent];
						}
						if($extraField=="check")
						{
							$input= new InputTag();
								$input->type("checkbox");
								$input->name($createdColumn."[".$row."]");
							if(	(	$this->asDBTable->aCheckDef[$isCheck]
									and
									$this->asDBTable->aCheckDef[$isCheck]===$columnValue	)
								or
								(	!$this->asDBTable->aCheckDef[$isCheck]
									and
									$columnValue!=null										)	)
							{
								$input->checked();
							}
							if(	$this->address["All"]
								or
								$this->address[$columnKey]	)
							{
								$input->onClick($file);
							}
							$td->add($input);
							$td->align("center");
							// info comming now directly from database
							// after the selection
							/*if($columnValue)
							{
  								$input2= new InputTag();
  								$input2->name("checked_".$createdColumn."[".$row."]");
  								$input2->type("hidden");
  								$input2->value("on");
//								$div->add($input2);
								$td->add($input2);
							}*/
						}elseif($extraField=="image")
						{
							if($columnValue)
							{
								$image= new ImageTag();
									$image->src($columnValue);
//							$div->add($image);
								$td->add($image);
							}
						}elseif($extraField=="download")
						{
							if($columnValue)
							{
								if($bHasAccess)
								{
    								if($table->showTypes["valueColumns"][$createdColumn])
    								{// wenn bei der Angabe des showTypes ein andere Value und Column gew�nscht wurde
    									$represent= $table->showTypes["valueColumns"][$createdColumn];
    									if($key==$createdColumn)
    										$createdRepresent= $represent;
    									else
    										$createdRepresent= "###STcolumn".$nDisplayColumn."###_".$represent;
    									$isValue= $rowArray[$createdRepresent];
    								}

									$button= new ButtonTag("downloadButton");
										$button->type("button");
										$param= $this->oGet;
										$address= "javascript:location.href='";
										$param->update("stget[".$tableName."][".$represent."]=".$isValue);
										$tableField= $table->findAliasOrColumn($createdColumn);
										$param->update("stget[download]=".$tableField["column"]);
										$address.= $param->getStringVars()."'";
										$button->onClick($address);
										$button->add("Download");
									$td->add($button);
								}elseif(Tag::isDebug())
								{
									$b= new BTag();
										$b->add("[no access to download]");
									$td->add($b);
								}
							}
						}elseif(preg_match("/^(container_)?imagelink([01])$/", $extraField, $preg))
						{
							if($columnValue)
							{
								if($bHasAccess)
								{
									$a= new ATag();
										$a->href($file);
										$image= new ImageTag();
											$image->src($columnValue);
											$image->border($preg[2]);
            							$a->add($image);
									$td->add($a);
								}else
								{
									if(Tag::isDebug())
									{
										$b= new BTag();
											$b->add("[no access]");
										$td->add($b);
										$td->add(br());
									}
									$image= new ImageTag();
										$image->src($columnValue);
           							$td->add($image);
								}
							}
						}elseif(preg_match("/^(container_)?namedlink$/", $extraField))
						{
							if($columnValue)
							{
								if($bHasAccess)
								{
									$Aclass= null;
									if(	isset($createdColumn) &&
										isset($this->asDBTable->aActiveLink["column"]) &&
										$createdColumn === $this->asDBTable->aActiveLink["column"] &&
										(	(	isset($columnValue) &&
												isset($this->asDBTable->aActiveLink["result"]) &&
												$columnValue === $this->asDBTable->aActiveLink["result"]		) ||
											(	!isset($this->asDBTable->aActiveLink["result"]) &&
												isset($rowArray[$createdRepresent]) &&
												isset($this->asDBTable->aActiveLink["represent"]) &&
												$rowArray[$createdRepresent]===$this->asDBTable->aActiveLink["represent"]	)	)	)
									{
										$Aclass= "stactivelink";
									}
									$a= new ATag($Aclass);
										$a->href($file);
            						$a->add($columnValue);
									$td->add($a);
								}elseif(Tag::isDebug())
								{
									$b= new BTag();
										$b->add("[no access]");
									$td->add($b);
								}
							}

						}elseif(preg_match("/^(container_)?namedcolumnlink$/", $extraField))
						{
							if($columnValue)
							{
								if($bHasAccess)
								{
									$Aclass= null;
									if(	$createdColumn===$this->asDBTable->aActiveLink["column"]
										and
										$rowArray[$createdRepresent]===$this->asDBTable->aActiveLink["represent"]	)
									{
										$Aclass= "stactivelink";
									}
									$a= new ATag($Aclass);
										$a->href($file);
        	    						$a->add($columnValue);
									$td->add($a);
								}elseif(Tag::isDebug())
								{
									$b= new BTag();
										$b->add("[no access]");
									$td->add($b);
								}
							}

						}elseif(preg_match("/^(container_)?link$/", $extraField))
						{
							if($columnValue)
							{
								if($bHasAccess)
								{
									$a= new ATag();
										$a->href($file);
            							$a->add($createdColumn);
									$td->add($a);
								}elseif(Tag::isDebug())
								{
									$b= new BTag();
										$b->add("[no access]");
									$td->add($b);
								}
							}

						}elseif($extraField=="get")
						{
							$getColumn= true;
							$input= new InputTag();
								$input->name($createdColumn."[".$row."]");
								$input->type("hidden");
								$input->value($columnValue);
							$td->add($input);
						}
          		}else
					{
						$td->add($columnValue);
					}
					if(!$getColumn)
						$tr->add($td);
				}// ende der Column Schleife
				if(!$CallbackClass->bSkip)	// wenn der User in einem Callback skipRow gew�hlt hat
					$hTable->add($tr);		// wird die RowColumn $tr nicht eingebunden
      	}// ende der Row Schleife



			/**********************************************************************************
			 *****     wenn der buttonText deffiniert ist wird ein Form-Tag ben�tigt      *****
			 *****     dann ist der hTable-Tag ein Div-Tag, sonst das objekt $this selbst *****
			 **********************************************************************************/
			if(	isset($showTypes["check"]) ||
				$this->asDBTable->bIsNnTable		)
			{
				$form= new FormTag();
					$form->name($this->formName);
				if(isset($this->action))
				{
					$form->action($this->action);
				}
					$form->method("post");
					$this->inherit= array();

			$tr= new RowTag();
				$td= new ColumnTag(TD);
					$input= new InputTag();
						$input->type("submit");
						$input->value($this->buttonText);
					$td->add($input);

					$input= new InputTag();
						$input->type("hidden");
						$input->name("stlisttable_make");
						$input->value(1);
					$td->add($input);
				$tr->add($td);
			$form->add($tr);

				if(count($this->aHidden))
				{
					foreach($this->aHidden as $key=>$value)
					{
						$input= new InputTag();
  						$input->type("hidden");
  						$input->name($key);
  						$input->value($value);
						$form->add($input);
					}

				}
				$form->add($hTable);
				$this->add($form);
			}

			/*****************************************************************************/

		}
		function createDbChanges()
		{
			global $HTTP_POST_VARS;
			
			$checked= array();
			if(	isset($HTTP_POST_VARS["stlisttable_make"])
				and
				!$this->insertStatement					)
			{// checkboxen mit Datenbank abgleichen

				Tag::echoDebug("fieldArray", "file:".__file__." line:".__line__);
				$fields= $this->db->fieldArray($this->statement, $this->getOnError("SQL"));
				$showTypes= array_flip($this->showTypes);
				$isCheck= $showTypes["check"];
				if(isset($isCheck))
				{
					$box= $HTTP_POST_VARS[$isCheck];
					//take checked directly from database
    				//$checked= $HTTP_POST_VARS["checked_".$isCheck];
					foreach($this->SqlResult as $key=>$value)
					{
						if($value[$isCheck])
							$checked[$key]= "on";
					}
				}

				if(count($box) || count($checked))
				{
					foreach($fields as $checkBoxColumn)
					{
						if($checkBoxColumn["name"]===$isCheck)
							break;// on brake, the variable checkBoxColumn has all contents of the column
					}
					if(is_array($box))
					{// all fields which are checked from gui
        				foreach($box as $kBox => $vBox)
        				{
        					if(!isset($checked[$kBox]))
        					{//the value is checked, but not in database
								if($this->asDBTable->bIsNnTable)
								{
        						}else// else no nnTable be set
								{
									if($this->asDBTable->aCheckDef[$isCheck])
									{
										$this->SqlResult[$kBox][$isCheck]= $this->asDBTable->aCheckDef[$isCheck];

									}elseif($checkBoxColumn["type"]=="string")
									{
										$this->SqlResult[$kBox][$isCheck]= "##st_newSet";
									}
								}// end of if nnTable in table-object save
        					}// end of if not box-key also before checked (in database)
						}// end of for-loop which fields be checked in the gui
					}// end of is value from box not null
					if(is_array($checked))
					{// all fields which are checked in the database
        				foreach($checked as $kChecked => $vChecked)
        				{
        					if(!isset($box[$kChecked]))
        					{//ist es in der Datenbank aber nicht angehackt
								if($this->asDBTable->bIsNnTable)
								{
								}else
								{
									if(preg_match("/not_null/", $checkBoxColumn["flags"]))
									{
										$this->SqlResult[$kChecked][$isCheck]= null;
									}
									if($checkBoxColumn["type"]=="string")
									{
										$this->SqlResult[$kChecked][$isCheck]= "##st_deleteSet";
									}
								}
        					}
        				}
					}
				}
			}
			return $checked;
		}
		function makeDbChanges($checked)
		{
			global $HTTP_POST_VARS;

			$error= $this->msg->getAktualMessageId();
			if(	isset($HTTP_POST_VARS["stlisttable_make"])
				and
				$error==="NOERROR"						)
			{// checkboxen mit Datenbank abgleichen

				Tag::echoDebug("fieldArray", "file:".__file__." line:".__line__);
				$fields= $this->db->fieldArray($this->statement, $this->getOnError("SQL"));
				$showTypes= array_flip($this->showTypes);
				$isCheck= $showTypes["check"];

				if(isset($isCheck))
				{
					$box= $HTTP_POST_VARS[$isCheck];
					//take checked directly from database
					//(incomming parameter)
    				//$checked= $HTTP_POST_VARS["checked_".$isCheck];
				}
				if(	!is_string($this->insertStatement)
					and
					$this->asDBTable->bIsNnTable
					and
					count($box) > count($checked)		)
				{
					$field= $this->asDBTable->searchByColumn($columnContent["name"]);
   					$aliasName= $field["alias"];
					$this->db->foreignKeyModification($this->asDBTable);
					foreach($this->asDBTable->columns as $columnContent)
					{
						if(!preg_match("/auto_increment/", $columnContent["flags"]))
						{
							if(isset($this->asDBTable->aSetAlso[$columnContent["name"]][STINSERT]))
							{
								$this->nnTableInsert[$columnContent["name"]]= $this->asDBTable->aSetAlso[$columnContent["name"]][STINSERT];
							}else
							{
								$value= $this->asDBTable->getWhereValue($columnContent["name"]);
								if(count($value))
								{
									$bSetValue= false;
									foreach($value as $content)
									{
										if(	$content["type"]==="value"
											and
											$content["operator"]==="="	)
										{
											if($bSetValue)
											{
												$this->msg->setMessageId("NNTABLEINSERT_MUCH@", $columnContent["name"]);
												return;
											}else
												$this->nnTableInsert[$columnContent["name"]]= $content["value"];
										}
									}

								}
							}
						}
					}
				}

				// if count from $box not the same as $checked
				// user check or uncheck an box
				if(count($box) || count($checked))
				{
					if(!$this->insertStatement)
					{
						$pkName= $this->asDBTable->getPkColumnName();
        				$field= $this->asDBTable->searchByColumn($pkName);
        				$aliasPk= $field["alias"];
						$existPk= false;
						foreach($this->SqlResult[0] as $column=>$content)
						{
							if($column===$aliasPk)
							{
								$existPk= true;
								break;
							}
						}
						//if(!isset($this->SqlResult[0][$aliasPk]))
						if(!$existPk)
						{
							$this->msg->setMessageId("NOPK_FORDBCHANGE@", $pkName);
							return;
						}
						if($this->asDBTable->bIsNnTable)
						{
    						$inserter= new STDbInserter($this->asDBTable);
    						$deleter= new STDbDeleter($this->asDBTable);

						}else
    					{
        					$field= $this->asDBTable->searchByAlias($isCheck);
        					$checkBoxColumn= $field["column"];
    						$updater= new STDbUpdater($this->asDBTable);
    					}
					}

					if(is_array($box))
					{// all fields which are checked from gui
        				foreach($box as $kBox => $vBox)
        				{
        					if(!isset($checked[$kBox]))
        					{//the value is checked, but not in database
								$bOnDbChanged= true;
								if($this->asDBTable->bIsNnTable)
								{
									if($this->insertStatement)
									{
        								$insert= $this->insertStatement;
        								foreach($aValues as $kValues => $vValues)
        									$insert= preg_replace("/%".$kValues."%/", $HTTP_POST_VARS[$vValues][$kBox], $insert);
        								if(!$this->db->fetch($insert, $this->getOnError("SQL")))
    									{
    										$this->msg->setMessageId("SQL_ERROR", $this->db->getError());
											break;
										}
									}else
									{
										foreach($this->asDBTable->columns as $columnContent)
										{
											if(!preg_match("/auto_increment/", $columnContent["flags"]))
											{
												echo __file__.__line__."<br />";
												echo "search value for column<br />";
												$value= $this->nnTableInsert[$columnContent["name"]];
												if($value===null)
												{
    												$field= $this->asDBTable->searchByColumn($columnContent["name"]);
    												$value= $this->SqlResult[$kBox][$field["alias"]];
												}
												echo "value in row $kBox from column ". $field["alias"]." SqlResult is $value<br />";
												echo "value from name ".$columnContent["name"]." in nnTableInsert is ".$this->nnTableInsert[$columnContent["name"]]."<br />";
												if(	$value===null
													and
													preg_match("/not_null/", $columnContent["flags"]))
												{
													$this->msg->setMessageId("NNTABLEINSERT_FAULT@", $columnContent["name"]);
													return;
												}
												echo __file__.__line__."<br />";
												echo "insert $value in column ".$columnContent["name"]."<br />";
												$inserter->fillColumn($columnContent["name"], $value);
											}
										}
										echo __file__.__line__."<br />";
										echo "fill next row<br />";
										$inserter->fillNextRow();
									}
        						}else// else no nnTable be set
								{
									$pkResult= $this->SqlResult[$kBox][$aliasPk];
									$checkBoxResult= $this->SqlResult[$kBox][$isCheck];
									$updater->where($pkName."=".$pkResult);
									$updater->update($checkBoxColumn, $checkBoxResult);
									$updater->fillNextRow();
								}// end of if nnTable in table-object save
        					}// end of if not box-key also before checked (in database)
						}// end of for-loop which fields be checked in the gui
					}// end of is value from box not null
					if(is_array($checked))
					{// all fields which are checked in the database
        				foreach($checked as $kChecked => $vChecked)
        				{
        					if(!isset($box[$kChecked]))
        					{// but not on the GUI
								$bOnDbChanged= true;
								if($this->asDBTable->bIsNnTable)
								{
									if($this->deleteStatement)
									{
        								$delete= $this->deleteStatement;
        								foreach($aValues as $kValues => $vValues)
        									$delete= preg_replace("/%".$kValues."%/", $HTTP_POST_VARS[$vValues][$kChecked], $delete);
        								if(!$this->db->solution($delete, $this->getOnError("SQL")))
    										$this->msg->setMessageId("SQL_ERROR", $this->db->getError());

									}else
									{
										$deleter->where($pkName."=".$this->SqlResult[$kChecked][$aliasPk]);
									}
								}else
								{
									$pkResult= $this->SqlResult[$kChecked][$aliasPk];
									$checkBoxResult= $this->SqlResult[$kChecked][$isCheck];
									$updater->where($pkName."=".$pkResult);
									$updater->update($checkBoxColumn, $checkBoxResult);
									$updater->fillNextRow();
								}
        					}
        				}
					}
					if(	!$this->asDBTable->bIsNnTable
						and
						!$this->insertStatement			)
					{
						if($updater->execute())
						{
							$this->msg->setMessageId("SQL_ERROR", $updater->getErrorString());
							return;
						}
					}elseif(!$this->insertStatement)
					{
						if($inserter->execute())
						{
							$this->msg->setMessageId("SQL_ERROR", $inserter->getErrorString());
							return;
						}
						if($deleter->execute())
						{
							$this->msg->setMessageId("SQL_ERROR", $deleter->getErrorString());
							return;
						}
					}
				}

				//st_print_r($this->SqlResult,2);
				if(!$bOnDbChanged)
					$this->msg->setMessageId("NO_CHANGING");
				else
				{
					if($this->msg->isDefOKUrl())
						$this->add($this->msg->getMessageEndScript());
					else
					{
						$params= new STQueryString();
						if(count($this->asDBTable->aPostToGet))
						{
							$post= $HTTP_POST_VARS;
							foreach($this->asDBTable->aPostToGet as $variable)
							{
								$post= $HTTP_POST_VARS;
								$varString= "";
								foreach($variable as $vKey=>$var)
								{
									$post= $post[$var];
									if($vKey)
										$var= "[".$var."]";
									$varString.= $var;
								}
								$varString.= "=".$post;
								$params->insert($varString);
							}
						}
						$address= $params->getStringVars();
						if(Tag::isDebug())
						{
							$tr= new RowTag();
								$td= new ColumnTag(TD);
									$td->colspan(40);
									$h1= new H1Tag();
										$h1->add("user will be forward to URL:");
										$h1->add(br());
										$a= new ATag();
											$a->href($address);
											$a->add($address);
										$h1->addObj($a);
									$td->addObj($h1);
								$tr->addObj($td);
							$this->addObj($tr);
						}else
						{
							header("location: ".$address);
							exit;
						}
					}
					//return $this->msg->getAktualMessageId();
				}
			}
		}
		function createOldInsertDeleteStatements()
		{
			if(	!is_string($this->insertStatement)
				or
				!is_string($this->deleteStatement)	)
			{
				return;
			}
			if($this->buttonText)
			{// welche Spalten werden f�r insert und delete ben�tigt

				Tag::echoDebug("fieldArray", "file:".__file__." line:".__line__);
				$fields= $this->db->fieldArray($this->statement, $this->getOnError("SQL"));
				$fieldArray= array();
				$nr= 0;
				foreach($fields as $column)
				{
					$fieldArray[$column["name"]]= $nr;
					++$nr;
				}
				$bOnDbChanged= false;
				$aValues= array();
				$showTypes= array_flip($this->showTypes);
				$isCheck= $showTypes["check"];
				$insert= $this->insertStatement;
				while(preg_match("/^(.*)%(.*)%(.*)$/", $insert, $preg))
				{
					$columnName= $preg[2];
					$set= $fieldArray[$columnName];
					if(!isset($set))
					{
						$split= preg_split("/-/", $columnName);
						$columnName= "";
						for($o= 1; $o<count($split); $o++)
							$columnName.= $split[$o]."-";
						$columnName= substr($columnName, 0, strlen($columnName)-1);
						if(!isset($fieldArray[$columnName]))
						{
							$isCheck= null;// isCheck auf NULL setzen, damit keine Aktion durchgef�hrt wird
							$this->msg->setMessageId("WRONG_INSERT",
										$this->msg->getMessageContent("WRONG_INSERT")."('".$preg[2]."')");
						}
						$aValues[$preg[2]]= $columnName;
					}else
						$aValues[$columnName]= $columnName;
					$insert= $preg[1].$preg[3];
				}
				$this->insertStatement= $insert;
				$delete= $this->deleteStatement;
				while(preg_match("/^(.*)%(.*)%(.*)$/", $delete, $preg))
				{
					$columnName= $preg[2];
					$set= $fieldArray[$columnName];
					if(!isset($set))
					{
						$split= preg_split("/-/", $columnName);
						$columnName= "";
						for($o= 1; $o<count($split); $o++)
							$columnName.= $split[$o]."-";
						$columnName= substr($columnName, 0, strlen($columnName)-1);
						if(!isset($fieldArray[$columnName]))
						{
							$isCheck= null;// isCheck auf NULL setzen, damit keine Aktion durchgef�hrt wird
							$this->msg->setMessageId("WRONG_DELETE", $this->msg->getMessageContent("WRONG_DELETE")."('".$preg[2]."')");
						}
						$aValues[$preg[2]]= $columnName;
					}else
						$aValues[$columnName]= $columnName;
					$delete= $preg[1].$preg[3];
				}
				$this->deleteStatement= $delete;
			}
		}
		function execute($onError= onErrorMessage)
		{
			global $HTTP_POST_VARS;

			Tag::alert(!typeof($this->getTable(), "STDbTable") && !$this->statement,
						get_class($this)."::execute()",
						"no table or statement exist, take before methode ::table() or ::solution() for statement");
				
			$this->createMessages();
			$this->defaultOnError($onError);
			$this->createStatement();

			// setze zuerst das SQL-Statement ab
			if(!$this->SqlResult)
				$this->makeResult($onError);

			$this->createOldInsertDeleteStatements();

			// if the table has checkBoxes,
			// or is an nnTable
			// make changes in database/SqlResult
			$onDbChecked= $this->createDbChanges();

			//hole aus der Tabelle alle Felder,
			//welche in einer anderen Form angezeigt werden sollen
			$tMainTable= true;

			$bTablehasCheck= false;
			if(!isset($this->SqlResult))
			{
				$indexTr= new RowTag();
					$indexTd= new ColumnTag(TD);
						$index= &$this->getIndexTable();
						$indexTd->addObj($index);
						$indexTd->addObj($this->msg->getMessageEndScript());
					$indexTr->addObj($indexTd);
				$this->addObj($indexTr);
				return $this->msg->getAktualMessageId();
			}
			$obj= $this->SqlResult;
			//$defaultAddress= $this->address;
			if(!count($obj))
			{
				$tr= new RowTag();
					$th= new ColumnTag(TH);
						$index= $this->getIndexTable();
						$th->add($index);
					$tr->add($th);
				$this->add($tr);
				// 2017/03/22 alex:
				// wird bereits von STSearchBox
				// inerhalb der execute() Methode erledigt
				//$this->msg->setMessageId("EMPTY_RESULT");
				//$this->add($this->msg->getMessageEndScript());
				return $this->msg->getAktualMessageId();
			}

			/******************************************************
			 **        Umschlichtung wenn Layout VERTICAL        **
			 ******************************************************/
			if($this->arrangement==STVERTICAL)
			{//Columns werden in Rows geschlichtet und umgekehrt
				$Rows= array();
				foreach($obj[0] as $key=>$value)
					$Rows[$key]= array();
				$objCount= count($obj);
				foreach($obj[0] as $key=>$value)
				{
					$keyRow= &$Rows[$key];
					for($n= 0; $n<$objCount; $n++)
						$keyRow[$n]= $obj[$n][$key];
				}
			}else
			{
				$Rows= &$obj;
			}
			/***********************************************/


			$this->SqlResult= &$Rows;
			$this->createTags();

			// if the table has checkBoxes,
			// or is an nnTable
			// make the createt changes in database
			$this->makeDbChanges($onDbChecked);

			$tr= new RowTag();
				$td= new ColumnTag(TD);
					$td->add($this->msg->getMessageEndScript());
				$tr->add($td);
			$this->add($tr);
			//STCheck::warning(1,"","");
			
			return $this->msg->getAktualMessageId();
  	}
		function form($buttonText, $formName, $action= null)
		{
			$this->buttonText= $buttonText;
			$this->formName= $formName;
			$this->formAdr= $action;
		}
		function display()
		{
			if($this->log)
			{
				global $user;
				$debug= $user->isDebug();
				$user->debug(true);
				$user->LOG("start STListBox->display", 4);
				$user->debug($debug);
			}
			STBaseTableBox::display();
			if($this->log)
			{
				$debug= $user->isDebug();
				$user->debug(true);
				$user->LOG("end STListBox->display", 4);
				$user->debug($debug);
			}
			return true;
		}
	function setLinkByNull($bSet= true)
	{
		$this->bSetLinkByNull= $bSet;
	}
	function setParamOnActivate($do, $param, $linkColumnAlias)
	{
		$field= $this->findAliasOrColumn($linkColumnAlias, true);
		$alias= $field["alias"];
		if(!is_array($this->setParams["onActivate"][$alias]))
			$this->setParams["onActivate"][$alias]= array();
		$this->setParams["onActivate"][$alias][]= array("do"=>$do, "param"=>$param);
	}
	function setAsLinkParam($linkColumnAlias, $asParam)
	{
		$field= $this->findAliasOrColumn($linkColumnAlias, true);
		$alias= $field["alias"];
		$this->setParams["asParam"][$alias]= $asParam;
	}
	function table($table, $name= null)
	{
		if(typeof($table, "STBaseTable"))
		{
			$this->arrangement= $table->listArrangement;
			$this->bCaption= $table->bListCaption;
			if(count($table->asForm))
			{
				$this->buttonText= $table->asForm["button"];
				$this->formName= $table->asForm["form"];
				$this->formAdr= $table->asForm["action"];
			}
		}
		STBaseTableBox::table($table, $name);
		if(typeof($table, "STBaseTable"))
			$this->takeTypesFromTable();
	}
	function setCaption($bSet)
	{
		$this->bCaption= $bSet;
	}
}

?>