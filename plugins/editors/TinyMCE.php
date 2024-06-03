<?php

class TinyMCE
{
	var	$initString= null;	// if this string not be null,
							// the initialization of TinyMCE only
							// about this string
	var	$sMode;
	var $sLanguage= null;
	var	$sTheme= null;
	var $sSelector= "textarea";
	var	$sElements= null;
	var	$sPlugins= null;
	var	$sLocation= null;
	var	$sAlign= null;
	var	$sDisable= null;
	var	$sStyle= null;
	var	$sContent= null;
	var	$sAdd= array();
	var	$sAddBefore= array();
	
	function __construct($mode= "exact")
	{
		$this->sMode= $mode;
	}
	function &getExternalScript()
	{
		global	$_dbselftable_tinymce_path;
		
		$script= new JavascriptTag();
		$script->src($_dbselftable_tinymce_path."tinymce.min.js");
		return $script;
	}
	function &getHeadScript($objectName= "tinyMCE")
	{
		$script= new JavascriptTag();
		if($objectName!=="tinyMCE")
			$script->add($objectName."= tinyMCE;");
		$object=  $objectName.".init(";
		if($this->initString===null)
		{
			$object.= "{";
			$object.= "mode:'".$this->sMode."'";
			$object.= "selector:'{$this->sSelector}'";
			if($this->sTheme!==null)
				$object.= ",theme:'".$this->sTheme."'";
			if($this->sLanguage!==null)
				$object.= ",language:'".$this->sLanguage."'";
			if($this->sElements!==null)
				$object.= ",elements:'".$this->sElements."'";
			if($this->sPlugins!==null)
				$object.= ",plugins:'".$this->sPlugins."'";
			if($this->sAddBefore!==null)
			{
				foreach($this->sAddBefore as $row=>$add)
					$object.= ",theme_advanced_buttons".$row."_add_before:'".$add."'";
			}
			if($this->sAdd!==null)
			{
				foreach($this->sAdd as $row=>$add)
					$object.= ",theme_advanced_buttons".$row."_add:'".$add."'";
			}
			if($this->sDisable!==null)
				$object.= ",theme_advanced_disable:'".$this->sDisable."'";
			if($this->sStyle!==null)
				$object.= ",theme_advanced_styles:'".$this->sStyle."'";
			if($this->sContent!==null)
				$object.= ",content_css:'".$this->sContent."'";
			if($this->sLocation!==null)
				$object.= ",theme_advanced_toolbar_location:'".$this->sLocation."'";
			if($this->sAlign!==null)
				$object.= ",theme_".$this->sTheme."_toolbar_align:'".$this->sAlign."'";
			$object.= "}";
		}else
			$object.= $this->initString;
		$object.= ");";
		$script->add($object);
		return $script;
	}
	function initialization($string)
	{
		$this->initString= $string;
	}
	function language($lang)
	{
		$this->sLanguage= $lang;
	}
	function theme($theme)
	{
		$this->sTheme= $theme;
	}
	function elements($element)
	{	
		$args= func_get_args();
		foreach($args as $arg)
		{
			if($this->sElements)
				$this->sElements.= ",";
			$this->sElements.= $arg;
		}
	}
	function plugins($plugin)
	{	
		$args= func_get_args();
		foreach($args as $arg)
		{
			if($this->sPlugins)
				$this->sPlugins.= ",";
			$this->sPlugins.= $arg;
		}
	}
	function disable($button)
	{
		if($this->sDisable)
			$this->sDisable.= ",";
		$this->sDisable.= $button;
	}
	function addButton($button, $row= 1)
	{
		if($this->sAdd[$row])
			$this->sAdd[$row].= ",";
		$this->sAdd[$row].= $button;
	}
	function addButtonBefore($button, $row= 1)
	{
		if($this->sAddBefore[$row])
			$this->sAddBefore[$row].= ",";
		$this->sAddBefore[$row].= $button;
	}
	function toolbar_location($location)
	{
		$this->sLocation= $location;
	}
	function toolbar_align($align)
	{
		$this->sAlign= $align;
	}
	function advanced_style($displayName, $styleName)
	{
		if($this->sStyle)
			$this->sStyle.= ";";
		$this->sStyle.= $displayName."=".$styleName;
	}
	function content_css($content)
	{
		$this->sContent= $content;
	}
}


?>