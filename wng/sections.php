<?php
//
// Description
// -----------
// Return the list of sections available from the events module
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_events_wng_sections(&$ciniki, $tnid, $args) {

    //
    // Check to make sure blog module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.events']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.99', 'msg'=>'Module not enabled'));
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Get the list of events
    //
    $dt = new DateTime('now', new DateTimeZone($intl_timezone));
    $strsql = "SELECT events.id, "
        . "events.name "
        . "FROM ciniki_events AS events "
        . "WHERE ("
            . "events.start_date >= '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "' "
            . "OR events.end_date >= '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "' "
            . ") "
        . "AND events.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.events', array(
        array('container'=>'upcoming', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.117', 'msg'=>'Unable to load upcoming', 'err'=>$rc['err']));
    }
    $upcoming = isset($rc['upcoming']) ? $rc['upcoming'] : array();

    //
    // The latest blog section
    //
    $sections['ciniki.events.upcoming'] = array(
        'name' => 'Upcoming',
        'module' => 'Events',
        'settings' => array(
            'title' => array('label'=>'Title', 'type'=>'text'),
            'include-current' => array('label'=>'Include Current Events', 'type'=>'toggle', 'default'=>'yes', 'toggles'=>array(
                'no' => 'No',
                'yes' => 'Yes',
                )),
//            'thumbnail-format' => array('label'=>'Thumbnail Format', 'type'=>'toggle', 'default'=>'square-cropped', 
//                'toggles'=>array(
//                    'square-cropped' => 'Cropped',
//                    'square-padded' => 'Padded',
//                )),
//            'thumbnail-padding-color' => array('label'=>'Padding Color', 'type'=>'colour'),
//            'show-date' => array('label'=>'Show Date', 'type'=>'toggle', 'default'=>'yes', 'toggles'=>array(
//                'no' => 'No',
//                'yes' => 'Yes',
//                )),
            'image-position'=>array('label'=>'Image Position', 'type'=>'select', 'default'=>'top-right', 'options'=>array(
                'top-left' => 'Top Left',
                'top-left-inline' => 'Top Left Inline',
                'bottom-left' => 'Bottom Left',
                'top-right' => 'Top Right',
                'top-right-inline' => 'Top Right Inline',
                'bottom-right' => 'Bottom Right',
                )),
            'image-size'=>array('label'=>'Image Size', 'type'=>'toggle', 'default'=>'half', 'toggles'=>array(
                'half' => 'Full',
                'large' => 'Large',
                'medium' => 'Medium',
                'small' => 'Small',
                'tiny' => 'Tiny',
                )),
            'button-text' => array('label'=>'Link Text', 'type'=>'text'),
            'button-class' => array('label'=>'Link Type', 'type'=>'toggle', 'default'=>'button', 
                'toggles'=>array(
                    'button' => 'Button',
                    'link' => 'Link',
                )),
//            'limit' => array('label'=>'Number of Items', 'type'=>'text', 'size'=>'small'),
            ),
        );
        
    $sections['ciniki.events.past'] = array(
        'name' => 'Past',
        'module' => 'Events',
        'settings' => array(
            'title' => array('label'=>'Title', 'type'=>'text'),
            'include-current' => array('label'=>'Include Current Events', 'type'=>'toggle', 'default'=>'no', 'toggles'=>array(
                'no' => 'No',
                'yes' => 'Yes',
                )),
            'image-position'=>array('label'=>'Image Position', 'type'=>'select', 'default'=>'top-right', 'options'=>array(
                'top-left' => 'Top Left',
                'top-left-inline' => 'Top Left Inline',
                'bottom-left' => 'Bottom Left',
                'top-right' => 'Top Right',
                'top-right-inline' => 'Top Right Inline',
                'bottom-right' => 'Bottom Right',
                )),
            'image-size'=>array('label'=>'Image Size', 'type'=>'toggle', 'default'=>'half', 'toggles'=>array(
                'half' => 'Full',
                'large' => 'Large',
                'medium' => 'Medium',
                'small' => 'Small',
                'tiny' => 'Tiny',
                )),
            'button-text' => array('label'=>'Link Text', 'type'=>'text'),
            'button-class' => array('label'=>'Link Type', 'type'=>'toggle', 'default'=>'button', 
                'toggles'=>array(
                    'button' => 'Button',
                    'link' => 'Link',
                )),
            ),
        );

    //
    // Get the list of upcoming events
    //
    array_unshift($upcoming, array('id' => 0, 'name' => 'None'));
    $sections['ciniki.events.eventprices'] = array(
        'name' => 'Event Price List',
        'module' => 'Events',
        'settings' => array(
            'event-id' => array('label' => 'Event', 'type'=>'select', 
                'complex_options' => array('value'=>'id', 'name'=>'name'), 
                'options' => $upcoming,
                ),
            'purchase-method' => array('label'=>'Purchase Method', 'type'=>'toggle', 'default'=>'cart', 
                'toggles'=>array(
                    'cart' => 'Cart',
                    'buy-now' => 'Buy Now',
                )),
            ),
        );

    $sections['ciniki.events.eventbuytickets'] = array(
        'name' => 'Event Buy Tickets',
        'module' => 'Events',
        'settings' => array(
            'image-id' => array('label'=>'Image', 'type'=>'image_id', 'controls'=>'all', 'size'=>'medium'),
            'image-position'=>array('label'=>'Image Position', 'type'=>'select', 'default'=>'top-right', 'options'=>array(
                'top-left' => 'Top Left',
                'top-left-inline' => 'Top Left Inline',
                'bottom-left' => 'Bottom Left',
                'top-right' => 'Top Right',
                'top-right-inline' => 'Top Right Inline',
                'bottom-right' => 'Bottom Right',
                )),
            'image-size'=>array('label'=>'Image Size', 'type'=>'toggle', 'default'=>'half', 'toggles'=>array(
                'half' => 'Full',
                'large' => 'Large',
                'medium' => 'Medium',
                'small' => 'Small',
                'tiny' => 'Tiny',
                )),
            'title' => array('label'=>'Title', 'type'=>'text'),
            'content' => array('label'=>'Intro', 'type'=>'textarea', 'size'=>'medium'),
            'success-msg' => array('label'=>'Success Message', 'type'=>'textarea', 'size'=>'small'),
            'closed-msg' => array('label'=>'Event Over Msg', 'type'=>'textarea', 'size'=>'small'),
            'event-id' => array('label' => 'Event', 'type'=>'select', 
                'complex_options' => array('value'=>'id', 'name'=>'name'), 
                'options' => $upcoming,
                ),
            ),
        );

    return array('stat'=>'ok', 'sections'=>$sections);
}
?>
