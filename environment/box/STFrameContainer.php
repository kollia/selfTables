<?php

require_once($_stbasecontainer);

class STFrameContainer extends STBaseContainer
{
	var	$urlAddress;
	var	$nFrameWidth= "100%";
	var	$nFrameHeight= "100%";
	var $aInherit= array();
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
		$bExists= is_file($address);
		if(substr($address, 0, 7) == "http://")
		{
			$res= @fopen($address, "r");
			if($res !== false)
				$bExists= true;

		}elseif(!$bExists)
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
		Tag::error(!$bExists, "STFrameContainer::STFrameContainer()",
					"address '$address' do not exists");

		$this->urlAddress= $address;
	}
	function execute(&$externSideCreator, $onError= onErrorMessage)
	{
		Tag::paramCheck($externSideCreator, 1, "STSideCreator");

		STBaseContainer::execute($externSideCreator);
		if(!$this->sBackButton)
			$this->sBackButton= $this->oExternSideCreator->sBackButton;

		$iFrame= new IFrameTag();
		$iFrame->src($this->urlAddress);
		$iFrame->inherit= $this->aInherit;
		$iFrame->width($this->nFrameWidth);
		$iFrame->height($this->nFrameHeight);
		if(isset($this->frameborder))
			$iFrame->frameborder($this->frameborder);

		$this->addAllInSide($iFrame);
		return "NOERROR";
	}
	function add($value)
	{
		Tag::paramCheck($value, 1, "Tag", "string", "null");

		$this->addObj($value);
	}
	function addObj(&$value)
	{
		Tag::paramCheck($value, 1, "Tag", "string", "null");

		$this->aInherit[]= &$value;
	}
	function frameborder($value)
	{
		$this->frameborder= $value;
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