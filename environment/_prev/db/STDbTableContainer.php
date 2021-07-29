<?php

require_once($_stobjectcontainer);

class STDbTableContainer extends STObjectContainer
{
	function __construct($name, &$container)
	{
		Tag::paramCheck($name, 1, "string");
		Tag::paramCheck($container, 2, "STObjectContainer");
		
		STObjectContainer::STObjectContainer($name, $container);
	}
	/*public*/function getTableName($tableName= null)
	{
	    Tag::paramCheck($tableName, 1, "string", "null");
			
		if($tableName===null)
		    return STObjectContainer::getTableName();
	    $orgTableName= $this->aExistTables[$tableName]["table"];
		if(!$orgTableName)
		    $orgTableName= $tableName;
		return $orgTableName;
	}
	function &needTable($tableName)
	{
		Tag::paramCheck($tableName, 1, "string");
		$nParams= func_num_args();
		Tag::lastParam(1, $nParams);
		
	    $orgTableName= $this->getTableName($tableName);
    	if(!$orgTableName)
    	    $orgTableName= $tableName;
    	$table= &STObjectContainer::needTable($orgTableName);
    	if(!typeof($table, "STDbDefTable"))
    	{
    	    $table= new STDbDefTable($table, $this);
    		$this->tables[$orgTableName]= $table;
    	}
    	return $table;
	}
	function stgetParams($containerName= null)
	{
		Tag::paramCheck($containerName, 1, "string", "null");
		
		if($containerName===null)
			$containerName= $this->getName();
		$params= new GetHtml();
		$stget= $params->getArrayVars();
		$stget= $stget["stget"];		
		while($stget)
		{
			if($stget["container"]===$containerName)
				return $stget;
			$stget= $stget["older"];
		}
		return null;
	}
	function &getTable($tableName= null)
	{
		Tag::paramCheck($tableName, 1, "string", "null");
		$nParams= func_num_args();
		Tag::lastParam(1, $nParams);
		
		if(!$tableName)
			$tableName= $this->getTableName();
		if(!$tableName && Tag::isDebug())
		    Tag::warning(!$tableName, "STDbTableContainer::getTable()", "can not find any table in container ".$this->getName());
		$orgTableName= $this->getTableName($tableName);
		
		if(!$orgTableName)
		   $orgTableName= $tableName;
		if(!$tableName)
		    return null;
		
		$table= &$this->tables[$orgTableName];
		if(!$table)
		{	// alex 24/05/2005:	// alex 17/05/2005:	die Tabelle wird von der �berschriebenen Funktion
			//					getTable() aus dem Datenbank-Objekt geholt.
			//					nat�rlich ohne Referenz, da sie noch in STDbContainerTable ge�ndert wird
			unset($this->tables[$orgTableName]);
			$table= $this->db->getTable($orgTableName);
			$table= new STDbDefTable($table, $this);
		}
		return $table;
	}
}

?>