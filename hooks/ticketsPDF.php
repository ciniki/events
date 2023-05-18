<?php
//
// Description
// -----------
// This function will attach any tickets to an existing PDF file
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_events_hooks_ticketsPDF(&$ciniki, $tnid, $args) {
    
    //
    // Check if the registration has tickets
    //
    if( isset($args['object']) && $args['object'] == 'ciniki.events.registration'
        && isset($args['object_id']) && $args['object_id'] > 0
        && isset($args['price_id']) && $args['price_id'] > 0 
        && isset($args['pdf'])
        ) {

        $strsql = "SELECT prices.id, "
            . "prices.webflags, "
            . "prices.ticket_image_id, "
            . "prices.ticket_event_name, "
            . "prices.ticket_timedate, "
            . "prices.ticket_location, "
            . "prices.ticket_notes, "
            . "registrations.num_tickets, "
            . "invoices.invoice_number AS order_number "
            . "FROM ciniki_event_registrations AS registrations "
            . "INNER JOIN ciniki_event_prices AS prices ON ("
                . "registrations.price_id = prices.id "
                . "AND prices.id = '" . ciniki_core_dbQuote($ciniki, $args['price_id']) . "' "
                . "AND (prices.webflags&0x0100) = 0x0100 "
                . "AND prices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_sapos_invoices AS invoices ON ("
                . "registrations.invoice_id = invoices.id "
                . "AND invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE registrations.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'ticket');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.112', 'msg'=>'Unable to load ticket', 'err'=>$rc['err']));
        }
        if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'templates', 'ticketsPDF');
            return ciniki_events_templates_ticketsPDF($ciniki, $tnid, array(
                'pdf' => $args['pdf'],
                'tickets' => $rc['rows'],
                ));
        }
    }

    return array('stat'=>'ok');
}
?>
