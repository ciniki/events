<?php
//
// Description
// ===========
// This method will produce a PDF of the event.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_events_templates_eventIndividualTickets(&$ciniki, $tnid, $event_id, $price_id, $tenant_details, $events_settings) {

    //
    // Load event maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'private', 'maps');
    $rc = ciniki_events_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Load the event information
    //
    $strsql = "SELECT ciniki_events.id, "
        . "ciniki_events.name, "
        . "ciniki_events.permalink, "
        . "ciniki_events.url, "
        . "ciniki_events.description, "
        . "ciniki_events.num_tickets, "
        . "ciniki_events.reg_flags, "
        . "DATE_FORMAT(ciniki_events.start_date, '%M %j, %Y') AS start_date, "
        . "DATE_FORMAT(ciniki_events.end_date, '%M %j, %Y') AS end_date, "
        . "ciniki_events.times, "
        . "ciniki_events.primary_image_id, "
        . "ciniki_events.long_description, "
        . "CONCAT_WS(':', ciniki_events.object, ciniki_events.object_id) AS oidref, "
        . "ciniki_events.object, "
        . "ciniki_events.object_id "
        . "FROM ciniki_events "
        . "WHERE ciniki_events.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_events.id = '" . ciniki_core_dbQuote($ciniki, $event_id) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'event');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['event']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.73', 'msg'=>'Unable to find event'));
    }
    $event = $rc['event'];

    //
    // Load the registrations
    //
    $strsql = "SELECT registrations.id, "
        . "registrations.customer_id, "
        . "registrations.num_tickets, "
        . "registrations.status, "
        . "registrations.status AS status_text, "
        . "registrations.notes, "
        . "prices.name "
        . "FROM ciniki_event_registrations AS registrations "
        . "LEFT JOIN ciniki_sapos_invoice_items AS items ON ("
            . "items.object = 'ciniki.events.registration' "
            . "AND registrations.id = items.object_id "
            . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_event_prices AS prices ON ("
            . "items.price_id = prices.id "
            . "AND prices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers ON ("
            . "registrations.customer_id = ciniki_customers.id "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE registrations.event_id = '" . ciniki_core_dbQuote($ciniki, $event_id) . "' "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
    if( $price_id > 0 ) {
        $strsql .= "AND registrations.price_id = '" . ciniki_core_dbQuote($ciniki, $price_id) . "' ";
    }
    $strsql .= "ORDER BY ciniki_customers.last, ciniki_customers.first "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.events', array(
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array('id', 'customer_id', 'num_tickets', 'status', 'status_text', 'notes', 'name'),
            'maps'=>array('status_text'=>$maps['registration']['status']),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['registrations']) || count($rc['registrations']) == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.74', 'msg'=>'No registrations'));
    }
    $registrations = $rc['registrations'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');

    require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
    $objPHPExcel = new PHPExcel();
    $objPHPExcelWorksheet = $objPHPExcel->setActiveSheetIndex(0);

    $col = 0;
    $row = 1;
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Customer', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Tickets', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Seat', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Phone', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Email', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Status', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Notes', false);

    $objPHPExcelWorksheet->getStyle('A1:E1')->getFont()->setBold(true);

    $row++;
    foreach($registrations as $reg) {
        //
        // Get the student information, so it can be added to the form and verified
        //
        if( $reg['customer_id'] > 0 ) {
            $rc = ciniki_customers_hooks_customerDetails($ciniki, $tnid, 
                array('customer_id'=>$reg['customer_id'], 'addresses'=>'yes', 'phones'=>'yes', 'emails'=>'yes'));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
//          print "<pre>" . print_r($rc, true) . "</pre>";
            if( isset($rc['customer']) ) {
                $customer = $rc['customer'];
                $objPHPExcelWorksheet->setCellValueByColumnAndRow(0, $row, $customer['display_name'], false);
                if( isset($customer['phones']) ) {
                    $phones = "";
                    foreach($customer['phones'] as $phone) {
                        if( count($customer['phones']) > 1 ) {
                            $p = $phone['phone_label'] . ': ' . $phone['phone_number'];
                            $phones .= ($phones!=''?', ':'') . $p;
                        } else {
                            $phones .= $phone['phone_number'];
                        }
                    }
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow(3, $row, $phones, false);
                }
                if( isset($customer['emails']) ) {
                    $emails = '';
                    $comma = '';
                    foreach($customer['emails'] as $e => $email) {
                        $emails .= ($emails!=''?', ':'') . $email['email']['address'];
                    }
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow(4, $row, $emails, false);
                }
            }
        }

        $objPHPExcelWorksheet->setCellValueByColumnAndRow(1, $row, $reg['num_tickets'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow(2, $row, $reg['name'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow(5, $row, $reg['status_text'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow(6, $row, $reg['notes'], false);
        $row++;
    }

    $objPHPExcelWorksheet->getColumnDimension('A')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('B')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('C')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('D')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('E')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('F')->setAutoSize(true);

    return array('stat'=>'ok', 'event'=>$event, 'excel'=>$objPHPExcel);
}
?>
