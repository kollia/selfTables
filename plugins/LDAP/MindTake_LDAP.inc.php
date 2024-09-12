<?php 

/************************************************************************
 * 
 * LDAP Server - Abstraction  / Object Wrapper
 * @author	benjamin.zotter@mindtake.com
 * @created 	2004-05-25
 *
 * example of usage:
 * <pre>
 *	class MY_LDAP_Server extends LDAPServer {
 *		var $host_ = 'sw02all001.klinikum.ad.local';
 *      var $loginUserName_ = 'ZotterR';
 *		var $loginBaseDN_ = 'OU=Users,OU=ADM,DC=klinikum,DC=ad,DC=local';
 * 		var $password_ = 'Bums.ti';
 * 		var $baseDN_ = 'DC=klinikum,DC=ad,DC=local';
 *		var $loginAttribName_ = 'cn';
 * 	}
 *
 *	$ldap = new MY_LDAP_Server();
 *	$ldap->showWarnings_ = true;
 *
 * 	$ldap->initialize_connection() OR die( "<br /><b>Unable to connect!</b>" );
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

	var $baseDN_ = 'DC=klinikum,DC=ad,DC=local';
	var $loginAttribName_ = 'cn';
	
	
	var $debug_ = false;
	var $showWarnings_ = false;
	/*private:*/
	var $con_ = false;
	var $binding_ = false;
	var $resultRessource_;
	var $resultEntryID_ ;
	var $resultAttributes_;
	
	protected $resultFirstEntryID= null;
	protected $resultEntryID= null;

	public function __construct(){
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
	function initialize_connection()
	{
		if( $this->con_ )
			return true;		
		if($this->debug_)
			$space= STCheck::echoDebug("user", "entering function <b>initialize_connection()</b> for {$this->host_}:{$this->port_}");
		
		//$this->con_ = ldap_connect( $this->host_, $this->port_ );
		$this->con_= ldap_connect( "{$this->protocol_}://{$this->host_}:{$this->port_}" );
		if($this->debug_)
		{
			STCheck::echoSpace($space);
		    echo "connection <b>result:</b> &#160;";
		    st_print_r($this->con_, 1, 1, false);
			//echo "result is <b>".$this->con_."</b><br />";
			echo " <b>error</b> ".ldap_errno($this->con_).": ".ldap_error($this->con_)."<br />";
		}		
		if(!$this->con_)
		{
			$string1= 'Could not initialize connection to LDAP Server!';
			$string2= "for server: ".htmlspecialchars( $this->host_ );
			$string2.= " (host isn't parsable)<br />";
			$this->reportWarning($string1, $string2);
		}elseif($this->debug_)
		{
			STCheck::echoSpace($space);
			$msg= "Successfully <b>initialize connection to LDAP Server:</b> host '{$this->host_}' was parsable (no connection was done) ";
			echo "$msg<br />";
		} 
		return ( $this->con_ == false ? false : true );// result should be false or an object of LDAP\Connection since PHP 8.1
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
		return getPlaceholdPassword($passWD);
	}
	//--------------------------------------------------------------------
	function bindAnonymously(){
		if( $this->binding_ ) return true;
		if( !$this->initialize_connection() ) return false;
	
		//if( !$this->debug_ ) error_reporting( E_ALL & ~E_NOTICE & ~E_WARNING );
		$this->binding_ = @ldap_bind( $this->con_ );
		if( !$this->binding_ ) $this->reportWarning( 'Anonymous Binding to LDAP Server failed!', 'LDAP-Error (Nr '.ldap_errno( $this->con_ ).'){'.htmlspecialchars( ldap_error( $this->con_) ).'}' );
		/**/ else if( $this->debug_ ) echo '<br />Successful anonymous binding to LDAP Server!';
		
		return ( $this->binding_ ? true : false );
	}
	//--------------------------------------------------------------------
	private function setLDAPoption(string $property, $option, $value) : bool
	{
		$predefined_values= false; // whether need to know predefined values for debugging		
	    if( $predefined_values &&
			$this->debug_ &&
	        $property != "ldap_start_tls"  )
	    {
	        $getValue= null;
			$con= null;
		    if(	$property != 'debug_level' &&
				substr($property, 0, 7) != "no_con_"	)
			{
				$con= $this->con_;
			}
	        if(!ldap_get_option($this->con_, $option, $getValue))
			{
				$message= null;
                echo "  -- <b>ERROR</b> cannot read ldap option '$property' ($option) maybe option not predefined, try to set to >> $value << <br />";
				if(ldap_get_option($this->con_, LDAP_OPT_ERROR_STRING, $message))
					echo "          $message<br />";
			}else
	            echo " -- set ldap option <b>'$property'</b> from >> $getValue << to >> $value << <br />";
	    }
	    $success= false;
	    if($property == "ldap_start_tls")
		{
			if($this->debug_)
			{
				ini_set('display_errors', 1);
				ini_set('display_startup_errors', 1);
				error_reporting(E_ALL);
	        	$success= ldap_start_tls($this->con_);
				echo "return code of ldap_start_tls was: ";
				var_dump($success);
				echo "<br />";
			}else
				$success= @ldap_start_tls($this->con_);
			
		}else
		{
			$con= null;
		    if(	$property != 'debug_level' &&
				substr($property, 0, 7) != "no_con_"	)
			{
				$con= $this->con_;
			}
	        $success= ldap_set_option($con, $option, $value);
		}
	    if(!$success)
	    {
	        $message= null;
	        $string1= "  -- <b>ERROR</b> cannot ";
	        if($property == "ldap_start_tls")
	            $string1.= "start <b>TLS</b>-connection<br />";
	        else
				$string1.= "set ldap option '$property' ($option)<br />";
			ldap_get_option($this->con_, LDAP_OPT_DIAGNOSTIC_MESSAGE, $message);
				$string1.=  "            m:$message<br />";
			$message= "";
	        ldap_get_option($this->con_, LDAP_OPT_ERROR_STRING, $message);
				$string1.=  "            m:$message<br />";
			$string2= ' LDAP-Error (Nr '.ldap_errno( $this->con_ );
			$string2.= '){'.htmlspecialchars( ldap_error( $this->con_) ).'}';
			$this->reportWarning($string1, $string2);
	        return false;
	    }elseif(	$this->debug_ &&
					!$predefined_values	&&
					$property != "ldap_start_tls"	)
		{
			echo " -- set ldap option <b>'$property'</b> to >> $value << <br />";
		}
	    return true;
	}
	/** 
	 * @param fullUserNameDN if this parameter is set it will overwrite $userName 
	 */
	function bind( $fullUserNameDN = '', $password = '',  $userNameForStandardUserDN = '' )
	{		
		if($this->debug_)
		{
			$msg= "entering function <b>bind(</b>";
			if($fullUserNameDN == "")
				$msg.= " with no user and or password ";
			else
				$msg.= "'$fullUserNameDN', '".$this->hidePassword($password)."', '$userNameForStandardUserDN'";
			$msg.= "<b>)</b>";
			STCheck::echoDebug("user", $msg);
		}
		if($userNameForStandardUserDN != '')
			$fullUserNameDN = 'AAA';
		if($this->binding_)
		{
			if($this->debug_)
				echo "connection was <b>binded</b> befor<br />";
			return true;
		}			
		
		if(	$this->debug_ &&
			isset($this->LDAP_options['debug_level'])	)
		{
			$this->setLDAPoption("debug_level", $this->LDAP_options['debug_level'][0], $this->LDAP_options['debug_level'][1]);	
		}			
		foreach($this->LDAP_options as $property => $value)
		{
			if(substr($property, 0, 7) == "no_con_")
				$this->setLDAPoption($property, $value[0], $value[1]);
		}
		if(!$this->initialize_connection())
		    return false;
		    
		foreach($this->LDAP_options as $property => $value)
		{
			if(	$property != "debug_level" &&
				substr($property, 0, 7) != "no_con_"	)
			{
		    	$this->setLDAPoption($property, $value[0], $value[1]);
			}
		}

		if( isset($this->LDAP_TLS_options) &&
		    $this->LDAP_TLS_options['require_tls'] == true    )
		{
		    foreach($this->LDAP_TLS_options as $property => $value)
		    {
		        if(	$property != 'require_tls' &&
					$property != 'ldap_start_tls'	)
		            $this->setLDAPoption($property, $value[0], $value[1]);
		    }
			if(	isset($this->LDAP_TLS_options['ldap_start_tls']) &&
				!$this->setLDAPoption('ldap_start_tls', NULL, NULL)	)
			{
		        return false;
			}
		}
		
		if( $fullUserNameDN != '' AND $password != '' )
		{
			$UserDN= $fullUserNameDN;
			if($userNameForStandardUserDN != '')
				$UserDN= $this->getUserDN( $userNameForStandardUserDN );
			showLine();
			if(1)//$this->debug_)
			{
			    echo "ldap_bind(";st_print_r($this->con_, 1, 1, false);
			    echo ", '$UserDN', '".$this->hidePassword($password)."')<br />";
				$this->binding_ = ldap_bind( $this->con_, $UserDN, $password );
			}else
				$this->binding_ = @ldap_bind( $this->con_, $UserDN, $password );
			if(!$this->binding_)
			{
				$string1= 'Binding to LDAP Server failed!';
				$string2=  'for UserDN:{<em>'.htmlspecialchars($UserDN);
				$string2.= '</em>}Password:{shielded:<em>';
				$string2.= $this->hidePassword($password);
				$string2.= '</em>}<br />LDAP-Error (Nr '.ldap_errno( $this->con_ );
				$string2.= '){'.htmlspecialchars( ldap_error( $this->con_) ).'}';
				$this->reportWarning($string1, $string2);
			}elseif($this->debug_)
			{ 
				echo 'Successful binding to LDAP Server: for UserDN:{<em>';
				echo htmlspecialchars($UserDN).'</em>}Password:{<em>';
				echo $this->hidePassword($password).'</em>}<br />';
			}
		}elseif(!isset($this->loginUserName_) AND !isset($this->password_))
		{
			if($this->debug_)
				echo "binding anonymously<br />";
			$this->bindAnonymously();
		}else
		{  
			if($this->debug_)
			{
				showLine();
				echo "ldap_bind(";
				st_print_r($this->con_, 1, 1, false);
				echo ", '".$this->getUserDN()."', '".$this->hidePassword($this->password_)."')<br />";
				$this->binding_ = ldap_bind( $this->con_, $this->getUserDN(), $this->password_ );
			}else
				$this->binding_ = @ldap_bind( $this->con_, $this->getUserDN(), $this->password_ );
			
			if( !$this->binding_ )
			{
				$string1= "Binding to LDAP Server ";
				if($fullUserNameDN == "")
					$string1.= "with standard user ";
				$string1.= "failed!";
				$string2=  'for UserDN:{<em>'.htmlspecialchars( $this->getUserDN( ) );
				$string2.= '</em>}Password:{shielded:<em>';
				$string2.= htmlspecialchars( $this->hidePassword($this->password_ ) );
				$string2.= '</em>}<br /><b>LDAP-Error</b> (Nr '.ldap_errno( $this->con_ );
				$string2.= ')<b> **{ '.htmlspecialchars( ldap_error( $this->con_) ).' }**</b>';
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
		if(  isset($this->resultRessource_) ) ldap_free_result(  $this->resultRessource_ );
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
			$foundPos = strpos( strtolower($groupDN), strtolower(',CN=Users,DC=klinikum,DC=ad,DC=local') );
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
			echo "<br />entering function <b>search(</b>'$filter', ";
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
		if( $retrieveAttributes == false &&
		    isset($this->retrieveAttributes_) &&
		    $this->retrieveAttributes_ != false     )
		{
		    $retrieveAttributes= $this->retrieveAttributes_;
		}
		if( $retrieveAttributes )
		{
			foreach( $retrieveAttributes AS $aVal )
				$myAttributes .= ( $myAttributes == '' ? '':', ' ).$aVal;
		}
		/**/ if( $this->debug_ )
		{
		    echo "performin search for: <br />";
		    echo "<pre>";
		    echo "ldap_search( [conntection]&nbsp".print_r($this->con_, true).",<br />";
		    echo "&nbsp;&nbsp;[rootDb]&nbsp'".htmlspecialchars( $rootDN );echo "',<br >";
		    echo "&nbsp;&nbsp;[filter]&nbsp'".htmlspecialchars( $filter );echo "',<br >";
		    echo "&nbsp;&nbsp;[retrieveAttributes]&nbsp'";
		    st_print_r( $retrieveAttributes, 50 );
		    echo "' );<pre><br />";
		}
		if( isset($retrieveAttributes) )
		{
			$this->resultRessource_ = @ldap_search( $this->con_, $rootDN, $filter, $retrieveAttributes );
			$this->resultAttributes_ = $retrieveAttributes ;
		}else
		{
			$this->resultRessource_ = @ldap_search( $this->con_, $rootDN, $filter );
			$this->resultAttributes_ = array();
			while( $this->next_record() )
			{
				$attribs = ldap_get_attributes( $this->con_, $this->resultEntryID );
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
		if($this->resultRessource_ == false)
		{
			$this->reportWarning(	'search to LDAP Server failed!', 
									'for filter:{<em>'.htmlspecialchars( $filter ).
										'</em>} in RootDN:{<em>'.htmlspecialchars($rootDN).
										'</em>} for Attributes:{<em>'.htmlspecialchars($myAttributes).
										'</em>}<br />LDAP-Error (Nr '.ldap_errno( $this->con_ ).
			    '){'.htmlspecialchars( ldap_error( $this->con_) ).'}' );
			showErrorTrace();
		}/**/elseif( $this->debug_ )
		{
			echo '<br />Successful <b>search()</b> on LDAP Server: for filter:{<em>';
			echo htmlspecialchars( $filter ).'</em>} in RootDN:{<em>';
			echo htmlspecialchars($rootDN).'</em>} for Attributes:{<em>';
			echo htmlspecialchars($myAttributes).'</em>}';
			echo "<pre>";
			st_print_r($this->resultRessource_);
			echo "</pre>";
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
		if( !isset($this->resultEntryID) )
			$this->resultEntryID = ldap_first_entry( $this->con_, $this->resultRessource_ );
		else	$this->resultEntryID = ldap_next_entry( $this->con_, $this->resultEntryID );
		return ( $this->resultEntryID  != false ? true : false );
	}
	//--------------------------------------------------------------------
	function first_record(){
		if( !$this->resultRessource_ ){
			$this->reportWarning( "first_record called upon uninitialized or unsuccessfull search" );
			return false;
		}
		if( !$this->resultEntryID )
			$this->resultEntryID = ldap_first_entry( $this->con_, $this->resultRessource_ );	
	}
	//--------------------------------------------------------------------
	function getAttributeValues( $attributeName ){
		if( !$this->resultEntryID )
		{
			if(Tag::isDebug("user"))
				echo "no resultEntrID for function getAttributeValues(".$attributeName.")<br />";
			return array();
		}
		$values = @ldap_get_values(  $this->con_, $this->resultEntryID, $attributeName );
		if( $values ) {
			unset( $values['count'] );
			return $values;
		} else	return array();
	}
	public function getAllUserData() : array
	{
		$aRv= array();
		foreach($this->retrieveAttributes_ as $attribute)
		{
			$res= $this->getAttributeValues($attribute);
			if(count($res) == 1)
				$aRv[$attribute]= array_pop($res);
			else
				$aRv[$attribute]= $res;
		}
		return $aRv;
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
		echo "found {$this->rowCount()} Entities:<br />"
		?>
		<table border="0" cellpadding="0" cellspacing="1" style="background-color:black;" >

			<tr>
				<td>
					<table border="0" cellpadding="0" cellspacing="1" >
						
						<tr>
							<?php  foreach( $this->resultAttributes_ AS $myAttrib ){ ?>
								<td nowrap style="background-color:red;color:white;font-width=normal;font-family:Tahoma,Arial;font-size:10pt;">
									&nbsp;<?php echo htmlspecialchars( $myAttrib )?>&nbsp;
								</td>
							<?php  } ?>
						 </tr>
						<?php  for(    $entryID = ldap_first_entry( $this->con_, $this->resultRessource_ );
							   $entryID != false;
							   $entryID = ldap_next_entry( $this->con_, $entryID )
						){?>
							<tr>
								<?php  foreach( $this->resultAttributes_ AS $myAttrib ){ 
									$values = ldap_get_values(  $this->con_, $entryID, $myAttrib );
									if( isset( $values ) ){
										unset( $values['count'] );
									?>
										<td nowrap style="font-family:Courier,Arial;font-size:10pt;background-color:white;" valign="top">
											<?php  if( is_array( $values ) ) foreach( $values AS $aVal ) echo htmlspecialchars( $aVal )."<br />"; ?>
										</td>
									<?php  } 
								}
								?>
							 </tr>						

						<?php  } ?>
						 
					</table>
				</td>
			</tr>
					
		</table><?php 
	}
	//--------------------------------------------------------------------
	function report( $externalMsg, $internalMsg, $type = 'Error' ){
		?><br /><table border="0" cellpadding="0" cellspacing="1" style="background-color:black;" >
			<tr>
				<td>
					<table border="0" cellpadding="0" cellspacing="1" style="background-color:white;" >
						<tr><td style="background-color:red;color:white;font-width=bold;font-family:Tahoma,Arial;font-size:10pt;">
							&nbsp;<?php echo htmlspecialchars( $type )?>&nbsp;
						    </td>
						    <td >
						    	<?php echo $externalMsg?>
						    	<hr />
						    	<em>Details:</em><?php echo $internalMsg?>
						    </td>
						 </tr>
					</table>
				</td>
			</tr>
		</table><?php 			    	
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
