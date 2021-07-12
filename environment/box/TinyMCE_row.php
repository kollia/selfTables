<?php

require_once($_tinymce);

class TinyMCE_row extends TinyMCE
{
	function TinyMCE_row($bJustify= false, $mode= "exact")
	{
		TinyMCE::TinyMCE($mode);
		
		$this->theme("advanced");
		
		$this->toolbar_location("top");
		
		if($bJustify)
			$this->addButton("separator,justifyleft,justifycenter,justifyright");
		$this->addButton("separator,removeformat,cleanup");
		$this->addButton("separator,help");
		
		$this->disable("justifyleft");
		$this->disable("justifycenter");
		$this->disable("justifyright");
		$this->disable("justifyfull");
		$this->disable("styleselect");
		$this->disable("bullist");
		$this->disable("numlist");
		$this->disable("outdent");
		$this->disable("indent");
		$this->disable("undo");
		$this->disable("redo");
		$this->disable("link");
		$this->disable("unlink");
		$this->disable("image");
		$this->disable("anchor");
		$this->disable("cleanup");
		$this->disable("help");
		$this->disable("code");
		$this->disable("table");
		$this->disable("row_before");
		$this->disable("row_after");
		$this->disable("delete_row");
		$this->disable("separator");
		$this->disable("rowseparator");
		$this->disable("col_before");
		$this->disable("col_after");
		$this->disable("delete_col");
		$this->disable("hr");
		$this->disable("removeformat");
		$this->disable("sub");
		$this->disable("sup");
		$this->disable("formatselect");
		$this->disable("fontselect");
		$this->disable("fontsizeselect");
		$this->disable("forecolor");
		$this->disable("charmap");
		$this->disable("visualaid");
		$this->disable("spacer");
		$this->disable("cut");
		$this->disable("copy");
		$this->disable("paste");
	}
}


?>