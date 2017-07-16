<?php
require_once($config["library_path"] ."/../plugins/crowd/crowd.php");

define('CROWD_REALM_ID', '255');

#function plugin_init_crowd() {
#  global $plugin_hooks;
#
#  // This is where you hook into the plugin archetecture
#  $plugin_hooks['login_realms']['crowd']            = 'crowd_login_realms';
#  $plugin_hooks['auth_alternate_realms']['crowd']            = 'crowd_auth_alternate_realms';
#  $plugin_hooks['login_process']['crowd']            = 'crowd_login_process';
#  $plugin_hooks['config_arrays']['crowd']            = 'crowd_config_arrays';
#  $plugin_hooks['config_settings']['crowd']            = 'crowd_config_settings';
#}

function plugin_crowd_install() {
  api_plugin_register_hook('crowd', 'login_process', 'crowd_login_process', "setup.php");
  api_plugin_register_hook('crowd', 'login_realms', 'crowd_login_realms', "setup.php");
  api_plugin_register_hook('crowd', 'auth_alternate_realms', 'crowd_auth_alternate_realms', "setup.php");
  api_plugin_register_hook('crowd', 'config_arrays', 'crowd_config_arrays', 'setup.php');
  api_plugin_register_hook('crowd', 'config_settings', 'crowd_config_settings', 'setup.php');
  api_plugin_register_hook('crowd', 'login_realms_exist', 'crowd_login_realms_exist', "setup.php");
}

function plugin_crowd_uninstall () {
  /* Do any extra Uninstall stuff here */
}


function plugin_crowd_version () {
  return crowd_version();
}

function crowd_auth_alternate_realms() {
     global $copy_user,$user_auth,$realm,$user,$auth_realms;

     /* check for remember me function ality */
     if (!isset($_SESSION['sess_user_id'])) {
             $cookie_user = check_auth_cookie();
             if ($cookie_user !== false) {
                     $_SESSION['sess_user_id'] = $cookie_user;
             }
     }
     $new_realm = array_search("Crowd", $auth_realms, TRUE);
     $realm = $new_realm;

     if(empty($_SESSION['sess_user_id'])) {
             // not already logged in
	     list($crowd,$crowd_error,$crowd_error_message) = crowd2Init();
	     list($crowd_isloggedin,$username,$crowd_error,$crowd_error_message) = crowd2IsLoggedIn($crowd);
	     if ( $crowd_error ) {
		$user_auth=false;
		$user=array();
		return;
	     }
	     if ($crowd_isloggedin) {
	             // logged in via SSO Token
	             $copy_user = true;
	             $user_auth = true;
	             /* Locate user in database */
	             $user = db_fetch_row_prepared("SELECT * FROM user_auth WHERE username = ? AND realm = ?" , array($username, $realm));


     
	
	             /* Create user from template if requested */
	             if ((!sizeof($user)) && ($copy_user) && (read_config_option('user_template') != '0') && (strlen($username) > 0)) {
	                     cacti_log("WARN: User '" . $username . "' does not exist, copying template user", false, 'AUTH');
	                     /* check that template user exists */
	                     if (db_fetch_row_prepared('SELECT id FROM user_auth WHERE username = ? AND realm = 0', array(read_config_option('user_template')))) {
	                             /* template user found */
	                             user_copy(read_config_option('user_template'), $username, 0, $realm);
	                             /* requery newly created user */
	                             $user = db_fetch_row_prepared('SELECT * FROM user_auth WHERE username = ? AND realm = ?', array($username, $realm));
	                     }else{
	                             /* error */
	                             cacti_log("LOGIN: Template user '" . read_config_option('user_template') . "' does not exist.", false, 'AUTH');
	                             auth_display_custom_error_message( __('Template user %s does not exist.', read_config_option('user_template')) );
	                             exit;
	                     }
	             }

                     cacti_log("LOGIN: User '" . $user['username'] . "' Authenticated", false, 'AUTH');
     
                     if (isset($_SERVER['HTTP_CLIENT_IP'])) {
                             $client_addr = $_SERVER['HTTP_CLIENT_IP'];
                     } elseif (isset($_SERVER['X-Forwarded-For'])) {
                             $client_addr = $_SERVER['X-Forwarded-For'];
                     } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                             $client_addr = $_SERVER['HTTP_X_FORWARDED_FOR'];
                     } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
                             $client_addr = $_SERVER['HTTP_FORWARDED_FOR'];
                     } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
                             $client_addr = $_SERVER['HTTP_FORWARDED'];
                     } elseif (isset($_SERVER['REMOTE_ADDR'])) {
                             $client_addr = $_SERVER['REMOTE_ADDR'];
                     } else {
                             $client_addr = '';
                     }
     
                     db_execute_prepared('INSERT IGNORE INTO user_log
                             (username, user_id, result, ip, time)
                             VALUES (?, ?, 1, ?, NOW())',
                             array($username, $user['id'], $client_addr));
     
		     $_SESSION['sess_user_id'] = $user["id"];
	     }
     }
     return;
}

