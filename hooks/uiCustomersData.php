<?php
//
// Description
// -----------
// This function will return the data for customer(s) to be displayed in the IFB display panel.
// The request might be for 1 individual, or multiple customer ids for a family.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get events for.
//
// Returns
// -------
//
function ciniki_events_hooks_uiCustomersData($ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

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
    // Default response
    //
    $rsp = array('stat'=>'ok', 'tabs'=>array());

    //
    // Get the list of registrations for the with latest first
    //
    $sections['ciniki.events.registrations'] = array(
        'label' => 'Event Registrations',
        'type' => 'simplegrid', 
        'num_cols' => 3,
        'headerValues' => array('Name', 'Event', 'Date'),
        'cellClasses' => array('', '', ''),
        'noData' => 'No registrations',
//            'editApp' => array('app'=>'ciniki.events.sapos', 'args'=>array('registration_id'=>'d.id;', 'source'=>'\'\'')),
        'cellValues' => array(
            '0' => "d.display_name",
            '1' => "d.name",
            '2' => "d.start_date",
            ),
        'data' => array(),
        );
    $strsql = "SELECT regs.id, regs.customer_id, "
        . "regs.status AS status_text, "
        . "IFNULL(customers.display_name, '') AS display_name, "
        . "events.name, "
        . "DATE_FORMAT(events.start_date, '%b %d, %Y') AS start_date "
        . "FROM ciniki_event_registrations AS regs "
        . "INNER JOIN ciniki_events AS events ON ("
            . "regs.event_id = events.id "
            . "AND events.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "regs.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE regs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    if( isset($args['customer_id']) ) {
        $strsql .= "AND regs.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
    } elseif( isset($args['customer_ids']) && count($args['customer_ids']) > 0 ) {
        $strsql .= "AND regs.customer_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['customer_ids']) . ") ";
    } else {
        return array('stat'=>'ok');
    }
    $strsql .= "ORDER BY customers.display_name, events.start_date DESC, events.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array('id', 'customer_id', 'display_name', 'start_date', 'name'),
            'maps'=>array('status_text'=>$maps['registration']['status']),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $sections['ciniki.events.registrations']['data'] = isset($rc['registrations']) ? $rc['registrations'] : array();
    $rsp['tabs'][] = array(
        'id' => 'ciniki.events.registrations',
        'label' => 'Events',
        'sections' => $sections,
        );
    $sections = array();

    return $rsp;
}
?>
