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
function ciniki_events_sapos_itemLookup($ciniki, $tnid, $args) {

    if( !isset($args['object']) || $args['object'] == ''
        || !isset($args['object_id']) || $args['object_id'] == '' 
        || !isset($args['price_id']) || $args['price_id'] == '' 
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.56', 'msg'=>'No event specified.'));
    }

    //
    // An event was added to an invoice item, get the details and see if we need to 
    // create a registration for this event
    //
    if( $args['object'] == 'ciniki.events.event' ) {
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
                'fields'=>array('id'=>'event_id', 'price_id', 'price_name', 'description', 'reg_flags', 'num_tickets', 
                    'available_to', 'unit_amount', 'unit_discount_amount', 'unit_discount_percentage', 'unit_donation_amount', 
                    'taxtype_id', 'webflags',
                    )),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['events']) || count($rc['events']) < 1 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.44', 'msg'=>'No event found.'));      
        }
        $event = array_pop($rc['events']);
        if( isset($event['price_name']) && $event['price_name'] != '' ) {
            $event['description'] .= ' - ' . $event['price_name'];
        }
/*        $strsql = "SELECT id, name, reg_flags, num_tickets "
            . "FROM ciniki_events "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'event');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['event']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.57', 'msg'=>'Unable to find event'));
        } 
        $event = $rc['event']; */
        $item = array(
            'id'=>$event['id'],
            'name'=>$event['name'],
            'flags'=>0x08,          // Registration item
            );
        
        //
        // If registrations online enabled, check the available tickets
        //
        if( ($event['reg_flags']&0x02) > 0 ) {
            $event['tickets_sold'] = 0;
            $strsql = "SELECT 'num_tickets', SUM(num_tickets) AS num_tickets "
                . "FROM ciniki_event_registrations "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND ciniki_event_registrations.event_id = '" . ciniki_core_dbQuote($ciniki, $event['id']) . "' "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
            $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.events', 'num');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['num']['num_tickets']) ) {
                $event['tickets_sold'] = $rc['num']['num_tickets'];
                $event['tickets_available'] = $event['num_tickets'] - $event['tickets_sold'];
                $item['limited_units'] = 'yes';
                $item['units_available'] = $event['tickets_available'];
            }
           
            //
            // Check for individual tickets flag
            //
            if( ($event['webflags']&0x02) == 0x02 ) {
                $item['limited_units'] = 'yes';
                $item['units_available'] = 1;
                $item['flags'] |= 0x08;
            }
            if( ($event['webflags']&0x04) == 0x04 ) {
                $item['limited_units'] = 'yes';
                $item['units_available'] = 0;
            }

        }

        return array('stat'=>'ok', 'item'=>$item);
    }

    return array('stat'=>'ok');
}
?>
