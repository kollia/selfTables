<?php

require_once($_stdbinserter);
		
class STDbDefInserter extends STDbInserter
{
    var $container;
		
		function __construct(&$container, &$table)
		{
		    Tag::paramCheck($container, 1, "STDbTableContainer");
		    Tag::paramCheck($table, 2, "STDbTable");
				
		    $this->container= &$container;
		    STDbInserter::STDbInserter($table);
		}
		function fillColumn($column, $value)
		{
		    Tag::paramCheck($column, 1, "string");
		    Tag::paramCheck($value, 2, "string", "empty(string)", "int", "null");
				
				$column= $this->container->getColumnFromTable($this->table->getName(), $column);
				STDbInserter::fillColumn($column, $value);
		}
}
?>