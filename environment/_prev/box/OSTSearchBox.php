<?php

require_once($base_table);

class OSTSearchBox extends OSTBaseTableBox
{
		var	$aResult= null;
		var $sMethod= "get";
		var $showAllPath= null;
		var	$oWhere= null;
		var	$aIn= array();// alle Spalten in denen gesucht werden soll
		var	$sqlEffect= MYSQL_NUM;
		var $choiseWhere= array();
		var	$lastCheckBoxNr= 0;
		var $checkBoxNrs= array();
		var $showAllButtonName= "l&ouml;sche Suche";
		
		function OSTSearchBox($database, $class= "OSTSearchBox")
		{		
			OSTBaseTableBox::OSTBaseTableBox($database, $class);
			$this->msg->setMessageContent("EMPTY_RESULT", "kein Ergebnis f�r den Suchbegriff");
			$this->msg->setMessageContent("SQL_ERROR", "Hier wird der SQL-Error Gesetzt");
			$this->msg->setMessageContent("NOERROR", "");
			$this->asDBTable= array();//asDBTable wird in searchBox f�r mehrere Tabellen verwendet
		}
		function table($oTable)
		{
			if(!typeof($oTable, "OSTDbTable"))
			{
				echo "<b>Error:</b> the table given in OSTSearchBox must be  an OSTDbTable object";
				exit;
			}
			$this->asDBTable[]= $oTable;
		}
		function makeButtonToShowAll($path= null)
		{			
			if(!$path)
			{
				$get= new GetHtml();
				$path= $get->getParamString(DELETE, "stget[searchbox][searchField]");
			}
			$this->showAllPath= $path;
		}
		function setShowAllButtonName($name)
		{
			$this->showAllButtonName= $name;
		}
		function setSqlEffect($type)
		{
			$this->sqlEffect= $type;
		}
		function execute($onError= onErrorMessage)
		{
			global	$HTTP_GET_VARS,
					$HTTP_POST_VARS;
			
			$this->defaultOnError($onError);
			if($this->sMethod=="post")
				$GetPost= $HTTP_POST_VARS;
			else
				$GetPost= $HTTP_GET_VARS;
			$value= $GetPost["stget"]["searchbox"]["searchField"];
			if(isset($value))
				$this->searchValue($value);
			
			$this->makeBox($value);
			$this->add($this->msg->getMessageEndScript());
			return $this->msg->getAktualMessageId();
		}
		function method($value)
		{
			$this->sMethod= strtolower(trim($value));
		}
		function select($column, $alias= null)
		{
			echo "<b>Error:</b> the function ->select() can not use in OSTSearchBox,<br />\n";
			echo " please apply select only in the tables!";
			exit;
		}
		function checkBox($text, $then, $else= null)
		{		
			$this->tableCheckBox("all", $text, $then, $else);	
		}
		function tableCheckBox($tableName, $text, $then, $else= null)
		{
			if(is_String($then))
				$then= new STDbWhere($then);
			if(is_String($else))
				$else= new STDbWhere($else);
			// wenn die Where-Objekte keiner Tabelle zugeordnet sind
			// kann angenommen werden das sie zur Tabelle $tableName geh�ren
			if(!$then->getForTableName())
				$then->forTable($tableName);
			if(!$else->getForTableName())
				$else->forTable($tableName);
							
			if(!$this->choiseWhere[$tableName])
				$this->choiseWhere[$tableName]= array();
			$this->choiseWhere[$tableName][$text]= array();
			$this->choiseWhere[$tableName][$text]["then"]= &$then;
			$this->choiseWhere[$tableName][$text]["else"]= &$else;
			
			// f�r identification �ber get oder post
			// wird f�r jeden unterschiedlichen Text
			// eine Nummer vergeben
			if(!$this->checkBoxNrs[$text])
			{		
				$this->lastCheckBoxNr++;
				$this->checkBoxNrs[$text]= $this->lastCheckBoxNr; 
			}
		}
		function searchValue($value)
		{
			if(!trim($value))
				return;
			foreach($this->asDBTable as $table)
				$this->search($table, $this->aIn[$table->getName()], $value);
			
			
			$bOk= false;
			foreach($this->aResult as $result)
			{
				if($result)
					$bOk= true;
			}				
			if(!$bOk)
				$this->msg->setMessageId("EMPTY_RESULT", $this->msg->getMessageContent("EMPTY_RESULT")." \"$value\"");
			else
				$this->msg->setMessageId("NOERROR");
		}
		function search($DBTable, $inColumn, $value)
		{
			global	$HTTP_GET_VARS;
					$where= new STDbWhere();
					$searchWhere= new STDbWhere();
			
			
			if(	count($this->aIn)==0 )
			{
				$inColumn= $DBTable->show;
			}
			$tableName= $DBTable->getName();
			$inCount= count($inColumn);
			if($inCount)
			{
				$split= preg_split("/ +/", $value);
				foreach($split as $search)
				{
					$date= $this->db->makeSqlDateFormat($search);
					if($date)
						$is= "='$date'";
					else
						$is= " like '%$search%'";
					//$aliasTable= $this->db->getAliases($this->asDBTable);
					foreach($inColumn as $column)
					{
						//$alias= $aliasTable[$column["table"]];
						//if($alias)				alex 29.12.2004: 	alias wird in der OSTDatabase erzeugt
							//$where.= $alias.".";						wenn n�tig
						$searchWhere->orWhere($column["column"].$is);
					}					
				}
				$searchWhere->forTable($tableName);
			}
			// where Statement aus checkBoxen erstellen			
			$kategorys= array_merge($this->choiseWhere["all"], $this->choiseWhere[$tableName]);
			foreach($kategorys as $text=>$do)
			{
				if($HTTP_GET_VARS["stget"]["searchbox"]["check"][$this->checkBoxNrs[$text]])
					$ifOb= &$do["then"];
				else					
					$ifOb= &$do["else"];
				$where->andWhere($ifOb);
			}
			$whereTable= $where->getForTableName();
			$searchTable= $searchWhere->getForTableName();
			if($whereTable==$searchTable)
			{
				$where->andWhere($searchWhere);
				$DBTable->where($where);
			}else
			{
				if(!typeof($DBTable, "OSTDbSelector"))
				{
					echo "<br /><b>##Error:</b> cannot search in table ";
					echo $tableName;
					echo " with an diffrent STDbWhere-Object from table ";
					echo $whereTable;
					echo " (inserted table in OSTSearchBox, must be an object from OSTDbSelector)<br />";
				}else
				{
					$DBTable->where($searchWhere);
					$DBTable->where($where);
				}
			}
			$statement= $this->db->getStatement($DBTable);
			$this->aResult[$tableName]= $this->db->fetch_array($statement, $this->sqlEffect, $this->getOnError("SQL"));
							
			if(!$this->aResult[$tableName])
			{
				if($this->db->errno()!=0)
					$this->msg->setMessageId("SQL_ERROR", $this->db->getError());
			}
		}
		function inColumn($tableName, $column)
		{
			if(!isset($this->aIn[$tableName]))
				$this->aIn[$tableName]= array();
			$this->aIn[$tableName][]= array("table"=>$tableName, "column"=>$column);
		}
		function &getResult_array($tableName= null)
		{
			if($this->aResult===NULL)
				return array();
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
			return $this->aResult[$tableName];
		}
		function getResult_single_array($nRow= 0, $tableName= null)
		{
			if($this->aResult===NULL)
				return array();
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
				echo "       so must given tableName in function ->getResult_single_array()";
				exit;
			}
			if(!$this->aResult[$tableName])
				return null;
			$aRv= array();
			foreach($this->aResult[$tableName] as $row)
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
			if($this->aResult===NULL)
				return "";
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
				echo "       so must given tableName in function ->getResult_inClause()";
				exit;
			}
			if(!$this->aResult[$tableName])
				return null;
			$sRv= "";
			foreach($this->aResult[$tableName] as $row)
			{
				if($nRow===0)
					$value= reset($row);
				else
					$value= $row[$nRow];
				if(!is_numeric($value))
					$value= "'$value'";
				$sRv.= $value.",";
			}
			$sRv= "in(".substr($sRv, 0, strlen($sRv)-1).")";
			return $sRv;			
		}
		function makeBox($value)
		{
			global	$HTTP_GET_VARS;	
			
			
			$getHtml= new GetHtml();
			$getparams= $getHtml->getParamString();
			
			$form = new FormTag();
				$form->action($getparams);
				$form->method($this->sMethod);
				$tr= new RowTag();
					$td= new ColumnTag(TD);
					if($this->sMethod=="get")
						$td->add(GetHtml::getHiddenParamTags("stget[searchbox]"));
						
						$td->width(80);
						$td->align("right");
						$td->add("suche nach:");
					$tr->add($td); 
					$td= new ColumnTag(TD);
						$td->width(175);
						$input= new InputTag("field");
							$input->type("text");
							$input->name("stget[searchbox][searchField]");
							$input->size(28);
  							$input->value($value);
						$td->add($input);	
					$tr->add($td); 
					$td= new ColumnTag(TD);
						$td->width(100);
						$td->rowspan(2);
						$td->valign("top");
						$input= new InputTag("button");
							$input->type("submit");
							$input->value("suchen");
						$td->add($input);
			if($this->showAllPath)
			{// zeige Alles Button
						$td->add(br());
						$input= new InputTag("button");
							$input->type("button");
							$input->value($this->showAllButtonName);
							$input->onClick("javascript:location.href='".$this->showAllPath."'");
						$td->add($input);
			}
					$tr->add($td);					
				$form->add($tr);
				
			if($this->choiseWhere)
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
			}
			$this->add($form);
		}
}


?>