<?php
//
// Description
// ===========
// This method will remore a file from an event.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id: 		The ID of the business to remove the item from.
// file_id:				The ID of the file to remove.
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_events_fileDelete(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'file_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'File'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'private', 'checkAccess');
    $rc = ciniki_events_checkAccess($ciniki, $args['business_id'], 'ciniki.events.fileDelete'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//  
	// Turn off autocommit
	// 
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.events');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Get the uuid of the events item to be deleted
	//
	$strsql = "SELECT uuid FROM ciniki_event_files "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['file_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'file');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['file']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1328', 'msg'=>'Unable to find existing item'));
	}
	$uuid = $rc['file']['uuid'];

	//
	// Start building the delete SQL
	//
	$strsql = "DELETE FROM ciniki_event_files "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['file_id']) . "' "
		. "";

	$rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.events');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
		return $rc;
	}
	if( !isset($rc['num_affected_rows']) || $rc['num_affected_rows'] != 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1329', 'msg'=>'Unable to delete art'));
	}

	$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.events', 'ciniki_event_history', 
		$args['business_id'], 3, 'ciniki_event_files', $args['file_id'], '*', '');

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.events');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'events');

	$ciniki['syncqueue'][] = array('push'=>'ciniki.events.file', 
		'args'=>array('delete_uuid'=>$uuid, 'delete_id'=>$args['file_id']));

	return array('stat'=>'ok');
}
?>
