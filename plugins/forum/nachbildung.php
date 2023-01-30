<?php

class table
{
	var $name;
	var $v;
	
	function table($name)
	{
		$this->name= $name;
	}
}
class database extends container
{
	var	$name;
	
	function database($name)
	{
		$this->name= $name;
		container::container($this);
	}
}
class container
{
	var $cont= array();
	var $db;
	
	function container(&$db)
	{
		$this->db= $db;
	}
	function &needTable($name)
	{
		$oTable= new table($name);
		$this->cont[]= &$oTable;
		//$this->db->cont[]= &$oTable;
		return $oTable;
	}
}

$oDb= new database("db");
$oTable= &$oDb->needTable("new");
$oTable->v= 1;
print_r($oDb);

?>