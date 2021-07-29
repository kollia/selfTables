<?php

class StyleTag extends Tag
{
		function __construct()
		{
			Tag::Tag("style", true);
		}
}

class style extends Tag
{
		function __construct($name)
		{
			Tag::Tag($name, false);
		}
		function border_width($value)
		{
			$this->insertAttribute("border-width", $value);
		}
		function border_style($value)
		{
			$this->insertAttribute("border-style", $value);
		}
		function border_darkcolor($value)
		{
			$this->insertAttribute("border-darkcolor", $value);
		}
		function border_lightcolor($value)
		{
			$this->insertAttribute("border-lightcolordth", $value);
		}
		function background_color($value)
		{
			$this->insertAttribute("background-color", $value);
		}
		function font($value)
		{
			$this->insertAttribute("font", $value);
		}
		function font_family($value)
		{
			$this->insertAttribute("font-family", $value);
		}
		function font_size($value)
		{
			$this->insertAttribute("font-size", $value);
		}
		function font_weight($value)
		{
			$this->insertAttribute("font-weight", $value);
		}
		function scrollbar_base_color($value)
		{
			$this->insertAttribute("scrollbar-base-color", $value);
		}		
  	function scrollbar_3dlight_color($value)
		{
			$this->insertAttribute("scrollbar-3dlight-color", $value);
		}		
  	function scrollbar_arrow_color($value)
		{
			$this->insertAttribute("scrollbar-arrow-color", $value);
		}		
  	function scrollbar_darkshadow_color($value)
		{
			$this->insertAttribute("scrollbar-darkshadow-color", $value);
		}		
  	function scrollbar_face_color($value)
		{
			$this->insertAttribute("scrollbar-face-color", $value);
		}		
  	function scrollbar_highlight_color($value)
		{
			$this->insertAttribute("scrollbar-highlight-color", $value);
		}		
  	function scrollbar_shadow_color($value)
		{
			$this->insertAttribute("scrollbar-shadow-color", $value);
		}		
	  function scrollbar_track_color($value)
		{
			$this->insertAttribute("scrollbar-track-color", $value);
		}
		function color($value)
		{
			$this->insertAttribute("color", $value);
		}
		function text_decoration($value)
		{
			$this->insertAttribute("text-decoration", $value);
		}
		function display()
		{
			global $tag_spaces;
			global $HTML_CLASS_DEBUG_CONTENT;
			
			if($HTML_CLASS_DEBUG_CONTENT)
			{
				$this->spaces($tag_spaces);
				$tag_spaces++;
			}
			echo $this->tag."{";
			foreach($this->aNames as $key => $value)
			{
				if($HTML_CLASS_DEBUG_CONTENT)
					$this->spaces($tag_spaces);
				echo $key.":".$value.";";
			}			
			if($HTML_CLASS_DEBUG_CONTENT)
			{
				echo "   ";
				$tag_spaces--;
			}
			echo "}";
		}
}

?>