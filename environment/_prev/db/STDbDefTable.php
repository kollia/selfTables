<?php

require_once($_stdbtable);
		
class STDbDefTable extends STDbTable
{
	var $container;
		
	function __construct($table, &$container, $onError= onErrorStop)
		{
			Tag::paramCheck($table, 1, "string", "STAliasTable");
			Tag::paramCheck($container, 2, "STDbTableContainer");
			STDbTable::__construct($table, $container, $onError);	
		}
}
?>