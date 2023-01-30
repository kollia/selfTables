<?php


		$user= $STForum->siteCreator->getUserManagement();
		$php_id= $user->getSessionID(); 		
		$statement= "select ID from MUAnswerList where subject='".$php_id."'";
		$newID= $STForum->db->fetch_single($statement);
		if(!$newID)
		{
			$statement= "insert into MUAnswerList(questionRef,subject) values(0,'".$php_id."')";
			$STForum->db->fetch($statement);
			$statement= "select ID from MUAnswerList where subject='".$php_id."'";
			$newID= $STForum->db->fetch_single($statement);
		}
		
		
		$box= new STItemBox($STForum->db);
			$box->align("center");
			$box->submitButtonValue(" send ");
			$box->inputSize("subject", 90);
	
	if(isset($HTTP_GET_VARS["answer"]))
	{//st_print_r($container, 5);	
		$MUAnswerList= &$STForum->db->needTable("MUAnswerList");
			$MUAnswerList->select("subject", "Betreff");
			$MUAnswerList->select("answer", "Antwort");
			$box->table($MUAnswerList);
			$nBeitraege= count($container["content"]);
			$box->preSelect("subject", "AW: ".$container["content"][($nBeitraege-1)]["subject"]);
			$box->setAlso("ID", $newID);
			$box->setAlso("questionRef", $HTTP_GET_VARS["stget"]["link"]["lesen"]);
			$box->setAlso("sendDate", "sysdate()");
			$box->setAlso("who", $STForum->siteCreator->getUserID());
			$box->inputSize("answer", 70, 40);
	}else
	{
		$user= $STForum->siteCreator->getUserManagement();
		$MUQuestionList= &$STForum->db->needTable("MUQuestionList");
			$MUQuestionList->clearSelects();
			$MUQuestionList->select("subject", "Betreff");
			$MUQuestionList->select("question", "Beitrag");
			$box->table($MUQuestionList);
			$box->setAlso("createDate", "sysdate()");
			$box->setAlso("userID", $user->getUserID());
			$box->inputSize("question", 70, 40);
	}
			$STForum->get->getParamString(DELETE, "answer");
			$STForum->get->getParamString(DELETE, "question");
			$url= "index.php".$STForum->get->getParamString(DELETE, "stget[link][lesen]");
			$box->onOkGotoUrl($url);
			//Tag::debug(true);
			
			   $box->where("ID=".$newID);	
		$done= $box->execute(UPDATE);
		//echo $done."<br />";
		if(	$done=="NOERROR"
			and
			isset($HTTP_GET_VARS["answer"])	)
		{
			$statement= "update MUQuestionList set status='answered' where ID=".$whoReaded["questionID"];
			$STForum->db->fetch($statement);
			
			$subject= $box->getResult("subject");
			$message=  "wenn sie wieder eine Anfrage auf diese Antwort richten,\n";
			$message.= "bitte geben sie dann im Feld 'Referenz auf Antwort' diese Nummer \"";
			$message.= $newID."\" ohne Anf�hrungszeichen an\n\n";
			$message.= "Antwort:\n";
			$message.= $box->getResult("answer");
			$header1= "CC:";//.$whoReaded["email"];
			$header2= "-f alexander.kolli@meduni-graz.at";
			mail($whoReaded["email"], $subject, $message, $header1, $header2);
		}
		
		if(!isset($HTTP_GET_VARS["question"]))
			require("listBoard.php");
		$box->display();
		
?>