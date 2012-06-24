<?php
//
// Description
// ===========
// This function will update an event in the database.
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_events_update($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
        'event_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No ID specified'), 
		'name'=>array('required'=>'no', 'blank'=>'no', 'errmsg'=>'No name specified'), 
		'url'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No url specified'), 
		'description'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No description specified'), 
		'start_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'errmsg'=>'No start date specified'), 
		'end_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'errmsg'=>'No end date specified'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/events/private/checkAccess.php');
    $rc = ciniki_events_checkAccess($ciniki, $args['business_id'], 'ciniki.events.update'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//  
	// Turn off autocommit
	//  
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionStart.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbAddModuleHistory.php');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'events');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Start building the update SQL
	//
	$strsql = "UPDATE ciniki_events SET last_updated = UTC_TIMESTAMP()";

	//
	// Add all the fields to the change log
	//
	$changelog_fields = array(
		'name',
		'url',
		'description',
		'start_date',
		'end_date',
		);
	foreach($changelog_fields as $field) {
		if( isset($args[$field]) ) {
			$strsql .= ", $field = '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "' ";
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'events', 'ciniki_event_history', $args['business_id'], 
				2, 'ciniki_events', $args['event_id'], $field, $args[$field]);
		}
	}
	$strsql .= "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' ";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'events');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'events');
		return $rc;
	}
	if( !isset($rc['num_affected_rows']) || $rc['num_affected_rows'] != 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'events');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'616', 'msg'=>'Unable to update event'));
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'events');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok', 'id'=>$event_id);
}
?>
