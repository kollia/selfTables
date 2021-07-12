<?php

class STCalendarSerieForm extends OSTBox
{
	function STCalendarSerieForm(&$database, $classID= "STcalendarSerie")
	{
		OSTBox::OSTBox($database, $classID);
	}
	function execute($action, $onError= onErrorMessage)
	{
		$serie= $this->db->getTable("calendarSeries");
		$this->table($serie);
		
		OSTBox::execute($action, $onError);
	}
}
?>