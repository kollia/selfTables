<?php

$_email= load_pluginModule("email");
require_once($_email['_stemail']);

function usermanagement_remove_empty_columns(string &$string, $fromTable)
{
	$columns= $fromTable->columns;
	while(preg_match("/\{(.*)\}/", $string, $preg))
	{
		$bFound= false;
		foreach($columns as $column)
		{
			if($preg[1] == $column['name'])
			{
				$bFound= true;
				$string= preg_replace("/".$preg[0]."/", "", $string);
				break;
			}
		}
		if(!$bFound)
			break;
	}
}
function usermanagement_email_replacement(bool $html, string &$string, array $replacement, array $dbreplacement) : int
{
	if($html)
		$html_val= "YES";
	else
		$html_val= "NO";
	$nChanged= 0;
	foreach($dbreplacement as $key => $value)
	{
		if(isset($value))
		{
			$string= preg_replace("/\{$key\}/", $value, $string, /*no limit*/-1, $count);
			$nChanged+= $count;
		}
	}
	foreach($replacement as $value)
	{
		//echo "sort {$value['case']} {$value['html']}<br>";
		if(	(	$value['html'] == "NO" ||  // the replacements have to be sorted that the html content
				$value['html'] == $html_val	) && // is for all cases the first
			preg_match("/\{(".$value['case'].")(\.exist)?\}/", $string, $preg))
		{
			$pattern= $preg[1];
			if(isset($preg[2]))
			{ // <case>.exist want to call
				$pattern.= "\\.exist";
				if(	$html &&
					$value['html'] == "NO"	)
				{// from normal string mail text replace carriage return for html text
					$replace= preg_replace("/\n/", "<br />", $value['text']);
				}else
					$replace= $value['text'];
				if(preg_match_all("/\{[^{}]+\}/", $replace, $counter))
				{
					$num= usermanagement_email_replacement($html, $replace, $replacement, $dbreplacement);
					if(count($counter) != $num)
						$replace= "";
				}
			}else
			{
				if(	$html &&
					$value['html'] == "NO"	)
				{// from normal string mail text replace carriage return for html text
					$replace= preg_replace("/\n/", "<br />", $value['text']);
				}else
					$replace= $value['text'];
			}
			$string= preg_replace("/\{$pattern\}/", $replace, $string, /*no limit*/-1, $count);
			$nChanged+= $count;
		}
	}
	if(preg_match("/\{.*\}/", $string, $preg))
	{ // if filled placeholders but now also placeholder exist do again
	  // but if doesn't filled placeholders but placeholder exist don't do again
		if($nChanged)
		{
			$nChanged+= usermanagement_email_replacement($html, $string, $replacement, $dbreplacement);
		}
	}
	return $nChanged;
}
function usermanagement_main_passwordCheckCallback(STCallbackClass &$callbackObject, $columnName, $rownum)
{
	global $__global_defined_password_callback_function;

	$action= $callbackObject->getAction();
	if($callbackObject->display)
	{
		$post= new STPostArray();
		$pwd= $post->getValue("Pwd");
		if(!isset($__global_defined_password_callback_function))
			return "";
		$ret= $__global_defined_password_callback_function(/*display*/true, $action, $pwd);
		if(!is_bool($ret))
			$callbackObject->addHtmlContent($ret);
		return "";
	}
    if(!$callbackObject->before)
        return;

	//$callbackObject->echoResult();
	$active= $callbackObject->getValue("active");
	$send= $callbackObject->getValue("sending");
	if(	$send == "yes" ||
		(	$action == STINSERT &&			// if action is STINSERT
			$active == "YES"			)	)	// revert "active user" set for dummy user (need no password check)
	{
		$callbackObject->setValue("", "Pwd");
		$callbackObject->setValue("", "re_Pwd");
		return;
	}
	$pwd= $callbackObject->getValue("Pwd");
	if( $action == STUPDATE &&
		$pwd == ""								)
	{
		// can be "" by update when password not changed
		return;
	}
	if(!isset($__global_defined_password_callback_function))
		return "";
	return $__global_defined_password_callback_function(/*display*/false, $action, $pwd);
}
function usermanagement_passwordCheckCallback(bool $display, string $onCase, string $password) : bool|string|Tag
{
	if($display)
	{
		if($password == "") // no error occurred
			return true;
		$table= new st_tableTag(LI);
			$table->style("background-color:red;");
			$table->add("password have to be longer than 8 digits");
			$table->nextRow();
			$table->add("The password must contain lowercase letters,<br />uppercase letters and numbers");
		return $table;
	}
	
	if(strlen($password) < 9)
		return false;
	if(	preg_match("/[a-z]/", $password) &&
		preg_match("/[A-Z]/", $password) &&
		preg_match("/[0-9]/", $password)	&&
		!preg_match("/^\*/", $password)		)
	{
		return true;
	}
	return false;
}
$__global_mail_text_dummys= null;
function get_db_mail_text(STDbTable $userTable, string $text_type, array $newdbvalue)
{
    global $__global_mail_text_dummys;

    if(!isset($__global_mail_text_dummys))
    {
        $selector= new STDbSelector($userTable->getDatabase());
        $selector->select("Mail", "case");
        $selector->select("Mail", "subject");
        $selector->select("Mail", "html");
        $selector->select("Mail", "text");
        $selector->orderBy("case");
        $selector->orderBy("html", /*ASC*/false);
        $selector->execute();
        $__global_mail_text_dummys= $selector->getResult();
    }

    $new_replacement= array();
    $admin_email= "";
    $mail_subject= "";
    $mail_text= "";
    $html_mail_text= "";
    foreach($__global_mail_text_dummys as $nr=>$row)
    {
        if($row['case'] == "ADMINISTRATION_MAIL")
            $admin_email= $row['text'];
        if($row['case'] == $text_type)
        {
            $mail_subject= $row['subject'];
            if($row['html'] == "YES")
                $html_mail_text= $row['text'];
            else
                $mail_text= $row['text'];
        }else
        {
            if(preg_match("/\./", $row['case']))
            {
                $cases= preg_split("/\./", $row['case']);
                if(	isset($newdbvalue[$cases[1]]) &&
                    $newdbvalue[$cases[1]] == $cases[2]	)
                {
                    $row['case']= $cases[0];
                    $row['text']= preg_replace("/\{subject\}/", $row['subject'], $row['text']);
                    $new_replacement[]= $row;
                }
            }else
            {
                if($row['case'] == "MAIL_CODE")
                {
                    $code= $newdbvalue['regcode'];
                    $row['text']= $code;
                }else
                    $row['text']= preg_replace("/\{subject\}/", $row['subject'], $row['text']);
                $new_replacement[]= $row;
            }
        }
    }
    //STCheck::debug();
    if($html_mail_text == "") // from normal string mail text replace carriage return for html text
        $html_mail_text= preg_replace("/\n/", "<br />", $mail_text);
    usermanagement_email_replacement(/*HTML*/false, $mail_subject, $new_replacement, $newdbvalue);
    if(preg_match("/\{.*\}/", $mail_subject, $preg))
        STCheck::warning(true, "usermanagement_email_replacement()", "placeholder '{$preg[0]}' from registration EMail-Text does not exist");
    usermanagement_email_replacement(/*HTML*/false, $mail_text, $new_replacement, $newdbvalue);
    usermanagement_email_replacement(/*HTML*/true, $html_mail_text, $new_replacement, $newdbvalue);
    usermanagement_remove_empty_columns($mail_text, $userTable);
    usermanagement_remove_empty_columns($html_mail_text, $userTable);
    if(preg_match("/\{.*\}/", $mail_text, $preg))
        STCheck::warning(true, "usermanagement_email_replacement()", "placeholder '{$preg[0]}' from registration EMail-Text does not exist");

    $tRv= array();
    $tRv['admin']= $admin_email;
    $tRv['subject']= $mail_subject;
    $tRv['text']= $mail_text;
    $tRv['html']= $html_mail_text;
    return $tRv;
}

?>