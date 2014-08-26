<?php
//
// Description
// ===========
// This method will be called whenever a item is updated in an invoice.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_events_sapos_itemUpdate($ciniki, $business_id, $invoice_id, $item) {

	//
	// An event was added to an invoice item, get the details and see if we need to 
	// create a registration for this event
	//
	if( isset($item['object']) && $item['object'] == 'ciniki.events.registration' && isset($item['object_id']) ) {
		//
		// Check the event registration exists
		//
		$strsql = "SELECT id, event_id, customer_id, num_tickets "
			. "FROM ciniki_event_registrations "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'registration');
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
		if( !isset($rc['registration']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1969', 'msg'=>'Unable to find event registration'));
		}
		$registration = $rc['registration'];

		//
		// If the quantity is different, update the registration
		//
		if( isset($item['quantity']) && $item['quantity'] != $registration['num_tickets'] ) {
			$reg_args = array('num_tickets'=>$item['quantity']);
			$rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.events.registration', 
				$registration['id'], $reg_args, 0x04);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}

		return array('stat'=>'ok');
	}

	return array('stat'=>'ok');
}
?>
