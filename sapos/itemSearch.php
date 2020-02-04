<?php
//
// Description
// ===========
// This function will search the events for the ciniki.sapos module.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_events_sapos_itemSearch($ciniki, $tnid, $args) {

    if( $args['start_needle'] == '' ) {
        return array('stat'=>'ok', 'items'=>array());
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // FIXME: Query for the taxes for events
    //
//  ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
//  $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_event_settings', 'tnid', $tnid,
//      'ciniki.artcatalog', 'taxes', 'taxes');
//  if( $rc['stat'] != 'ok' ) {
//      return $rc;
//  }
//  if( isset($rc['taxes']) ) {
//      $tax_settings = $rc['taxes'];
//  } else {
//      $tax_settings = array();
//  }

    //
    // Set the default taxtype for the item
    //
    $taxtype_id = 0;
//  if( isset($tax_settings['taxes-default-taxtype']) ) {
//      $taxtype_id = $tax_settings['taxes-default-taxtype'];
//  }

    $args['start_needle'] = preg_replace("/ +/", '%', $args['start_needle']);

    //
    // Prepare the query
    //
    $strsql = "SELECT ciniki_events.id, "
        . "ciniki_events.name, "
        . "DATE_FORMAT(ciniki_events.start_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS start_date, "
        . "ciniki_event_prices.id AS price_id, "
        . "ciniki_event_prices.name AS price_name, "
        . "CONCAT_WS(' ', ciniki_events.name, ciniki_event_prices.name) AS search_name, "
        . "ciniki_event_prices.unit_amount, "
        . "ciniki_event_prices.unit_discount_amount, "
        . "ciniki_event_prices.unit_discount_percentage, "
        . "ciniki_event_prices.unit_donation_amount, "
        . "ciniki_event_prices.taxtype_id "
        . "FROM ciniki_events "
        . "LEFT JOIN ciniki_event_prices ON ("
            . "ciniki_events.id = ciniki_event_prices.event_id "
            . "AND ciniki_event_prices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (ciniki_event_prices.webflags&0x04) = 0 "
            . ") "
        . "WHERE ciniki_events.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (ciniki_events.reg_flags&0x03) > 0 "
        . "AND (ciniki_events.end_date >= DATE(NOW()) OR ciniki_events.start_date >= DATE(NOW())) "
        . "HAVING (search_name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR search_name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . ") "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.events', array(
        array('container'=>'events', 'fname'=>'id',
            'fields'=>array('id', 'name', 'start_date')),
        array('container'=>'prices', 'fname'=>'price_id',
            'fields'=>array('id'=>'price_id', 'name'=>'price_name', 'unit_amount'=>'unit_amount', 
                'unit_discount_amount', 'unit_discount_percentage', 'unit_donation_amount',
                'taxtype_id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['events']) ) {
        $events = $rc['events'];
    } else {
        return array('stat'=>'ok', 'items'=>array());
    }

    $items = array();
    foreach($events as $eid => $event) {
        if( isset($event['prices']) && count($event['prices']) > 1 ) {
            foreach($event['prices'] as $pid => $price) {
                $details = array(
                    'status'=>0,
                    'object'=>'ciniki.events.event',
                    'object_id'=>$event['id'],
                    'description'=>$event['name'],
                    'quantity'=>1,
                    'unit_amount'=>$price['unit_amount'],
                    'unit_discount_amount'=>$price['unit_discount_amount'],
                    'unit_discount_percentage'=>$price['unit_discount_percentage'],
                    'taxtype_id'=>$price['taxtype_id'], 
                    'price_id'=>$price['id'],
                    'notes'=>'',
                    );
                if( $price['unit_donation_amount'] > 0 ) {
                    $details['unit_donation_amount'] = $price['unit_donation_amount'];
                }
                if( $price['name'] != '' ) {
                    $details['description'] .= ' - ' . $price['name'];
                }
                $items[] = array('item'=>$details);
            }
        } else {
            $details = array(
                'status'=>0,
                'object'=>'ciniki.events.event',
                'object_id'=>$event['id'],
                'description'=>$event['name'],
                'quantity'=>1,
                'unit_amount'=>0,
                'unit_discount_amount'=>0,
                'unit_discount_percentage'=>0,
                'taxtype_id'=>0, 
                'price_id'=>0,
                'notes'=>'',
                );
            if( isset($event['prices']) && count($event['prices']) == 1 ) {
                $price = array_pop($event['prices']);
                $details['price_id'] = $price['id'];
                if( isset($price['name']) && $price['name'] != '' ) {
                    $details['description'] .= ' - ' . $price['name'];
                }
                if( isset($price['unit_amount']) && $price['unit_amount'] != '' ) {
                    $details['unit_amount'] = $price['unit_amount'];
                }
                if( isset($price['unit_discount_amount']) && $price['unit_discount_amount'] != '' ) {
                    $details['unit_discount_amount'] = $price['unit_discount_amount'];
                }
                if( isset($price['unit_discount_percentage']) && $price['unit_discount_percentage'] != '' ) {
                    $details['unit_discount_percentage'] = $price['unit_discount_percentage'];
                }
                if( isset($price['unit_donation_amount']) && $price['unit_donation_amount'] > 0 ) {
                    $details['unit_donation_amount'] = $price['unit_donation_amount'];
                }
                if( isset($price['taxtype_id']) && $price['taxtype_id'] != '' ) {
                    $details['taxtype_id'] = $price['taxtype_id'];
                }
            }
            $items[] = array('item'=>$details);
        }
    }

    return array('stat'=>'ok', 'items'=>$items);        
}
?>
