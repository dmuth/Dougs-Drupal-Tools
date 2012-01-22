<?php
/**
* This file contains our menu hook.
* 
* @author Douglas Muth <http://www.dmuth.org/>
*
*/


/**
* Our menu hook.
*
* @return array An array of menu items
*/
function ddt_menu() {

	$retval = array();

	$retval["admin/settings/ddt"] = array(
		"title" => "DDT",
		"page callback" => "ddt_main",
		"page arguments" => array(3, 4, 5),
		"access callback" => "user_access",
		"access arguments" => array("administer nodes"),
		"type" => MENU_NORMAL_ITEM,
		);

	$retval["admin/settings/ddt/main"] = array(
		"title" => "Settings",
		"page callback" => "ddt_main",
		"page arguments" => array(3, 4, 5),
		"access callback" => "user_access",
		"access arguments" => array("administer nodes"),
		"type" => MENU_DEFAULT_LOCAL_TASK,
		"weight" => 0,
		);

	$retval["admin/settings/ddt/abuse"] = array(
		"title" => "Anti-Abuse",
		"page callback" => "ddt_main",
		"page arguments" => array(3, 4, 5),
		"access callback" => "user_access",
		"access arguments" => array("administer nodes"),
		"type" => MENU_LOCAL_TASK,
		"weight" => 1,
		);

	$retval["admin/settings/ddt/about"] = array(
		"title" => "About",
		"page callback" => "ddt_main",
		"page arguments" => array(3, 4, 5),
		"access callback" => "user_access",
		"access arguments" => array("administer nodes"),
		"type" => MENU_LOCAL_TASK,
		"weight" => 2,
		);

	return($retval);

} // End of ddt_menu()


