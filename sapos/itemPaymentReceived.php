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
    // Check if price_id specified and if that price is individual ticket
    //
    if( isset($args['price_id']) && $args['price_id'] > 0 ) {
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
