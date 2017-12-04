<?php
//
// Description
// ===========
// This method will return the list of prices for an event.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant the event is attached to.
// event_id:        The ID of the event to get the details for.
// 
// Returns
// -------
// <event id="419" name="Event Name" url="http://myevent.com" 
//      description="Event description" start_date="July 18, 2012" end_date="July 19, 2012"
//      date_added="2012-07-19 03:08:05" last_updated="2012-07-19 03:08:05" />
//
function ciniki_events_eventPriceList($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'event_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Event'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'private', 'checkAccess');
    $rc = ciniki_events_checkAccess($ciniki, $args['tnid'], 'ciniki.events.eventPriceList'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Load the tenant intl settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Load the status maps for the text description of each status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'private', 'maps');
    $rc = ciniki_events_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Get the price list for the event
    //
    $strsql = "SELECT ciniki_event_prices.id, "
        . "ciniki_event_prices.name, "
        . "ciniki_event_prices.available_to, "
        . "ciniki_event_prices.available_to AS available_to_text, "
        . "ciniki_event_prices.unit_amount, "
        . "ciniki_event_prices.unit_discount_amount, "
        . "ciniki_event_prices.unit_discount_percentage, "
        . "ciniki_event_prices.taxtype_id, "
        . "ciniki_events.name AS event_name "
        . "FROM ciniki_event_prices "
        . "LEFT JOIN ciniki_events ON (ciniki_event_prices.event_id = ciniki_events.id "
            . "AND ciniki_events.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE ciniki_event_prices.event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
        . "AND ciniki_event_prices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY ciniki_event_prices.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.events', array(
        array('container'=>'prices', 'fname'=>'id', 'name'=>'price',
            'fields'=>array('id', 'event_name', 'name', 'available_to', 'available_to_text',
                'unit_amount', 'unit_discount_amount', 'unit_discount_percentage', 'taxtype_id'),
            'flags'=>array('available_to_text'=>$maps['prices']['available_to'])),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['prices']) ) {
        $prices = $rc['prices'];
        foreach($prices as $pid => $price) {
            $prices[$pid]['price']['unit_amount_display'] = numfmt_format_currency(
                $intl_currency_fmt, $price['price']['unit_amount'], $intl_currency);
            $prices[$pid]['price']['unit_discount_amount_display'] = numfmt_format_currency(
                $intl_currency_fmt, $price['price']['unit_discount_amount'], $intl_currency);
        }
    } else {
        $prices = array();
    }

    return array('stat'=>'ok', 'prices'=>$prices);
}
?>
