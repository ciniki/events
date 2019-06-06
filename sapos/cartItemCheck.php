<?php
//
// Description
// ===========
// This function will lookup an invoice item and make sure it is still available for purchase.
// This function is called for any items previous to paypal checkout.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_events_sapos_cartItemCheck($ciniki, $tnid, $customer, $args) {

    if( !isset($args['object']) || $args['object'] == '' 
        || !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.83', 'msg'=>'No event specified.'));
    }

    //
    // Lookup the requested event if specified along with a price_id
    //
    if( $args['object'] == 'ciniki.events.event' && isset($args['price_id']) && $args['price_id'] > 0 ) {
        $strsql = "SELECT ciniki_events.id AS event_id, "
            . "ciniki_events.name AS description, "
            . "ciniki_events.reg_flags, "
            . "ciniki_events.num_tickets, "
            . "ciniki_event_prices.id AS price_id, "
            . "ciniki_event_prices.name AS price_name, "
            . "ciniki_event_prices.available_to, "
            . "ciniki_event_prices.unit_amount, "
            . "ciniki_event_prices.unit_discount_amount, "
            . "ciniki_event_prices.unit_discount_percentage, "
            . "ciniki_event_prices.unit_donation_amount, "
            . "ciniki_event_prices.taxtype_id, "
            . "ciniki_event_prices.webflags "
            . "FROM ciniki_event_prices "
            . "LEFT JOIN ciniki_events ON ("
                . "ciniki_event_prices.event_id = ciniki_events.id "
                . "AND ciniki_events.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND ciniki_events.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
                . ") "
            . "WHERE ciniki_event_prices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_event_prices.id = '" . ciniki_core_dbQuote($ciniki, $args['price_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
            array('container'=>'events', 'fname'=>'event_id',
                'fields'=>array('event_id', 'price_id', 'price_name', 'description', 'reg_flags', 'num_tickets', 
                    'available_to', 'unit_amount', 'unit_discount_amount', 'unit_discount_percentage', 'unit_donation_amount', 
                    'taxtype_id', 'webflags',
                    )),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['events']) || count($rc['events']) < 1 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.84', 'msg'=>'No event found.'));      
        }
        $item = array_pop($rc['events']);

        //
        // Check the available_to is correct for the specified customer
        //
        if( ($item['available_to']|0xF0) > 0 ) {
            if( ($item['available_to']&$customer['price_flags']) == 0 ) {
                return array('stat'=>'unavailable', 'err'=>array('code'=>'ciniki.events.85', 'msg'=>"I'm sorry, but this product is no longer available."));
            }
        }

        //
        // Check if ticket is sold out
        //
        if( ($item['webflags']&0x04) == 0x04 ) {
            return array('stat'=>'unavailable', 'err'=>array('code'=>'ciniki.events.86', 'msg'=>"I'm sorry but this item has already been sold."));
        }

        return array('stat'=>'ok');
    }

    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.87', 'msg'=>'No event specified.'));
}
?>
