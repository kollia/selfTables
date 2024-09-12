<?

	//require_once("tools_path.php");
	require_once($php_html_description);
/************************************************************************
 * 
 * LDAP Server - Abstraction  / Object Wrapper
 * @author	benjamin.zotter@mindtake.com
 * @created 	2004-05-25
 *
 * example of usage:
 * <pre>
 *	class MY_LDAP_Server extends LDAPServer {
 *		var $host_ = 'sw02all001.ad.local';
 *      var $loginUserName_ = 'ZotterR';
 *		var $loginBaseDN_ = 'OU=Users,OU=ADM,DC=ad,DC=local';
 * 		var $password_ = 'Bums.ti';
 * 		var $baseDN_ = 'DC=ad,DC=local';
 *		var $loginAttribName_ = 'cn';
 * 	}
 *
 *	$ldap = new MY_LDAP_Server();
 *	$ldap->showWarnings_ = true;
 *
 * 	$ldap->connect() OR die( "<br /><b>Unable to connect!</b>" );
 *	$ldap->bind() OR die( "<br /><b>Bind was not successfull!</b>" );
 *	$ldap->search( "(&(sAMAccountName=".$userIDPrefix."*)(objectClass=user))", 
 *			array( 'sAMAccountName', 'displayName', 'description', 'memberOf' ),
 *			"", "OU=Users,DC=ad,DC=local"
 *	);
 *	$ldap->debugPrintResult();
 *	echo "<hr>manual output of searchresults (only the first found attribut each):<br />";
 *	while( $ldap->next_record() ){
 *		echo "Found: <em>".array_pop( $ldap->f('displayName') )."</em> with description:<font size='2'>".array_pop( $ldap->f( 'description' ) )."</font><br />";
 *	}
 *
 * </pre>
 *
**/

class LDAPServer{

	/*public:*/
	var $host_ = 'localhost';
	var $port_ = '389';
	
	var $loginUserName_ ;
	var $loginBaseDN_ ;
	var $password_ ;

	var $baseDN_ = 'DC=ad,DC=local';
	var $loginAttribName_ = 'cn';
	
	
	var $debug_ = false;
	var $showWarnings_ = false;
	/*private:*/
	var $con_ = false;
	var $binding_ = false;
	var $resultRessource_;
	var $resultEntryID_ ;
	var $resultAttributes_;
	
