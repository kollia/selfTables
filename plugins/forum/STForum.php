<?php

require_once($php_javascript);

class STForum extends STDbSiteCreator
{
	var $container;
	var $questionList; // Tabelle MUQuestionList;
	var $get; // GetHtml Objekt
	var $nProjectID;
	var $nLastCategory;// letzte Kategorie
	var $nCategoryID;// augenblickliche Kategorie
	var	$sCategoryName;
	var $bUserIsOfficer;
	
	function __construct($project, $database)
	{
		Tag::echoDebug("forum", "STForum::constructor()");
		STDbSiteCreator::__construct($project, $database);
		STForum::init();
		
		$tableName= $this->getTableName();
		if($tableName=="MUQuestionList")
			$this->chooseInTable(false);
		$this->container= $this->getContainer();		
		$this->questionList= $this->container->getTable("MUQuestionList");
		$this->questionList->clearSelects();
		$this->get= new GetHtml();
		$this->get->delete("stget[searchbox]");
		
		$this->createProjectID();
		$this->createCategoryID();
		$this->userIsOfficer();
		Tag::echoDebug("forum", "STForum::constructor() ENDING");
	}
	function init()
	{
		Tag::echoDebug("forum", "STForum::init()");
		//$object->getUserManagement();
		$statement=  "select ClusterID from MUJoinCluster";
		//$statement.= " inner join MUGroups as g on cg.GroupID=g.GroupID";
		$accessResult= $this->db->fetch_array($statement, MYSQL_ASSOC);
		$sAccess= "interactCreateKategorys,";
		foreach($accessResult as $access)
			$sAccess.= $access["ClusterID"].",";
		$sAccess= substr($sAccess, 0, strlen($sAccess)-1);		
			$this->hasAccess($sAccess, "Access to project Bulletin Board", 0);
			$this->accessBy($sAccess);
			$this->accessBy("interactCreateKategorys", "MUJoinCluster");
			$this->accessBy("interactCreateKategorys", "MUKategorys");
			$this->accessBy("interactCreateKategorys", "MUOfficer");
	
		$this->userManagement->makeTableMeans();
		$userDb= &$this->getUserDb();
		$users= &$userDb->getTable("MUUser");		
		$project= &$userDb->getTable("MUProject");
		$cluster= $userDb->getTable("MUCluster");
			$cluster->clearIdentifColumns();
			$cluster->identifColumn("ID", "UserManagement-Zugriffscluster");
			//$cluster->identifColumn("Description", "Beschreibung");
			$cluster->where("ProjectID=17" /*=UserInteraction*/);
				
		$this->needVar("project", $project);
		
		//Tag::debug("db.statements.where");
		//Tag::debug("db.statements.table");
		//Tag::debug("db.statements.aliases");
		$MUKategorys= &$this->db->needTable("MUKategorys");
			$MUKategorys->setDisplayName("Kategorie-Unterteilung");
			$MUKategorys->identifColumn("projectID", "Projekt");
			$MUKategorys->identifColumn("Name", "Kategory");
			$MUKategorys->select("ID");
			$MUKategorys->select("projectID", "Projekt");
			$MUKategorys->select("Name", "Kategorie");
			$MUKategorys->select("parentID", "�bergeordnete Kategorie");
			$MUKategorys->select("new", "neue Beitr�ge erstellen");
			$MUKategorys->select("app", "zu vorhandenen Fragen hinzuf�gen");
			$MUKategorys->select("del", "Beitr�ge l�schen");
			$MUKategorys->select("up", "upload Attachemend");
			$MUKategorys->select("newsCluster", "NewsLetter versenden an");
			$MUKategorys->foreignKey("parentID", "MUKategorys");
			$MUKategorys->foreignKeyObj("projectID", $project);
			$MUKategorys->foreignKeyObj("newsCluster", $cluster);		
		$MUJoinCluster= &$this->db->needTable("MUJoinCluster");
			$MUJoinCluster->setIdentifier("Zugriffsberechtigungen");
			$MUJoinCluster->foreignKey("KategoryID", "MUKategorys");
			$MUJoinCluster->foreignKey("ClusterID", $cluster);
			$MUJoinCluster->select("KategoryID", "Kategorie");
			$MUJoinCluster->select("ClusterID");
		$MUOfficer= &$this->db->needTable("MUOfficer");
			$MUOfficer->setIdentifier("Verantwortliche");
			$MUOfficer->identifColumn("kategoryID");
			$MUOfficer->identifColumn("userID");
			$MUOfficer->foreignKey("kategoryID", $MUKategorys);	
			$MUOfficer->foreignKey("userID", $users);
			$MUOfficer->select("kategoryID", "Kategorie");
			$MUOfficer->select("userID", "Verantwortlicher");
		$MUQuestion= &$this->db->needTable("MUQuestionList");
			$MUQuestion->setIdentifier("Fragenkatalog");
			$MUQuestion->foreignKey("userID", $users);
			$MUQuestion->foreignKey("customID", "MUKategorys");
			//$MUQuestion->foreignKey("ownRef", "MUQuestionList");
			//$MUQuestion->foreignKey("answerRef", "MUAnswerList");
			$MUQuestion->select("createDate", "Datum");
			$MUQuestion->select("userID", "User");
		if(	$HTTP_GET_VARS["stget"]["searchbox"]["searchField"]
			and
			$HTTP_GET_VARS["stget"]["searchbox"]["check"]["1"]	)
		{
			$MUQuestion->select("customID", "Kategorie");
		}
			//$MUQuestion->select("ownRef", "Referenze auf zuvor gestellte Frage");
			$MUQuestion->select("subject", "Betreff");
			$MUQuestion->select("ID", "lesen");
			$MUQuestion->link("lesen");		
			$MUQuestion->noInsert();
			$MUQuestion->noUpdate();
			// alex 10/06/2005:	das ausdokumentierte noDelete wird jetzt in questionlist.php gemacht,
			//					da der Link "l�schen" manchmal gebraucht wird
			//$object->noDelete("MUQuestionList");
		
		
		$this->setProjectIdentifier("<h1>Projekt <font color='red'>Bulletin Board</font></h1>");
		$this->noChoise("MUAnswerList");
		//$this->onTableRequireSite("MUQuestionList", "questionlist.php");
		$this->setMessageContent("list", "MUQuestionList", "EMPTY_RESULT", "");
		$this->callback(INSERT, "MUOfficer", "emailCheck");
		$this->callback(UPDATE, "MUOfficer", "emailCheck");
		$this->callback(DELETE, "MUQuestionList", "deleteArticle");
		Tag::echoDebug("forum", "STForum::init() ENDING");
	}
	function execute()
	{
		Tag::echoDebug("forum", "STForum::execute()");
		global	$HTTP_GET_VARS;
		//Tag::debug("db.statement");
		//Tag::debug("gethtml.delete");		
		$aktTableName= $this->getTableName(); 
		if($aktTableName=="MUQuestionList")
		{
			if(isset($HTTP_GET_VARS["where"]))
				$this->projectSelected();
			else
				$this->noProjectSelected();
		}
		STDbSiteCreator::execute();
		
		Tag::echoDebug("forum", "STForum::execute() ENDING");
	}
	function createProjectID()
	{
		global	$HTTP_GET_VARS;
		
		if(!$HTTP_GET_VARS["where"])
			return;
    	/**************************************************************/
    	/*		hole ProjektID aus der DB							  */
    		// alex 10/05/2005:	refferenz "&" vor $siteCreator->getVar() entfernt,
    		//					da sich die where-Clausel auf die Tabelle 
    		//					MUKategory beim join auf MUProject auswirkt
    		$project= $this->getVar("project");
    		$project->where("Name='".$HTTP_GET_VARS["where"]."'");
    		$whereSelect= new STDbSelector($project);
    		$whereSelect->execute();
    		$this->nProjectID= $whereSelect->getSingleResult();				
    	/**************************************************************/
			
	}
	function createCategoryID()
	{
		global	$HTTP_GET_VARS;
		
		if(!$this->nProjectID)
			return;

					
		$this->nCategoryID= $HTTP_GET_VARS["Kategorie"];
		$statement=  "select ID,name,parentID from MUKategorys where ";
		
		if($this->nCategoryID===null)
		{
			$statement.= "projectID=".$this->nProjectID;
			$statement.= " and parentID is null";
		}else
			$statement.= "ID=".$this->nCategoryID;
		$aCategory= $this->db->fetch_row($statement, MYSQL_ASSOC);
		
		$this->nLastCategory= $aCategory["parentID"];
		$this->nCategoryID= $aCategory["ID"];
		$this->sCategoryName= $aCategory["name"];
		//echo "Category:".$this->sCategoryName." ID:".$this->nCategoryID." last ".$this->nLastCategory."<br />";
	}
	function noLinkIsSet($projectID)
	{// also wird auch eine SearchBox ben�tigt
	 // wenn nicht der Ablauf einer Frage angezeigt wird
	 	global	$HTTP_GET_VARS;

		Tag::echoDebug("forum", "STForum::noLinkIsSet(projectID:$projectID)");
		
		$MUQuestion= $this->container->getTable("MUQuestionList");
			$MUQuestion->clearSelects();
			$MUQuestion->clearFKs();	
			$MUQuestion->select("ID");
		$MUAnswer= $this->container->getTable("MUAnswerList");
		$MUAnswer->foreignKey("questionRef", "MUQuestionList");
		$AnswerSelect= new STDbSelector($MUAnswer);
			$AnswerSelect->add($MUQuestion);
			$AnswerSelect->select("MUAnswerList", "ID");
			//$AnswerSelect->select("MUQuestionList", "ID", "Q_ID");
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
			$categorys= $this->db->fetch_single_array($statement);
			if(count($categorys)==1)
				$currentCategorie= $categorys[0];
			else echo __FILE__." ".__LINE__." toDo: mehrere ROOT-Verzeichnisse vorhanden";
		}
		$aKategories= $this->getSubKategoryIDs($currentCategorie);
		$inClausel= "in(";
		foreach($aKategories as $kategory)
			$inClausel.= $kategory.",";
		$inClausel= substr($inClausel, 0, strlen($inClausel)-1);
		$inClausel.= ")";
		//Tag::debug("db.statement");
		if(!isset($HTTP_GET_VARS["question"]))
		{//			
			$searchBox= &new STSearchBox($this->db);
			$searchBox->table($MUQuestion);
			$searchBox->makeButtonToShowAll();
			$searchBox->inColumn("MUQuestionList", "subject");
			$searchBox->inColumn("MUQuestionList", "question");
				$text= "Unterkategorien<br />bei der Suche<br />miteinbeziehen";
				$then= new STDbWhere("customID ".$inClausel);
					$then->forTable("MUQuestionList");
				$else= new STDbWhere("customID=".$currentCategorie);
					$else->forTable("MUQuestionList");
			$searchBox->fieldset(false);
			$searchBox->table($AnswerSelect);
			$searchBox->inColumn("MUAnswerList", "subject");
			$searchBox->inColumn("MUAnswerList", "answer");
			$category= new STCategoryGroup("searchButtons");
			$category->defineSearchButtons();
			$subCategory= new STCategoryGroup("withSubCategorys");
			$subCategory->checkButton($text, $then, $else);
			$subCategory->fieldset(false);
			$searchBox->addCategory($subCategory, false);
			$searchBox->addCategory($category, false);
			//$searchBox->checkButton($text, $then, $else);
			$searchBox->execute();
		//$statement= $db->getStatement($AnswerSelect);	
    		$questionResult= $searchBox->getResult_single_array(0, "MUQuestionList");
    		$questionResult= $this->getFirstQuestionIds($questionResult);
    		$answerResult= $searchBox->getResult_single_array(0, "MUAnswerList");
			//echo "get QuestionResult:";st_print_r($questionResult);if(!count($questionResult))echo "<br />";
			//echo "get AnswerResult:";st_print_r($answerResult);if(!count($answerResult))echo "<br />";
    		$answerResult= $this->getQuestionIdsFromAnswer($answerResult);
    		$questionResult= array_merge($questionResult, $answerResult);	
			//echo "finished QuestionResult:";st_print_r($questionResult);if(!count($questionResult))echo "<br />";	
    		$questionInClausel= "in (";
		}
				
