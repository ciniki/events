<?php
//
// Description
// -----------
// This method will delete a event from the business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business the event is attached to.
// event_id:			The ID of the event to be removed.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_events_delete($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'event_id'=>array('required'=>'yes', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No event specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/events/private/checkAccess.php');
	$ac = ciniki_events_checkAccess($ciniki, $args['business_id'], 'ciniki.events.delete');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	//
	// Start transaction
	//
	require($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionStart.php');
	require($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
	require($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
	require($ciniki['config']['core']['modules_dir'] . '/core/private/dbDelete.php');
	require($ciniki['config']['core']['modules_dir'] . '/core/private/dbAddChangeLog.php');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'events');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// remove the event
	//
	$strsql = "DELETE FROM ciniki_events "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' ";
	$rc = ciniki_core_dbDelete($ciniki, $strsql, 'events');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'events');
		return $rc;
	}

	if( $rc['num_affected_rows'] == 0 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'events');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'614', 'msg'=>'Unable to remove event'));
	}

	// FIXME: Add code to track deletions
	ciniki_core_dbAddChangeLog($ciniki, 'events', $args['business_id'], 'ciniki_events', $args['event_id'], 'delete', '');

	//
	// Commit the transaction
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'events');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');
}
?>
