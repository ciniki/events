<?php
//
// Description
// -----------
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_events_wng_eventPricesProcess(&$ciniki, $tnid, &$request, $section) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'wng', 'private', 'contentProcess');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'wng', 'private', 'urlProcess');

    $blocks = array();
    $s = isset($section['settings']) ? $section['settings'] : array();

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $dt = new DateTime('now', new DateTimezone($intl_timezone));

    //
    // Load the event details
    //
    if( !isset($s['event-id']) || $s['event-id'] <= 0 ) {
        return array('stat'=>'ok');
    }

    //
    // Load the event prices
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'wng', 'eventLoad');
    $rc = ciniki_events_wng_eventLoad($ciniki, $tnid, $request, $s['event-id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'ok', 'blocks'=>array(
            array(
                'type' => 'msg',
                'level' => 'error',
                'message' => 'Event not found',
                ),
            ));
    }
    $event = $rc['event'];

    //
    // FIXME: Make sure event is still upcoming and not past
    //
    if( isset($s['purchase-method']) && $s['purchase-method'] == 'buy-now' ) {
        $blocks[] = array(
            'type' => 'pricelist', 
            'title' => isset($s['title']) ? $s['title'] : '',
            'content' => isset($s['intro']) ? $s['intro'] : '',
            'image-id' => isset($s['image-id']) ? $s['image-id'] : '0',
            'prices' => $event['prices'],
            );
    } else {
        $blocks[] = array(
            'type' => 'pricelist', 
            'title' => isset($s['title']) ? $s['title'] : '',
            'intro' => isset($s['intro']) ? $s['intro'] : '',
            'prices' => $event['prices'],
            );
    }

//    $blocks[] = array(
//        'type' => 'html',
//        'html' => '<pre>' . print_r($rc, true) . '</pre>',
//        );

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
