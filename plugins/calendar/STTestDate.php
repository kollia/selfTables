<?php 

$global_selftable_dateFormat_struct=  array(
    "locale" => "en_US",
    "pattern" => null,
    "dateType" => IntlDateFormatter::SHORT,
    "timeType" => IntlDateFormatter::NONE,
    "formatter" => null
);

class STDate
{
    private $fmdate;
    
    public function __construct(string $locale= null)
    {
        global $global_selftable_dateFormat_struct;
        
        $this->fmdate= &$global_selftable_dateFormat_struct;
        if( !isset($this->fmdate['formatter']) ||
            (   isset($locale) &&
                $this->fmdate['locale'] != $locale    )   )
        {
            //st_print_r($this->fmdate);  
            if(!isset($locale))
                $locale= $this->fmdate['locale'];
            $this->fmdate['formatter'] = new IntlDateFormatter($locale, $this->fmdate['dateType'], $this->fmdate['timeType']);
        }
        if(isset($locale))// set local member after formatter creation
            $this->fmdate['local']= $locale; 
    }
    public function format($format, int $time= null) : string
    {
        if(isset($time))
        {
            STCheck::param($format, 0, "string");
            return date($format, $time);
        }
        
        STCheck::param($format, 0, "int");
        
        $time= $format;
        return $this->fmdate['formatter']->format($time);
    }
}
    
?>