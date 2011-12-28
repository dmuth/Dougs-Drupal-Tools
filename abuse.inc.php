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
	$ddt_abuse_ips = variable_get("ddt_abuse_ips", "");
	$ddt_abuse_chat = variable_get("ddt_abuse_chat", "");

	$retval["abuse"] = array(
		"#type" => "fieldset",
		"#title" => t("Anti-Abuse"),
		);

	$retval["abuse"]["users"] = array(
		"#type" => "textfield",
		"#title" => t("View User IPs"),
		"#description" => t("Enter 1 or more comma-delimited usernames or UIDs. (UIDs take precedence)"),
		"#default_value" => $ddt_abuse_users,
		);

	//
	// If this is set do a search.
	//
	if ($_SESSION["ddt"]["abuse"]["search"]) {

		if ($ddt_abuse_users) {
			$retval["abuse"]["user_ips"] = array(
				"#type" => "item",
				"#title" => "User IP Results",
				"#value" => ddt_abuse_get_user_ips($ddt_abuse_users),
				);
		}

	}

	$retval["abuse"]["ips"] = array(
		"#type" => "textfield",
		"#title" => t("View Users of IPs"),
		"#description" => t("Enter 1 or more comma-delimited IP addresses "
			. "to see what users came from them. "
			. "Using % wildcards is acceptable."
			. "This is an intensive query and WILL hammer the server.  I warned ya!"
			),
		"#default_value" => $ddt_abuse_ips,
		);

	//
	// If this is set do a search.
	//
	if ($_SESSION["ddt"]["abuse"]["search"]) {

		if ($ddt_abuse_ips) {
			$retval["abuse"]["ip_users"] = array(
				"#type" => "item",
				"#title" => "IP User Results",
				"#value" => ddt_abuse_get_ip_users($ddt_abuse_ips),
				);
		}

	}

	if (module_exists("chatroom")) {
		$oldest_message = ddt_abuse_get_chat_oldest_message();
		$retval["abuse"]["chat"] = array(
			"#type" => "textfield",
			"#title" => t("Search chat logs"),
			"#description" => t("Search messages in chatlogs for a string. "
				. "Useful for investigating reports of harassment, abuse, or spam. "
				. "This is also a very intensive query. "
				. "Oldest message is from: <b>$oldest_message</b>. "
				. "Please note that if you use this on chatrooms NOT "
				. "available to all users, you may be violating users' "
				. "privacy.  I warned ya! "
			),
			"#default_value" => $ddt_abuse_chat,
			);

		if ($_SESSION["ddt"]["abuse"]["search"]) {

			if ($ddt_abuse_chat) {
				$retval["abuse"]["chat_results"] = array(
					"#type" => "item",
					"#title" => "Chat Search Results",
					"#value" => ddt_abuse_get_chat_results($ddt_abuse_chat),
					);
			}

		}

	}

	$retval["abuse"]["submit"] = array(
		"#type" => "submit",
		"#value" => "Go!",
		);

	//
	// All done with our search, reset this session variable!
	//
	//$no_unset_debug = true; // Debugging
	if ($_SESSION["ddt"]["abuse"]["search"] && !$no_unset_debug) {
		unset($_SESSION["ddt"]["abuse"]["search"]);
	}

	return($retval);

} // End of ddt_abuse_form()


function ddt_abuse_form_validate($form, $form_state) {
}


/**
* Our form submission handler.
*/
function ddt_abuse_form_submit($form, $form_state) {

	$values = $form_state["values"];
	//ddt_debug($values); // Debugging

	$users = $values["users"];
	$ips = $values["ips"];
	$chat = $values["chat"];

	variable_set("ddt_abuse_users", $users);
	variable_set("ddt_abuse_ips", $ips);
	variable_set("ddt_abuse_chat", $chat);

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
	//ddt_debug("Search criteria: " . print_r($users, true)); // Debugging
	foreach ($users as $key => $value) {
		$users[$key] = ltrim(rtrim($value));
	}

	//
	// First, search by user ID.
	// If we can't find the UID, then assume it's a user name and put into 
	// the $users_left array.
	//
	$uids = array();
	$users_left = array();
	foreach ($users as $key => $value) {

		$user = user_load($value);
		if ($user) {
			$uids[$user->uid] = $user->uid;

		} else {
			$users_left[] = $value;

		}

	}

	foreach ($users_left as $key => $value) {

		$params = array("name" => $value);
		$user = user_load($params);

		if ($user) {
			$uids[$user->uid] = $user->uid;

		} else {
			$error = t("Unable to find username '%name'!", array("%name" => $value));
			drupal_set_message($error, "error");

		}

	}

	//ddt_debug("UIDs: " . print_r($uids, true)); // Debugging

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

		sort($value);
		$ip_string = $link . ": " . join($value, ", ");
		$user_list[] = $ip_string;
	}

	$retval = theme("item_list", $user_list);

	return($retval);

} // End of ddt_abuse_get_user_ips()


