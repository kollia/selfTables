<?php

require_once($insert_update);
require_once($database_selector);
require_once($_KAGES_LDAP_Server);


		// callback-Funktion um den user zu deffinieren und Einzutragen
		function userCheck(&$oCallbackClass)
		{
			global $OSTDatabase_userInteraction;
			
			$found= preg_match("/([0-9]+)/", $oCallbackClass->sqlResult["answerRef"], $ref);
			if($found!=0)
			{
				$found= $ref[1];
				$statement= "select ID from MUAnswerList where ID=".$found;
				$found= $OSTDatabase_userInteraction->fetch_single($statement);
			}
			if(	$found==0
				and
				trim($oCallbackClass->sqlResult["answerRef"]))
			{
				if(!$ref[1])
				{
					$error=  "Eintrag im Feld \\\"Referenz auf Antwort\\\"";
					$error.= " nur wenn zuvor eine Frage gestellt wurde ";
					$error.= "und die Aktuelle auf diese referenzieren soll.\\n";
					$error.= "Referenz-Nr. steht immer bei der erhaltenen Antwort im EMail.";
					return $error;
				}else
				{
					$error=  "eine Antwort mit der Referenz-Nr. \\\"".$ref[1]."\\\"";
					$error.= " gibt es nicht!\\n";
					$error.= "Referenz-Nr. steht immer bei der erhaltenen Antwort im EMail.";
					return $error;
				}
			}
			$oCallbackClass->sqlResult["answerRef"]= $ref[1];
		/*	echo " do userCheck<br />";
			print_r($oCallbackClass->sqlResult);
			echo "<br />";
			print_r($ref);
			echo "<br />";	exit;	*/
		}
	
		// callback-Funktion um der Liste eine Struktur zu geben
		function kategoryJoin(&$oCallbackClass)
		{
			global $OSTDatabase_userInteraction;
			
			$project= split("-", $oCallbackClass->sqlResult[0][0]["Name"]);
			$project= trim($project[0]);
			$db= &$OSTDatabase_userInteraction;
			$statement=  "select * from MUKategorys where ID=";
			$statement.= $oCallbackClass->sqlResult[0][0]["PK"];
			$result= $db->fetch_row($statement, MYSQL_ASSOC);
			$hirarchie= array();
			$hirarchie[0]["PK"]= $result["ID"];
			$hirarchie[0]["Name"]= $project." - ".$result["Name"]."</font>";
			
			loadChilds($project, $hirarchie);
			$oCallbackClass->sqlResult[0]= $hirarchie;
		}
		function zwRoom($do= "nothing")
		{
			global	$zwRoom_space;
			
			$null= "&#160;&#160;&#160;";
			$nullLen= strlen($null);
			if($do=="-")
				$zwRoom_space= substr($zwRoom_space, $nullLen, strlen($zwRoom_space));
			elseif($do=="+")
				$zwRoom_space.= $null;
			return $zwRoom_space;
		}
		function loadChilds($projectName, &$aList)
		{		
			global $OSTDatabase_userInteraction;
			
			$db= &$OSTDatabase_userInteraction;
			$parentID= $aList[(count($aList)-1)]["PK"];
			$statement=  "select ID,Name from MUKategorys where parentID=".$parentID;
			$result= $db->fetch_array($statement, MYSQL_ASSOC);
			zwRoom("+");
			foreach($result as $row)
			{
				$Name= $projectName." - ".zwRoom().$row["Name"];
				$aList[]= array("PK"=>$row["ID"], "Name"=>$Name);
				loadChilds($projectName, $aList);
			}
			zwRoom("-");
		}
					
class OSTQuestionBox extends OSTBox
{
		var		$nKategoryID;
		var		$nUserID;
		var		$ProjectID;
		var		$oUserDb;
		var		$bLoggedIn;
		var		$kategoryTable;
		var		$userTable;
		var		$projectTable;
		var		$showFaqButton= null;
		var		$bSendEMails= true;
		var		$sNewsCluster= null;
		
