<?php

class STBaseSearch extends TableTag
{
	var $sMethod= "get";
	var $categoryName;
	var $aoTable= array();
	var	$aoGroups= array();
	
	var $checkBoxNrs= array();
	var	$lastCheckBoxNr= 0;
	var $nDisplayColumns= 1; //in wieviel Spalten die checkboxen aufgeteilt werden sollen
	var $aCategorys= array(); // alle Kategorien welche beinhaltet sind

	var $choiseWhere;	
	var $checkAll= false;//ob alle Checkboxen aktiviert sein sollen
	var $asCheckBoxes= array(); // welche CheckBox aktiviert sein soll
	var $categoryList= array();// Liste alle Kategorien
	var	$columns; // aus welchen Felder die categoryList besteht
	var $sFieldset= false; // ob um die Kategorien ein Rand angezeigt werden soll
	var $sInputType= "checkbox"; // ob die Gruppe aus Checkboxen oder Radio-Buttons besteht
	
	var $bBreakAfterGroup= true;
	var	$sbAndOrDefined= null; // ob die AndOrRadioButtons aktiviert sind 
	var	$sbCaseSensitive= null; // ob zwischen Klein und Gro�schreiben unterschiden werden soll
	var	$sbWholeWords= null; // ob auch ganze Worte ber�cksichtigt werden sollen
	
