<?php
//
// Description
// ===========
// This function will be a callback when an item is added to ciniki.sapos.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_events_sapos_itemAdd($ciniki, $business_id, $invoice_id, $item) {

	//
	// An event was added to an invoice item, get the details and see if we need to 
	// create a registration for this event
	//
	if( isset($item['object']) && $item['object'] == 'ciniki.events.event' && isset($item['object_id']) ) {
		//
		// Check the event exists
		//
		$strsql = "SELECT id, name "
			. "FROM ciniki_events "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'event');
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
		if( !isset($rc['event']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1511', 'msg'=>'Unable to find event'));
		}
		$event = $rc['event'];

		//
		// Load the customer for the invoice
		//
		$strsql = "SELECT id, customer_id "
			. "FROM ciniki_sapos_invoices "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
		if( !isset($rc['invoice']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1512', 'msg'=>'Unable to find invoice'));
		}
		$invoice = $rc['invoice'];
		
		//
		// Create the registration for the customer
		//
		$reg_args = array('event_id'=>$event['id'],
			'customer_id'=>$invoice['customer_id'],
			'num_tickets'=>(isset($item['quantity'])?$item['quantity']:1),
			'invoice_id'=>$invoice['id'],
			'customer_notes'=>'',
			'notes'=>'',
			);
		$rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.events.registration', $reg_args, 0x04);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$reg_id = $rc['id'];

		return array('stat'=>'ok', 'object'=>'ciniki.events.registration', 'object_id'=>$reg_id);
	}

	return array('stat'=>'ok');
}
?>
