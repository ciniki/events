<?php
//
// Description
// -----------
// Add to an existing PDF the tickets.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_events_templates_ticketsPDF(&$ciniki, $tnid, $args) {

    if( !isset($args['pdf']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.113', 'msg'=>'No pdf specified.'));
    }
    if( !isset($args['tickets']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.114', 'msg'=>'No tickets specified.'));
    }

    //
    // Load tenant details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'tenantDetails');
    $rc = ciniki_tenants_tenantDetails($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']) && is_array($rc['details']) ) {    
        $tenant_details = $rc['details'];
    } else {
        $tenant_details = array();
    }

    
    $args['pdf']->SetMargins(PDF_MARGIN_LEFT, 13, PDF_MARGIN_RIGHT);
    $args['pdf']->SetHeaderMargin(0);
    $args['pdf']->SetfooterMargin(0);
    $args['pdf']->SetPrintHeader(false);
    $args['pdf']->SetPrintFooter(false);
    $args['pdf']->SetLineWidth(1.5);
    $args['pdf']->SetDrawColor(220);

    foreach($args['tickets'] as $ticket) {
        $args['pdf']->AddPage();

        $args['pdf']->setFont('helvetica', 'B', 24);
//Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=0, $link='', $stretch=0, $ignore_min_height=false, $calign='T', $valign='M')
        $args['pdf']->Cell(180, 14, $tenant_details['name'], 0, 1, 'L', 0, '', 1);
        
        //
        // Add the image
        //
        if( isset($ticket['ticket_image_id']) && $ticket['ticket_image_id'] > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadImage');
            $rc = ciniki_images_loadImage($ciniki, $tnid, $ticket['ticket_image_id'], 'original');
            if( $rc['stat'] == 'ok' ) {
                $image = $rc['image'];
                $h = $image->getImageHeight();
                $w = $image->getImageWidth();
                $ir = $w/$h;    // image ratio
                $ar = 176/40;   // Available ratio
                if( $ar < $ir ) {
                    $args['pdf']->Image('@'.$image->getImageBlob(), '', '', 186, 0, 'JPEG', '', 'C', 2, '150');
                } else {    
                    $new_width = ($w*40)/$h;
                    $args['pdf']->Image('@'.$image->getImageBlob(), PDF_MARGIN_LEFT + 5 + ((176-$new_width)/2), ($args['pdf']->getY() + 5), $new_width, 40, 'JPEG', '', 'C', 2, '150');
                    $args['pdf']->Cell(186, 45, '', 'TLR', 1);
                }
            }
        }
        //
        // Add the event name
        //
        $args['pdf']->setCellPaddings(5,4,5,1);
        $args['pdf']->setFont('helvetica', 'B', 26);
        if( isset($ticket['ticket_event_name']) && $ticket['ticket_event_name'] != '' ) {
            $args['pdf']->Multicell(186, 20, $ticket['ticket_event_name'], 'LR', 'L');
        }
        $args['pdf']->setTextColor(50);
        $args['pdf']->setFont('helvetica', '', 16);
        $args['pdf']->setCellPaddings(5,1,5,3);
        if( isset($ticket['ticket_timedate']) && $ticket['ticket_timedate'] != '' ) {
            $args['pdf']->Multicell(93, 10, $ticket['ticket_timedate'], 'L', 'L', 0, 0);
        } else {
            $args['pdf']->Multicell(93, 10, '', 'L', 'L', 0, 0);
        }
        if( isset($ticket['ticket_location']) && $ticket['ticket_location'] != '' ) {
            $args['pdf']->Multicell(93, 10, $ticket['ticket_location'], 'R', 'L', 0, 1);
        } else {
            $args['pdf']->Multicell(93, 10, '', 'R', 'L', 0, 1);
        }

        if( isset($ticket['num_tickets']) && $ticket['num_tickets'] == 1 ) {
            $args['pdf']->Multicell(93, 12, $ticket['num_tickets'] . ' x Ticket' , 'L', 'L', 0, 0);
        } elseif( isset($ticket['num_tickets']) && $ticket['num_tickets'] > 0 ) {
            $args['pdf']->Multicell(93, 12, $ticket['num_tickets'] . ' x Tickets' , 'L', 'L', 0, 0);
        } else {
            $args['pdf']->Multicell(93, 12, '', 'L', 'L', 0, 0);
        }

        $args['pdf']->setFont('helvetica', '', 14);
        $args['pdf']->setTextColor(125);
        $args['pdf']->setCellPaddings(5,2,5,5);
        if( isset($ticket['order_number']) && $ticket['order_number'] != '' ) {
            $args['pdf']->Multicell(93, 12, 'Order #' . $ticket['order_number'], 'R', 'R', 0, 1);
        } else {
            $args['pdf']->Multicell(93, 12, '', 'R', 'R', 0, 1);
        }

        $args['pdf']->setCellPaddings(0,1,0,0);
        $args['pdf']->Cell(186, 0.5, '', 'T', 1);
    
        $args['pdf']->Ln(2);
        $args['pdf']->setTextColor(0);
        $args['pdf']->setFont('helvetica', '', 14);
        if( isset($ticket['ticket_notes']) && $ticket['ticket_notes'] != '' ) {
            $args['pdf']->Multicell(186, 20, print_r($ticket['ticket_notes'], true), 0, 'L');
        }
    }

    return array('stat'=>'ok');
}
?>
