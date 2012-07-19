<?php
//
// Description
// -----------
// This method will return the list of actions that were applied to an element of an event. 
// This method is typically used by the UI to display a list of changes that have occured 
// on an element through time. This information can be used to revert elements to a previous value.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to get the details for.
// event_id:			The ID of the event to get the history for.
// field:				The field to get the history for. This can be any of the elements 
//						returned by the ciniki.events.get method.
//
// Returns
// -------
// <history>
// <action user_id="2" date="May 12, 2012 10:54 PM" value="Event Name" age="2 months" user_display_name="Andrew" />
// ...
// </history>
//
function ciniki_events_getHistory($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'event_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No item specified'), 
		'field'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No field specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner, or sys admin
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/events/private/checkAccess.php');
	$rc = ciniki_events_checkAccess($ciniki, $args['business_id'], 'ciniki.events.getHistory');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	if( $args['field'] == 'start_date' ) {
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbGetModuleHistoryReformat.php');
		return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'events', 'ciniki_event_history', $args['business_id'], 'ciniki_events', $args['event_id'], $args['field'],'date');
	}
	if( $args['field'] == 'end_date' ) {
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbGetModuleHistoryReformat.php');
		return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'events', 'ciniki_event_history', $args['business_id'], 'ciniki_events', $args['event_id'], $args['field'], 'date');
	}

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbGetModuleHistory.php');
	return ciniki_core_dbGetModuleHistory($ciniki, 'events', 'ciniki_event_history', $args['business_id'], 'ciniki_events', $args['event_id'], $args['field']);
}
?>
