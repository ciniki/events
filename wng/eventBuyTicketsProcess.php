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
function ciniki_events_wng_eventBuyTicketsProcess(&$ciniki, $tnid, &$request, $section) {

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
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];
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
    $dt = new DateTime('now', new DateTimeZone($intl_timezone));
    if( $event['end_date'] != '' ) {
        $edt = new DateTime($event['end_date'] . ' 23:59:59', new DateTimeZone($intl_timezone));
    } else {
        $edt = new DateTime($event['start_date'] . ' 23:59:59', new DateTimeZone($intl_timezone));
    }
    if( $edt < $dt ) {
        $blocks[] = array(
            'type' => 'contentphoto',
            'title' => $s['title'],
            'checkout_id' => $section['sequence'],
            'content' => $s['content'],
            'image-id' => $s['image-id'],
            'buy-now' => 'closed',
            'closed-msg' => isset($s['closed-msg']) ? $s['closed-msg'] : 'The event is finished, tickets are no longer for sale.',
            );
    } else {
        $blocks[] = array(
            'type' => 'contentphoto',
            'title' => $s['title'],
            'checkout_id' => $section['sequence'],
            'content' => $s['content'],
            'image-id' => $s['image-id'],
            'buy-now' => 'yes',
            'button-text' => 'Buy Tickets',
            'prices' => $event['prices'],
            'return-url' => $request['ssl_domain_base_url'] . $request['page']['path'],
            'success-msg' => isset($s['success-msg']) ? $s['success-msg'] : '',
            );
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
