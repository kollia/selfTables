<?php
//print_r($container);
		$head= $container["head"];
		$content= $container["content"];
		$kontentCount= count($content);
		$address= $STForum->get->getParamString();
		$questionAddress= "\"javascript:location.href='index.php".$address."&question'\"";
		$answerAddress= "\"javascript:location.href='index.php".$address."&answer'\"";
		//Tag::debug("gethtml.delete");
		//print_r($HTTP_GET_VARS);echo "<br />";
		global $HTTP_SERVER_VARS;
		$backAddress= $HTTP_SERVER_VARS["QUERY_STRING"];
		//echo "$backAddress<br />";		
		$backAddress= $STForum->get->getParamString(DELETE, "kategorie");
		//echo "$backAddress<br />";
		$backAddress= $STForum->get->getParamString(DELETE, "stget[link][lesen]");
		//echo "$backAddress<br />";
		$backAddress= "\"javascript:location.href='index.php".$backAddress."'\"";
		$bIsOfficer= $STForum->userIsOfficer($siteCreator, $head["KategorieID"]);
		//echo $backAddress."<br />";
		//echo $address."<br />";
		
?>
			<table width="100%">
				<tr>
					<td align="right">
						<button class="backButton" type="button" onclick=<? echo $backAddress; ?>>
							zurück
						</button>
					</td>
				</tr>
		<?	
			if(	!isset($HTTP_GET_VARS["answer"])
				and
				!$bIsOfficer)
			{	?>
				</tr>
					<td align="right">
						neue&#160;&#160;
						<button type="button" onclick=<? echo $questionAddress; ?>>
							Frage
						</button>
						&#160;&#160;im Beitrag
					</td>
				</tr>
		<? 	}	?>
			</table>
			<br /><br /><br />
			<table align="center">
				<tr>
					<td align="right">
						<b>
							Projekt:
						</b>
					</td>
					<td>
						<? echo $head["Projekt"] ?>
					</td>
				</tr>
				<tr>
					<td align="right">
						<b>
							Kategorie:
						</b>
					</td>
					<td>
						<? echo $head["Kategorie"] ?>
					</td>
				</tr>
  			<?
					if(isset($HTTP_GET_VARS["answer"]))
					{
						$content= array();
						$content[]= $whoReaded;
					}
					foreach($content as $one)
					{
						if(isset($one["question"]))
						{
							$state= "Beitrag: ".$one["who"];
							$text= $one["question"];
						}else
						{
							$state= "ID:".$one["ID"]." Beitrag: ".$one["who"];
							$text= $one["answer"];
						}	?>
						<tr height="100">
							<td height="20"></td>
						</tr>
						<tr>
							<td>
								<b>
									<? echo $state; ?>
								</b>
							</td>
						</tr>
				<? 	if($one["attachment"])
					{	
						preg_match("/([^\/]*)$/", $one["attachment"], $preg);;
						preg_match("/\/home\/htdocs(.+)$/", $one["attachment"], $preg2);
						$address= "\"javascript:location.href='".$preg2[1]."'\"";	?>
						<tr>
							<td colspan="2">
								<button type="button" onclick=<?echo $address;?>>
									Download
								</button>
								<font color="red">
									<? echo $preg[1]; ?>
								</font>
							</td>
						</tr>
				<? 	}	?>
						<tr bgcolor="#999999">
							<td colspan="2">
								<b>
									<? echo $one["subject"]; ?>
								</b>
							</td>
						</tr>
						<tr>
							<td colspan="2">
								<? echo $text; ?>
							</td>
						</tr>
				<? 	} ?>
			</table>
			
<?
		if(	!isset($HTTP_GET_VARS["answer"])
			and
			$bIsOfficer)
		{	?>
			<table width="100%">
				<tr>
					<td height="100">
					</td>
				</tr>
				<tr>
					<td align="center">
						<button class="backButton" type="button" onclick=<? echo $answerAddress; ?>>
							antworten
						</button>
					</td>
				</tr>
			</table>
<?	}	?>