	/*public:*/
	function LDAPServer(){
	}
	//--------------------------------------------------------------------	
	function setBaseDN( $baseDN ){
		$this->baseDN_ = $baseDN;
	}
	//--------------------------------------------------------------------
	function getBaseDN(  ){
		return $this->baseDN_;
	}
	//--------------------------------------------------------------------
	function getUserDN( $userName = '' )
	{
		$UserDN=  $this->loginAttribName_.'=';
		if($userName == '')
			$UserDN.= $this->loginUserName_;
		else
			$UserDN.= $userName;
		if($this->loginUserName_ != '')
			$UserDN.= ',';
		$UserDN.= $this->loginBaseDN_;
		return	$UserDN;
	}
	//--------------------------------------------------------------------
	function connect()
	{
		if( $this->con_ )
			return true;		
		if($this->debug_)
			echo "entering function <b>connect()</b><br />";
		
		$this->con_ = ldap_connect( $this->host_, $this->port_ );
		if($this->debug_)
		{
			echo "result is <b>".$this->con_."</b><br />";
			echo "error ".ldap_errno($this->con_).": ".ldap_error($this->con_)."<br />";
		}		
		if(!$this->con_)
		{
			$string1= 'Could not connect to LDAP Server!';
			$string2= "for server: ".htmlspecialchars( $this->host_ );
			$string2.= " on port ".$this->port_."<br />";
			$this->reportWarning($string1, $string2);
		}elseif($this->debug_)
		{
			echo 'Successfully <b>connected LDAP Server:</b> "';
			echo htmlspecialchars( $this->host_ ).'"';
			echo " on port ".$this->port_."<br />";			
		} 
		return ( !$this->con_ ? false : true );
	}
	//--------------------------------------------------------------------	
	function disconnect(){
		$this->freeResult();
		$this->unbind();
		if( !$this->con_ ) return;
		ldap_close( $this->con_ );
		$this->con_ = false;
	}
	//--------------------------------------------------------------------
	function hidePassword( $passWD ){
		$res = '';
		for( $i = 0; $i < strlen( $passWD ); $i++ ) $res .= '*';
		return $res;
	}
	//--------------------------------------------------------------------
	function bindAnonymously(){
		if( $this->binding_ ) return true;
		if( !$this->connect() ) return false;
	
		//if( !$this->debug_ ) error_reporting( E_ALL & ~E_NOTICE & ~E_WARNING );
		$this->binding_ = @ldap_bind( $this->con_ );
		if( !$this->binding_ ) $this->reportWarning( 'Anonymous Binding to LDAP Server failed!', 'LDAP-Error (Nr '.ldap_errno( $this->con_ ).'){'.htmlspecialchars( ldap_error( $this->con_) ).'}' );
		/**/ else if( $this->debug_ ) echo '<br />Successful anonymous binding to LDAP Server!';
		
		return ( $this->binding_ ? true : false );
	}
	//--------------------------------------------------------------------
	/** 
	 * @param fullUserNameDN if this parameter is set it will overwrite $userName 
	 */
	function bind( $fullUserNameDN = '', $password = '',  $userNameForStandardUserDN = '' )
	{		
		if($this->debug_)
			echo "entering function <b>bind(</b>'$fullUserNameDN', '$password', '$userNameForStandardUserDN'<b>)</b><br />";
		if($userNameForStandardUserDN != '')
			$fullUserNameDN = 'AAA';
		if($this->binding_)
		{
			if($this->debug_)
				echo "connection is <b>binded</b> befor<br />";
			return true;
		}
		if(!$this->connect())
			return false;
			
		
		if( $fullUserNameDN != '' AND $password != '' )
		{
			$UserDN= $fullUserNameDN;
			if($userNameForStandardUserDN != '')
				$UserDN= $this->getUserDN( $userNameForStandardUserDN );
			if($this->debug_)
				echo "ldap_bind($this->con_, '$UserDN', '$password')<br />";
			$this->binding_ = @ldap_bind( $this->con_, $UserDN, $password );
			if(!$this->binding_)
			{
				$string1= 'Binding to LDAP Server failed!';
				$string2=  'for UserDN:{<em>'.htmlspecialchars($UserDN);
				$string2.= '</em>}Password:{shielded:<em>';
				$string2.= htmlspecialchars($this->hidePassword($password));
				$string2.= '</em>}<br />LDAP-Error (Nr '.ldap_errno( $this->con_ );
				$string2.= '){'.htmlspecialchars( ldap_error( $this->con_) ).'}';
				$this->reportWarning($string1, $string2);
			}elseif($this->debug_)
			{ 
				echo 'Successful binding to LDAP Server: for UserDN:{<em>';
				echo htmlspecialchars($UserDN).'</em>}Password:{<em>';
				echo htmlspecialchars($password).'</em>}<br />';
			}
		}elseif(!isset($this->loginUserName_) AND !isset($this->password_))
		{ 
			if($this->debug_)
				echo "binding anonymously<br />";
			$this->bindAnonymously();
		}else
		{
			if($this->debug_)
				echo "ldap_bind(".$this->con_.", '".$this->getUserDN()."', '".$this->password_."')<br />";
			$this->binding_ = @ldap_bind( $this->con_, $this->getUserDN(), $this->password_ );
			if( !$this->binding_ )
			{
				$string1= 'Binding to LDAP Server failed!';
				$string2=  'for UserDN:{<em>'.htmlspecialchars( $this->getUserDN( ) );
				$string2.= '</em>}Password:{shielded:<em>';
				$string2.= htmlspecialchars( $this->hidePassword($this->password_ ) );
				$string2.= '</em>}<br />LDAP-Error (Nr '.ldap_errno( $this->con_ );
				$string2.= '){'.htmlspecialchars( ldap_error( $this->con_) ).'}';
				$this->reportWarning($string1 , $string2);
			}elseif( $this->debug_ )
			{
				echo 'Successful binding to LDAP Server: for UserDN:{<em>';
				echo htmlspecialchars( $this->getUserDN( ) ).'</em>}Password:{shielded:<em>';
				echo htmlspecialchars( $this->hidePassword( $this->password_ ) ).'</em>}<br />';
			}
		}
		
		if( !$this->binding_ )
			return false;
		else
			return true;
	}
	//--------------------------------------------------------------------
	function unbind(){
		$this->freeResult();
		if( $this->binding_ ) $this->binding_ = false;
	}
	//--------------------------------------------------------------------
	function freeResult(){
		if(  $this->resultRessource_ ) ldap_free_result(  $this->resultRessource_ );
		unset( $this->resultEntryID_ ); 
		unset( $this->resultRessource_ );
		unset( $this->resultAttributes_ );
	}
	//--------------------------------------------------------------------
	function extractGroupNames( $groupDNList ){
		$result = array();
		if(Tag::isDebug("user"))
		{
			echo "<br /><br />all founded members:";
			st_print_r($groupDNList);
			if($groupDNList===0)
				echo "<br />";
		}
		foreach( $groupDNList AS $groupDN )
		{
			preg_match("/^CN=([^,]+).*/", $groupDN, $preg);
			//st_print_r($preg);
			$result[]= trim($preg[1]);
			
			/*$groupDN = trim( $groupDN );
			$foundPos = strpos( strtolower($groupDN), strtolower(',CN=Users,DC=ad,DC=local') );
			if( !$foundPos ) {
				if( Tag::isDebug("user") ) echo "<br />discard GroupDN(<em>".$groupDN."</em> because the reqired postfix of the groupname was not found";
				continue;
			}
			$group = trim( substr( $groupDN, 3, $foundPos - 3 ) );
			if( $group == '' ) {
				if( Tag::isDebug("user") ) echo "<br />discard GroupDN(<em>".$groupDN."</em> because groupname was empty";
				continue;
			}				
			if( Tag::isDebug("user") ) echo "<br />found GroupDN(<em>".$groupDN."</em> extracted:[".$group."]";
			array_push( $result, $group );*/
		}
		return $result;

	}
	//--------------------------------------------------------------------
	function getMembersOf($group, $baseDNPrefix = '', $baseDN = '')
	{
		$members= array();
		$alpha= array(	'a','b','c','d','e','f','g','h','i','j',
						'k','l','m','n','o','p','q','r','s','t',
						'u','v','w','x','y','z'						);
//		foreach($alpha as $letter)
//		{
			if($this->debug_)
				echo "<br /><b>&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;</b><br />";
			$this->search(	"(&(description=diginfo)(objectClass=user))",
							array('sAMAccountName', 'membersOf'), $baseDNPrefix, $baseDN);
			$groups= $this->f('memberOf');
			echo "1--------------------------------------------------<br />";
			print_r($groups);
			$groups= $this->extractGroupNames($groups);
			echo "2--------------------------------------------------<br />";
			print_r($groups);
			if(array_search($group, $groups))
			{
				$user= $this->f('sAMAccountName');
				$members[]= $user;
			}
//		}
		return $members;
	}
	function search( $filter, $retrieveAttributes = false, $baseDNPrefix = '', $baseDN = '' )
	{
		if($this->debug_)
		{
			echo "entering function <b>search(</b>'$filter', ";
			echo "'". var_dump($retrieveAttributes)."', ";
			echo "'$baseDNPrefix', '$baseDN'<b>)</b><br />";
		}
		$this->bind(); //if not bound/connected yet -> connect and bind now
		
		$rootDN = trim( $baseDNPrefix );
		if( ( $rootDN != '' ) AND ( $this->baseDN_ != '' ) )
			$rootDN .= ',';
		$rootDN .= $this->baseDN_;		
		if( trim( $baseDN ) != '' )
			$rootDN = trim( $baseDN );
		
		//$filter="(|(sn=$person*)(vorname=$person*))";
		//$justthese = array( "ou", "sn", "vorname", "mail");

		$this->freeResult();
		$myAttributes = '';
		if( $retrieveAttributes )
			foreach( $retrieveAttributes AS $aVal )
				$myAttributes .= ( $myAttributes == '' ? '':', ' ).$aVal;
		/**/ if( $this->debug_ )
		{ 
			echo "performin search for: <br />";
			echo "ldap_search( ".$this->con_.",<br />";
			echo "&nbsp;&nbsp;".htmlspecialchars( $rootDN );
			echo ",<br >&nbsp;&nbsp;".htmlspecialchars( $filter );
			if($retrieveAttributes)
				echo ",<br >&nbsp;&nbsp;".htmlspecialchars( $retrieveAttributes );
			echo " );<br />";
		}
		if( $retrieveAttributes )
		{
			$this->resultRessource_ = @ldap_search( $this->con_, $rootDN, $filter, $retrieveAttributes );
			$this->resultAttributes_ = $retrieveAttributes ;
		}else
		{
			$this->resultRessource_ = @ldap_search( $this->con_, $rootDN, $filter );
			$this->resultAttributes_ = array();
			while( $this->next_record() )
			{
				$attribs = ldap_get_attributes( $this->con_, $this->resultEntyID );
				$attributeNames = array();
				for( $i = 0; $i < $attribs["count"]; ++$i )
				{
					$attributeNames[] = $attribs[ $i ];
				}
				
				//$attributeNames = array_change_key_case( $attributeNames );
				$this->resultAttributes_ = array_merge( $this->resultAttributes_, array_flip( $attributeNames ) );
			}
			$this->resultAttributes_ = array_flip( $this->resultAttributes_ );
			if($this->debug_ ) 
			{ 
				echo "Found following ".count($this->resultAttributes_);
				echo " attributes in Result:<br />";
				foreach( $this->resultAttributes_ AS $value )
					echo htmlspecialchars( $value )."<br />";
			}
		}
		if( !$this->resultRessource_ )
		{
			$this->reportWarning(	'search to LDAP Server failed!', 
									'for filter:{<em>'.htmlspecialchars( $filter ).
										'</em>} in RootDN:{<em>'.htmlspecialchars($rootDN).
										'</em>} for Attributes:{<em>'.htmlspecialchars($myAttributes).
										'</em>}<br />LDAP-Error (Nr '.ldap_errno( $this->con_ ).
										'){'.htmlspecialchars( ldap_error( $this->con_) ).'}' );
		}/**/elseif( $this->debug_ )
		{
			echo '<br />Successful <b>search()</b> on LDAP Server: for filter:{<em>';
			echo htmlspecialchars( $filter ).'</em>} in RootDN:{<em>';
			echo htmlspecialchars($rootDN).'</em>} for Attributes:{<em>';
			echo htmlspecialchars($myAttributes).'</em>}';
		}
		if( $this->debug_ )
			echo "---&gt;found ".$this->rowCount()." Entities<br />";
	}
	//--------------------------------------------------------------------
	function rowCount(){ 
		if( !$this->resultRessource_ ) return 0;
		else return ldap_count_entries( $this->con_, $this->resultRessource_ );
	}
	//--------------------------------------------------------------------
	function next_record(){
		if( !$this->resultRessource_ ){
			$this->reportWarning( "next_record called upon uninitialized or unsuccessfull search" );
			return false;
		}
		if( !$this->resultEntyID )
			$this->resultEntyID = ldap_first_entry( $this->con_, $this->resultRessource_ );
		else	$this->resultEntyID = ldap_next_entry( $this->con_, $this->resultEntyID );
		return ( $this->resultEntyID  != false ? true : false );
	}
	//--------------------------------------------------------------------
	function first_record(){
		if( !$this->resultRessource_ ){
			$this->reportWarning( "first_record called upon uninitialized or unsuccessfull search" );
			return false;
		}
		if( !$this->resultEntyID )
			$this->resultEntyID = ldap_first_entry( $this->con_, $this->resultRessource_ );	
	}
	//--------------------------------------------------------------------
	function getAttributeValues( $attributeName ){
		if( !$this->resultEntyID )
		{
			if(Tag::isDebug("user"))
				echo "no resultEntryID for function getAttributeValues(".$attributeName.")<br />";
			return array();
		}
		$values = @ldap_get_values(  $this->con_, $this->resultEntyID, $attributeName );
		if( $values ) {
			unset( $values['count'] );
			return $values;
		} else	return array();
	}
	//--------------------------------------------------------------------
	/**short-cut for getAttributeValues **/
	function f( $attributeName ){ 
		return $this->getAttributeValues( $attributeName ); 
	}
	//--------------------------------------------------------------------
	function debugPrintResult( ){
		if( !isset( $this->resultRessource_ ) ) {
			echo "No successfull query executed yet!"; 
			return;
		}
		?>found <?=$this->rowCount()?> Entities:<br />
		<table border="0" cellpadding="0" cellspacing="1" style="background-color:black;" >

			<tr>
				<td>
					<table border="0" cellpadding="0" cellspacing="1" >
						
						<tr>
							<? foreach( $this->resultAttributes_ AS $myAttrib ){ ?>
								<td nowrap style="background-color:red;color:white;font-width=normal;font-family:Tahoma,Arial;font-size:10pt;">
									&nbsp;<?=htmlspecialchars( $myAttrib )?>&nbsp;
								</td>
							<? } ?>
						 </tr>
						<? for(    $entryID = ldap_first_entry( $this->con_, $this->resultRessource_ );
							   $entryID != false;
							   $entryID = ldap_next_entry( $this->con_, $entryID )
						){?>
							<tr>
								<? foreach( $this->resultAttributes_ AS $myAttrib ){ 
									$values = ldap_get_values(  $this->con_, $entryID, $myAttrib );
									if( isset( $values ) ){
										unset( $values['count'] );
									?>
										<td nowrap style="font-family:Courier,Arial;font-size:10pt;background-color:white;" valign="top">
											<? if( is_array( $values ) ) foreach( $values AS $aVal ) echo htmlspecialchars( $aVal )."<br />"; ?>
										</td>
									<? } 
								}
								?>
							 </tr>						

						<? } ?>
						 
					</table>
				</td>
			</tr>
					
		</table><?
	}
	//--------------------------------------------------------------------
	function report( $externalMsg, $internalMsg, $type = 'Error' ){
		?><br /><table border="0" cellpadding="0" cellspacing="1" style="background-color:black;" >
			<tr>
				<td>
					<table border="0" cellpadding="0" cellspacing="1" style="background-color:white;" >
						<tr><td style="background-color:red;color:white;font-width=bold;font-family:Tahoma,Arial;font-size:10pt;">
							&nbsp;<?=htmlspecialchars( $type )?>&nbsp;
						    </td>
						    <td >
						    	<?=$externalMsg?>
						    	<hr />
						    	<em>Details:</em><?=$internalMsg?>
						    </td>
						 </tr>
					</table>
				</td>
			</tr>
		</table><?			    	
	}
	//--------------------------------------------------------------------
	function reportWarning( $externalMsg, $internalMsg = '' ){
		if( $this->showWarnings_ OR $this->debug_ ) $this->report( $externalMsg, $internalMsg, 'Warning' );
	}
	//--------------------------------------------------------------------
	function reportError($externalMsg, $internalMsg = ''  ){
		$this->report( $externalMsg, $internalMsg, 'Error' );
	}
	//--------------------------------------------------------------------
	function reportFatalError($externalMsg, $internalMsg = '' ){
		$this->report( $externalMsg, $internalMsg, 'FatalError' );
		exit;
	}

}



?>
