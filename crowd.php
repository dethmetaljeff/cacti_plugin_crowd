<?
// http://pear.php.net/package/Services_Atlassian_Crowd
require_once('Services/Atlassian/Crowd.php');

function crowd2Init(){
	global $config;
	$crowd_app_name = read_config_option('crowd_user', TRUE);
	$crowd_app_password = read_config_option('crowd_pass');
	$crowd_url = rtrim(read_config_option('crowd_server'),"/").'/services/SecurityServer?wsdl';
	$crowd_error = "";
	$crowd_error_message = "";
	
	
	//print_r(array($crowd_app_name,$crowd_app_password,$crowd_url));
	$crowd = new Services_Atlassian_Crowd(array(
	'app_name' => $crowd_app_name,
	'app_credential' => $crowd_app_password,
	'service_url' => $crowd_url,
	));

	try{
		$crowd->authenticateApplication();
	}
	catch (Services_Atlassian_Crowd_Exception $e) {
		$crowd_error = TRUE;
		$crowd_error_message = $e->getMessage();
	}
	return array($crowd,$crowd_error,$crowd_error_message);
}

function crowd2IsLoggedIn($crowd) {
        $crowd_error = FALSE;
	$crowd_isloggedin = FALSE;
        $crowd_error_message = "";
	$crowd_username = "";

	if (!empty($_COOKIE['crowd_token_key']))
	{
	        // If the user already had a crowd token, we need to verify that it's still valid
		try
		{
	        	$crowd_isloggedin = $crowd->isValidPrincipalToken(
	        	        $_COOKIE['crowd_token_key'],
	        	        $_SERVER['HTTP_USER_AGENT'],
	        	        $_SERVER['REMOTE_ADDR']
	        	);
		}
		catch (Services_Atlassian_Crowd_Exception $e)
		{
			$crowd_error = TRUE;
			$crowd_error_message = $e->getMessage();
			$crowd_isloggedin = FALSE;
		}
	}
	
	if ($crowd_isloggedin) {
	        $principal = $crowd->findPrincipalByToken($_COOKIE['crowd_token_key']);
	        $crowd_username = $principal->name;
	}
	//print_r(array("IsLoggedIn",$crowd_isloggedin,$crowd_username,$crowd_error,$crowd_error_message));
	return array($crowd_isloggedin,$crowd_username,$crowd_error,$crowd_error_message);
}

function crowd2Login($username,$password,$crowd){
	$crowd_error = FALSE;
	$crowd_error_message = "";
	$crowd_isloggedin = FALSE;
	$crowd_username = "";
	$crowd_sso_domain = read_config_option('crowd_sso_domain');

	try
	{
		$_COOKIE['crowd.token_key'] = $crowd->authenticatePrincipal(
		$username,
		$password,
		$_SERVER['HTTP_USER_AGENT'],
		$_SERVER['REMOTE_ADDR']
		);
	
		setcookie('crowd.token_key', $_COOKIE['crowd.token_key'], time() + 3600, '/', $crowd_sso_domain);
	
		$crowd_isloggedin = TRUE;
	        $principal = $crowd->findPrincipalByToken($_COOKIE['crowd.token_key']);
	        $crowd_username = $principal->name;
		//print_r(array("TLogIn",$crowd_isloggedin,$crowd_username,$crowd_error,$crowd_error_message));
		return array($crowd_isloggedin,$crowd_error,$crowd_error_message);
	}
	catch (Services_Atlassian_Crowd_Exception $e)
	{
		$crowd_error = TRUE;
		$crowd_error_message = $e->getMessage();
		$crowd_isloggedin = FALSE;
		//print_r(array("FLogIn",$crowd_isloggedin,$crowd_username,$crowd_error,$crowd_error_message));
		return array($crowd_isloggedin,$crowd_error,$crowd_error_message);
	}
}
?>
