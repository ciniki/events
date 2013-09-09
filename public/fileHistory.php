<?php
//
// Description
// -----------
// This function will return the history for an element in the file.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to get the history for.
// file_id:				The ID of the file to get the history for.
// field:				The field to get the history for.
//
// Returns
// -------
//	<history>
//		<action date="2011/02/03 00:03:00" value="Value field set to" user_id="1" />
//		...
//	</history>
//	<users>
//		<user id="1" name="users.display_name" />
//		...
//	</users>
//
function ciniki_events_fileHistory($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'file_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'File'), 
		'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Field'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner, or sys admin
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'private', 'checkAccess');
	$rc = ciniki_events_checkAccess($ciniki, $args['business_id'], 'ciniki.events.fileHistory', 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	if( $args['field'] == 'publish_date' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');
		return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'ciniki.events', 
			'ciniki_event_history', $args['business_id'], 
			'ciniki_event_files', $args['file_id'], $args['field'], 'date');
	}
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
	return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.events', 
		'ciniki_event_history', $args['business_id'], 
		'ciniki_event_files', $args['file_id'], $args['field']);
}
?>
