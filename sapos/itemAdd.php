<?php
//
// Description
// ===========
// This function will be a callback when an item is added to ciniki.sapos.invoice
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_events_sapos_itemAdd($ciniki, $tnid, $invoice_id, $item) {

    //
    // An event was added to an invoice item, get the details and see if we need to 
    // create a registration for this event
    //
    if( isset($item['object']) && $item['object'] == 'ciniki.events.event' && isset($item['object_id']) && isset($item['customer_id']) && $item['customer_id'] > 0 ) {
        //
        // Check the event exists
        //
        $strsql = "SELECT id, name "
            . "FROM ciniki_events "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'event');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['event']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.53', 'msg'=>'Unable to find event'));
        }
        $event = $rc['event'];

        //
        // Load the customer for the invoice
        //
        $strsql = "SELECT id, customer_id "
            . "FROM ciniki_sapos_invoices "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['invoice']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.54', 'msg'=>'Unable to find invoice'));
        }
        $invoice = $rc['invoice'];
        
        //
        // Create the registration for the customer
        //
        $reg_args = array(
            'event_id'=>$event['id'],
            'price_id'=>(isset($item['price_id']) ? $item['price_id'] : 0),
            'customer_id'=>$invoice['customer_id'],
            'num_tickets'=>(isset($item['quantity'])?$item['quantity']:1),
            'invoice_id'=>$invoice['id'],
            'customer_notes'=>'',
            'notes'=>'',
            );
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.events.registration', $reg_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $reg_id = $rc['id'];

        return array('stat'=>'ok', 'object'=>'ciniki.events.registration', 'object_id'=>$reg_id);
    }

    //
    // If a registration was added to an invoice, update the invoice_id for the registration
    //
    if( isset($item['object']) && $item['object'] == 'ciniki.events.registration' && isset($item['object_id']) ) {
        //
        // Check the registration exists
        //
        $strsql = "SELECT id, invoice_id "
            . "FROM ciniki_event_registrations "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'registration');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['registration']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.55', 'msg'=>'Unable to find event registration'));
        }
        $registration = $rc['registration'];
    
        //
        // If the registration does not already have an invoice
        //
        if( $registration['invoice_id'] == '0' ) {
            $reg_args = array('invoice_id'=>$invoice_id);
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.events.registration', 
                $registration['id'], $reg_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            return array('stat'=>'ok');
        }
    }

    return array('stat'=>'ok');
}
?>
