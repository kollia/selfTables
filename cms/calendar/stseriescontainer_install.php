<?php


		require_once($_stdbtabledescriptions);
		
		$_stseriescontainer_table_description= &STDbTableDescriptions::instance();
		//     setColumnInTable($tableName, $column, $type, $null= true, $pk= false, $fkToTable= null, $unikIndex= 0)
		$_stseriescontainer_table_description->table("exceptions");
		$_stseriescontainer_table_description->column("exceptions", "ID", "int", false);
		$_stseriescontainer_table_description->primaryKey("exceptions", "ID");
		$_stseriescontainer_table_description->autoIncrement("exceptions", "ID");
		$_stseriescontainer_table_description->column("exceptions", "date", "date", false);
		$_stseriescontainer_table_description->uniqueKey("exceptions", "date", 1);
		$_stseriescontainer_table_description->column("exceptions", "description", "text", false);
		$_stseriescontainer_table_description->column("exceptions", "mainMenueID", "int", false);
		$_stseriescontainer_table_description->uniqueKey("exceptions", "mainMenueID", 1);
		$_stseriescontainer_table_description->foreignKey("exceptions", "mainMenueID", "mainMenue");
		$_stseriescontainer_table_description->column("exceptions", "shifted", "set('N','Y')", false);
		$_stseriescontainer_table_description->column("exceptions", "toDate", "date", true);
		$_stseriescontainer_table_description->column("exceptions", "make", "set('Y','N')", false);
				
		//     setColumnInTable($tableName, $column, $type, $null= true, $pk= false, $fkToTable= null, $unikIndex= 0)
		$_stseriescontainer_table_description->table("calendarSeries", "Staat");
		$_stseriescontainer_table_description->column("calendarSeries", "ID", "int", false);
		$_stseriescontainer_table_description->primaryKey("calendarSeries", "ID");
		$_stseriescontainer_table_description->autoIncrement("calendarSeries", "ID");
		//$_stseriescontainer_table_description->column("calendarSeries", "beginTime", "time", false);
		//$_stseriescontainer_table_description->column("calendarSeries", "endTime", "time", false);
		//$_stseriescontainer_table_description->column("calendarSeries", "continuityType", "enum('days','weeks','months','years')", false);
		$_stseriescontainer_table_description->column("calendarSeries", "model", "set('daily','weekly','monthly','yearly')", false);
		$_stseriescontainer_table_description->column("calendarSeries", "AllDWM", "int", true);
		$_stseriescontainer_table_description->column("calendarSeries", "worktime", "set('N','Y')", false);
		$_stseriescontainer_table_description->column("calendarSeries", "Sunday", "set('N','Y')", false);
		$_stseriescontainer_table_description->column("calendarSeries", "Monday", "set('N','Y')", false);
		$_stseriescontainer_table_description->column("calendarSeries", "Tuesday", "set('N','Y')", false);
		$_stseriescontainer_table_description->column("calendarSeries", "Wednesday", "set('N','Y')", false);
		$_stseriescontainer_table_description->column("calendarSeries", "Thursday", "set('N','Y')", false);
		$_stseriescontainer_table_description->column("calendarSeries", "Friday", "set('N','Y')", false);
		$_stseriescontainer_table_description->column("calendarSeries", "Saturday", "set('N','Y')", false);
		$_stseriescontainer_table_description->column("calendarSeries", "myChoose", "set('day','weekday')", true);
		$_stseriescontainer_table_description->column("calendarSeries", "myWeekday", "int", true);
		$_stseriescontainer_table_description->column("calendarSeries", "myMonth", "int", true);
		$_stseriescontainer_table_description->column("calendarSeries", "myday", "int", false);
		$_stseriescontainer_table_description->column("calendarSeries", "BeginDate", "date", false);
		$_stseriescontainer_table_description->column("calendarSeries", "ending", "set('keinEnde','anDatum')", false);
		$_stseriescontainer_table_description->column("calendarSeries", "EndDate", "date", false);		
		//$_stseriescontainer_table_description->column("calendarSeries", "EndTermins", "int", false);
		//$_stseriescontainer_table_description->column("calendarSeries", "toEntry", "int", false);
?>