	function __construct($name, $id= "STCategoryGroup")
	{		
		TableTag::__construct($id);
		$this->categoryName= $name;
	}
	function addCategory(&$oCategory, $bBreakAfterGroup= false)
	{
		Tag::paramCheck($oCategory, 1, "STCategoryGroup");
		Tag::paramCheck($bBreakAfterGroup, 2, "bool");
				
		//$this->choiseWhere= array_merge($this->choiseWhere, $oCategory->choiseWhere);
		$oCategory->bBreakAfterGroup= $bBreakAfterGroup;
		$aCategory[]= &$oCategory;
		$lastGroup= count($this->aCategorys)-1;
		if($lastGroup>=0)
		{
			$lastCat= count($this->aCategorys[$lastGroup])-1;
			if($this->aCategorys[$lastGroup][$lastCat]->bBreakAfterGroup)
				$this->aCategorys[]= &$aCategory;
			else
				$this->aCategorys[$lastGroup][]= $oCategory;
		}else
			$this->aCategorys[]= &$aCategory;
	}
	function getCategoryTags($catCount= "")
	{		
		$nCategorys= count($this->aCategorys); 	
		$mainCategory= $this->getThisCategoryTags($catCount."_0");
		$table= new TableTag("STCategoryGroup");
		$table->cellspacing(0);
		$table->cellpadding(0);
		if($nCategorys)
		{
			$countCategorys= 1;	
			$nCategorys= count($this->aCategorys);
			// alex 11/10/2005: for-schleife statt foreach
			//					weil dort nur eine Kopie erzeugt wird
			//					f�r die Setzung identifizierung durch die Get-Vars
			//					wird aber eine Refferenz ben�tigt
			for($c= 0; $c<$nCategorys; $c++)	
			//foreach($this->aCategorys as $group)	
			{
				$tr= new RowTag();		
				$count= count($this->aCategorys[$c]);
				if(	$count==1
					and
					!$mainCategory)
				{
					$td= new ColumnTag(TD);
					$td->valign("top");
					$tags= $this->aCategorys[$c][0]->getCategoryTags($catCount."_".$c);
					$c++;
					$td->add($tags);
					$tr->add($td);
				}else
				{		
					if($mainCategory)
					{
						$td= new ColumnTag(TD);
						$td->valign("top");
						$td->add($mainCategory);
						$tr->add($td);
						$mainCategory= null;
					}	
					$n2Categorys= count($this->aCategorys[$c]);
					// alex 11/10/2005: for-schleife statt foreach
					//					weil dort nur eine Kopie erzeugt wird
					//					f�r die Setzung identifizierung durch die Get-Vars
					//					wird aber eine Refferenz ben�tigt
					for($n= 0; $n<$n2Categorys; $n++)
					//foreach($group as $category)
					{
						$td= new ColumnTag(TD);
						$td->valign("top");
						$tags= $this->aCategorys[$c][$n]->getCategoryTags($catCount."_".$countCategorys);
						++$countCategorys;
						$td->add($tags);
						$tr->add($td);
					}
				}
				$table->add($tr);
			}
		}elseif($mainCategory)
		{
			$tr= new RowTag();
			$td= new ColumnTag(TD);
			$td->valign("top");
			$td->add($mainCategory);
			$tr->add($td);
			$table->add($tr);
		}else
			return null;
		if(	$this->sFieldset!==false
			and
			$this->sFieldset!==null	)
		{
			$tr= new RowTag();
			$td= new ColumnTag(TD);
			$fieldSet= new FieldSetTag();
			$fieldsetName= $this->categoryName;
			if(is_string($this->sFieldset))
				$fieldsetName= $this->sFieldset;
			if($fieldsetName)
			{
				$legend= new LegendTag();
				$legend->add($fieldsetName);
			}
			$fieldSet->add($legend);
			$fieldSet->add($table);
			$td->add($fieldSet);
			$tr->add($td);
			$table= new TableTag("STCategoryGroup");
			$table->add($tr);
			$table->cellspacing(0);
			$table->cellpadding(0);
		}
		return $table;		
	}
	/*protected*/function getThisCategoryTags($catCount)
	{
		global	$HTTP_GET_VARS,
				$HTTP_POST_VARS;
		
    	if($this->sMethod=="post")
    		$GetPost= $HTTP_POST_VARS;
    	else
    		$GetPost= $HTTP_GET_VARS;
    	$set= $GetPost["stget"]["searchbox"][$this->categoryName];
			
		$count= count($this->categoryList);
		if(!$count)
			return null;
		$rows= ceil($count/$this->nDisplayColumns);		
		$columns= $this->nDisplayColumns;
		$table= new TableTag("STCategoryGroup");
		$table->cellspacing(0);
		$table->cellpadding(0);
		$count= 0;
		for($r= 0; $r<$rows; $r++)
		{
			$tr= new RowTag();
			for($c= 0; $c<$columns; $c++)
			{
				if(!isset($this->categoryList[$count]))
					break;
				$td= new ColumnTag(TD);
					$iTable= new TableTag("STCategoryGroup");
    	        		$iTable->cellspacing(0);
	            		$iTable->cellpadding(0);
						$iTr= new RowTag();
							$iTd= new ColumnTag(TD);
							$iTd->valign("top");
							
					$input= new InputTag();
						$input->type($this->sInputType);
						$value= $catCount."_".$this->categoryList[$count]["value"];
						$this->categoryList[$count]["value"]= $value;
						$name= "stget[searchbox][".$this->categoryName."]";
						if($this->sInputType=="checkbox")
							$name.= "[".$count."]";
						$input->name($name);
						$input->value($value);
						$check= false;
						if($set)
						{
  							if($this->sInputType=="checkbox")
							{
								if(in_array($value, $set))
									$check= true;
							}else
							{
								if($set==$value)
									$check= true;
							}
						}elseif($this->checkAll)
							$check= true;
						elseif($this->asCheckBoxes[$this->categoryList[$count]["name"]])
							$check= true;
						if($check)
							$input->checked();
							
					$iTd->add($input);
				$iTr->add($iTd);
				$iTd= new ColumnTag(TD);
					$iTd->add($this->categoryList[$count]["name"]);
				$iTr->add($iTd);
				$iTable->add($iTr);
				$td->add($iTable);
				$tr->add($td);
				$count++;
			}
			$table->add($tr);
		}
		return $table;
		
	}
	function radioButtons($bRadio)
	{
		Tag::paramCheck($bRadio, 1, "bool");
		if($bRadio)
			$this->sInputType= "radio";
		else
			$this->sInputType= "checkbox";
	}
	function tableCheckButtons($table, $columns= 1)
	{
		Tag::paramCheck($table, 1, "STBaseTable");
		Tag::paramCheck($columns, 2, "int");
		
		$table->clearSelects();
		$this->columns= $table->getIdentifColumns();
		$this->sPk= $table->getPkColumnName();
		$bNeedPk= true;
		foreach($this->columns as $column)
		{
			$columnName= $column["column"];
			if($columnName==$this->sPk)
				$bNeedPk= false;
			$table->select($columnName);
		}
		if($bNeedPk)
			$table->select($this->sPk);
		$statement= $table->db->getStatement($table);
		$result= $table->db->fetch_array($statement, MYSQL_ASSOC);
		foreach($result as $row)
		{
			$text= "";
			foreach($this->columns as $column)
				$text.= $row[$column["column"]]. " - ";
			$text= substr($text, 0, strlen($text)-3)."&#160;&#160;&#160;";
			$where= $this->sPk."=".$row[$this->sPk];
			//$this->tableCheckBox($table->getName(), $text, $where);	
			$this->categoryList[]= array("value"=>$row[$this->sPk], "name"=>$text);		
		}
		$this->inColumns= $columns;
	}
	function displayColumns($columns)
	{
		$this->nDisplayColumns= $columns;
	}
	function fieldset($bDraw)
	{
		Tag::paramCheck($bDraw, 1, "bool", "string", "empty(string)");
		$this->sFieldset= $bDraw; 
	}
	function checkAll()
	{
		$this->checkAll= true;
	}
	function check($text)
	{
		$this->asCheckBoxes[$text]= "check";
	}
	function checkButton($text, $then= null, $else= null)
	{
		if(is_String($then))
			$then= new STDbWhere($then);
		if(is_String($else))
			$else= new STDbWhere($else);
		// wenn die Where-Objekte keiner Tabelle zugeordnet sind
		// kann angenommen werden das sie zur Tabelle $tableName geh�ren
		if(	$then
			and
			!$then->getForTableName()	)
		{
			$then->forTable($tableName);
		}
		if($else)
			if(!$else->getForTableName())
				$else->forTable($tableName);
			
		$this->lastCheckBoxNr++;
		$this->categoryList[]= array(	"value"=> $this->lastCheckBoxNr,
										"name"=>  $text,
										"then"=>  $then,
										"else"=>  $else							);		
/*		// f�r identification �ber get oder post
		// wird f�r jeden unterschiedlichen Text
		// eine Nummer vergeben
		if(!$this->checkBoxNrs[$text])
		{		
			$this->lastCheckBoxNr++;
			$this->checkBoxNrs[$text]= $this->name.$this->lastCheckBoxNr; 
		}*/
	}
	function &getNewCategory($name)
	{	
		if(typeof($this, "STSearchBox"))
			$category= new STCategoryGroup($name);
		else
			$category= &$this;
		return $category;
	}
	function createWhere(&$oTable)
	{
		$newWhere= new STDbWhere();
		foreach($this->categoryList as $checkBox)
		{					
			if($this->isChecked($checkBox["name"]))
				$newWhere->andWhere($checkBox["then"]);
			else
				$newWhere->andWhere($checkBox["else"]);
		}
		//$oTable->andWhere($newWhere);
		if($newWhere->isModified())
		{
    		$where= $oTable->getWhere();
    		if($where && $where->isModified())
    			$where->andWhere($newWhere);
    		else
    			$where= $newWhere;
    		$oTable->where($where,null, true);
		}
		foreach($this->aCategorys as $category)
			foreach($category as $innerCategory)
				$innerCategory->createWhere($oTable);
	}
	function isChecked($name)
	{
		foreach($this->categoryList as $entry)
		{
			if($entry["name"]==$name)
			{
				$value= $entry["value"];
				break;
			}
		}
		if(!$value)
			return false;
		$bSet= false;
		$param= new GetHtml();
		$vars= $param->getArrayVars();
		$set= $vars["stget"]["searchbox"][$this->categoryName];
		if($this->sInputType=="checkbox")
		{
			if($set)
    			foreach($set as $entry)
    			{
    				if($value==$entry)
    				{
    					$bSet= true;
    					break;
    				}
    			}
		}else
			if($value==$set)
				$bSet= true;
		return $bSet;
	}
	function isAndChecked()
	{
		if($this->sbAndOrDefined)
		{
			if(is_bool($this->sbAndOrDefined))
				return $this->sbAndOrDefined;
			return $this->isChecked($this->sbAndOrDefined);
		}
		foreach($this->aCategorys as $category)
			foreach($category as $innerCategory)
			{
				$bRv= $innerCategory->isAndChecked();
				if($bRv!==null)
				{// deffinition der Variable als boolean
				 // damit kein zweites mal gesucht werden muss
					$this->sbAndOrDefined= $bRv;
					return $bRv;
				}
			}
		return null;
	}
	function isCaseSensitiveChecked()
	{
		if($this->sbCaseSensitive)
		{
			if(is_bool($this->sbCaseSensitive))
				return $this->sbCaseSensitive;
			return $this->isChecked($this->sbCaseSensitive);
		}
		foreach($this->aCategorys as $category)
			foreach($category as $innerCategory)
			{
				$bRv= $innerCategory->isCaseSensitiveChecked();
				if($bRv!==null)
				{// deffinition der Variable als boolean
				 // damit kein zweites mal gesucht werden muss
					$this->sbCaseSensitive= $bRv;
					return $bRv;
				}
			}
		return null;
	}
	function isWholeWordsChecked()
	{
		if($this->sbWholeWords)
		{
			if(is_bool($this->sbWholeWords))
				return $this->sbWholeWords;
			return $this->isChecked($this->sbWholeWords);
		}
		foreach($this->aCategorys as $category)
			foreach($category as $innerCategory)
			{
				$bRv= $innerCategory->isWholeWordsChecked();
				if($bRv!==null)
				{// deffinition der Variable als boolean
				 // damit kein zweites mal gesucht werden muss
					$this->sbWholeWords= $bRv;
					return $bRv;
				}
			}
		return null;
	}
	function defineAndOrRadioButtons($break= false, $and= "und", $or= "oder")
	{
		$group= &$this->getNewCategory("AndOr");	
		$group->checkButton($and);
		$group->checkButton($or);
		$group->fieldset("");
		$group->radioButtons(true);
		$group->check($or);
		$group->sbAndOrDefined= $and;
		if(typeof($this, "STSearchBox"))
			$this->addCategory($group, $break);
	}
	// ACHTUNG: nur wenn die Column im Table nicht mit BINARY definiert ist
	function defineCaseSensitiveButton($break= false, $name= "Gro&szlig;/Kleinschreibung beachten")
	{
		$category= &$this->getNewCategory("CaseSensitive");
		$category->checkButton($name);
		$category->fieldset(false);
		$category->sbCaseSensitive= $name;
		if(typeof($this, "STSearchBox"))
			$this->addCategory($category, $break);
	}
	function defineWholeWordsButton($break= false, $name= "Nur ganze W&ouml;rter")
	{
		$category= &$this->getNewCategory("WholeWords");
		$category->checkButton($name);
		$category->fieldset(false);
		$category->sbWholeWords= $name;
		if(typeof($this, "STSearchBox"))
			$this->addCategory($category, $break);
	}
	function defineMatchButtons($break= false, $sensitive= "Gro&szlig;/Kleinschreibung beachten", $words= "Nur ganze W&ouml;rter")
	{
		$category= &$this->getNewCategory("MatchWords");
		$category->checkButton($sensitive);
		$category->checkButton($words);
		$category->fieldset(false);
		$category->sbCaseSensitive= $sensitive;
		$category->sbWholeWords= $words;
		if(typeof($this, "STSearchBox"))
			$this->addCategory($category, $break);
	}
	function defineSearchButtons($break= false,	$and= "und", $or= "oder",
												$sensitive= "Gro&szlig;/Kleinschreibung beachten", $words= "Nur ganze W&ouml;rter")
	{
		//$category1= &$this->getNewCategory("AndOr");
		$category1= new STCategoryGroup("AndOr");
		$category1->defineAndOrRadioButtons(false, $and, $or);
		$category2= new STCategoryGroup("MatchWords");
		$category2->defineMatchButtons(false, $sensitive, $words);
		
		$this->addCategory($category2, false);
		//if(typeof($this, "STSearchBox"))
			$this->addCategory($category1, $break);
	}
}
?>