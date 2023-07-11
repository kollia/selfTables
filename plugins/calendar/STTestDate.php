<?php 

$global_selftable_dateFormat_struct=  array(
    "locale" => "en_US",
    "pattern" => null,
    "format" => "Y-m-d",
    "dateType" => IntlDateFormatter::SHORT,
    "timeType" => IntlDateFormatter::NONE,
    "formatter" => null
);
$global_selftable_specific_days= null;
$global_defined_specific_days= array();

class STtDate
{
    private $fmdate;
    public $year;
    public $month;
    public $day;
    public $hour;
    public $minute;
    public $second;
    public $date= null;
    private $btime= false;
    private $bdate= false;
    
    /**
     * object of STtDate creation
     * 
     * @param string|int $yearDate can be an year number lower than 3000, an timestamp or an date string 
     * @param string|int $monthFormat can be an month 1-12 if first parameter is a year number or a format string 
     *                                if first parameter is an date string
     *                                the format is like <code>DateTimeImmutable::createFromFormat()</code>
     *                                as example 'Y-m-d' is '2023-03-26' or 'H:i:s' is '15:03:97' or both delimited with space
     * @param int $day number of day 1-31
     * @param int $hour number of hour 0-23
     * @param int $minute number of minute 0-59
     * @param int $second number of seconds 0-59
     */
    public function __construct($yearDate= null, $monthFormat= null, int $day= null,
        int $hour= null, int $minute= null, int $second= null)
    {
        global $global_selftable_dateFormat_struct;
        
        STCheck::param($yearDate, 0, "STtDate", "DateTime", "string", "int", "null");
        STCheck::param($monthFormat, 0, "string", "int", "null");
        
        STtDate::init();        
        $this->fmdate= &$global_selftable_dateFormat_struct;
        if(typeof($yearDate, "DateTime"))
            $this->date= $yearDate;
        elseif(typeof($yearDate, "STtDate"))
            $this->date= $yearDate->date;
        elseif( $yearDate === null ||
            (   is_numeric($yearDate) &&
                $yearDate > 3000         )  )
        {
            $this->date= new DateTime();
            if(isset($yearDate))
                $this->date->setTimestamp($yearDate);
            
        }elseif( is_string($yearDate) &&
                 !is_numeric($yearDate)  )
        {
            if(!isset($monthFormat))
                $monthFormat= $this->fmdate['format'];
            $this->date= DateTime::createFromFormat($monthFormat, $yearDate);
            
        }else
        {
            $this->date= new DateTime();
            if($yearDate > 0)
                $this->bdate= true;
            if(!isset($hour))   
                $hour= 0;
            if(!isset($minute))
                $minute= 0;
            if(!isset($second))
                $second= 0;
            if( $hour > 0 ||
                $minute > 0 ||
                $second > 0     )
            {
                $this->btime= true;
            }
            $timestamp= mktime($hour, $minute, $second, $monthFormat, $day, $yearDate);
            $this->date->setTimestamp($timestamp);              
        }
    }
    static public function init(string $locale= null)
    {
        global $global_selftable_dateFormat_struct;
        global $global_selftable_specific_days;
        
        $fm= &$global_selftable_dateFormat_struct;
        if( !isset($fm['formatter']) ||
            (   isset($locale) &&
                $fm['locale'] != $locale    )   )
        {
            //st_print_r($fm);  
            if(!isset($locale))                
                $locale= $fm['locale'];
            $fm['formatter'] = new IntlDateFormatter($locale, $fm['dateType'], $fm['timeType']);
        }
        if(isset($locale))// set local member after formatter creation
            $fm['locale']= $locale;
        
        if(!isset($global_selftable_specific_days))
        {
            $global_selftable_specific_days= array();
            $global_selftable_specific_days['de_AT']= array();
            $days= &$global_selftable_specific_days;
            $global_selftable_specific_days['de_AT']= array(
                array(  
                    "name" => "Neujahr",             
                    "public" => true,
                    "date" => "exact",
                    "day" => "01",
                    "month" => "01"
                ),
                array(
                    "name" => "Heilige Drei Könige",
                    "public" => true,
                    "date" => "exact",
                    "day" => "06",
                    "month" => "01"
                ),
                array( "name" => "Aschermittwoch",      "public" => false, "date" => "easter", "day" => -46 ),
                array( "name" => "Palmsonntag",         "public" => false, "date" => "easter", "day" =>  -7 ),
                array( "name" => "Gründonnerstag",		"public" => false, "date" => "easter", "day" =>  -3 ),
                array( "name" => "Karfreitag",		    "public" => false, "date" => "easter", "day" =>  -2 ),
                array( "name" => "Ostersonntag",		"public" => true,  "date" => "easter", "day" =>   0 ),
                array( "name" => "Ostermontag",		    "public" => true,  "date" => "easter", "day" =>   1 ),
                array(
                    "name" => "Staatsfeiertag",
                    "public" => true,
                    "date" => "exact",
                    "day" => "01",
                    "month" => "05"
                ),
                array( "name" => "Christi Himmelfahrt", "public" => true,  "date" => "easter", "day" =>  39 ),
                array( "name" => "Pfingstsonntag",		"public" => true,  "date" => "easter", "day" =>  49 ),
                array( "name" => "Pfingstmontag",		"public" => true,  "date" => "easter", "day" =>  50 ),
                array( "name" => "Fronleichnam",		"public" => true,  "date" => "easter", "day" =>  60 ),
                array(
                    "name" => "Mariä Himmelfahrt",
                    "public" => true,
                    "date" => "exact",
                    "day" => "15",
                    "month" => "08"
                ),
                array(
                    "name" => "Nationalfeiertag",
                    "public" => true,
                    "date" => "exact",
                    "day" => "26",
                    "month" => "10"
                ),
                array(
                    "name" => "Allerheiligen",
                    "public" => true,
                    "date" => "exact",
                    "day" => "01",
                    "month" => "11"
                ),
                array(
                    "name" => "Allerseelen",
                    "public" => false,
                    "date" => "exact",
                    "day" => "02",
                    "month" => "11"
                ),
                array(
                    "name" => "Mariä Empfängnis",
                    "public" => true,
                    "date" => "exact",
                    "day" => "08",
                    "month" => "12"
                ),
                array(
                    "name" => "Heiliger Abend",
                    "public" => false,
                    "date" => "exact",
                    "day" => "24",
                    "month" => "12"
                ),
                array(
                    "name" => "Christtag",
                    "public" => true,
                    "date" => "exact",
                    "day" => "25",
                    "month" => "12"
                ),
                array(
                    "name" => "Stefanitag",
                    "public" => true,
                    "date" => "exact",
                    "day" => "26",
                    "month" => "12"
                ),
                array(
                    "name" => "Silvester",
                    "public" => false,
                    "date" => "exact",
                    "day" => "31",
                    "month" => "12"
                )
            );
        }
    }
    protected function defineSpecificDays_forYear(string $year)
    {
        global $global_selftable_dateFormat_struct;
        global $global_selftable_specific_days;
        global $global_defined_specific_days;

        
        $locale= $global_selftable_dateFormat_struct['locale'];
        $easter_timestamp= easter_date($year);        
        $defined= array();
        foreach($global_selftable_specific_days[$locale] as $day)
        {
            if($day['date'] == "easter")
            {
                $easter= new DateTime();
                $easter->setTimestamp($easter_timestamp);
                if($day['day'] != 0)
                    $easter->modify($day['day']." days");
                $day['year']= $easter->format("Y");
                $day['month']= $easter->format("m");
                $day['day']= $easter->format("d");
                $monthday= $easter->format("m-d");
                $defined[$monthday]= $day;
            }else
            {
                $day['year']= $year;
                $defined[$day['month']."-".$day['day']]= $day;
            }
        }
        $global_defined_specific_days[$year]= $defined;
    }
    public function getSpecificDayData(int $timestamp= null)
    {
        global $global_defined_specific_days;
        
        $currentYear= $this->format("Y", $timestamp);
        if(!isset($global_defined_specific_days[$currentYear]))
            $this->defineSpecificDays_forYear($currentYear);
        $currentMonthDay= $this->format("m-d", $timestamp);
        if(isset($global_defined_specific_days[$currentYear][$currentMonthDay]))
            return $global_defined_specific_days[$currentYear][$currentMonthDay];
        return null;
    }
    public function isPublicHoliday(int $timestamp= null) : bool
    {
        $data= $this->getSpecificDayData($timestamp);
        if(isset($data))
            return $data['public'];
        return false;
    }
    public function getPublicHolidayName(int $timestamp= null) : string
    {
        $data= $this->getSpecificDayData($timestamp);
        if( isset($data) &&
            $data['public'] )
        {
            return $data['name'];
        }
        return "";
    }
    public function getSpecificDayName(int $timestamp= null) : string
    {
        $data= $this->getSpecificDayData($timestamp);
        if(isset($data))
            return $data['name'];
        return "";
    }
    public function diff(STtDate $date)
    {
        echo "difference between ".$this->date->format("Y-m-d H:i:s")." and ".$date->date->format("Y-m-d H:i:s")."<br>";
        $length= $this->date->diff($date->date);
        st_print_r($length);
        $oRv= new STtDate($length);
        return $oRv;
    }    
    public function format($format, int $timestamp= null) : string
    {        
        if( isset($timestamp) ||
            is_string($format)   )
        {
            STCheck::param($format, 0, "string");
            if(!isset($timestamp))
                $timestamp= $this->date->getTimestamp();
            return date($format, $timestamp);
        }
        
        STCheck::param($format, 0, "int");
        
        $time= $format;
        return $this->fmdate['formatter']->format($time);
    }
    public function add($time)
    {
        STCheck::param($time, 0, "STtDate", "int");
        
        $timestamp= $this->date->getTimestamp();
        if(typeof($time, "STtDate"))
            $timestamp+= $time->date->getTimestamp();
        else
            $timestamp+= $time;
        $this->date->setTimestamp($timestamp);
        return $timestamp;
    }
    public function minus($time)
    {
        STCheck::param($time, 0, "STtDate", "int");

        $timestamp= $this->date->getTimestamp();
        if(typeof($time, "STtDate"))
            $timestamp-= $time->date->getTimestamp();
        else
            $timestamp-= $time;
        $this->date->setTimestamp($timestamp);
        return $timestamp;
    }
    public function timestamp()
    {
        return $this->getTimestamp();
    }
    public function modify(string $modifier)
    {
        return $this->modify($modifier);
    }
}
    
?>