/**
* Search for users from 1 or more IP addresses.
*
* @param string $ddt_abuse_users A comma-delimited string of IPs.
*
* @return string HTML of Users from each IP.
*/
function ddt_abuse_get_ip_users($ddt_abuse_ips) {
	
	$retval = "";

	//
	// Get our array of IPs and trim the whitespace from each.
	//
	$ips = explode(",", $ddt_abuse_ips);
	//ddt_debug("Search criteria: " . print_r($ips, true)); // Debugging
	foreach ($ips as $key => $value) {
		$ips[$key] = ltrim(rtrim($value));
	}

	//
	// Now, loop through each IP and search the database
	//
	$ip_list = array();
	foreach ($ips as $key => $value) {

		$ip = $value;
		$query = "SELECT uid FROM {accesslog} "
			. "WHERE "
			. "hostname LIKE '%s' "
			. "GROUP BY uid "
			;
		$query_args = array($ip);
		$cursor = db_query($query, $query_args);

		while ($row = db_fetch_array($cursor)) {
			$uid = $row["uid"];
			if ($uid != 0) {

				if (empty($ip_list[$ip])) {
					$ip_list[$ip] = array();
				}

				$ip_list[$ip][]= $uid;

			}

		}

	}

	ksort($ip_list);

	//
	// Now create usernames and links for each IP
	//
	foreach ($ip_list as $key => $value) {

		$ip = $value;

		foreach ($ip as $key2 => $value2) {

			$uid = $value2;
			$user = user_load($uid);
			$name = $user->name;
			$url = l($name, "user/" . $uid);
			$ip_list[$key][$key2] = $url;

		}

	}

	//
	// Now collapse each list of users into an array
	//
	foreach ($ip_list as $key => $value) {
		$ip_list[$key] = $key . ": " . join($value, ", ");
	}

	//
	// Finally, turn it into HTML
	//
	$retval = theme("item_list", $ip_list);

	return($retval);

} // End of ddt_abuse_get_ip_users()


/**
* Get the datestamp of our oldest chatroom message.
*
* @return string A string of our oldest message date.
*/
function ddt_abuse_get_chat_oldest_message() {

	$retval = "(No Messages Found)";

	$query = "SELECT modified FROM {chatroom_msg} "
		. "ORDER BY cmid "
		. "LIMIT 1 "
		;
	$cursor = db_query($query);
	$row = db_fetch_array($cursor);

	if ($row && $row["modified"]) {
		$retval = format_date($row["modified"]);
	}

	return($retval);

} // End of ddt_abuse_get_chat_oldest_message()


/**
* Search through chat for our string and get our results.
*
* @param string $ddt_abuse_chat Our string to search for
*
* @return string HTML of the matching chat room messages.
*/
function ddt_abuse_get_chat_results($ddt_abuse_chat) {

	$retval = "";

	//
	// Take off whitespace, for sanity.
	//
	$ddt_abuse_chat = ltrim(rtrim($ddt_abuse_chat));

	$max = 50;

	$query = "SELECT uid, msg, modified "
		. "FROM {chatroom_msg} "
		. "WHERE "
		. "msg LIKE '%%%s%%' "
		. "ORDER BY cmid "
		. "LIMIT $max "
		;
	$query_args = array($ddt_abuse_chat);
	$cursor = db_query($query, $query_args);

	$results = array();
	while ($row = db_fetch_array($cursor)) {

		$uid = $row["uid"];
		$date = $row["modified"];
		$message = $row["msg"];

		$user = user_load($uid);
		$name = $user->name;
		$link = l ($name, "user/" . $uid);

		$date_string = format_date($date, "custom", "r");

		$result = $date_string . " " . $link . ": " . $message;
		$results[] = $result;

	}

	if ($results) {
		$retval = theme("item_list", $results);
	}

	if ($retval) {
		$message = t("Maximum number of chat search results is currently: %max",
			array("%max" => $max));
		drupal_set_message($message);
	}

	return($retval);

} // End of ddt_abuse_get_chat_results()


