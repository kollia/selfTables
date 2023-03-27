<?php

/* ab version php 5.0
interface STMessagHandlingInterface
{
		public function onOKGotoUrl();
		public function getMessageId()
		public function setMessageContent($messageId, $messageString)
}*/

class STMessageHandling // implements STMessageHandlingInterface <- ab version 5
{
		var $sObject; // f�r welches Objekt STMessageHandling ausgef�hrt wird
		var $aMessageStrings= array();
		var	$messageId= "NOERROR";
		var	$EndScripts= array(); // werden immer am Ende ausgef�hrt
		var	$ErrorScripts= array(); // werden bei jedem Fehler nach EndScript ausgef�hrt
		var	$OKScripts= array(); // werden bei keinem Fehler nach EndScript ausgef�hrt
		var	$EndUrl= null; // wenn OKUrl und ErrorUrl NULL ist, nach den entsprechenden Urls
		var	$ErrorUrl= null; // nach ErrorScript
		var	$OKUrl= null; // nach OKScript
		var	$onError;
		
		function __construct($forObject, $onError= onDebugErrorShow)
		{
			Tag::paramCheck($forObject, 1, "string");
			$this->sObject= $forObject;
			$this->onError= $onError;
		}
		function setOnErrorStatus($onError)
		{
			$this->onError= $onError;
		}
		function getOnErrorStatus($type= "normal")
		{
			$onError= $this->onError;
			if(	$type!="normal"
				and
				$onError==onErrorMessage	)
			{
				$onError= noErrorShow;
			}
			return $onError;
		}
		function setMessageContent($messageId, $messageString= "")
		{
			$this->aMessageStrings[$messageId]= $messageString;
		}
		function getMessageContent($messageId= null)
		{
			if(!$messageId)
			    $messageId= $this->messageId;
			return $this->aMessageStrings[$messageId];
		}
		function clearMessageId()
		{
			$this->messageId= "NOERROR";
		}
		function setMessageId(string $messageId, string $newString= null)
		{
			if(Tag::isDebug())
			{
				Tag::alert(!isset($this->aMessageStrings[$messageId]), "STMessageHandling::setMessageId()",
							"messageid $messageId not be set in content of aMessageStrings");
			}
			$onError= $this->onError;
			if(	$this->messageId!="NOERROR"
				and
				$this->messageId!=null)
				return;// damit der erste Fehler erhalten bleibt
			STCheck::echoDebug("STMessageHandling", "set MessageId: $messageId");
			$this->messageId= $messageId;
			
			// alex 2006/01/17:	if an messageId have one or more '@',
			//					but in function setMessageContent() is not given an Error-String
			//					the messageId is only for handing over
			$ats= array();
			if(	preg_match("/@+$/", $messageId, $ats)
				and
				$this->aMessageStrings[$messageId]!=="")
			{//st_print_r($ats);
				$need= strlen($ats[0]);
				$have= func_num_args();
				$sMessageString= trim($this->aMessageStrings[$messageId]);
				$split= preg_split("/@/", $sMessageString);							
				Tag::alert($need!=($have-1), "STMessageHandling::setMessageID", "function must have ".($need)." params");
				
				$args= func_get_args();
				$sNewMessageString= $split[0];
				for($count=1; $count<$have; $count++)
				    $sNewMessageString.= $args[$count].$split[$count];
				$this->aMessageStrings[$messageId]= $sNewMessageString;
			}elseif($newString!==null)
			{// wenn ein Fehler-String herein kommt,
			 // wird der alte Fehler-Text auf der angegebenen Fehlerbezeichnung �berschrieben
				$newString= preg_replace("/\n/", " ", $newString);
				$newString= preg_replace("/\"/", "\\\"", $newString);
				$newString= preg_replace("/'/", "\\'", $newString);
				$this->aMessageStrings[$messageId]= $newString;
			}
  		if(	$onError>noErrorShow &&
			$onError!=onErrorMessage &&
			$messageId!="NOERROR" &&
			$messageId!="BOXDISPLAY"	)
			{
  				echo "<br><b>Error ".$messageId.":</b> ".$this->getMessageContent()."<br>";
			}
  		if(	$onError==onErrorStop &&
			$messageId!="NOERROR" &&
			$messageId!="BOXDISPLAY"	)
  		{
  		    STCheck::echoDebug("STMessageHandling", "ERROR handling set to <b>onErrorStop</b> by message ID <b>'$messageId'</b>");
  			exit;
  		}
	}
		function getMessageId()
		{
			Tag::echoDebug("STMessageHandling", "getMessageId: ".$this->messageId);
			return $this->messageId;
		}
		/*protected*/function addScripts($mixedScripts, &$scriptArray, &$javaScript)
		{
			if(!count($mixedScripts))
				return;
    		foreach($mixedScripts as $content)
    		{
    			if(typeof($content, "scriptTag"))
    			{
    				if($javaScript)
    				{
    					$scriptArray[]= $javaScript;
    					$javaScript= null;
    				}
    				$scriptArray[]= $content;
    			}else
    			{
    				if(!$javaScript)
    					$javaScript= new JavascriptTag();
    				$javaScript->add($content);
    			}
    		}
		}
		function getMessageEndScript()
		{
			/*
			 * return value
			 */
		    $oReturnScript= NULL;
		    $javaScript= null;
		    $scripts= array();
			
			$messageId= $this->messageId;
			Tag::echoDebug("STMessageHandling", "entering getMessageEndScript() by message ID <b>$messageId</b>");
						
			// insert all from user set scripts into the array
			// as also create the url for jump				
			$this->addScripts($this->EndScripts, $scripts, $javaScript);
			if($messageId=="NOERROR")
			{
				$this->addScripts($this->EndScripts, $scripts, $javaScript);
				$Url= $this->OKUrl;
			}else
			{
				$this->addScripts($this->EndScripts, $scripts, $javaScript);
				$Url= $this->ErrorUrl;
			}
			if(!$Url)
				$Url= $this->EndUrl;
				
			// f�ge alle Messages in das Array
			$string= $this->getMessageContent();
			if($string)
			{   
			    if(STCheck::isDebug("STMessageHandling"))
			    {
			        switch($this->onError)
			        {
			            case noErrorShow:
			                $handling= "noErrorShow";
			                break;
			            case onDebugErrorShow:
			                $handling= "onDebugErrorShow";
			                break;
			            case onErrorMessage:
			                $handling= "onErrorMessage";
			                break;
			            case onErrorShow:
			                $handling= "onErrorShow";
			                break;
			            case onErrorStop:
			                $handling= "onErrorStop";
			                break;
			            default:
			                $handling= "UNKNOWN";
			        }
			        STCheck::echoDebug("STMessageHandling", "ERROR handling set to <b>$handling</b>");
				    STCheck::echoDebug("STMessageHandling", "display '".$messageId."' for given messageId");
			    }
				if($messageId!="NOERROR")
				{
					if(	!Tag::isDebug()
						and
						(	$this->onError==onErrorShow
							or
							$this->onError==onErrorStop	)	)
					{
						echo $this->sObject." execute result <b>".$messageId.":</b> ".$string."<br />";
					}					
					if($this->onError==onErrorStop)
						exit;
				}
				if($this->onError==onErrorMessage)
				{
				    STCheck::echoDebug("STMessageHandling", "add javascript:alert() message to the scripts");
				    $string= preg_replace("/'/", "\\'", $string);
					$this->addScripts(array("alert('$string');"), $scripts, $javaScript);
				}
			}else
				Tag::echoDebug("STMessageHandling", "display no alert-string for given messageId: ".$messageId);
			if(Tag::isDebug())
			{
				echo $this->sObject." execute result <b>".$messageId;
				if($string)
					echo ":</b> ".$string;
				else
					echo "</b>";
				echo "<br />";
			}
				
			// f�ge die Url in das Array
			if($Url)
			{
				if(Tag::isDebug())
				{					
    				if($javaScript)
    				{
    					$scripts[]= $javaScript;
    					$javaScript= null;
    				}
					$h1= new H1Tag();
						$h1->add("user will be forwarded to Url ");
						$a= new ATag();
							$a->href($Url);	
							$a->add($Url);	
						$h1->add($a);
					$scripts[]= $h1;						
				}else
				{
  					$this->addScripts(array("self.location.href='".$Url."';"),
															$scripts, $javaScript);
				}
			}
			
			if($javaScript)
    			$scripts[]= $javaScript;
			// wenn mehrere scripts vorhanden,
			// gib sie in einem span-Tag zur�ck
			$scriptCount= count($scripts);
			$oReturnScript= null;
			if($scriptCount>1)
			{
				$oReturnScript= new SpanTag();
				foreach($scripts as $script)
					$oReturnScript->add($script);
			}elseif($scriptCount==1)
				$oReturnScript= $scripts[0];
				
			return $oReturnScript;
		}
		function setOKMessage($okString)
		{
			$this->setMessageContent("NOERROR", $okString);		
		}
		function setEndScript($script)
		{
			$this->EndScripts[]= $script;
		}
		function setErrorScript($script)
		{
			$this->ErrorScripts[]= $script;
		}
		function setOKScript($script)
		{
			$this->OKScripts[]= $script;
		}
		function onEndGotoUrl($Url)
		{
			$this->EndUrl= $Url;
		}
		function onErrorGotoUrl($Url)
		{
			$this->ErrorUrl= $Url;
		}
		function onOKGotoUrl($url)
		{
			$this->OKUrl= $url;
		}
		function getOKUrl()
		{
			if(!$this->OKUrl)
				return false;
			return $this->OKUrl;
		}
		function isDefOKUrl()
		{
			if($this->OKUrl)
				return true;
			return false;
		}
}
?>