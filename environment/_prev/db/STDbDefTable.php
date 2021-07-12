<?php

		require_once($_stdbtable);
		
		class STDbDefTable extends STDbTable
		{
		    var $container;
				
		    function STDbDefTable($table, &$container, $onError= onErrorStop)
				{
				    Tag::paramCheck($table, 1, "string", "STAliasTable");
				    Tag::paramCheck($container, 2, "STDbTableContainer");
					STDbTable::STDbTable($table, $container, $onError);	
				}
		}
?>