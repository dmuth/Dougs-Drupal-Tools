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
		"description" => t("DDT Settings"),
		"page callback" => "ddt_main",
		"page arguments" => array(3, 4, 5),
		"access callback" => "user_access",
		"access arguments" => array("administer nodes"),
		);

	return($retval);

} // End of ddt_menu()


