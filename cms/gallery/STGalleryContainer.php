<?php

require_once($_stsubgallerycontainer);

class STGalleryContainer extends STSubGalleryContainer
{
	function STGalleryContainer(&$container, $name= "gallery", $name2= "sgallery", $name3= "pgallery")
	{
		Tag::alert($name==$name2, "STGalleryContainer::constructor()", "first name($name) for order, can not be the same than second name($name2)");
		Tag::alert($name==$name3, "STGalleryContainer::constructor()", "first name($name) for order, can not be the same than third name($name3)");
		Tag::alert($name2==$name3, "STGalleryContainer::constructor()", "second name($name2) for order can not be the same than third name($name3)");
		
		STSubGalleryContainer::STSubGalleryContainer($container, $name);
		
		$this->oOrderContainer= &new STSubGalleryContainer($container, $name2);
    	$this->oPicContainer= &new STSubGalleryContainer($container, $name3);
    	
    	$this->oOrderContainer->oOrderContainer= &$this->oOrderContainer;
    	$this->oOrderContainer->oPicContainer= &$this->oPicContainer;
    	
    	$this->oPicContainer->oOrderContainer= &$this->oOrderContainer;
    	$this->oPicContainer->oPicContainer= &$this->oPicContainer;
    	
    	$this->oOrderContainer->setDisplayName("zurück");
    	$this->oPicContainer->setDisplayName("zurück");
		
			
	}
	function noAdministration()
	{
	    $this->oOrderContainer->noAdministration();
	    $this->oPicContainer->noAdministration();
		STSubGalleryContainer::noAdministration();
	}
	function uploadPath($path)
	{
	    $this->oOrderContainer->uploadPath($path);
	    $this->oPicContainer->uploadPath($path);
		STSubGalleryContainer::uploadPath($path);
	}
	function ftpPath($path)
	{
	    $this->oOrderContainer->ftpPath($path);
	    $this->oPicContainer->ftpPath($path);
		STSubGalleryContainer::ftpPath($path);
	}
	function setFirstDisplayOrder($orderName)
	{
	    $this->oOrderContainer->setFirstDisplayOrder($orderName);
	    $this->oPicContainer->setFirstDisplayOrder($orderName);
		STSubGalleryContainer::setFirstDisplayOrder($orderName);
	}
	function &needContainer($container)
	{
	    $this->oOrderContainer->needContainer($container);
	    $this->oPicContainer->needContainer($container);
		$container= &STSubGalleryContainer::needContainer($container);
		return $container;
	}
	function navigationTable($oTable)
	{
	    $this->oOrderContainer->navigationTable($oTable);
	    $this->oPicContainer->navigationTable($oTable);
		STSubGalleryContainer::navigationTable($oTable);
	}
}

?>