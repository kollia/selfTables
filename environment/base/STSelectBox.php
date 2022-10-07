<?php


require_once($php_html_description);


class STSelectBox extends TableTag
{
		var $method= null;
		var	$file;
		var $sSettings;
		var	$aSettings;
		var	$aSelects= array();
		var	$aPreSelects;
		var	$sFirstOption;
		var	$asFirstNullRegister;
		var $aWithout= array();
		var	$sCallAsName;
		
		// alex 20/06/2005:	habe Parameter umgedreht,
		//					da ich den class-Parameter f�r wichtiger halte
		//					und ich glaube bis Dato wurde der settings-Parameter
		//					eh nie gebraucht
		function __construct($class= "STSelectBox", $settings= STGET)
		{
			global	$HTTP_POST_VARS;
			
			TableTag::__construct($class);
			$this->sSettings= $settings;
			if(is_array($settings))
			{
				$this->aSettings= $settings;
				$this->sSettings= "INCOMMING";
			}elseif($settings==STPOST	)
			{
				$this->aSettings= $HTTP_POST_VARS;
			}else
			{
				$get= new STQueryString();
				$params= $get->getArrayVars();
				$this->aSettings= $params;
			}
		}
		function needForm($toFile= "", $method= STGET)
		{
			$this->file= $toFile;
			$this->method= $method;
		}
		/**
		 *	@param $name:	der Name vor der Pop-Up-Box
		 *	@param $array: 	ist eine array wie aus einem DB-Select
		 *	@param $key: 	name der Spalte f�r die values des option-Tags
		 *	@param $value:	name der Spalte welche als Liste im Pop-up angezeigt wird
		 *  @param $var:	name der Variable im gesetzten Array (HTTP_POST/GET_VARS) des Konstruktors (optional $name)
		 */
		function select($name, $array, $key, $value, $var= null)
		{
			if(Tag::isDebug())
			{
				if(!is_array($array))
				{
					echo "<b>Warning</b> second Parameter in STSelectBox::select() must be an <b>array</b><br />";
				}
			}
			if($var===null)
				$var= $name;
			$selects= array();
			$selects["name"]= $name;
			$selects["array"]= $array;
			$selects["key"]= $key;
			$selects["value"]= $value;
			$selects["var"]= $var;
			$this->aSelects[]= $selects;
		}
		function setFirstNullRegisterName($value)
		{	// alex 20/06/2005:	Parameter selectName heraus genommen
			//					da jede STSelectBox eh in einer eigenen Table
			//					existieren kann
			$this->asFirstNullRegister= $value;
		}
		function setValueByNoParam($value)
		{	// alex 20/06/2005:	Parameter selectName heraus genommen
			//					da jede STSelectBox eh in einer eigenen Table
			//					existieren kann
			$this->sFirstOption= $value;
		}
		function preSelect($key)
		{	// alex 20/06/2005:	Parameter selectName heraus genommen
			//					da jede STSelectBox eh in einer eigenen Table
			//					existieren kann
			$this->aPreSelects= $key;
		}
		function description($name)
		{
			$this->sCallAsName= $name;
		}
		function onChange($value= "submit()")
		{
			$this->onChange= $value;
		}
		function execute()
		{
			$get= new STQueryString();
			$aNames= array();// in diesem Array werden alle Select Namen 
							 // welche im Parameter �bergeben werden
							 // als key aufgelistet
			$form= new FormTag();
			$tr= new RowTag();
			foreach($this->aSelects as $selects)
			{
				$array= array();
				if($this->asFirstNullRegister)
				{// wenn ein NullRegisterName angegeben ist
				 // diesen an erster stelle einf�gen
					$register= array();
					$register[$selects["key"]]= null;
					$register[$selects["value"]]= $this->asFirstNullRegister;
					$array[-1]= $register;					
				}
				$array= array_merge($array, $selects["array"]);
				
				if(isset($this->aPreSelects))
					$choosen= $this->aPreSelects;
				else
				{
					if(preg_match("/\[/", $selects["var"]))
					{
						$split= preg_split("/\[/", $selects["var"]);
						$choosen= $this->aSettings;
						$get= new STQueryString($this->aSettings);
						$get->delete($selects["var"]);
						$this->aSettings= $get->getArrayVars();
						foreach($split as $param)
						{
							$param= trim($param);
							$len= strlen($param)-1;
							if(substr($param, $len)=="]")
								$param= substr($param, 0, $len);
							$choosen= $choosen[$param];
							
						}
					}else
						$choosen= $this->aSettings[$selects["var"]];
				}
				$td= new ColumnTag(TD);
				if($this->sCallAsName)
					$td->add($this->sCallAsName.": ");
					$aNames[$selects["var"]]= true;
					// alex 27/05/2005:	wenn keine Option gesetzt ist
					//					mimm sie aus sFirstOption
					if($choosen===null)
						$choosen= $this->sFirstOption;
					$select= $this->getSelectArray($array, $selects["key"], $selects["value"], $choosen);
					$select->name($selects["var"]);
					if($this->onChange!==null)
						$select->onChange($this->onChange);
					$td->add($select);
					$td->align("center");
				$tr->add($td);
			}
			$td= new ColumnTag(TD);
			$this->makeHiddenVars($td, $this->aSettings, $aNames);
			$tr->add($td);
			if($this->method)
			{
				$form->method($this->method);
				$form->action($this->file.$get->getStringVars());
				$form->addObj($tr);
				$this->addObj($form);
			}else
				$this->addObj($tr);
		}
		function withoutParam($param)
		{
			$this->aWithout[$param]= $param;
		}
		function makeHiddenVars(&$tag, $array, $aNames, $var= "")
		{
			foreach($array as $key=>$value)
			{
				if($var)
					$variable= $var."[".$key."]";
				else
					$variable= $key;
				if(	is_array($value))
				{
					$this->makeHiddenVars($tag, $value, $aNames, $variable);
				}elseif(!$aNames[$key])
				{
					if(!isset($this->aWithout[$variable]))
					{
						$input= new InputTag();
							$input->type("hidden");
							$input->name($variable);
							$input->value($value);
						$tag->add($input);
					}
				}
			}
		} 		
		function getSelectArray($array, $nKey, $nValue, $selected)
		{
			$selectTag= new SelectTag();
			foreach($array as $row)
			{
				$option= new OptionTag();
				$option->value($row[$nKey]);				
				$option->add($row[$nValue]);
				//echo "'".$selected."'=='".$row[$nKey]."'<br>";
				if($selected==$row[$nKey])
					$option->selected();
				$selectTag->add($option);
			}
			return $selectTag;
		}
}
?>