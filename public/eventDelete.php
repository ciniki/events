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
function ciniki_events_eventDelete(&$ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'event_id'=>array('required'=>'yes', 'default'=>'', 'blank'=>'yes', 'name'=>'Event'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'private', 'checkAccess');
	$ac = ciniki_events_checkAccess($ciniki, $args['business_id'], 'ciniki.events.eventDelete');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	//
	// Get the uuid of the event to be deleted
	//
	$strsql = "SELECT uuid FROM ciniki_events "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'event');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['event']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'888', 'msg'=>'The event does not exist'));
	}
	$event_uuid = $rc['event']['uuid'];

	//
	// Start transaction
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.events');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Remove the images
	//
	$strsql = "SELECT id, uuid, image_id FROM ciniki_event_images "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'image');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
		return $rc;
	}
	if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
		$images = $rc['rows'];
		
		foreach($images as $iid => $image) {
			$rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.events.image', 
				$image['id'], $image['uuid'], 0x04);
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
				return $rc;	
			}
		}
	}

	//
	// Remove the files for the event
	//
	$strsql = "SELECT id, uuid "
		. "FROM ciniki_event_files "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'file');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
		return $rc;
	}
	if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
		$files = $rc['rows'];
		foreach($files as $fid => $file) {
			$rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.events.file', 
				$file['id'], $file['uuid'], 0x04);
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
				return $rc;	
			}
		}
	}

	//
	// Remove the registrations
	//
	$strsql = "SELECT id, uuid "
		. "FROM ciniki_event_registrations "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'registration');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
		return $rc;
	}
	if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'private', 'registrationDelete');
		$registrations = $rc['rows'];
		foreach($registrations as $rid => $registration) {
			$rc = ciniki_core__registrationDelete($ciniki, $args['business_id'], 
				$registration['id'],$registration['uuid']);
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
				return $rc;
			}
		}
	}

	//
	// Remove any registration questions for this event
	//
/*	$strsql = "SELECT id, uuid "
		. "FROM ciniki_event_registration_questions "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'question');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
		return $rc;
	}
	if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
		$questions = $rc['rows'];
		foreach($questions as $qid => $question) {
			$rc = ciniki_core_objectDelete($ciniki, 'ciniki.events.question', $question['id'], $question['uuid'],
				array('business_id'=>$args['business_id']), 0x04);
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
				return $rc;	
			}
		}
	}
*/

	//
	// Remove the event
	//
	$rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.events.event', 
		$args['event_id'], $event_uuid, 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
		return $rc;
	}

	//
	// Commit the transaction
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

//	$ciniki['syncqueue'][] = array('push'=>'ciniki.events.event',
//		'args'=>array('delete_uuid'=>$event_uuid, 'delete_id'=>$args['event_id']));

	return array('stat'=>'ok');
}
?>