function crowd_config_settings () {
        global $tabs, $settings, $config;

        /* check for an upgrade */
        plugin_crowd_check_config();

        if (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) != 'settings.php')
                return;


        $temp = array(
                "crowd_header" => array(
                        "friendly_name" => "Crowd Auth",
                        "method" => "spacer",
                ),
                "crowd_server" => array(
                        "friendly_name" => "Crowd Server",
                        "description" => "Full URL to Crowd Webapp inc. protocol (https://)",
                        "method" => "textbox",
                        "default" => "https://crowd.domain.com/crowd/",
                        "max_length" => 255,
                ),
                "crowd_user" => array(
                        "friendly_name" => "Crowd User",
                        "description" => "Crowd Application User",
                        "method" => "textbox",
                        "default" => "",
                        "max_length" => 255,
                ),
                "crowd_sso_domain" => array(
                        "friendly_name" => "Crowd SSO Domain",
                        "description" => "Crowd SSO Domain.  This should match your SSO domain in the Crowd server.",
                        "method" => "textbox",
                        "default" => ".domain.com",
                        "max_length" => 255,
                ),
                "crowd_pass" => array(
                        "friendly_name" => "Crowd Password",
                        "description" => "Crowd Application Password",
                        "method" => "textbox_password",
                        "default" => "",
                        "max_length" => 255,
                )
                );

	/* insert description into settings page */
	$crowd_desc = "<br><br><i>Crowd</i> - Authenticate using Atlassian Crowd server.";
	$tmp=$settings["authentication"]["auth_method"]["description"];
	$settings["authentication"]["auth_method"]["description"] = substr_replace($tmp,$crowd_desc,strpos($tmp,"</blockquote>"),0);
	//print substr_replace($tmp,$crowd_desc,strpos($tmp,"</blockquote>"),0);

        /* create a new Settings Tab, if not already in place */
        if (!isset($tabs["authentication"])) {
                $tabs["authentication"] = "Authentication";
        }

        /* and merge own settings into it */
        if (isset($settings["authentication"]))
                $settings["authentication"] = array_merge($settings["authentication"], $temp);
        else
                $settings["authentication"] = $temp;
}

function crowd_config_arrays () {
        global $auth_methods, $auth_realms, $user_auth_realms, $user_auth_realm_filenames, $menu;

        if (function_exists('api_plugin_register_realm')) {
                # register all php modules required for this plugin
                api_plugin_register_realm('crowd', 'crowd.php', 'Crowd Auth', 1);
        } else {
                # realms
                $user_auth_realms[37] = 'Crowd Auth';
                # these are the files protected by our realm id
                $user_auth_realm_filenames['crowd.php']     = 37;
                $user_auth_realm_filenames['crowd.php']     = 37;
        }
	$auth_methods[] = "Crowd";
	$auth_realms[] = "Crowd";
	//print_r($auth_realms);
}


function crowd_login_process() {
     global $copy_user,$user_auth,$realm,$user,$auth_realms;

     /* Crowd Auth */
     list($crowd,$crowd_error,$crowd_error_message) = crowd2Init();
     $new_realm = array_search("Crowd", $auth_realms, TRUE);
//	print_r($auth_realms);
     if ( $crowd_error ) {
	// unable to connect to crowd...no point in moving on
	auth_display_custom_error_message("$crowd_error_message");
	$user_auth=false;
	$user=array();
	return;
     }
     $username = get_nfilter_request_var('login_username');
     $password = get_nfilter_request_var('login_password');
     list($crowd_isloggedin,$crowd_error,$crowd_error_message) = crowd2Login($username,$password,$crowd);
     if ($crowd_isloggedin) {
             $copy_user = true;
             $user_auth = true;
             $realm = $new_realm;
             /* Locate user in database */
     	     $user = db_fetch_row_prepared("SELECT * FROM user_auth WHERE username = ? AND realm = ?" , array($username, $new_realm));
     } else {
        $user=array();
        $user_auth=false;
     }
     //     print_r($user);
     return true;
}
function crowd_login_realms($realms){
	// we should be default
	foreach ($realms as $k => &$v) {
		$v["selected"]=false;
	}
	$crowd_realm = array("name" => "Crowd", "selected" => true);
	$realms['crowd2']=$crowd_realm;
	return $realms;
}
function crowd_login_realms_exist(){
	return true;
}

function plugin_crowd_check_config () {
  crowd_check_upgrade();
  return true;
}

function plugin_crowd_upgrade () {
  crowd_check_upgrade();
  return false;
}

function crowd_check_upgrade () {
  global $config;

  $files = array('index.php', 'plugins.php', 'crowd.php');
  if (isset($_SERVER['PHP_SELF']) && !in_array(basename($_SERVER['PHP_SELF']), $files)) {
    return;
  }

  $current = plugin_crowd_version();
  $current = $current['version'];
  $old     = db_fetch_row("SELECT * FROM plugin_config WHERE directory='crowd'");
  if (sizeof($old) && $current != $old["version"]) {
    /* if the plugin is installed and/or active */
    if ($old["status"] == 1 || $old["status"] == 4) {
      /* re-register the hooks */
      plugin_crowd_install();

      /* perform a database upgrade */
      crowd_database_upgrade();
    }

    /* update the plugin information */
    $info = plugin_crowd_version();
    $id   = db_fetch_cell("SELECT id FROM plugin_config WHERE directory='crowd'");
    db_execute("UPDATE plugin_config
      SET name='" . $info["longname"] . "',
      author='"   . $info["author"]   . "',
      webpage='"  . $info["homepage"] . "',
      version='"  . $info["version"]  . "'
      WHERE id='$id'");
  }
}

function crowd_database_upgrade(){
}

function crowd_version () {
        return array('name' => 'Crowd Auth',
                        'version'       => '0.1',
                        'longname'      => 'Crowd Auth',
                        'author'        => 'Jeffrey Engleman',
                        'homepage'      => 'http://docs.cacti.net/plugin:crowd',
                        'email'         => 'dethmetaljeff@gmail.com',
                        'url'           => 'http://docs.cacti.net/plugin:crowd'
                        );
}


?>