		$aWrite= array();// alle in die Inclausel eingef�gten ID's
		if(count($questionResult))
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
			$this->questionLimitation= $questionInClausel;
			$this->underCategorys= $subCategory->isChecked($text, true);
			//echo "underCategorys:";st_print_r($this->underCategorys);echo "<br />";
		}else
			$this->questionLimitation= null;
		//echo "created inClausel: ";st_print_r($questionInClausel);echo "<br />";
		//echo "questionResult: ".$questionInClausel."<br />";
		$this->addObjBehindHeadLineButtons($searchBox);
		$currentKategory= $HTTP_GET_VARS["Kategorie"];
		//st_print_r($this->container);
		
		// alex 10/05/2005:	wenn neuer Button "Beitrag" gedr�ckt wird
		//					soll das answerBoard auch ohne stget[link] Eintrag,
		//					f�r spezifischen Eintrag, aktiviert werden
		if(isset($HTTP_GET_VARS["question"]))
		{
			Tag::echoDebug("forum", "require questionBoard");
  				echo "<html>\n";
  				echo "	<body>\n";
			require_once("questionBoard.php");
  				echo "	</body>\n";
  				echo "</html>";
			Tag::echoDebug("forum", "required questionBoard EXIT");
  				exit;
		}
		Tag::echoDebug("forum", "STForum::noLinkIsSet(projectID:$projectID) ENDING");
	}
	function linkIsSet($projectID, $ownID)
	{
		global	$HTTP_GET_VARS;
		
		Tag::echoDebug("forum", "STForum::linkIsSet(projectID:$projectID, userID:$ownID)");
		// es wurde ein Link aus der Auflistung ausgewaehlt
	 	// alle Eintraege mit Text ausgehend von der ersten Frage
	 	// werden angezeigt
		  
		$stget= $HTTP_GET_VARS["stget"];
		$MUQuestion= &$this->db->getTable("MUQuestionList");
		$MUQuestion->clearSelects();
		//$MUQuestion->select("customID");
		$linkValueLesen= $stget["link"]["lesen"];
		if(!$linkValueLesen)
			$linkValueLesen= $stget["link"]["VALUE"];
		$where= new STDbWhere("ID=".$linkValueLesen);
		$MUQuestion->where($where);
		$statement= $this->db->getStatement($MUQuestion);
		$result= $this->db->fetch_row($statement, MYSQL_ASSOC, onErrorStop);
		if(	!$result
			and
			$this->db->errno()===0	)
		{
			$script= new JavaScriptTag();
				$script->add("alert('Dokument wurde verschoben oder gel�scht');");
				$param= new GetHtml();
				$param->delete("stget[link][lesen]");
				$address= $param->getStringVars();
				$script->add("location.href='".$address."'");
			$script->display();
			return false;
		}
		$whoReaded= array();
		$whoReaded["questionID"]= $linkValueLesen;
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
		$kategoryID= $this->db->fetch_single($statement);
		
		$container["head"]["email"]= $result["email"];
		$container["head"]["Projekt"]= $result["Projekt"];
		$container["head"]["KategorieID"]= $kategoryID;
		$container["head"]["Kategorie"]= $result["Kategory"];
		$ownRef= $linkValueLesen;
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
				$result= $this->db->fetch_row($statement, MYSQL_ASSOC);
				$questionCont[$questionRef]= $result;
				$questionRef= $result["ownRef"];
				$answerRef= $result["answerRef"];
				$questionID= $result["ID"];
			}elseif(isset($answerRef) && !isset($answerCont[$answerRef]))
			{
				$statement=  "select ID,subject,answer,ownRef,questionRef from MUAnswerList";
				$statement.= " where ID=$answerRef";
				$result= $this->db->fetch_row($statement, MYSQL_ASSOC);
				$answerCont[$questionRef]= $result;
				$questionRef= $result["questionRef"];
				$answerRef= $result["ownRef"];
			}else
			{
				$questionRef= null;
				$answerRef= null;
			}
		}
		
		$statement=  "select unknownUser, subject, question,userID,attachment from MUQuestionList";
		$statement.= " where ID=".$questionID;
		$result= $this->db->fetch_row($statement, MYSQL_ASSOC);
    	$who= $userNames[$result["userID"]];
    	if(	!isset($who)
    		and
    		$result["userID"]	)
    	{
    		if($result["userID"]!=41)
    		{
    			$statement=  "select UserName,FullName from UserManagement.MUUser ";
    			$statement.= "where ID=".$result["userID"];
    			$o= $this->db->fetch_row($statement, MYSQL_ASSOC);
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
		$content["attachment"]= $result["attachment"];
  		$content["question"]= ereg_replace(" ", "&#160;", $result["question"]);
  		$content["question"]= ereg_replace("\n", "<br> ", $content["question"]);
  		$content["who"]= $who;
  		$container["content"][]= $content;
    
  		// schreibe alle Fragen und Antworten sortiert in den Container
  		$this->fillContainer($container, $questionID, null);
								
					// Array von allen Fragen und Antworten
					//echo "<br /><br />";
					//print_r($aQuestions);
		
		$STForum= &$this;
		echo "<html>\n";
		echo "	<body>\n";
		if(isset($HTTP_GET_VARS["answer"])	)
		{
			require_once("answerBoard.php");
		}elseif(isset($HTTP_GET_VARS["question"]))
		{
//  				echo "<html>\n";
//	 				echo "	<body>\n";
			require_once("questionBoard.php");
//  				echo "	</body>\n";
//  				echo "</html>";
//  				exit;
		}else
		{
			if(	$whoReaded["status"]=="created"
				or
				$whoReaded["status"]=="seen"	)
			{						
				if(!preg_match("/ $ownID\|/", $whoReaded["who"]))
					$whoReaded["who"].= " $ownID\|";
				$statement=  "update MUQuestionList set status='seen', who='".$whoReaded["who"]."'";
				$statement.= " where ID=".$linkValueLesen;
				$this->db->fetch($statement, onErrorShow);
			}
			$STForum= &$this;
			require_once("listBoard.php");
		}
		echo "	</body>\n";
		echo "</html>";
		exit;
	}
	function projectSelected()
	{		
		global	$HTTP_GET_VARS;

		Tag::echoDebug("forum", "STForum::projectSelected()");
						
    	$stget= $HTTP_GET_VARS["stget"];
    	$ownID= $this->getUserID();
    	if(	isset($stget["link"]["lesen"])
			and
			$stget["action"]!=STDELETE	)
    	{
    		$bOk= $this->linkIsSet($this->nProjectID, $ownID);
    	}else
    		$this->noLinkIsSet($this->nProjectID);
									
		/**************************************************************/
		/*		DB-Select											  */	
			$operator= "=";
			$nKategory= $HTTP_GET_VARS["Kategorie"];
			if($nKategory===null)
			{
				$operator= " is ";
				$nKategory= "null";
			}
			$statement=  "select ID,name from MUKategorys where projectID=".$this->nProjectID;
			$statement.= " and parentID".$operator.$nKategory;
			$array= $this->db->fetch_array($statement, MYSQL_ASSOC);
			$kategoryName= "Unter-Kategorie";
						
			if($nKategory=="null")
			{
				Tag::echoDebug("forum", "first category is not set in params -> set backButton to choose MUProject in UserManagement");	
				// back Button auf Auswahl deffinieren
				$this->get->getParamString(DELETE, "Kategorie");
				$this->get->getParamString(DELETE, "Status");
				$this->get->getParamString(DELETE, "stget[searchbox][searchField]");
				$backAddress= "index.php".$this->get->getParamString(DELETE, "where");
				$this->setBackButtonAddress($backAddress);	
						
				if(count($array)==1)
				{// wenn nur eine Root-Kategorie des Projektes besteht
				 // das array f�r die n�chst H�here-Kategory-Verzweigung festlegen
				 
					$currentKategory= $array[0]["ID"];
					$statement=  "select ID, name from MUKategorys where projectID=".$this->nProjectID;
					$statement.= " and parentID =".$array[0]["ID"];
					$array= $this->db->fetch_array($statement, MYSQL_ASSOC);
				}else
				{// sonst ist das zuvor deffinierte schon das n�chst H�here
					$kategoryName= "Kategorie";
					$currentKategory= 0;
				}
			}else
			{
				Tag::echoDebug("forum", "first category in params is $nKategory");
				// back Button auf letzte Kategory, Auswahl oder FrageKatalog deffinieren
				if($this->nCategoryID==$HTTP_GET_VARS["entrance"]["kategory"])
				{
					Tag::echoDebug("forum", "category is the same as kategory wich comming in, so Backbutton set to STQuestionBox");
					$backAddress= rawurldecode($HTTP_GET_VARS["entrance"]["address"]);
				}else
				{
					if(!$this->nLastCategory)
					{		
						$this->get->getParamString(DELETE, "Kategorie");
						//$this->get->getParamString(DELETE, "Status");
						$this->get->getParamString(DELETE, "stget[searchbox][searchField]");
						$backAddress= $this->get->getParamString(DELETE, "where");
						$backAddress= "index.php".$backAddress;
					}else
					{
						$this->get->getParamString(DELETE, "Status");
						$this->get->getParamString(DELETE, "stget[searchbox][searchField]");
						$backAddress= "index.php".$this->get->getParamString(UPDATE, "Kategorie=".$this->nLastCategory);
					}
				}
				$this->setBackButtonAddress($backAddress);
			}
    		$aKategorys= $this->getHigherKategoryIDs($this->nCategoryID);
    		$inClausel= "in(";
    		foreach($aKategorys as $one)
    			$inClausel.= $one.",";
    		$inClausel= substr($inClausel, 0, strlen($inClausel)-1).")";
    		// alex 10/05/2005:	erstellung des Arrays $officerResult in Funktion 
    		//					createKategoryOfficerSequence() verschoben,
    		//					da ich dieses Array noch an anderen stellen ben�tige
    		$officerResult= $this->createKategoryOfficerSequence($this->nCategoryID);
			$nLastOfficerResult= count($officerResult)-1;
    		$lastKategoryName= $officerResult[($officerAnz-1)]["Kategory"];

    		/**************************************************************/
    		/*		erzeuge Status										  */
    		$showStatus= $HTTP_GET_VARS["Status"];
    		if(!$this->userIsOfficer())
    			$showStatus= "all";
			if(	$this->userIsOfficer()
				and
				(	$officerResult[$nLastOfficerResult]["new"]=="N"
					and
					$officerResult[$nLastOfficerResult]["app"]=="N"	)	)
			{
				$showStatus= "all";
			}				
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
    		$question= &$this->container->getTable("MUQuestionList");
    		if($this->questionLimitation)
    		{// wenn $questionInClausel gesetzt ist
    		 // wurde eine Suche getaetigt
    		 	$thisWhere= new STDbWhere("ID ".$this->questionLimitation);
    		}else
    		{
    			$thisWhere= new STDbWhere("customID=".$this->nCategoryID);
    			if(	$showStatus=="answered"
    				or
    				$showStatus=="all"	)
    			{
    				$thisWhere->andWhere("answerRef is null");
    			}
    			if($this->userIsOfficer())
    				$thisWhere->andWhere($where);
    		}
    		$question->where($thisWhere);
				
    		if(	(	isset($inClausel)
    				or
    				$officerResult	)
				and
				!$this->underCategorys	)
    		{
    			$table= new TableTag();
					$table->align("center");
    				$tr= new RowTag();
    					$th= new ColumnTag(TH);
    						$th->bgcolor("#C0C0C0");
    						$th->colspan(3);
    						$th->add($HTTP_GET_VARS["where"]);
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
    			
    			$this->addObjBehindTableIdentif($table);
    		}// if(isset($inClausel)) end
				
				
				
    		/*if(	!$HTTP_GET_VARS["stget"]["searchbox"]["searchField"]
    			or
    			!$HTTP_GET_VARS["stget"]["searchbox"]["check"]["1"]	)*/
			if(!$this->underCategorys)
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
					$selectBox1->description("Status");
    				// alex 27/05/2005:	Status all an erster Position hinzugef�gt
    				//					wenn nun kein status vorhanden ist
    				//					soll created ausgew�hlt werden
    				$selectBox1->setValueByNoParam("created");
    				$selectBox1->needForm("index.php");
    				$selectBox1->onChange("submit()");
    				$selectBox1->withoutParam("STSearchBox_searchField");
    				$selectBox1->execute();
					if(count($array))
					{	
						$selectBox2= new STSelectBox();
						$selectBox2->select("Kategorie", $array, "ID", "name");
						$selectBox2->description("n&auml;chste Kategorie");
						$selectBox2->setFirstNullRegisterName(" bitte w�hlen ");
						$selectBox2->needForm("index.php");
						$selectBox2->onChange("submit()");
						$selectBox2->withoutParam("STSearchBox_searchField");
						$selectBox2->execute();
					}	
					$t= new TableTag();
						$t->align("center");
						$tr= new RowTag();
					$userIsOfficer= $this->userIsOfficer();
					
					if(	$userIsOfficer
						and
						(	$officerResult[$nLastOfficerResult]["new"]=="Y"
							or
							$officerResult[$nLastOfficerResult]["app"]=="Y"	)	)
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
    			$this->addObjBehindTableIdentif($t);
				// alex 14/06/05:	l�schen Link nicht anzeigen wenn User kein Verantwortlicher ist
				//					und oder in Tabelle MUKategorys Spalte del auf N steht
				if(	$officerResult[($katAnz-1)]["del"]=="N"
					or
					!$userIsOfficer							)
				{
					$question->noDelete();
				}
    		}// end of if (!searchField or !check)
    		$this->addObjBehindTableIdentif(br());
			//}
	}
	function noProjectSelected()
	{
		global	$HTTP_GET_VARS;
		
		Tag::echoDebug("forum", "STForum::noProjectSelected()");
			// alex 04/05/2005:	select-Statement ge�ndert
			//					brauche den ganzen Overhead nicht
			//$accessGroupTable= $container->getTable("MUJoinCluster");
			$statement=  "select p.Name,j.ClusterID from MUJoinCluster as j";//$db->getStatement($accessGroupTable);
			$statement.= " left join MUKategorys as k on j.KategoryID=k.ID";
			$statement.= " left join UserManagement.MUProject as p on k.projectID=p.ID";
			$result= $this->db->fetch_array($statement, MYSQL_ASSOC);
			$access= array();
			foreach($result as $row)
				$access[$row["Name"]]= $row["ClusterID"];
			$kategoryTable= &$this->container->getTable("MUKategorys");
			$kategoryTable->clearSelects();
			$kategoryTable->distinct();
			$kategoryTable->select("projectID");
			$statement= $this->db->getStatement($kategoryTable);
			$result= $this->db->fetch_single_array($statement, MYSQL_ASSOC);
			$oManagement= $this->getUserManagement();

			if(isset($HTTP_GET_VARS["stget"]["onlyone"]))
				$this->setBackButtonAddress(null);
			else
			{
				$this->get->getParamString(STDELETE, "stget[onlyone]");
				$backAddress= $this->get->getParamString(STDELETE, "stget[table]");
				$backAddress= $this->get->getParamString(STDELETE, "stget[searchbox][searchField]");
				$backAddress= $this->get->getParamString(STUPDATE, "stget[action]=".STCHOOSE);
				$this->setBackButtonAddress("index.php".$backAddress);
				$get= new GetHtml();
				$get->getParamString(DELETE, "STSearchBox_searchField");
			}
			$projects= array();
			foreach($result as $projectName)
			{
				if(!isset($projects[$projectName]))
				{
					$get= new GetHtml();
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
				$head= &$this->getHead("Auswahlmenue");
			$html->addObj($head);
			$body= new BodyTag();
				$headline= &$this->getHeadline($HTTP_GET_VARS["stget"]);
				$body->addObj($headline);
    		$body->addObj($chooseTable);
			$html->addObj($body);
			$html->display();
			Tag::echoDebug("forum", "STForum::noProjectSelected() EXIT");
			exit;
	}	
	var $aHigherKategoryIDs= array();
	var $oKategorySelector= null;
	function getHigherKategoryIDs($ID)
	{
		if($this->aHigherKategoryIDs[$ID])
			return $this->aHigherKategoryIDs[$ID];
		
		if(!is_numeric($ID))
			return array(0);
		$kategoryTable= $this->db->getTable("MUKategorys");
		$aRv= array();
		$aRv[]= $ID;
		if(!$this->oKategorySelector)
		{	
			$this->oKategorySelector= new STDbSelector($kategoryTable);
			//$this->oKategorySelector->clearSelects();
			$this->oKategorySelector->select("MUKategorys", "parentID");
		}
		$this->oKategorySelector->where("ID=".$ID);
		$this->oKategorySelector->execute();
		$parent= $this->oKategorySelector->getSingleResult();
		if(	$parent
			and
			$parent!=$ID)
		{
			$aRv= array_merge($aRv, $this->getHigherKategoryIDs($parent));
		}
		$this->aHigherKategoryIDs[$ID]= $aRv;
		return $aRv;
	}
	function getSubKategoryIDs($ID)
	{		
			if(!is_numeric($ID))
				return array(0);//st_print_r($container);
			$kategoryTable= $this->db->getTable("MUKategorys");
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
						if($kategory!=$ID)
							$aRv= array_merge($aRv, $this->getSubKategoryIDs($kategory));
				}
			return $aRv;
	}
	function fillContainer(&$container, $questionID, $answerID)
	{	
			global 	$userNames;
						
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
				
				$statement=  "select ID, userID,subject,question,attachment from MUQuestionList";
				$statement.= $questionWhere;
				$result= $this->db->fetch_array($statement, MYSQL_ASSOC);
				foreach($result as $row)
				{
					$who= $userNames[$row["userID"]];
					if(!isset($who))
					{
						$statement=  "select UserName,FullName from UserManagement.MUUser ";
						$statement.= "where ID=".$row["userID"];
						$o= $this->db->fetch_row($statement, MYSQL_ASSOC);
						if(isset($o["UserName"]))
							$of= " (".$o["UserName"].")";
						$userNames[$row["who"]]= $o["FullName"].$of;
						$who= $userNames[$row["who"]];
					}
					$content= array();
					$content["ID"]= $row["ID"];
					$content["subject"]= $row["subject"];
					if($row["attachment"])
						$content["attachment"]= $row["attachment"];
					$content["question"]= ereg_replace(" ", "&#160;", $row["question"]);
					$content["question"]= ereg_replace("\n", "<br> ", $content["question"]);
					$content["who"]= $who;
					$container["content"][]= $content;
					$this->fillContainer($container, $row["ID"], null);
				}
						
				$statement=  "select ID,subject,answer,who from MUAnswerList";
				$statement.= $answerWhere;
				$result= $this->db->fetch_array($statement, MYSQL_ASSOC);				
				foreach($result as $row)
				{
					$who= $userNames[$row["who"]];
					if(!isset($who))
					{
						$statement=  "select UserName,FullName from UserManagement.MUUser ";
						$statement.= "where ID=".$row["who"];
						$o= $this->db->fetch_row($statement, MYSQL_ASSOC);
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
					$this->fillContainer($container, null, $row["ID"]);
				}
			}
	}
	//echo "siteCreator:";print_r($siteCreator);echo "<br />";
	function userIsOfficer()
	{
		if(!$this->nCategoryID)
			return false;
		if($this->bUserIsOfficer!==null)
			return $this->bUserIsOfficer;
		
		$currentUserID= $this->getUserID();
		$IDs= $this->getHigherKategoryIDs($this->nCategoryID);
        $inClausel= "in(";
        foreach($IDs as $one)
        	$inClausel.= $one.",";
        $inClausel= substr($inClausel, 0, strlen($inClausel)-1).")";
		$statement=  "select ID from MUOfficer where userID=".$currentUserID;
		$statement.= " and kategoryID ".$inClausel;
		$result= $this->db->fetch_array($statement);
		if(!count($result))
		{
			$this->bUserIsOfficer= false;
			$no= " no";
		}else
			$this->bUserIsOfficer= true;
		Tag::echoDebug("forum", "user is$no Officer");
		return $this->bUserIsOfficer;
	}
	function getQuestionIdsFromAnswer($resultArray)
	{
			global 	$MUAnswer;
			
			if(!$resultArray)
				return array();
			if(!count($resultArray))
				return array();
			$MUAnswer= $this->container->getTable("MUAnswerList");
				$MUAnswer->clearSelects();
				$MUAnswer->clearFKs();
				$MUAnswer->select("ownRef");
				$MUAnswer->select("questionRef");
			$aRv= array();
			foreach($resultArray as $ID)
			{
				$MUAnswer->where("ID=".$ID);
				$statement= $this->db->getStatement($MUAnswer);
				$result= $this->db->fetch_row($statement);
				if($result[1])
					$first= $this->getFirstQuestionIds(array($result[1]));
				elseif($result[0])
				{
					$Ids= $this->getQuestionIdsFromAnswer(array($result[0]));
					$first= $this->getFirstQuestionIds($Ids);
				}else
				{
					echo "<br /><b>ERROR:</b> an Answer with no Question found<br />";
					echo "ID from table MUAnswer is $ID";
					exit;
				}
				$aRv= array_merge($aRv, $first);
			}
			return $aRv;
	}
	function getFirstQuestionIds($resultArray)
	{
			if(!$resultArray)
				return array();
			if(!count($resultArray))
				return array();
			$MUQuestion= $this->container->getTable("MUQuestionList");
				$MUQuestion->clearSelects();
				$MUQuestion->clearFKs();
				$MUQuestion->select("ownRef");
				$MUQuestion->select("answerRef");
			$aRv= array();
			foreach($resultArray as $ID)
			{
				$MUQuestion->where("ID=".$ID);
				$statement= $this->db->getStatement($MUQuestion);				
				$result= $this->db->fetch_row($statement);//print_r($result);
				if($result[0])
					$first= $this->getFirstQuestionIds(array($result[0]));
				elseif($result[1])
					$first= $this->getQuestionIdsFromAnswer(array($result[1]));
				else
					$first= array($ID);
				$aRv= array_merge($aRv, $first);
			}
			return $aRv;
	}
	function createKategoryOfficerSequence($currentKategory)
	{
    		// selects f�r Anzeige von Kategorien mit Verantwortlichen
    		$aKategorys= $this->getHigherKategoryIDs($currentKategory);
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
    		$MUKategorys= $this->db->getTable("MUKategorys");
    			$MUKategorys->identifColumn("ID", "KategoryID");
    			$MUKategorys->identifColumn("new");
    			$MUKategorys->identifColumn("app");
    			$MUKategorys->identifColumn("del");
				$MUKategorys->identifColumn("up");
				$MUKategorys->identifColumn("newsCluster");
    		$MUOfficer= $this->db->getTable("MUOfficer");
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
    			$MUKategorys= $this->db->getTable("MUKategorys");
				// alex 01/06/2005:	neue Spalten new, app und del mitselectieren 
    				$MUKategorys->select("ID", "KategoryID");
    				$MUKategorys->select("new");
    				$MUKategorys->select("app");
	    			$MUKategorys->select("del");
					$MUKategorys->select("up");
					$MUKategorys->select("newsCluster");
    				$MUKategorys->where("ID ".$inClausel);
    				$MUKategorys->orderBy("ID");
    			$statement= $this->db->getStatement($MUKategorys);
    			$kategoryResult= $this->db->fetch_array($statement, MYSQL_ASSOC);
    			foreach($kategoryResult as $row)
    			{// nur neuen Eintrag hinzuf�gen, wenn er nicht schon im
    			 // aVerantwKat steht -> das sind alle Kategorien vom officerResult
    				if(!isset($aVerantwKat[$row["Kategorie"]]))
    					$officerResult[]= array(	"Kategory"=>$row["Kategorie"],
													"KategoryID"=>$row["KategoryID"],
													"new"=>$row["new"],
													"app"=>$row["app"],
													"del"=>$row["del"],
													"up"=>$row["up"],
													"newsCluster"=>$row["newsCluster"]);
    			}
    		}
			return $officerResult;
	}
}


		function deleteArticle(&$oCallbackClass)
		{
			global	$object,
					$sAnswerInClausel;
			
			$container= array();
			$startID= $oCallbackClass->sqlResult[0]["ID"];			
			$sQuestionInClausel= "ID in(".$startID.",";			
			$sAnswerInClausel= "ID in(";
			$forum= new STForum($object);
			$forum->fillContainer($container, $startID, null);
			$bAnswer= false;
			if(count($container["content"]))
    			foreach($container["content"] as $row)
    			{
    				if(isset($row["question"]))
    					$sQuestionInClausel.= $row["ID"].",";
    				else
    				{
    					$bAnswer= true;
    					$sAnswerInClausel.= $row["ID"].",";
    				}
    			}
			$sQuestionInClausel= substr($sQuestionInClausel, 0, (strlen($sQuestionInClausel)-1)).")";
			if($bAnswer)
				$sAnswerInClausel= substr($sAnswerInClausel, 0, (strlen($sAnswerInClausel)-1)).")";
			else
				$sAnswerInClausel= null;
			$oCallbackClass->setWhere($sQuestionInClausel);
			return true; // no Error
		}
		function emailCheck(&$oCallbackClass)
		{
			global	$db;
			
			$statement=  "select EmailAddress from UserManagement.MUUser";
			$statement.= " where ID=".$oCallbackClass->sqlResult["userID"];
			$email= $db->fetch_single($statement);
			if(	$email==null
				or
				trim($email)==""	)
			{
				return "Dieser User besitzt keine EMail-Adresse. (Bitte vorher eintragen)";
			}
		}
		
?>