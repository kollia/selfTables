<?php


		//Tag::debug("db.statement");
		//Tag::debug("gethtml.delete");
		$siteCreator->chooseInTable(false);
		$container= $siteCreator->getContainer();
		$db= $container->getDatabase();		
		$questionList= $container->getTable("MUQuestionList");
		$questionList->clearSelects();
		$get= new GetHtml();
		$get->getParamString(DELETE, "STSearchBox_searchField");
		if(!isset($HTTP_GET_VARS["where"]))
		{
			// alex 04/05/2005:	select-Statement ge�ndert
			//					brauche den ganzen Overhead nicht
			//$accessGroupTable= $container->getTable("MUJoinCluster");
			$statement=  "select p.Name,j.ClusterID from MUJoinCluster as j";//$db->getStatement($accessGroupTable);
			$statement.= " left join MUKategorys as k on j.KategoryID=k.ID";
			$statement.= " left join UserManagement.MUProject as p on k.projectID=p.ID";
			$result= $db->fetch_array($statement, MYSQL_ASSOC);
			$access= array();
			foreach($result as $row)
				$access[$row["Name"]]= $row["ClusterID"];
			$kategoryTable= &$container->getTable("MUKategorys");
			$kategoryTable->clearSelects();
			$kategoryTable->distinct();
			$kategoryTable->select("projectID");
			$statement= $db->getStatement($kategoryTable);
			$result= $db->fetch_single_array($statement, MYSQL_ASSOC);
			$oManagement= $siteCreator->getUserManagement();

			if(isset($HTTP_GET_VARS["stget"]["onlyone"]))
				$siteCreator->setBackButtonAddress(null);
			else
			{
				$get->getParamString(DELETE, "stget[onlyone]");
				$backAddress= $get->getParamString(DELETE, "stget[table]");
				$backAddress= $get->getParamString(DELETE, "stget[searchbox][searchField]");
				$backAddress= $get->getParamString(UPDATE, "stget[action]=choose");
				$siteCreator->setBackButtonAddress($backAddress);
				$get= new GetHtml();
				$get->getParamString(DELETE, "STSearchBox_searchField");
			}
			$projects= array();
			foreach($result as $projectName)
			{
				if(!isset($projects[$projectName]))
				{
					$params= $get->getParamString(UPDATE, "where=".$projectName);
					if(isset($access[$projectName]))					
					{
						if($oManagement->hasAccess($access[$projectName]))
							$projects[$projectName]= "index.php".$params;
					}
				}
			}
			$chooseTable= new STChooseTable($projects);
			$chooseTable->align("center");
			$chooseTable->setStartPage("index.php");
    		$chooseTable->execute();
			
			$html= new HtmlTag();
				$head= &$siteCreator->getHead("Auswahlmenue");
			$html->addObj($head);
			$body= new BodyTag();
				$headline= &$siteCreator->getHeadline($HTTP_GET_VARS["stget"]);
				$body->addObj($headline);
    		$body->addObj($chooseTable);
			$html->addObj($body);
			$html->display();
			exit;
		}else // from if(!isset($HTTP_GET_VARS["where"]))
		{// Hier werden die kategorien angezeigt
		
			/**************************************************************/
			/*		hole ProjektID aus der DB							  */
				// alex 10/05/2005:	refferenz "&" vor $siteCreator->getVar() entfernt,
				//					da sich die where-Clausel auf die Tabelle 
				//					MUKategory beim join auf MUProject auswirkt
  			$project= $siteCreator->getVar("project");
  			$project->where("Name='".$HTTP_GET_VARS["where"]."'");
				$whereSelect= new STDbSelector($project);
				$whereSelect->execute();
				$projectID= $whereSelect->getSingleResult();				
			/**************************************************************/
			
			
			if(!isset($HTTP_GET_VARS["stget"]["link"]))
			{// also wird auch eine SearchBox ben�tigt
			 // wenn nicht der Ablauf einer Frage angezeigt wird
				$MUQuestion= $container->getTable("MUQuestionList");
					$MUQuestion->clearSelects();
					$MUQuestion->clearFKs();				
					$MUQuestion->select("ID");
				$MUAnswer= $container->getTable("MUAnswerList");
				$AnswerSelect= new STDbSelector($MUAnswer);
					$AnswerSelect->add($MUQuestion);
					$AnswerSelect->select("MUAnswerList", "ID");
					$AnswerSelect->select("MUQuestionList", "ID", "Q_ID");
					$AnswerSelect->innerJoin("questionRef", $MUQuestion);
									
/*				$MUAnswer->clearSelects();
				$MUAnswer->select("ID");
				$MUAnswer->select("questionRef");
				$MUAnswer->clearFKs();
				$MUAnswer->foreignKey("questionRef", $MUQuestion);	*/
				//Tag::debug(db.statement);

				// erroiere in welchen Kategorien gesucht werden soll
				$currentCategorie= $HTTP_GET_VARS["Kategorie"];
				if(!$currentCategorie)
				{
					$statement=  "select ID from MUKategorys where projectID=";
					$statement.= $projectID." and parentID is null";
					$categorys= $db->fetch_single_array($statement);
					if(count($categorys)==1)
						$currentCategorie= $categorys[0];
					else echo __FILE__." ".__LINE__." toDo: mehrere ROOT-Verzeichnisse vorhanden";
				}
				$aKategories= getSubKategoryIDs($currentCategorie);
				$inClausel= "in(";
				foreach($aKategories as $kategory)
					$inClausel.= $kategory.",";
				$inClausel= substr($inClausel, 0, strlen($inClausel)-1);
				$inClausel.= ")";
				
				//Tag::debug("db.statement");
				$searchBox= new STSearchBox($db);
					$searchBox->table($MUQuestion);
					$searchBox->makeButtonToShowAll();
					$searchBox->inColumn("MUQuestionList", "subject");
					$searchBox->inColumn("MUQuestionList", "question");
						$text= "Unterkategorien bei der Suche miteinbeziehen";
						$then= new STDbWhere("customID ".$inClausel);
							$then->forTable("MUQuestionList");
						$else= new STDbWhere("customID=".$currentCategorie);
							$else->forTable("MUQuestionList");
					$searchBox->tableCheckBox("MUQuestionList", $text, $then, $else);
					$searchBox->table($AnswerSelect);
					$searchBox->inColumn("MUAnswerList", "subject");
					$searchBox->inColumn("MUAnswerList", "answer");
					$searchBox->tableCheckBox("MUAnswerList", $text, $then, $else);
					$searchBox->execute();
				//$statement= $db->getStatement($AnswerSelect);
					
				$questionResult= $searchBox->getResult_single_array(0, "MUQuestionList");
				$questionResult= getFirstQuestionIds($questionResult);
				$answerResult= $searchBox->getResult_single_array(0, "MUAnswerList");
				$answerResult= getQuestionIdsFromAnswer($answerResult);
				$questionResult= array_merge($questionResult, $answerResult);
				$questionInClausel= "in (";
				$aWrite= array();// alle in die Inclausel eingef�gten ID's
				if($questionResult)
				{
					foreach($questionResult as $ID)
					{
						if(!$aWrite[$ID])
						{
							$questionInClausel.= $ID.",";
							$aWrite[$ID]= "written";
						}
						
					}
					$questionInClausel= substr($questionInClausel, 0, strlen($questionInClausel)-1);
					$questionInClausel.= ")";
				}else
					$questionInClausel= null;
				
		//echo "questionResult: ".$questionInClausel."<br />";
				$siteCreator->addObjBehindHeadLineButtons($searchBox);
			}

				
			$stget= $HTTP_GET_VARS["stget"];
			$ownID= $siteCreator->getUserID();
			if(isset($stget["link"]))
			{// es wurde ein Link aus der Auflistung ausgewaehlt
			 // alle Eintraege mit Text ausgehend von der ersten Frage
			 // werden angezeigt
			  
				$MUQuestion= &$db->getTable("MUQuestionList");
				$MUQuestion->clearSelects();
				//$MUQuestion->select("customID");
				$where= new STDbWhere("ID=".$stget["link"]["lesen"]);
				$MUQuestion->where($where);
				$statement= $db->getStatement($MUQuestion);
				$result= $db->fetch_row($statement, MYSQL_ASSOC, onErrorStop);
				$whoReaded= array();
				$whoReaded["questionID"]= $stget["link"]["lesen"];
				$whoReaded["who"]= $result["who"];
				$whoReaded["status"]= $result["status"];
				$whoReaded["email"]= $result["email"];
				$whoReaded["subject"]= $result["subject"];
				$whoReaded["question"]= $result["question"];
  				$container= array();
				$container["head"]= array();
				$container["content"]= array();
				if($result["User"]=="unknownUser")
				{
					$aQuestions["head"]["User"]= $result["unknownUser"];
				}else
				{
					$aQuestions["head"]["User"]= $result["User"];
					$aQuestions["head"]["group"]= $result["GroupType"];
				}
				$statement=  "select ID from MUKategorys where Name='".$result["Kategory"];
				$statement.= "' and projectID=".$projectID;
				$kategoryID= $db->fetch_single($statement);
				
				$container["head"]["email"]= $result["email"];
				$container["head"]["Projekt"]= $result["Projekt"];
				$container["head"]["KategorieID"]= $kategoryID;
				$container["head"]["Kategorie"]= $result["Kategory"];
				$ownRef= $stget["link"]["lesen"];
				// suche zuerst die erste Frage
				$questionRef= $result["ownRef"];
				$answerRef= $result["answerRef"];
				$questionID= $result["ID"];
				while($questionRef || $answerRef)
				{
					if(isset($questionRef) && !isset($questionCont[$questionRef]))
					{
						$statement=  "select ID,subject,question,ownRef,answerRef from MUQuestionList";
						$statement.= " where ID=$questionRef";
						$result= $db->fetch_row($statement, MYSQL_ASSOC);
						$questionCont[$questionRef]= $result;
						$questionRef= $result["ownRef"];
						$answerRef= $result["answerRef"];
						$questionID= $result["ID"];
					}elseif(isset($answerRef) && !isset($answerCont[$answerRef]))
					{
						$statement=  "select ID,subject,answer,ownRef,questionRef from MUAnswerList";
						$statement.= " where ID=$answerRef";
						$result= $db->fetch_row($statement, MYSQL_ASSOC);
						$answerCont[$questionRef]= $result;
						$questionRef= $result["questionRef"];
						$answerRef= $result["ownRef"];
					}else
					{
						$questionRef= null;
						$answerRef= null;
					}
				}
				
				$statement=  "select unknownUser, subject, question,userID from MUQuestionList";
				$statement.= " where ID=".$questionID;
				$result= $db->fetch_row($statement, MYSQL_ASSOC);
					$who= $userNames[$result["userID"]];
					if(	!isset($who)
						and
						$result["userID"]	)
					{
						if($result["userID"]!=41)
						{
							$statement=  "select UserName,FullName from UserManagement.MUUser ";
							$statement.= "where ID=".$result["userID"];
							$o= $db->fetch_row($statement, MYSQL_ASSOC);
							if(isset($o["UserName"]))
								$of= " (".$o["UserName"].")";
							$userNames[$row["who"]]= $o["FullName"].$of;
							$who= $userNames[$row["who"]];
						}else
						{
							$who= $result["unknownUser"]." (unknown User)";
						}
					}else
						$who= "unknown User";
				$content= array();
				$content["ID"]= $questionID;
				$content["subject"]= $result["subject"];
				$content["question"]= ereg_replace(" ", "&#160;", $result["question"]);
				$content["question"]= ereg_replace("\n", "<br> ", $content["question"]);
				$content["who"]= $who;
				$container["content"][]= $content;

				// schreibe alle Fragen und Antworten sortiert in den Container
				fillContainer($container, $questionID, null);
				
								
					// Array von allen Fragen und Antworten
					//echo "<br /><br />";
					//print_r($aQuestions);
				
				echo "<html>\n";
				echo "	<body>\n";
				if(isset($HTTP_GET_VARS["answer"])	)
				{
					require_once("answerBoard.php");
				}elseif(isset($HTTP_GET_VARS["question"]))
				{
    				echo "<html>\n";
    				echo "	<body>\n";
					require_once("questionBoard.php");
    				echo "	</body>\n";
    				echo "</html>";
    				exit;
				}else
				{
					if(	$whoReaded["status"]=="created"
						or
						$whoReaded["status"]=="seen"	)
					{						
						if(!preg_match("/ $ownID\|/", $whoReaded["who"]))
							$whoReaded["who"].= " $ownID\|";
						$statement=  "update MUQuestionList set status='seen', who='".$whoReaded["who"]."'";
						$statement.= " where ID=".$stget["link"]["lesen"];
						$db->fetch($statement, onErrorShow);
					}
					require_once("listBoard.php");
				}
				echo "	</body>\n";
				echo "</html>";
				exit;
			}else
			{// es wurde kein Link aus der Auflistung ausgewaehlt
			 // Auflistung wird mit Link's zum lesen angezeigt
			 
				$currentKategory= $HTTP_GET_VARS["Kategorie"];
				// alex 10/05/2005:	wenn neuer Button "Beitrag" gedr�ckt wird
				//					soll das answerBoard auch ohne stget[link] Eintrag,
				//					f�r spezifischen Eintrag, aktiviert werden
				if(isset($HTTP_GET_VARS["question"]))
				{
    				echo "<html>\n";
    				echo "	<body>\n";
					require_once("questionBoard.php");
    				echo "	</body>\n";
    				echo "</html>";
    				exit;
				}
									
				/**************************************************************/
				/*		DB-Select											  */	
					$operator= "=";
					$nKategory= $HTTP_GET_VARS["Kategorie"];
					if($nKategory===null)
					{
						$operator= " is ";
						$nKategory= "null";
					}
					$statement=  "select ID,name from MUKategorys where projectID=".$projectID." and parentID".$operator.$nKategory;
					$array= $db->fetch_array($statement, MYSQL_ASSOC);
					$kategoryName= "Unter-Kategorie";
					if($nKategory=="null")
					{	
						// back Button auf Auswahl deffinieren
						$get->getParamString(DELETE, "Kategorie");
						$get->getParamString(DELETE, "Status");
						$get->getParamString(DELETE, "stget[searchbox][searchField]");
						$backAddress= "index.php".$get->getParamString(DELETE, "where");
						$siteCreator->setBackButtonAddress($backAddress);	
								
						if(count($array)==1)
						{// wenn nur eine Root-Kategorie des Projektes besteht
						 // das array f�r die n�chst H�here-Kategory-Verzweigung festlegen
						 
							$currentKategory= $array[0]["ID"];
							$statement=  "select ID, name from MUKategorys where projectID=".$projectID;
							$statement.= " and parentID =".$array[0]["ID"];
							$array= $db->fetch_array($statement, MYSQL_ASSOC);
echo $statement."<br />";
print_r($array);echo "<br />";
						}else
						{// sonst ist das zuvor deffinierte schon das n�chst H�here
							$kategoryName= "Kategorie";
							$currentKategory= 0;
						}
					}else
					{
						// back Button auf letzte Kategory, Auswahl oder FrageKatalog deffinieren
						if($currentKategory==$HTTP_GET_VARS["entrance"]["kategory"])
						{
							$backAddress= rawurldecode($HTTP_GET_VARS["entrance"]["address"]);
						}else
						{
							$statement= "select parentID from MUKategorys where ID=".$currentKategory;
							$nLastKategory= $db->fetch_single($statement);
							if(!$nLastKategory)
							{		
								$get->getParamString(DELETE, "Kategorie");
								$get->getParamString(DELETE, "Status");
								$get->getParamString(DELETE, "stget[searchbox][searchField]");
								$backAddress= $get->getParamString(DELETE, "where");
								$backAddress= "index.php".$backAddress;
							}else
							{
								$get->getParamString(DELETE, "Status");
								$get->getParamString(DELETE, "stget[searchbox][searchField]");
								$backAddress= "index.php".$get->getParamString(UPDATE, "Kategorie=".$nLastKategory);
							}
						}
						$siteCreator->setBackButtonAddress($backAddress);
					}
						$aKategorys= getHigherKategoryIDs($currentKategory);
						$inClausel= "in(";
						foreach($aKategorys as $one)
							$inClausel.= $one.",";
						$inClausel= substr($inClausel, 0, strlen($inClausel)-1).")";
						// alex 10/05/2005:	erstellung des Arrays $officerResult in Funktion 
						//					createKategoryOfficerSequence() verschoben,
						//					da ich dieses Array noch an anderen stellen ben�tige
						$officerResult= createKategoryOfficerSequence($currentKategory);
						$lastKategoryName= $officerResult[($officerAnz-1)]["Kategory"];
		
				/**************************************************************/
				/*		erzeuge Status										  */
				$showStatus= $HTTP_GET_VARS["Status"];
				if(!userIsOfficer($siteCreator, $currentKategory))
					$showStatus= "answered";				
				if(!isset($showStatus))
					$showStatus= "created";
				$where= new STDbWhere();	
				if($showStatus!="all")			
					$where->andWhere("status='$showStatus'");
				if($showStatus=="created")
				{
					$where2= new STDbWhere("status='seen'");
					$where2->andWhere("who not like '% $ownID|%'");
					$where->orWhere($where2);
				}elseif($showStatus=="seen")
				{
					$where->andWhere("who like '% $ownID|%'");
				}					
				/**************************************************************/
				
				// where Clausel f�r MUQuestionList deffinieren
				$question= &$container->getTable("MUQuestionList");
				if($questionInClausel)
				{// wenn $questionInClausel gesetzt ist
				 // wurde eine Suche getaetigt
				 	$thisWhere= new STDbWhere("ID ".$questionInClausel);
				}else
				{
					$thisWhere= new STDbWhere("customID=".$currentKategory);
					if(	$showStatus=="answered"
						or
						$showStatus=="all"	)
					{
						$thisWhere->andWhere("answerRef is null");
					}
					if(userIsOfficer($siteCreator, $currentKategory))
						$thisWhere->andWhere($where);
				}
				$question->where($thisWhere);
				
				if(	isset($inClausel)
					or
					$officerResult	)
				{
					$table= new TableTag();
						$table->border(false);
						$tr= new RowTag();
							$th= new ColumnTag(TH);
								$th->bgcolor("#C0C0C0");
								$th->colspan(3);
								$th->add($HTTP_GET_VARS["where"]." anfragen");
							$tr->add($th);
						$table->add($tr);
					$katAnz= count($officerResult);
					$o= 0;
					$lastKategory= "";
					$lastOfficer= "";
					if(	!$HTTP_GET_VARS["stget"]["searchbox"]["searchField"]
						or
						!$HTTP_GET_VARS["stget"]["searchbox"]["check"]["1"]	)
					{
						while($o<$katAnz)
						{
							$tr= new RowTag();
								$td= new ColumnTag(TD);
								if($officerResult[$o]["Kategory"]!=$lastKategory)
								{
									$b= new BTag();
										$b->add("Kategorie: ");
									$td->add($b);
									$td->add($officerResult[$o]["Kategory"]);
								}
								$tr->add($td);
								$td= new ColumnTag(TD);
								if(isset($officerResult[$o]["User"]))
								{
									$b= new BTag();
										$b->add("Verantwortlicher: ");
									$td->add($b);
								}
								$tr->add($td);
								$td= new ColumnTag(TD);
								if(isset($officerResult[$o]["User"]))
								{
									$td->add($officerResult[$o]["Name"]." (".$officerResult[$o]["User"].")");
								}
								$tr->add($td);
							$table->add($tr);
							$lastKategory= $officerResult[$o]["Kategory"];
							//$kategoryName= $officerResult[$o]["Kategory"];
							$o++;
							while($kategoryName==$officerResult[$o]["Kategory"])
							{
								$tr= new RowTag();
									$td= new ColumnTag(TD);
									$tr->add($td);
									$td= new ColumnTag(TD);
									$tr->add($td);
									$td= new ColumnTag(TD);
										$td->add($officerResult[$o]["Name"]."(".$officerResult[$o]["User"].")");
									$tr->add($td);
								$table->add($tr);
								$o++;
							}
						}// end of while($o<$katAnz)
					}// end of if (!searchField or !check)
					
					$siteCreator->addObjBehindTableIdentif($table);
				}// if(isset($inClausel)) end
				
				
				
				if(	!$HTTP_GET_VARS["stget"]["searchbox"]["searchField"]
					or
					!$HTTP_GET_VARS["stget"]["searchbox"]["check"]["1"]	)
				{// erzeuge select Box
					$selectBox1= new STSelectBox("STSearchBox_searchField");
						$status= array();
						// alex 27/05/2005:	Status all an erster Position hinzugef�gt
						$status[0]= array();
						$status[0]["ID"]= "all";
						$status[0]["value"]= "alle";
						$status[1]= array();
						$status[1]["ID"]= "created";
						$status[1]["value"]= "neue";
						$status[2]= array();
						$status[2]["ID"]= "seen";
						$status[2]["value"]= "gesehene";
						$status[3]= array();
						$status[3]["ID"]= "answered";
						$status[3]["value"]= "beantwortete";
						$selectBox1->select("Status", $status, "ID", "value");
						// alex 27/05/2005:	Status all an erster Position hinzugef�gt
						//					wenn nun kein status vorhanden ist
						//					soll created ausgew�hlt werden
						$selectBox1->setValueByNoParam("Status", "created");
						$selectBox1->needForm("index.php");
						$selectBox1->onChange("submit()");
						$selectBox1->withoutParam("STSearchBox_searchField");
						$selectBox1->execute();
					if(count($array))
					{	
						$selectBox2= new STSelectBox();
						$selectBox2->select("Kategorie", $array, "ID", "name");
						$selectBox2->setFirstNullRegisterName("Kategorie", " bitte w�hlen ");
						$selectBox2->needForm("index.php");
						$selectBox2->onChange("submit()");
						$selectBox2->withoutParam("STSearchBox_searchField");
						$selectBox2->execute();
					}	
					$t= new TableTag();
						$t->align("center");
						$tr= new RowTag();
					$userIsOfficer= userIsOfficer($siteCreator, $currentKategory);
					if($userIsOfficer)
					{
							$td= new ColumnTag(TD);
								$td->add($selectBox1);
							$tr->add($td);
					}
							$td= new ColumnTag(TD);
								$td->add($selectBox2);
							$tr->add($td);
					// alex 10/05/2005:	Button f�r neue Beitr�ge erstellt
					//					nur wenn dieser laut Tabelle MUKategorys
					//					erlaubt ist
					if(	$officerResult[($katAnz-1)]["new"]=="Y"
						or
						$userIsOfficer							)
					{
						$get2= new GetHtml();
						$newAddr=  "index.php".$get2->getParamString();//UPDATE, "Kategorie=".$officerResult[($katAnz-1)]["Kategory"])
						$newAddr.= "&question";
							$td= new ColumnTag(TD);
								$td->add("neuer &#160;");
								$button= new ButtonTag();
									$button->type("button");
									$button->add("Beitrag");
									$button->onClick("javascript:document.location.href='".$newAddr."'");
								$td->add($button);
								$td->add("&#160; f�r Kategorie ");
								$b= new BTag();
									$b->add($officerResult[($katAnz-1)]["Kategory"]);
								$td->add($b);
							$tr->add($td);
					}
						$t->add($tr);
					$siteCreator->addObjBehindTableIdentif($t);
				}// end of if (!searchField or !check)
					$siteCreator->addObjBehindTableIdentif(br());
			}
		}// end of else from if(!isset($HTTP_GET_VARS["where"]))
		
		function getHigherKategoryIDs($ID)
		{
			global	$db,
					$container;
			
			if(!is_numeric($ID))
				return array(0);
			$kategoryTable= $db->getTable("MUKategorys");
			$aRv= array();
			$aRv[]= $ID;
			$select= new STDbSelector($kategoryTable);
				$select->select("MUKategorys", "parentID");
				$select->where("ID=".$ID);
				$select->execute();
				$parent= $select->getSingleResult();
				if($parent)
				{
					$aRv= array_merge($aRv, getHigherKategoryIDs($parent));
				}
			return $aRv;
		}
		function getSubKategoryIDs($ID)
		{
			global	$db,
					$container;

//st_print_r($container, 1);			
			if(!is_numeric($ID))
				return array(0);//st_print_r($container);
			$kategoryTable= $db->getTable("MUKategorys");
			$aRv= array();
			$aRv[]= $ID;
			$select= new STDbSelector($kategoryTable);
				$select->select("MUKategorys", "ID");
				$select->where("parentID=".$ID);
				$select->execute();
				$kategories= $select->getSingleArrayResult();
				if(	$kategories
					and
					count($kategories)	)
				{
					foreach($kategories as $kategory)
						$aRv= array_merge($aRv, getSubKategoryIDs($kategory));
				}
			return $aRv;
		}
		function fillContainer(&$container, $questionID, $answerID)
		{	
			global 	$userNames,
					$db;
						
			while($questionID || $answerID)
			{
				if($questionID)
				{
					$questionWhere= " where ownRef=".$questionID;
					$answerWhere= " where questionRef=".$questionID;
					$questionID= null;
				}else
				{
					$questionWhere= " where answerRef=".$answerID;
					$answerWhere= " where ownRef=".$answerID;
					$answerID= null;
				}
				
				$statement=  "select ID, userID,subject,question from MUQuestionList";
				$statement.= $questionWhere;
				$result= $db->fetch_array($statement, MYSQL_ASSOC);
				foreach($result as $row)
				{
					$who= $userNames[$row["userID"]];
					if(!isset($who))
					{
						$statement=  "select UserName,FullName from UserManagement.MUUser ";
						$statement.= "where ID=".$row["userID"];
						$o= $db->fetch_row($statement, MYSQL_ASSOC);
						if(isset($o["UserName"]))
							$of= " (".$o["UserName"].")";
						$userNames[$row["who"]]= $o["FullName"].$of;
						$who= $userNames[$row["who"]];
					}
					$content= array();
					$content["ID"]= $row["ID"];
					$content["subject"]= $row["subject"];
					$content["question"]= ereg_replace(" ", "&#160;", $row["question"]);
					$content["question"]= ereg_replace("\n", "<br> ", $content["question"]);
					$content["who"]= $who;
					$container["content"][]= $content;
					fillContainer($container, $row["ID"], null);
				}
						
				$statement=  "select ID,subject,answer,who from MUAnswerList";
				$statement.= $answerWhere;
				$result= $db->fetch_array($statement, MYSQL_ASSOC);				
				foreach($result as $row)
				{
					$who= $userNames[$row["who"]];
					if(!isset($who))
					{
						$statement=  "select UserName,FullName from UserManagement.MUUser ";
						$statement.= "where ID=".$row["who"];
						$o= $db->fetch_row($statement, MYSQL_ASSOC);
						if(isset($o["UserName"]))
							$of= " (".$o["UserName"].")";
						$userNames[$row["who"]]= $o["FullName"].$of;
						$who= $userNames[$row["who"]];
					}
					$content= array();
					$content["ID"]= $row["ID"];
					$content["subject"]= $row["subject"];
					$content["answer"]= ereg_replace(" ", "&#160;", $row["answer"]);
					$content["answer"]= ereg_replace("\n", "<br> ", $content["answer"]);
					$content["who"]= $who;
					$container["content"][]= $content;
					fillContainer($container, null, $row["ID"]);
				}
			}
		}
		//echo "siteCreator:";print_r($siteCreator);echo "<br />";
		function userIsOfficer($siteCreator, $kategoryID)
		{
			global	$db,
					$container,
					$aKatOfficer;
	
			if(!$kategoryID)
				return false;		
			if(isset($aKatOfficer[$kategoryID]))
				return $aKatOfficer[$kategoryID];
			$currentUserID= $siteCreator->getUserID();
			$IDs= getHigherKategoryIDs($kategoryID);
        $inClausel= "in(";
        foreach($IDs as $one)
        	$inClausel.= $one.",";
        $inClausel= substr($inClausel, 0, strlen($inClausel)-1).")";
			$statement=  "select ID from MUOfficer where userID=".$currentUserID;
			$statement.= " and kategoryID ".$inClausel;
			$result= $db->fetch_array($statement);
			if(count($result))
			{
				$aKatOfficer[$kategoryID]= true;
				return true;
			}
			$aKatOfficer[$kategoryID]= false;
			return false;
		}
		function getQuestionIdsFromAnswer($resultArray)
		{
			global 	$MUAnswer,
					$db,
					$container;
			
			if(!$resultArray)
				return array();
			if(!count($resultArray))
				return array();
			$MUAnswer= $container->getTable("MUAnswerList");
				$MUAnswer->clearSelects();
				$MUAnswer->clearFKs();
				$MUAnswer->select("ownRef");
				$MUAnswer->select("questionRef");
			$aRv= array();
			foreach($resultArray as $ID)
			{
				$MUAnswer->where("ID=".$ID);
				$statement= $db->getStatement($MUAnswer);
				$result= $db->fetch_row($statement);
				if($result[1])
					$first= getFirstQuestionIds(array($result[1]));
				elseif($result[0])
				{
					$Ids= getQuestionIdsFromAnswer(array($result[0]));
					$first= getFirstQuestionIds($Ids);
				}else
				{
					echo "<br /><b>ERROR:</b> an Answer with no Question found<br />";
					exit;
				}
				$aRv= array_merge($aRv, $first);
			}
			return $aRv;
		}
		function getFirstQuestionIds($resultArray)
		{
			global 	$db,
					$container;
			
			if(!$resultArray)
				return array();
			if(!count($resultArray))
				return array();
			$MUQuestion= $container->getTable("MUQuestionList");
				$MUQuestion->clearSelects();
				$MUQuestion->clearFKs();
				$MUQuestion->select("ownRef");
				$MUQuestion->select("answerRef");
			$aRv= array();
			foreach($resultArray as $ID)
			{
				$MUQuestion->where("ID=".$ID);
				$statement= $db->getStatement($MUQuestion);				
				$result= $db->fetch_row($statement);//print_r($result);
				if($result[0])
					$first= getFirstQuestionIds(array($result[0]));
				elseif($result[1])
					$first= getQuestionIdsFromAnswer(array($result[1]));
				else
					$first= array($ID);
				$aRv= array_merge($aRv, $first);
			}
			return $aRv;
		}
		function createKategoryOfficerSequence($currentKategory)
		{
			global	$db,
					$container;
			
    		// selects f�r Anzeige von Kategorien mit Verantwortlichen
    		$aKategorys= getHigherKategoryIDs($currentKategory);
    		$inClausel= "in(";
    		foreach($aKategorys as $one)
    			$inClausel.= $one.",";
    		$inClausel= substr($inClausel, 0, strlen($inClausel)-1).")";
    	// alex 10/05/2005: erstelle eine Refference auf MUKategorys,
    	//					damit beim Select auf MUOfficer auch die neuen
    	//					Spalten new, app und del als identifColumn
    	//					mitselectiert werden und diese Eintr�ge
    	//					im Verlauf von dem Array $officerResult
    	//					miteinbezogen sind
    		$MUKategorys= $db->getTable("MUKategorys");
    			$MUKategorys->identifColumn("new");
    			$MUKategorys->identifColumn("app");
    			$MUKategorys->identifColumn("del");
    		$MUOfficer= $db->getTable("MUOfficer");
    		$MUOfficer->where("kategoryID ".$inClausel);
    		$MUOfficer->orderBy("kategoryID");
			$MUUser= $MUOfficer->getFKTable("userID");
			// alex 11/05/2005:	MUOfficer Datenbankselect �ber STDbSelector erzeugt,
			//					damit die drei neuen identifColumns new, app und del
			//					nicht im Datenbank-Objekt vermerkt werden
			//					und somit nicht in der Auflistung, wenn eine Suche
			//					mit der Option "Unterkategorien bei der Suche miteinbeziehen"
			//					durchgef�hrt wird, aufscheinen.
			$OfficerSelect= new STDbSelector($MUOfficer, MYSQL_ASSOC);
				$OfficerSelect->add($MUKategorys);
				$OfficerSelect->add($MUUser);
			$OfficerSelect->execute();
			
			$officerResult= $OfficerSelect->getResult();
    		$officerAnz= count($officerResult);						
    		if($officerAnz!=count($aKategorys))
    		{// wenn nicht alle Eintr�ge einen Verantwortlichen haben
    		 // sollen diese aber auch aufgelistet werden
    		 	$aVerantwKat= array();		
    			foreach($officerResult as $row)
    			{
    				$aVerantwKat[$row["Kategory"]]= true;
    			}				
    			$MUKategorys= $db->getTable("MUKategorys");
				// alex 01/06/2005:	neue Spalten new, app und del mitselectieren 
    				$MUKategorys->select("new");
    				$MUKategorys->select("app");
	    			$MUKategorys->select("del");
    				$MUKategorys->where("ID ".$inClausel);
    				$MUKategorys->orderBy("ID");
    			$statement= $db->getStatement($MUKategorys);
    			$kategoryResult= $db->fetch_array($statement, MYSQL_ASSOC);
    			foreach($kategoryResult as $row)
    			{// nur neuen Eintrag hinzuf�gen, wenn er nicht schon im
    			 // aVerantwKat steht -> das sind alle Kategorien vom officerResult
    				if(!isset($aVerantwKat[$row["Kategorie"]]))
    					$officerResult[]= array(	"Kategory"=>$row["Kategorie"],
													"new"=>$row["new"],
													"app"=>$row["app"],
													"del"=>$row["del"]);
    			}
    		}
			return $officerResult;
		}						 
?>