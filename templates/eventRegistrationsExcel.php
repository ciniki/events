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
function ciniki_events_templates_eventRegistrationsExcel(&$ciniki, $business_id, $event_id, $business_details, $events_settings) {

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
        . "WHERE ciniki_events.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_events.id = '" . ciniki_core_dbQuote($ciniki, $event_id) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'event');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['event']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3581', 'msg'=>'Unable to find event'));
    }
    $event = $rc['event'];

    //
    // Load the registrations
    //
    $strsql = "SELECT ciniki_event_registrations.id, "
        . "ciniki_event_registrations.customer_id, "
        . "ciniki_event_registrations.num_tickets, "
        . "ciniki_event_registrations.status, "
        . "ciniki_event_registrations.status AS status_text, "
        . "ciniki_event_registrations.notes "
        . "FROM ciniki_event_registrations "
        . "LEFT JOIN ciniki_customers ON ("
            . "ciniki_event_registrations.customer_id = ciniki_customers.id "
            . "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "WHERE ciniki_event_registrations.event_id = '" . ciniki_core_dbQuote($ciniki, $event_id) . "' "
        . "AND ciniki_event_registrations.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "ORDER BY ciniki_customers.last, ciniki_customers.first "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.events', array(
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array('id', 'customer_id', 'num_tickets', 'status', 'status_text', 'notes'),
            'maps'=>array('status_text'=>$maps['registration']['status']),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['registrations']) || count($rc['registrations']) == 0 ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3580', 'msg'=>'No registrations'));
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
            $rc = ciniki_customers_hooks_customerDetails($ciniki, $business_id, 
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
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow(2, $row, $phones, false);
                }
                if( isset($customer['emails']) ) {
                    $emails = '';
                    $comma = '';
                    foreach($customer['emails'] as $e => $email) {
                        $emails .= ($emails!=''?', ':'') . $email['email']['address'];
                    }
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow(3, $row, $emails, false);
                }
            }
        }

        $objPHPExcelWorksheet->setCellValueByColumnAndRow(1, $row, $reg['num_tickets'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow(4, $row, $reg['status_text'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow(5, $row, $reg['notes'], false);
        $row++;
    }

    $objPHPExcelWorksheet->getColumnDimension('A')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('B')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('C')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('D')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('E')->setAutoSize(true);

    return array('stat'=>'ok', 'event'=>$event, 'excel'=>$objPHPExcel);
}
?>
