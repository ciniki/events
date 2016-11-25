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
function ciniki_events_web_eventDetails($ciniki, $settings, $business_id, $permalink) {

    
//  print "<pre>" . print_r($ciniki, true) . "</pre>";
    //
    // Load INTL settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $business_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    $strsql = "SELECT ciniki_events.id, "
        . "ciniki_events.name, "
        . "ciniki_events.permalink, "
        . "ciniki_events.flags, "
        . "ciniki_events.url, "
        . "DATE_FORMAT(ciniki_events.start_date, '%a %b %e, %Y') AS start_date, "
        . "DATE_FORMAT(ciniki_events.end_date, '%a %b %e, %Y') AS end_date, "
        . "UNIX_TIMESTAMP(ciniki_events.start_date) AS start_date_ts, "
        . "DATE_FORMAT(ciniki_events.start_date, '%M') AS start_month, "
        . "DATE_FORMAT(ciniki_events.start_date, '%D') AS start_day, "
        . "DATE_FORMAT(ciniki_events.start_date, '%Y') AS start_year, "
        . "IF(ciniki_events.end_date = '0000-00-00', '', DATE_FORMAT(ciniki_events.end_date, '%M')) AS end_month, "
        . "IF(ciniki_events.end_date = '0000-00-00', '', DATE_FORMAT(ciniki_events.end_date, '%D')) AS end_day, "
        . "IF(ciniki_events.end_date = '0000-00-00', '', DATE_FORMAT(ciniki_events.end_date, '%Y')) AS end_year, "
        . "ciniki_events.times, "
        . "ciniki_events.reg_flags, "
        . "ciniki_events.num_tickets, "
        . "ciniki_events.description AS short_description, "
        . "ciniki_events.long_description, "
        . "ciniki_events.object, "
        . "ciniki_events.object_id, "
        . "ciniki_events.primary_image_id, "
        . "ciniki_event_images.image_id, "
        . "ciniki_event_images.name AS image_name, "
        . "ciniki_event_images.permalink AS image_permalink, "
        . "ciniki_event_images.description AS image_description, "
        . "ciniki_event_images.url AS image_url, "
        . "UNIX_TIMESTAMP(ciniki_event_images.last_updated) AS image_last_updated "
        . "FROM ciniki_events "
        . "LEFT JOIN ciniki_event_images ON ("
            . "ciniki_events.id = ciniki_event_images.event_id "
            . "AND ciniki_event_images.image_id > 0 "
            . "AND (ciniki_event_images.webflags&0x01) = 0 "
            . ") "
        . "WHERE ciniki_events.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_events.permalink = '" . ciniki_core_dbQuote($ciniki, $permalink) . "' "
        . "AND (ciniki_events.flags&0x01) = 0x01 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.artclub', array(
        array('container'=>'events', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'permalink', 'flags', 'image_id'=>'primary_image_id', 
            'start_date', 'start_date_ts', 'start_day', 'start_month', 'start_year', 
            'end_date', 'end_day', 'end_month', 'end_year', 'times',
            'reg_flags', 'num_tickets', 
            'url', 'short_description', 'description'=>'long_description', 'object', 'object_id')),
        array('container'=>'images', 'fname'=>'image_id', 
            'fields'=>array('image_id', 'title'=>'image_name', 'permalink'=>'image_permalink',
                'description'=>'image_description', 'url'=>'image_url',
                'last_updated'=>'image_last_updated')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['events']) || count($rc['events']) < 1 ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.events.61', 'msg'=>"I'm sorry, but we can't find the event you requested."));
    }
    $event = array_pop($rc['events']);

    //
    // If registrations online enabled, check the available tickets
    //
    $event['tickets_sold'] = 0;
    if( ($event['reg_flags']&0x02) > 0 ) {
        $strsql = "SELECT 'num_tickets', SUM(num_tickets) AS num_tickets "
            . "FROM ciniki_event_registrations "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_event_registrations.event_id = '" . ciniki_core_dbQuote($ciniki, $event['id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
        $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.events', 'num');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['num']['num_tickets']) ) {
            $event['tickets_sold'] = $rc['num']['num_tickets'];
        }
    }

    //
    // Check if any prices are attached to the event
    //
    if( isset($ciniki['session']['customer']['price_flags']) ) {
        $price_flags = $ciniki['session']['customer']['price_flags'];
        //
        // Check to make sure at least one class is before the membership expiration date, if member flag is set
        //
        if( isset($ciniki['session']['customer']['membership_expiration']) && ($price_flags&0x20) == 0x20 ) {
            //
            // Remove price flags if event starts after membership expiration
            //
            if( $event['start_date_ts'] > $ciniki['session']['customer']['membership_expiration'] ) {
                $price_flags = $price_flags &~ 0x20;
            }
        }
    } else {
        $price_flags = 0x01;
    }
    $strsql = "SELECT id, name, available_to, unit_amount "
        . "FROM ciniki_event_prices "
        . "WHERE ciniki_event_prices.event_id = '" . ciniki_core_dbQuote($ciniki, $event['id']) . "' "
        . "AND ciniki_event_prices.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND (ciniki_event_prices.webflags&0x01) = 0 "
        . "AND ((ciniki_event_prices.available_to&$price_flags) > 0 OR (webflags&available_to&0xF0) > 0) "
        . "ORDER BY ciniki_event_prices.name "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.events', array(
        array('container'=>'prices', 'fname'=>'id',
            'fields'=>array('price_id'=>'id', 'name', 'available_to', 'unit_amount')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['prices']) ) {
        $event['prices'] = $rc['prices'];
        foreach($event['prices'] as $pid => $price) {
            // Check if online registrations enabled
            if( ($event['reg_flags']&0x02) > 0 && ($price['available_to']&$price_flags) > 0 ) {
                $event['prices'][$pid]['cart'] = 'yes';
            } else {
                $event['prices'][$pid]['cart'] = 'no';
            }
            $event['prices'][$pid]['object'] = 'ciniki.events.event';
            $event['prices'][$pid]['object_id'] = $event['id'];
            if( $event['num_tickets'] > 0 ) {
                $event['prices'][$pid]['limited_units'] = 'yes';
                $event['prices'][$pid]['units_available'] = $event['num_tickets'] - $event['tickets_sold'];
            }
            $event['prices'][$pid]['unit_amount_display'] = numfmt_format_currency(
                $intl_currency_fmt, $price['unit_amount'], $intl_currency);
        }
    } else {
        $event['prices'] = array();
    }

    //
    // Get the links for the event
    //
    $strsql = "SELECT id, name, url, description "
        . "FROM ciniki_event_links "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_event_links.event_id = '" . ciniki_core_dbQuote($ciniki, $event['id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.events', array(
        array('container'=>'links', 'fname'=>'id', 'name'=>'link',
            'fields'=>array('id', 'name', 'url', 'description')),
    ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['links']) ) {
        $event['links'] = $rc['links'];
    } else {
        $event['links'] = array();
    }

    //
    // Check if any files are attached to the event
    //
    $strsql = "SELECT id, name, extension, permalink, description "
        . "FROM ciniki_event_files "
        . "WHERE ciniki_event_files.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_event_files.event_id = '" . ciniki_core_dbQuote($ciniki, $event['id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.events', array(
        array('container'=>'files', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'extension', 'permalink', 'description')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['files']) ) {
        $event['files'] = $rc['files'];
    }

    //
    // Get any sponsors for this event, and that references for sponsors is enabled
    //
    if( isset($ciniki['business']['modules']['ciniki.sponsors']) 
        && ($ciniki['business']['modules']['ciniki.sponsors']['flags']&0x02) == 0x02
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'web', 'sponsorRefList');
        $rc = ciniki_sponsors_web_sponsorRefList($ciniki, $settings, $business_id, 
            'ciniki.events.event', $event['id']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['sponsors']) ) {
            $event['sponsors'] = $rc['sponsors'];
        }
    }

    //
    // Get any additional images from the linked object
    //
    if( isset($event['object']) && $event['object'] != '' && isset($event['object_id']) && $event['object_id'] != '' ) {
        if( !isset($event['images']) ) {
            $event['images'] = array();
        }
        list($pkg, $mod, $obj) = explode('.', $event['object']);
        $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'web', 'eventImages');
        if( $rc['stat'] == 'ok' ) {
            $fn = $rc['function_call'];
            $rc = $fn($ciniki, $settings, $business_id, array('object'=>$event['object'], 'object_id'=>$event['object_id']));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['images']) ) {
                $event['images'] = array_merge($event['images'], $rc['images']);
            }
        }
    }

    return array('stat'=>'ok', 'event'=>$event);
}
?>
