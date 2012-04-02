<?php
/**
* Our cron hooks.
*
* @author Douglas Muth <http://www.dmuth.org/>
*/


/**
* Our crontab, which is run periodically.
* Right now, it just compares variables, so we can see if any of our admins
*	have changed things since the last run.
*/
function ddt_cron() {

	ddt_log("Starting DDT cron entry.");

	ddt_cron_vars();

	if (module_exists("chatroom")) {

		$chat_delete = variable_get("ddt_chat_delete", false);
		if ($chat_delete) {
			ddt_cron_chat();
		}

	}

	//
	// Are we trimming large variables?
	//
	if (variable_get("variable_trim", "")) {
		ddt_cron_variable_trim();
	}


	ddt_log("Done with DDT cron entry!");

} // End of ddt_cron()


/**
* Code to check system variables while in cron.
*/
function ddt_cron_vars() {

	$old_vars = ddt_get_vars();
	$vars = variable_init();

	if (empty($old_vars)) {
		$message = t("No stored variables found.");
		ddt_log($message);

	} else {
		$fields = ddt_get_changed_vars($old_vars, $vars);
		ddt_log_changed_vars($fields);

	}

	//
	// Store our variables for the next pass.
	//
	ddt_put_vars($vars);

} // End of ddt_cron_vars()


/**
* Delete old chatroom messages.
*/
function ddt_cron_chat() {

	$ddt_num_messages = $GLOBALS["ddt_num_messages"];

	//
	// Get the number of rows
	//
	$query = "SELECT COUNT(cmid) AS cmid "
		. "FROM {chatroom_msg} "
		;
	$cursor = db_query($query);
	$row = db_fetch_array($cursor);
	$num_rows = $row["cmid"];

	//
	// Now figure out what rows we're deleting.
	//
	$query = "SELECT "
		. "max(cmid) AS cmid "
		. "FROM {chatroom_msg}"
		;
	$cursor = db_query($query);
	$row = db_fetch_array($cursor);

	//
	// Our max CMID
	//
	$cmid = $row["cmid"];

	//
	// Get our target CMID to delete before.
	//
	$cmid = $cmid - $ddt_num_messages;
	$num_delete = $num_rows - $ddt_num_messages;
	//$cmid = 1; // Debugging

	if ($cmid > 0) {
		$message = t("Deleting all chatroom messages with ID <= %id (%num messages)",
			array("%id" => $cmid, "%num" => $num_delete));
		ddt_log($message);
		$query = "DELETE FROM {chatroom_msg} "
			. "WHERE "
			. "cmid <= '%s' "
			;
		$query_args = array($cmid);
		db_query($query, $query_args);

	} else {
		$message = t("Not enough messages to delete. (Needed at least $ddt_num_messages!)");
		ddt_log($message);

	}

} // End of ddt_cron_chat()


/**
* For reasons known only to the author of the CAPTCHA module, the 
* captcha_placement_map_cache variable grows without bounds.  It 
* actually made it to 5 Megabytes on one of my sites.  
* See http://www.dmuth.org/node/1369 for more info.
* Anyway, chop that variable down if it's too big.
*/
function ddt_cron_variable_trim() {

	$key = "captcha_placement_map_cache";
	$max_len = 1024 * 10;
	//$max_len = -1; // Debugging

	$value = variable_get($key, "");
	$data = json_encode($value);
	$len = strlen($data);

	if ($len > $max_len) {
		$message = t("Variable %var% is greater than %max% bytes. Deleting!",
			array("%var%" => $key, "%max%" => $max_len));
		ddt_log($message);
		variable_del($key);

	}

} // End of ddt_cron_variable_trim()



