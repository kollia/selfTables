<?php

require_once($php_html_description);
require_once($php_javascript);

class STChooseBox extends TableTag
{
		var	$tableContainer;
		var $aEntries;
		var	$startPage;
		var	$aButtons= array();
		var	$sButtonAlign= "center";
		var $bForward= false;
		
		// $Db muss STDatabase oder ein Array sein
		// wenn $Db ein Array ist sind die Eintr�ge [Button-Name] => Adresse
		// oder [Button-Name] => [0] => Adresse
		//						 [1] => Beschreibung	
		function __construct(&$container, $class= "tableChoose")
		{
			TableTag::__construct($class);
			// alex 26/04/2005:	Bei Angabe von Datenbank oder Array
			//					wird nun die Variable f�r besseres Verst�ndnis
			//					auf eigene Members aufgeteilt,
			//					sowie das Array wurde noch weiter aufgeschl�sselt
			//					in Adresse und Beschreibung
			if(is_array($container))
			{
				$first= key($container);
				if(!is_array($container[$first]))
				{
					$newArray= array();
					foreach($container as $Button=>$Address)
						$newArray[$Button]= array($Address);
					$this->aEntries= $newArray;
				}else
					$this->aEntries= $container;
			}else
				$this->tableContainer= &$container;
		}
		function setStartPage($file)
		{
			$this->startPage= $file;
		}
		function noChoise($array)
		{
			$this->aNoChoise= $array;
		}
		function execute($table= null)
		{
			if(Tag::isDebug())
			{
				/*if(	!typeof($this->tableContainer, "stdbtablecontainer")
					and $table===null	)
				{
					echo "<b>Error:</b> if \$Db in constructor from STChooseBox is no object from STDbTableContainer,<br />";
					echo "\$table in function execute can not be null";
					exit;					
				}*/
				if(!$this->startPage)
				{
					showBackTrace();
					if(STCheck::isDebug())
						STCheck::echoDebug("", "for object STChooseBox is no start site defined");
					else
						echo "for object STChooseBox is no start site defined";
					exit;
				}
			}		
			$nButtonCreated= 0;
			// alex	26/04/2005:	Abfrage muss nun nur mehr sein ob Db NULL ist,
			//					da ein Array nun auf aEntries gelegt wird
			if($this->tableContainer)
			//if(typeof($this->tableContainer, "OSTDatabase"))
			{
				$aktTable= $this->tableContainer->getTableName();
				$aTables= array();
				if(is_string($table))
					$aTables[]= $this->getTable($table);
				else
					$aTables= &$this->tableContainer->getTables();
				
				
				$get= new STQueryString();
				foreach($aTables as $table)
				{
					if($table)
					{
    					$tableName= $table->getName();
    					if(	array_value_exists($tableName, $this->aNoChoise)===false
							and
							$tableName!=$aktTable										)
    					{
    						$get->resetParams();
							//echo "for table $tableName set Action ".$table->sFirstAction."<br />";
    						$get->update("stget[action]=".$table->sFirstAction);
							$get->update("stget[table]=".$tableName);
    						$address=  $this->startPage;
    						$address.= $get->getStringVars();
    						$sButton= $table->getDisplayName();
							if(!$sButton)
								$sButton= $tableName;
    						$this->makeButton("  ".$sButton."  ", $address);
    						++$nButtonCreated;							
    					}elseif($tableName==$aktTable)
						{
							$tr= new RowTag();
								$td= new ColumnTag(TD);
									$td->height(20);
								$tr->add($td);
							$this->add($tr);
							++$nButtonCreated;
						}
					}
					if($nButtonCreated===1)
					{
						$get->update("stget[fromchoose][onlyone]=true");
						$address=  $this->startPage;
						$address.= $get->getStringVars();
					}
				}
			}else // else of if($this->tableContainer)
			{	// alex 26/04/2005:	var aEntries wurde oben erweitert
				if(is_array($this->aEntries))
					foreach($this->aEntries as $name=>$entry)
					//foreach($this->tableContainer as $name=>$address)
					{
						$sButton= $entry[0];
						$address= $entry[1];
						$this->makeButton($name, $sButton, $address);
						$nButtonCreated++;
					}
				if($nButtonCreated===1)
				{
					if(preg_match("/\?/", $address))
						$address.= "&";
					else
						$address.= "?";
					$address.= "stget[fromchoose][onlyone]=true";
				}
			}// end of if($this->tableContainer)
			if(	$nButtonCreated===1
				and
				$this->bForward
				and
				$this->tableContainer->getAction()==STCHOOSE	)
			{
				Tag::alert(!$address, "STChooseBox::execute()", "address for showed button $sButton to forward is null");
				if(Tag::isDebug())
				{
					$tr= new RowTag();
						$td= new ColumnTag(TD);
							$td->align("center");
							$h1= new H1Tag();
								$h1->add("table $sButton is only one, so user will be forward:");
								$h1->add(br());
								$a= new ATag();
									$a->href($address);
									$a->add($address);
								$h1->add($a);
							$td->add($h1);
						$tr->add($td);
					$this->add($tr);
				}else
				{
					$script= new JavascriptTag();
					$script->add("document.location.href='".$address."'");
					$this->add($script);
				}
			}	
			return $nButtonCreated;
		}
		function getButtons()
		{
			return $this->aButtons;
		}
		function getFirstButtonAddress()
		{
			return reset($this->aButtons);
		}
		function alignButtons($align)
		{
			$this->sButtonAlign= $align;
		}
	function makeButton($name, $address)
	{
		// alex 08/06/2005:	sollten die alten Parameter erhalten bleiben?
		//					nochmal �berdacht, und doch nicht eingef�gt.
		//					wenn von aussen ein Parameter extra gel�scht wurde
		//					bleibt er hierbei ja sonst wieder erhalten 
		/*$address= trim($address);
		$split= preg_split("/\?/", $address);
		$address= $split[0];
		$params= $split[1];
		$Get= new STQueryString();
		if($params)
		{
			$split= preg_split("/&/", $params);
			foreach($split as $param)
				$Get->getParamString(STINSERT, $param);
		}   
		$address.= $Get->getParamString();*/
   
		$this->aButtons[$name]= $address;
		$tr= new RowTag();
			$td= new ColumnTag(TD);
				$td->align($this->sButtonAlign);
				$td->valign("middle");
				$button= new ButtonTag("menue");
					$button->add($name);
					$address= "javascript:location='".$address."'";
					$button->onClick($address);
				$td->add($button);
			/*if($description)
			{
				$td->add(br());
				$td->add($description);
				$td->add(br());
			}*/
			$tr->add($td);
		$this->add($tr);
	}
	function forwardByOne()
	{
		$this->bForward= true;
	}
}

?>