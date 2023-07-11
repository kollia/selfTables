<?php

require_once( $_sttdate );

class STDbtDate extends STtDate
{
    protected function defineSpecificDays_forYear(string $year)
    {
        global $global_selftable_dateFormat_struct;
        global $global_selftable_specific_days;
        global $global_defined_specific_days;
        
       
        // toDo: fill global_defined_specific_days
        //       for year from database
        $statement= "select `name`, `month`, `day`, `date`, `public` from `red_letter_days` ";
        $statement.= "where `used`='yes' and `month`='easter_day'";
        $statement.= " order by `day`";
        $global_defined_specific_days[$year]= $defined;
    }
}

?>