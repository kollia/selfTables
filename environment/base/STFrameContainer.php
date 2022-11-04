<?php

require_once($_stbasecontainer);

class STFrameContainer extends STBaseContainer
{
	var	$urlAddress;
	var	$nFrameWidth= "100%";
	var	$nFrameHeight= "100%";
	var $aInherit= array();
	var $bframeset= false;
	var	$frameborder= null;

	function __construct($name= "STFrame", $address= "")
	{
		Tag::paramCheck($name, 1, "string");
		Tag::paramCheck($address, 2, "string", "empty(string)");

		if($address)
			$this->setFramePath($address);
		STBaseContainer::__construct($name);
	}
	function setFramePath($address)
	{
	    if(STCheck::isDebug())
	    {
    	    if( substr($address, 0, 7) == "http://" ||
    	        substr($address, 0, 8) == "https://"    )
    	    {
    	        $res= @fopen($address, "r");
    	        if($res !== false)
    	            $bExists= true;	            
    	    }else
    	    {
        	    $preg= preg_split("/\?/", $address);
        	    if($preg[0] === "")
        	    {
        	        $bExists= is_file("index.html");
        	        if(!$bExists)
        	            $bExists= is_file("index.php");
        	    }else
        	    {
        		    $bExists= is_file($preg[0]);
            		if(!$bExists)
            		{
            			$adr= $address;
            			if(substr($adr, strlen($adr)-1)!="/")
            				$adr.= "/";
            			$adr2= $adr."index.htm";
            			$bExists= file_exists($adr2);
            			if(!$bExists)
            			{
            				$adr2= $adr."index.html";
            				$bExists= is_file($adr2);
            				if(!$bExists)
            				{
            					$adr2= $adr."index.php";
            					$bExists= file_exists($adr2);
            				}
            			}
            		}
        	    }
    	    }
    		STCheck::is_error(!$bExists, "STFrameContainer::STFrameContainer()",
    					"address '$address' do not exists");
    		if(!$bExists)
    		    exit;
	    }
		$this->urlAddress[]= $address;
	}
	function execute(&$externSideCreator, $onError= onErrorMessage)
	{
		Tag::paramCheck($externSideCreator, 1, "STSiteCreator");

		STBaseContainer::execute($externSideCreator, $onError= onErrorMessage);
		if(!$this->sBackButton)
			$this->sBackButton= $this->oExternSideCreator->sBackButton;

		if($this->bframeset)
		{
		    $this->tag= "frameset";
		    $this->class= "STFrame";
		    if( !$this->hasAttribut("frameborder") &&
		        !$this->hasAttribut("framespacing")   )
		    {
    		    $this->frameborder(0);
    		    $this->framespacing(0);
		    }
		    $frame= new FrameTag("navigation");
		        $frame->src($this->urlAddress[0]);
		        $frame->noresize();
		        $frame->scrolling("NO");
		        $frame->marginheight(2);
		        $frame->marginwidth(10);
		        $frame->topmargin(2);
		        $frame->leftmargin(10);
		        $this->addAllInSide($frame);
		    $frame2= new FrameTag("project");
		        $frame2->src($this->urlAddress[1]);
		        $frame2->name("display");
		        $this->addAllInSide($frame2);
		}else
		{
    		$iFrame= new IFrameTag();
    		$iFrame->src($this->urlAddress[0]);
    		$iFrame->inherit= $this->aInherit;
    		$iFrame->width($this->nFrameWidth);
    		$iFrame->height($this->nFrameHeight);
    		if(isset($this->frameborder))
    			$iFrame->frameborder($this->frameborder);
    
    		$this->addAllInSide($iFrame);
		}
		return "NOERROR";
	}
	function add($value)
	{
		Tag::paramCheck($value, 1, "Tag", "string", "null");

		$this->addObj($value);
	}
	function addObj(&$value, $showWarning = false)
	{
		Tag::paramCheck($value, 1, "Tag", "string", "null");

		$this->aInherit[]= &$value;
	}
	function framesetRows($value)
	{
	    $this->bframeset= true;
	    $this->insertAttribute("rows", $value);
	}
	function framesetColumns($value)
	{
	    $this->bframeset= true;
	    $this->insertAttribute("columns", $value);
	}
	function frameBorder($value)
	{
	    if($value)
	        $value= "1";
        else
            $value= "0";
        $this->insertAttribute("frameborder", $value);
        $this->frameborder= $value;
	}
	function frameSpacing($value)
	{
	    $this->insertAttribute("framespacing", $value);
	}
	function width($nWidth)
	{
		$this->nFrameWidth= $nWidth;
	}
	function height($nHeight)
	{
		$this->nFrameHeight= $nHeight;
	}
}

?>