		function OSTQuestionBox($kategoryID, $ostuser= null, $class= "OSTQuestionBox")
		{
			$this->nKategoryID= $kategoryID;			
			$this->init($ostuser, $class);
		}
		function init($ostuser, $class)
		{
			global	$OSTDatabase_userInteraction;
			
			$host= "localhost";
			$user= "interact";
			$password= "mufeedback";
			$database= "UserInteraction";
			$this->db= new STDbMySql("UserInteraction");
			$this->db->connect($host, $user, $password);
			$this->db->toDatabase($database);
			$OSTDatabase_userInteraction= $this->db;
			if($ostuser)
			{
				$UserDb= $ostuser->getUserDb();
				$this->ProjectID= $ostuser->getProjectID();	
				$this->bLoggedIn= $ostuser->isLoggedIn();
				$this->nUserID= $ostuser->getUserID();				
			}else
			{
				$user_host= "localhost";
				$user_user= "usermanagement";
				$user_password= "hupfauf45";
				$user_database= "UserManagement";
				$UserDb= new STDbMySql("noneUserDb");
				$UserDb->connect($user_host, $user_user, $user_password);	
				$UserDb->toDatabase($user_database);
				$this->nUserID= 41;//= Unknown User
				if(!STSession::sessionGenerated())
					STUserSession::init($UserDb);
				$ostuser= &STSession::instance();
			}
			$this->oUserDb= $UserDb;
			
			$this->userTable= new OSTDbTable("MUUser", $UserDb);
				$this->userTable->identifColumn("GroupType");
				$this->userTable->identifColumn("UserName", "User");
				$this->userTable->identifColumn("FullName", "Name");
			$this->projectTable= new STDbTable("MUProject", $UserDb);
				$this->projectTable->identifColumn("Name", "Projekt");
			
  		$this->kategoryTable= &$this->db->needTable("MUKategorys");
  			//$this->kategoryTable->identifColumn("projectID", "Projekt");
  			$this->kategoryTable->identifColumn("Name", "�bergeordnet");
  			$this->kategoryTable->foreignKey("projectID", $this->projectTable);
  			$this->kategoryTable->foreignKey("parentID", "MUKategorys");
		
		$fkTable= $this->kategoryTable->FK["MUProject"]["table"];
		$hisDb= $fkTable->getDatabase();
		
		$question= &$this->db->needTable("MUQuestionList");
  			$question->foreignKey("userID", $this->userTable);
  			$question->foreignKey("customID", "MUKategorys");
  			$question->foreignKey("ownRef", "MUQuestionList");
  			//$question->foreignKey("answerRef", "MUAnswerList");
			if(!$this->bLoggedIn)
			{
				$question->needPkInResult("unknownUser", "User");
			}else	
			{
				$question->select("userID", "User");
			}
				$question->needPkInResult("subject");
				$question->select("email", "EMail-Adresse");
				$question->select("customID", "Kategorie");
				
			//if($this->bLoggedIn)
				$question->select("answerRef", "Referenz auf Antwort");
				
				$question->select("subject", "Betreff");
				$question->select("question", "Text");
				
				
				$this->setAlso("userID", $this->nUserID);
			OSTBox::OSTBox($this->db, $class);
			// erroiere alle m�glichen Kategorien
			/*$aShowKategorys= $this->getLowerKategoryIDs($this->nKategoryID);
			$inClausel= "in(";
			foreach($aShowKategorys as $one)
				$inClausel.= $one.",";
			$inClausel= substr($inClausel, 0, strlen($inClausel)-1).")";
			$where= new STDbWhere("ID ".$inClausel);*/
			
			// es wird nur die vom User eingegebene Kategorie selectiert
			// da diese dann in sp�terer folge beim join-Aufruf
			// �ber denn callback ver�ndert wird
			$this->kategoryTable->where("ID=".$this->nKategoryID);
			$this->table($question);
			$this->setAlso("status", "created");
			$this->inputSize("subject", 120);
			$this->inputSize("question", 90, 40);
			//print_r($aShowKategorys);echo "is ShowKategorys<br />";
			/*if(count($aShowKategorys)==1)
			{
				$this->preSelect("customID", $this->nKategoryID);
				$this->disabled("customID");
			}*/
			if($this->bLoggedIn)
			{
				$userID= $ostuser->getUserID();
				$this->preSelect("userID", $userID);
				$this->disabled("userID");
				$statement=  "select EmailAddress from MUUser ";
				$statement.= "where ID=".$userID;
				$email= $UserDb->fetch_single($statement);
				$this->preSelect("email", $email);
			}			
			$this->callback(STINSERT, "userCheck");
			$this->callback("customID", "kategoryJoin");
		/*	
			// zusammenstellung des Selectors f�r sp�teren Callback
			$this->userTable->select("ID");
			$this->userTable->select("UserName");
			$this->userTable->select("FullName");
			$this->userTable->select("EmailAddress");	*/
		}
		function uploadAttachment($toPath, $type= null, $byte= 0)
		{
			$this->select("attachment", "Attachment");
			$this->upload("Attachment", $toPath, $type, $byte);
		}
		function setNewsGroup($toCluster)
		{
			$this->sNewsCluster= $toCluster;
			$this->select("newsGroup", "sende News-Letter");
			$this->isEnum("newsGroup", "N", "Y");
			$this->isNotNull("newsGroup");
		}
		function getLowerKategoryIDs($ID)
		{
			$aRv= array();
			$aRv[]= $ID;
			$where= new STDbWhere("parentID=".$ID);
			$select= new OSTDbSelector($this->kategoryTable);
				$select->select("MUKategorys", "ID");
				$select->where($where);
				$select->execute();
				$result= &$select->getSingleArrayResult();
			foreach($result as $new)
			{
				$aRv= array_merge($aRv, $this->getLowerKategoryIDs($new));
			}
			return $aRv;
		}
		function getHigherKategoryIDs($ID)
		{
			$aRv= array();
			$aRv[]= $ID;
			$select= new OSTDbSelector($this->kategoryTable);
				$select->select("MUKategorys", "parentID");
				$select->where("ID=".$ID);
				$select->execute();
				$parent= $select->getSingleResult();
				if(	$parent
					and
					$parent!=$ID)
				{
					$aRv= array_merge($aRv, $this->getHigherKategoryIDs($parent));
				}
			return $aRv;
		}
		function getOfficerEmails($ID)
		{
			$IDs= $this->getHigherKategoryIDs($ID);
			$inClausel= "in(";
			foreach($IDs as $one)
				$inClausel.= $one.",";
			$inClausel= substr($inClausel, 0, strlen($inClausel)-1).")";
			
			$this->userTable->identifColumn("EmailAddress");
			$officer= &$this->db->needTable("MUOfficer");
				$officer->foreignKey("userID", $this->userTable);
				$officer->where("kategoryID ".$inClausel);
			$select= new OSTDbSelector($officer);
				$select->select("MUUser", "EmailAddress", null, true);
				$select->execute();
				$aRv= $select->getSingleArrayResult();
			return $aRv;
		}
		function getOfficerArray($ID)
		{
			$IDs= $this->getHigherKategoryIDs($ID);
			$inClausel= "in(";
			foreach($IDs as $one)
				$inClausel.= $one.",";
			$inClausel= substr($inClausel, 0, strlen($inClausel)-1).")";
			
			//$this->userTable->identifColumn("EMailAddress");
			$officer= &$this->db->needTable("MUOfficer");
				//$officer->foreignKey("userID", $this->userTable);
				$officer->select("userID");
				$officer->where("kategoryID ".$inClausel);
			$statement= $this->db->getStatement($officer);
			$aRv= $this->db->fetch_single_array($statement);
			/*$select= new OSTDbSelector($officer);
				$select->select("MUUser", "EmailAddress");
				$select->execute();
				$aRv= $select->getSingleArrayResult();*/
			return $aRv;
		}
		function sendEmails($bSend)
		{
			$this->bSendEMails= $bSend;
		}
		function execute()
		{
			global	$HTTP_SERVER_VARS,
					$HTTP_POST_VARS,
					$host,
					$client_root;

		
			if(	$HTTP_POST_VARS["newsGroup"]=="Y"	)
			{
				$statement=  "select g.Name ";
				$statement.= "from MUClusterGroup as cg";
				$statement.= " inner join MUGroup as g on cg.GroupID=g.ID";
				$statement.= " where cg.ClusterID='".$this->sNewsCluster."'";
				$groups= $this->oUserDb->fetch_single_array($statement);
				
				$ldap = new KAGES_LDAP_Server();
				Tag::alert(!$ldap->connect(), "OSTQuestionBox::execute", "cannot Connect to LDAP-Server");
				Tag::alert(!$ldap->bind(), "OSTQuestionBox::execute", "cnnot bind on LDAP-Server");
				
				$members= array();
				foreach($groups as $group)
				{
					if(preg_match("/^LKHGraz_(.*)$/", $group, $preg))
					{
						$ldap->search(	"(&(objectClass=user)(memberOf=CN=".$preg[1].",CN=Users,DC=klinikum,DC=ad,DC=local))" 
			 							,array( 'sAMAccountName', 'displayName', 'mail' )
										,""
										,"OU=Users, OU=ADM, DC=klinikum,DC=ad,DC=local"												);
						while($ldap->next_record())
						{
							$name= array_pop( $ldap->f( 'sAMAccountName' ) );
							$displayName= array_pop( $ldap->f('displayName') );
							$mail= array_pop( $ldap->f( 'mail' ) );
							if(!$members[$name])
								$members[$name]= array("fullName"=>$displayName, "mail"=>$mail, "from"=>"LDAP");
						}
					}else
					{
						$statement=  "select u.UserName, u.FullName, u.EmailAddress ";
						$statement.= "from MUUser as u";
						$statement.= " inner join MUUserGroup as ug on u.ID=ug.UserID";
						$statement.= " inner join MUGroup as g on ug.GroupID=g.ID and g.Name='".$group."'";
						$result= $this->oUserDb->fetch_array($statement, MYSQL_ASSOC);
						foreach($result as $member)
						{
							if(!$members[$member["UserName"]])
								$members[$member["UserName"]]= array("fullName"=>$member["FullName"], "mail"=>$member["EmailAddress"], "from"=>"custom");
							else
								if(!$members[$member["UserName"]]["mail"])
								{
									$members[$member["UserName"]]["mail"]= $member["EmailAddress"];
									$members[$member["UserName"]]["from"]= "custom";
								}
						}
					}
				}
				$send= array();
				foreach($members as $name=>$member)
				{
					if(!$member["mail"])
					{// durchsuche nochmals ob irgendwo keine EMailAddresse eingetragen ist.
						$statement= "select EmailAddress from MUUser where UserName='".$name."'";
						$statement.= " and GroupType='LKHGraz'";
						$members[$name]["mail"]= $this->oUserDb->fetch_single($statement);
						if($members[$name]["mail"])
							$members[$name]["from"]= "custom";
					}
					if(!$member["mail"])
					{
						$logText= "User ".$member["fullName"]." has no EMailAddress from ".$member["from"];
						$statement=  "insert into MULog values(0,sysdate(),";
						$statement.= $this->nUserID.",17,'NewLetter',0,'".$logText."')";
						$this->oUserDb->fetch($statement);
					}
				}
			}
			$this->setAlso("createDate", "sysdate()");
			$sMessage= OSTBox::execute(STINSERT);
			// alex 11/05/2005: wenn Benutzer ->sendEmails(false) angibt, 
			//					sollen keine Emails gesendet werden
			if(	$sMessage=="NOERROR"
				and
				$this->bSendEMails
				and
				$HTTP_POST_VARS["newsGroup"]!="Y"	)
			{// emails werden nur verschickt wenn kein Fehler aus der Box zur�ckkommt,
			 // bzw. die Box nicht nur angezeigt wird	

				$result= $this->getResult();
				$mails= $this->getOfficerEmails($result["customID"]);
				$kategory= new OSTDbSelector($this->kategoryTable);
					$kategory->select("MUKategorys", "projectID");
					$kategory->select("MUKategorys", "Name");
					$kategory->where("ID=".$result["customID"]);
					$kategory->execute(MYSQL_ASSOC);
					$kat= $kategory->getResult();
				
				$user= new OSTDbSelector($this->userTable);
					$user->select("MUUser", "UserName");
					$user->where("ID=".$result["userID"]);
					$user->execute(MYSQL_ASSOC);
					$userResult= $user->getResult();
					$user= $userResult[0]["UserName"];
					
				$subject= "Im Projekt UserInteraction Kategorie:".$kat[0]["Projekt"].".".$kat[0]["Name"];
				$subject.= " ist ein neuer Eintrag von $user eingegangen";
				$message= "\nSubject: ".$result["subject"];
				$message.= "\n\n".$result["question"]."\n\n";
				$message.= "Dies ist ein automatisch generiertes EMail\n";
				$message.= "Bitte schicken Sie auf diese Addresse keine Antwort\n";
				$message.= "\n\n\n";
				$message.= "---------------------------------------------------------------------------------------------------\n";
				$message.= "Radiologie - Intranet:  http://".$host;
				$header1= "CC:";
				$header2= "-f robot@meduni-graz.at";
				
				foreach($mails as $mail)
				{
					//echo "send mail($mail, $subject, $message, $header1, $header2)<br />";
					mail($mail, $subject, $message, $header1, $header2);
				}
				if($result["userID"]!=41)
				{// wenn die Select-Box eine ID mitbekommt 
					// angegebene EMail-Adresse in MUUser speichern
					$email= $this->getResult("email");
					$statement=  "update MUUser set EmailAddress='".$email."'";
					$statement.= " where ID=".$result["userID"];
					$this->oUserDb->fetch($statement);
				}
			}
			if(	$sMessage=="NOERROR"
				and
				$HTTP_POST_VARS["newsGroup"]=="Y"	)
			{	
				
				$result= $this->getResult();
				$mails= $this->getOfficerEmails($result["customID"]);
				$kategory= new OSTDbSelector($this->kategoryTable);
					$kategory->select("MUKategorys", "Name");
					$kategory->select("MUKategorys", "app");
					$kategory->select("MUKategorys", "projectID");
					$kategory->where("ID=".$result["customID"]);
					$kategory->execute(MYSQL_ASSOC);
					$kat= $kategory->getResult();
				
				$statement= "select projectID from MUKategorys where ID=".$result["customID"];
				$projectID= $this->db->fetch_single($statement);
					
				$subject= "News-Letter f�r Kategorie:".$kat[0]["Projekt"].".".$kat[0]["Name"];
				if($result["attachment"])
					$message_text.= "Content-transfer-encoding: 7BIT\r\n";
				else
					$message_text= "";
				$message_text.= "Content-type: text/plain\n\n";
				$message_text= "\n".$result["subject"];
				$message_text.= "\n\n".$result["question"];
				$message_text.= "\n\n";
				$message_text.= "Orginal-Beitrag siehe unter ";
				$address=  "http://".$host.$client_root."/show.php";
				$address.= "?ProjectID=17";//.$projectID;
				$address.= "&To=".$client_root."/userinteraction/index.php";
				$address.= "&Kategorie=".$result["customID"];
				$address.= "&stget[action]=list";
				$address.= "&stget[table]=MUQuestionList";
				$address.= "&stget[link][lesen]=".$result["ID"];
				$address.= "&where=".$kat[0]["Projekt"];
				$message_text.= $address.".\n";
				if($result["attachment"])
				//	$message_text.= "siehe unter dieser Address auch das vorliegende Dokument.\n\n";
				$message_text.= "Sollte dieser Link in Ihrem Email-Programm nicht funktionieren,\n";
				$message_text.= "weil er eventuell zu lang ist,\n";
				$message_text.= "bitte kopieren sie ihn in die Adress-Liste vom Internet-Explorer.\n";
				$message_text.= "\n";
				$message_text.= "Diesen Beitrag";
				if($result["attachment"])
					$message_text.= ", sowie das angeh�ngte Dokument,\n";
				else
					$message_text.= " ";
				$message_text.= "finden sie auch jederzeit im Radiologie-Intranet http://".$host."\n";
				$message_text.= "unter der Webapplikation UserInteraction Kategorie ".$kat[0]["Projekt"].">> ".$kat[0]["Name"].".\n\n";
				$message_text.= "Dies ist ein automatisch generiertes EMail\n";
				$message_text.= "Bitte schicken Sie auf diese Addresse keine Antwort\n";
				if($kat[0]["app"]=="Y")
				{
					$message_text.= "  Sie k�nnen jedoch jederzeit auf unsrer Radiologi-Intranet Homepage\n";
					$message_text.= "  ( in der vorherbeschriebenen Kategorie beim Beitrag auf lesen klicken,\n";
					$message_text.= "    rechts oben unter zur�ck-Button \"neue Frage im Beitrag\" ),\n"; 
					$message_text.= "  im vorhandenen Dokument, weitere Fragen dazu stellen.\n";
				}
				$message_text.= "\n\n\n";
				$message_text.= "---------------------------------------------------------------------------------------------------\n";
				$message_text.= "Radiologie - Intranet:  http://".$host;
				$message_text.= "\n\n";
				
                // EMails mit Attechment
				$grenzlinie= "grenzlinie"; //bezeichnet die Grenze der Bereiche

				$header1=  "MIME-Version: 1.0\r\n"; // besagt das in der EMail mehrere Bereiche sind
				$header1.= "From: robot@".$host."\n";
				$header1.= "Reply-To: robot@meduni-graz.at\n";
				$header1.= "CC:\n";
				$header2= "-f robot@meduni-graz.at\n";
				if($result["attachment"])
				{
					$header1.= "Content-Type: multipart/mixed;\n\tboundary=".$grenzlinie."\n";

					preg_match("/[^\\\\\/]+$/", $result["attachment"], $preg);
					$attechment_name= $preg[0];
					$attechment= fopen($result["attachment"], "rb");
					$attechment_content= fread($attechment, filesize($result["attachment"]));
					fclose($attechment);
					$attechment_content= chunk_split(base64_encode($attechment_content));

					$message_attachment.= "Content-Type: application/octetstream;\n\tname=".$attechment_name."\n";
					$message_attachment.= "Content-Transfer-Encoding: base64\n";
					$message_attachment.= "Content-Disposition: attachment;\n\tfilename=".$attechment_name."\n\n";
					$message_attachment.= $attechment_content."\n\n";
					
					$message=  "\n--".$grenzlinie."\n"; // in der message muss die Grenze mit zwei vorangestellten "-" bezeichnet werden
					$message.= $message_text;
					$message.= "\n--".$grenzlinie."\n";
					$message.= $message_attachment;
					//$message.= "\n--".$grenzlinie."\n";
				}else
					$message= $message_text;
				
				//echo "sende mail(\"alexander.kolli@meduni-graz.at\", $subject, $message, $header1, $header2)<br />";
				//mail("alexander.kolli@meduni-graz.at", $subject, $message, $header1, $header2);	
				//st_print_r($members,5);
				foreach($members as $name=>$member)
    			{// EMail gefunden -> wenn noch nicht auf diese addresse geschickt wurde
    			 // jetzt abschicken
    				$email= strtolower($members[$name]["mail"]);
    				if(!$send[$email])
    				{
    					$send[$email]= true;
						mail($email, $subject, $message, $header1, $header2);
						//echo $member["fullName"]." &lt;$email&gt;;<br />";
						
    				}
    			}
			}
			if($sMessage=="BOXDISPLAY")
			{
				if($this->showFaqButton)
				{	
					$atr= &$this->getElementsByTagName("tr");
					$count= count($atr);
					for($n= 0; $n<$count; $n++)
					{
						if($n==1)
						{
							$td= &$atr[$n]->getElementByTagName("td", 3);
								$td->add("sehen sie zuvor alle bereits gestellten Fragen &#160;&#160;");
								$td->valign("bottom");
								$td->align("right");
								$button= $this->getFaqButton($this->showFaqButton);								
									$td->add($button);
						}else
						{
							$td= &$atr[$n]->getElementByTagName("td", 2);
							if($td)
								$td->colspan(2);
						}
					}
				}
			}
			return $sMessage;
		}
		function getFaqButton($value= "FAQ", $class= "FAQButton")
		{
			global 	$HTTP_SERVER_VARS,
					$client_root;
			
			$kategory= new OSTDbSelector($this->projectTable);
				$kategory->select("MUProject", "Name");
				//$kategory->select("MUKategorys", "Name");
				$kategory->where("ID=".$this->nKategoryID);
				$kategory->execute();
				$kat= $kategory->getResult();
					
			$button= new ButtonTag($class);
				$button->type("button");
				$button->add($value);
				$ownUrl= urlencode($HTTP_SERVER_VARS["REQUEST_URI"]);
				$address= $client_root."/userinteraction/index.php";
				$address.= "?stget[action]=".STLIST."&stget[table]=MUQuestionList";
				$address.= "&where=".$kat[0][0];
				$address.= "&Kategorie=".$this->nKategoryID;
				$address.= "&entrance[kategory]=".$this->nKategoryID;
				$address.= "&entrance[address]=".$ownUrl;
				$button->onClick("javascript:self.location.href='".$address."'");
			return $button;
		}
		function showFaqButton($name= "FAQ")
		{
			$this->showFaqButton= $name;
		}
}

?>