<?php

		require_once("st_development.php");
		require_once($site_creator);
		require_once($stselectbox);
		require_once($database_selector);
		require_once($_stsearchbox);
		require_once($stquestionbox);
		require_once("STForum.php");

//		Tag::debug(true);
		//Tag::debug("fieldArray");
		//Tag::debug("db.statements.select");
		$DB_host= "localhost";
		$DB_user= "interact";
		$DB_password= "mufeedback";
		$DB_database= "UserInteraction";
		$DB_project= 17;//UserInteraction

		$db= new STDatabase();
		$db->connect($DB_host, $DB_user, $DB_password);
		$db->toDatabase($DB_database);
		$object= new STForum($DB_project, $db, $client_root."/userinteraction/index.php");

		$result= $object->execute();
		if(	$object->getAction()==DELETE
			and
			$result=="NOERROR"
			and
			isset($sAnswerInClausel)			)
		{
			$MUAnswer= &$db->getTable("MUAnswerList");
			$statement= $db->getDeleteStatement($MUAnswer, $sAnswerInClausel);
			$db->fetch($statement);
		}
		$object->display();


?>