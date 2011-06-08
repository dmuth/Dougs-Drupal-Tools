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

	ddt_cron_vars();

	$chat_delete = variable_get("ddt_chat_delete", false);
	if ($chat_delete) {
		ddt_cron_chat();
	}

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
	$cmid = $row["cmid"];
	$cmid = $cmid - 1000;
	$num_delete = $num_rows - 1000;
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
	}

} // End of ddt_cron_chat()


