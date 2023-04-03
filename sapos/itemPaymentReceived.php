<?php
//
// Description
// ===========
// This function executes when a payment is received for an invoice or POS.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_events_sapos_itemPaymentReceived($ciniki, $tnid, $args) {

    if( !isset($args['object']) || $args['object'] == '' 
        || !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.75', 'msg'=>'No event specified.'));
    }

    if( !isset($args['price_id']) || $args['price_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.76', 'msg'=>'No event specified.'));
    }
    if( !isset($args['invoice_id']) || $args['invoice_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.77', 'msg'=>'No event specified.'));
    }

    //
    // If payment received on a cart item, then convert to a registration
    //
    if( $args['object'] == 'ciniki.events.event' && isset($args['price_id']) ) {
        //
        // Get the event details
        //
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
            . "ciniki_event_prices.taxtype_id, "
            . "ciniki_event_prices.webflags, "
            . "ciniki_event_prices.num_tickets "
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
                    'available_to', 'unit_amount', 'unit_discount_amount', 'unit_discount_percentage', 'taxtype_id', 'webflags', 'num_tickets',
                    )),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['events']) || count($rc['events']) < 1 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.50', 'msg'=>'No event found.'));      
        }
        $event = array_pop($rc['events']);
        
        //
        // Create the registration for the customer
        //
        $reg_args = array(
            'event_id' => $event['event_id'],
            'price_id' => $event['price_id'],
            'customer_id' => $args['customer_id'],
            'num_tickets' => (isset($args['quantity'])?$args['quantity']:1),
            'invoice_id' => $args['invoice_id'],
            'customer_notes' => '',
            'notes' => '',
            );
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.events.registration', $reg_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $reg_id = $rc['id'];

        //
        // Check if price is individual ticket or mapped ticket and not marked as sold out
        //
        if( ($event['webflags']&0x06) == 0x02 || ($event['webflags']&0x0C) == 0x08 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.events.price', $event['price_id'], array(
                'webflags' => ($event['webflags']|0x04),
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
        
        return array('stat'=>'ok', 'object'=>'ciniki.events.registration', 'object_id'=>$reg_id);
    }
    //
    // Check if price_id specified and if that price is individual ticket
    //
    elseif( isset($args['price_id']) && $args['price_id'] > 0 ) {
        $strsql = "SELECT prices.id, "
            . "prices.name, "
            . "prices.available_to, "
            . "prices.unit_amount, "
            . "prices.unit_discount_amount, "
            . "prices.unit_discount_percentage, "
            . "prices.taxtype_id, "
            . "prices.webflags "
            . "FROM ciniki_event_prices AS prices "
            . "WHERE prices.id = '" . ciniki_core_dbQuote($ciniki, $args['price_id']) . "' "
            . "AND prices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'price');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.79', 'msg'=>'Unable to load price', 'err'=>$rc['err']));
        }
        if( isset($rc['price']) ) {
            $price = $rc['price'];
            //
            // Check if price is individual ticket or mapped ticket and not marked as sold out
            //
            if( ($price['webflags']&0x06) == 0x02 || ($price['webflags']&0x0C) == 0x08 ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.events.price', $price['id'], array(
                    'webflags'=>$price['webflags']|0x04,
                    ), 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }
        }
    }

    return array('stat'=>'ok');
}
?>
