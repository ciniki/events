<?php
//
// Description
// -----------
// This method will add a new event for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to add the event to.
// name:            The name of the event.
// url:             (optional) The URL for the event website.
// description:     (optional) The description for the event.
// start_date:      (optional) The date the event starts.  
// end_date:        (optional) The date the event ends, if it's longer than one day.
//
// Returns
// -------
// <rsp stat="ok" id="42">
//
function ciniki_events_eventAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'name'=>array('required'=>'yes', 'blank'=>'no', 'trim'=>'yes', 'name'=>'Name'), 
        'permalink'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Permalink'), 
        'flags'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Options'), 
        'url'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'URL'), 
        'description'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Description'), 
        'num_tickets'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Number of Tickets'),
        'reg_flags'=>array('required'=>'no', 'default'=>'0', 'blank'=>'no', 'name'=>'Registration Flags'),
        'start_date'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'date', 'name'=>'Start Date'), 
        'end_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'End Date'), 
        'times'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Times'), 
        'primary_image_id'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Image'), 
        'long_description'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Long Description'), 
        'oidref'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Link'),
        'object'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object'), 
        'object_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object ID'), 
        'ticketmap1_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Ticket Map'), 
        'ticketmap1_ptext'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Ticket Map Name'), 
        'ticketmap1_btext'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Ticket Map Button'), 
        'ticketmap1_ntext'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Ticket Map No Select'), 
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Categories'),
        'webcollections'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Web Collections'), 
        'dup_event_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Duplicate ID'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'private', 'checkAccess');
    $rc = ciniki_events_checkAccess($ciniki, $args['tnid'], 'ciniki.events.eventAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    
    if( !isset($args['permalink']) || $args['permalink'] == '' ) {  
        ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'private', 'makePermalink');
        $args['permalink'] = ciniki_events_makePermalink($ciniki, $args['tnid'], array(
            'name' => $args['name'],
            'start_date' => new DateTime($args['start_date'], new DateTimezone($intl_timezone)),
            ));
    }

    if( isset($args['oidref']) && $args['oidref'] != '' && preg_match("/(.*):(.*)/", $args['oidref'], $m) ) {
        $args['object'] = $m[1];
        $args['object_id'] = $m[2];
    }

    //
    // Check the permalink doesn't already exist
    //
    $strsql = "SELECT id FROM ciniki_events "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' " 
        . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'event');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( $rc['num_rows'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.6', 'msg'=>'You already have an event with this name, please choose another name'));
    }

    //
    // Check if prices should be copied
    //
    if( isset($args['dup_event_id']) && $args['dup_event_id'] > 0 ) {
        $strsql = "SELECT ticketmap1_image_id, "
            . "ticketmap1_ptext, "
            . "ticketmap1_btext, "
            . "ticketmap1_ntext "
            . "FROM ciniki_events "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['dup_event_id']) . "' "
            . "AND ciniki_events.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'dup_event');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.57', 'msg'=>'Unable to load dup_event', 'err'=>$rc['err']));
        }
        $dup_event = isset($rc['dup_event']) ? $rc['dup_event'] : array();
        $args['ticketmap1_image_id'] = $dup_event['ticketmap1_image_id'];
        $args['ticketmap1_ptext'] = $dup_event['ticketmap1_ptext'];
        $args['ticketmap1_btext'] = $dup_event['ticketmap1_btext'];
        $args['ticketmap1_ntext'] = $dup_event['ticketmap1_ntext'];

        //
        // Get the price list for the event
        //
        $strsql = "SELECT prices.id, "
            . "prices.name, "
            . "prices.available_to, "
            . "prices.valid_from, "
            . "prices.valid_to, "
            . "prices.unit_amount, "
            . "prices.unit_discount_amount, "
            . "prices.unit_discount_percentage, "
            . "prices.unit_donation_amount, "
            . "prices.taxtype_id, "
            . "(prices.webflags&0xfffb) as webflags, "  // Clear the Sold Out flag
            . "prices.num_tickets, "
            . "prices.position_num, "
            . "prices.position_x, "
            . "prices.position_y, "
            . "prices.diameter "
            . "FROM ciniki_event_prices AS prices "
            . "WHERE prices.event_id = '" . ciniki_core_dbQuote($ciniki, $args['dup_event_id']) . "' "
            . "GROUP BY prices.id "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'price');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.91', 'msg'=>'Unable to load price', 'err'=>$rc['err']));
        }
        $dup_prices = isset($rc['rows']) ? $rc['rows'] : array();
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.events');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Add the event to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.events.event', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
        return $rc;
    }
    $event_id = $rc['id'];

    //
    // Update the categories
    //
    if( isset($args['categories']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.events', 'tag', $args['tnid'],
            'ciniki_event_tags', 'ciniki_event_history',
            'event_id', $event_id, 10, $args['categories']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
            return $rc;
        }
    }

    //
    // If event was added ok, Check if any web collections to add
    //
    if( isset($args['webcollections'])
        && isset($ciniki['tenant']['modules']['ciniki.web']) 
        && ($ciniki['tenant']['modules']['ciniki.web']['flags']&0x08) == 0x08
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'hooks', 'webCollectionUpdate');
        $rc = ciniki_web_hooks_webCollectionUpdate($ciniki, $args['tnid'],
            array('object'=>'ciniki.events.event', 'object_id'=>$event_id, 
                'collection_ids'=>$args['webcollections']));
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
            return $rc;
        }
    }

    //
    // Add prices from duplicated event
    //
    if( isset($dup_prices) ) {
        foreach($dup_prices as $price) {
            $price['event_id'] = $event_id;
            $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.events.price', $price, 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
                return $rc;
            }
        }
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.events');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'events');

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.events.event', 'object_id'=>$event_id));

    return array('stat'=>'ok', 'id'=>$event_id);
}
?>
