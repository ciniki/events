<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_events__registrationDelete($ciniki, $business_id, $id, $uuid) {
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');

    //
    // Remove the registration from the invoice
    //

	//
	// FIXME: Get the list of answers for this registration
	//
/*	$strsql = "SELECT id, uuid "
		. "FROM ciniki_event_answers "
		. "WHERE business_id = '" . $business_id . "' "
		. "AND registration_id = '" . $id . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'registration');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
		return $rc;
	}
	if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
		$answers = $rc['rows'];
		foreach($answers as $qid => $answer) {
			$rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.events.answer', 
				$answer['id'], $answer['uuid'], 0x04);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	} */

	//
	// Delete the registration
	//
	return ciniki_core_objectDelete($ciniki, $business_id, 'ciniki.events.registration', $id, $uuid, 0x04);
}
?>
