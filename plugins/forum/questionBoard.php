<?php

	
	$officerResult= $this->createKategoryOfficerSequence($this->nCategoryID);
	$backAddress= $this->get->getParamString(DELETE, "question");
	$user= $this->getUserManagement();
	$ID= $this->nCategoryID;
	$officerUserName= $user->getUserName();
	$bFound= false;
	foreach($officerResult as $officerIndex=>$content)
	{
		if($content["KategoryID"]==$this->nCategoryID)
		{
			$bFound= true;
			break;
		}
	}
	Tag::alert(!$bFound, "questionBoard.php", "do not found any right Kategory in createKategoryOfficer");
	$box= new STQuestionBox($ID, $user);
	if($officerResult[$officerIndex]["newsCluster"])
		$box->setNewsGroup($officerResult[$officerIndex]["newsCluster"]);
	if($officerResult[$officerIndex]["up"]=="Y")
		$box->uploadAttachment("/home/htdocs/userinteraction/uploaded");//, "application/vnd.ms-excel, application/x-zip-compressed, application/msword");
		$box->inputSize("subject", 88);
		$box->inputSize("question", 90, 15);
		$box->unSelect("answerRef");
		$box->preSelect("customID", $ID);
		$box->disabled("customID");
		$box->align("center");
		$box->style("border-width:1; border-style:outset; border-darkcolor:#000000; border-lightcolor:#ffffff");
		$box->onOkGotoUrl($backAddress);
	if(is_array($container))
	{		
		$last= $container["content"][(count($container["content"])-1)];
		preg_match("/^^(AW:( |(&#160;)))?(.*)$/", $last["subject"], $preg);
		if(isset($last["answer"]))
		{
			$box->setAlso("answerRef", $last["ID"]);
		}else
			$box->setAlso("ownRef", $last["ID"]);
		$box->preSelect("subject", $preg[4]);
	}
		// alex 11/05/2005:	erroiere ob der User selbst ein Verantwortlicher ist
		//					dann sollen keine EMails verschickt werden
		$aOfficer= $box->getOfficerArray($ID);//HTTP_GET_VARS["Kategorie"]);
		//st_print_r($aOfficer);
		if(array_value_exists($user->getUserID(), $aOfficer)!==false)
			$box->sendEmails(false);
		$box->execute(INSERT);
	
	$table= new TableTag();
		$table->width("100%");
		$tr= new RowTag();
			$td= new ColumnTag(TD);
				$td->align("right");
				$button= new ButtonTag();
					$button->type("button");
					$button->add("zur�ck");
					$button->onClick("javascript:document.location.href='".$backAddress."'");
				$td->add($button);
				$td->add(br());
				$td->add(br());
			$tr->add($td);
		$table->add($tr);
		$tr= new RowTag();
			$td= new ColumnTag(TD);
				$td->add($box);
			$tr->add($td);
		$table->add($tr);
	$table->display();
		
		
?>