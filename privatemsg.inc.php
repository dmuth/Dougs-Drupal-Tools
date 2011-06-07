<?php
/**
* Functions to hook private message activity.
*
* @author Douglas Muth <http://www.dmuth.org/>
*/


/**
* Hook for inserting a new private message.
*
* @param array $message An associative array of message information.
*/
function ddt_privatemsg_message_insert($message) {
	$log_message = t("New private message created. MID: %mid");
	$var = array();
	$var["%mid"] = $message["mid"];
	ddt_log($log_message, $var);
}


/**
* Hook for viewing a private message.
*
* @param array $message An associative array of message information.
*/
function ddt_privatemsg_message_load($message) {

	$mid = $message["mid"];

	if (empty($GLOBALS["ddt"]["mid"])) {
		$GLOBALS["ddt"]["mid"] = array();
		$GLOBALS["ddt"]["mid"]["high"] = "";
		$GLOBALS["ddt"]["mid"]["low"] = "";
	}

	$mids = &$GLOBALS["ddt"]["mid"];

	//
	// Determine our low and high MIDs.  These will later be logged
	// in the exit hook.
	//
	if (!$mids["high"]
		|| $mid > $mids["high"]) {
		$mids["high"] = $mid;
	}
	
	if (!$mids["low"]
		|| $mid < $mids["low"]) {
		$mids["low"] = $mid;
	}
	
} // End of ddt_privatemsg_message_load()


