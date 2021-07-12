<?php

function array_key_exists($search, $array)
{
    foreach($array as $key=>$value)
		{
		    if($search===$key)
				    return true;
		}
		return false;
}
?>