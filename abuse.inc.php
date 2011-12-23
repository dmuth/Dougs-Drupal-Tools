<?php
/**
* This file is used to create and process our anti-abuse section.
*
* @author Douglas Muth <http://www.dmuth.org/>
*/


/**
* Our settings form.
*
* @return array An array of form data
*/
function ddt_abuse_form($form_state) {

	$retval = array();

	$ddt_abuse_users = variable_get("ddt_abuse_users", "");

	$retval["abuse"] = array(
		"#type" => "fieldset",
		"#title" => t("Anti-Abuse"),
		);

/*
		- Add features to view all IPs from a comma-delimited list of users
			- Can be usernames or UIDs
				- Strip whitespace
				- Convert all to usernames

*/
	$retval["abuse"]["users"] = array(
		"#type" => "textfield",
		"#title" => t("View User IPs"),
		"#description" => t("Enter 1 or more comma-delimited usernames or UIDs"),
		"#default_value" => $ddt_abuse_users,
		);

	//
	// If this is set do a search.
	//
	if ($_SESSION["ddt"]["abuse"]["search"]) {

		if ($ddt_abuse_users) {
			$retval["abuse"]["user_ips"] = array(
				"#type" => "item",
				"#title" => "User IPs",
				"#value" => ddt_abuse_get_user_ips($ddt_abuse_users),
				);
		}

	}

	//
	// All done with our search, reset this session variable!
	//
	if ($_SESSION["ddt"]["abuse"]["search"]) {
// TEST
		//unset($_SESSION["ddt"]["abuse"]["search"]);
	}

	$retval["abuse"]["submit"] = array(
		"#type" => "submit",
		"#value" => "Go!",
		);

	return($retval);

} // End of ddt_abuse_form()


function ddt_abuse_form_validate($form, $form_state) {
}


/**
* Our form submission handler.
*/
function ddt_abuse_form_submit($form, $form_state) {

	$values = $form_state["values"];

	$users = $values["users"];
	variable_set("ddt_abuse_users", $users);
	$_SESSION["ddt"]["abuse"]["search"] = true;

} // End of ddt_abuse_form_submit()


/**
* Search for the IP addresses of 1 or more users.
*
* @param string $ddt_abuse_users A comma-delimited string of usernames/UIDs.
*
* @return string HTML of IPs that each user has used.
*/
function ddt_abuse_get_user_ips($ddt_abuse_users) {
	
	$retval = "";

	//
	// Get our array of users/UIDs and trim the whitespace from each.
	//
	$users = explode(",", $ddt_abuse_users);
	foreach ($users as $key => $value) {
		$users[$key] = ltrim(rtrim($value));
	}

	//
	// Now convert usernames to UIDs
	//
	// This should scale up to a few 10s of thousands of users.
	//
	$uids = array();
	foreach ($users as $key => $value) {
		$query = "SELECT uid FROM {users} "
			. "WHERE "
			. "name = '%s' "
			;
		$query_args = array($value);
		$cursor = db_query($query, $query_args);
		$row = db_fetch_array($cursor);

		if ($row["uid"]) {
			$uids[] = $row["uid"];
		} else {
			$uids[] = $value;
		}

	}

	//
	// Get IPs for all UIDs
	//
	$ips = array();
	$query = "SELECT uid, hostname FROM {accesslog} "
		. "WHERE "
		. "uid IN (" . db_placeholders($uids, "int") . ") "
		. "AND uid != 0 "
		. "GROUP BY uid, hostname "
		;
	$query_args = $uids;
	$cursor = db_query($query, $query_args);

	while ($row = db_fetch_array($cursor)) {

		$uid = $row["uid"];
		$ip = $row["hostname"];

		if (empty($ips[$uid])) {
			$ips[$uid] = array();
		}

		$ips[$uid][] = $ip;

	}

	//
	// Now turn our IPs into HTML
	//
	$user_list = array();
	foreach ($ips as $key => $value) {

		$user = user_load($key);
		$username = $user->name;
		$url = "user/" . $key;
		$link = l($username, $url);

		$ip_string = $link . ": " . join($value, ", ");
		$user_list[] = $ip_string;
	}

	$retval = theme("item_list", $user_list);

	return($retval);

} // End of ddt_abuse_get_user_ips()



