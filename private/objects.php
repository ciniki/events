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
function ciniki_events_objects($ciniki) {
    
    $objects = array();
    $objects['event'] = array(
        'name'=>'Events',
        'sync'=>'yes',
        'table'=>'ciniki_events',
        'fields'=>array(
            'name'=>array(),
            'permalink'=>array(),
            'flags'=>array('name'=>'Options', 'default'=>1),
            'url'=>array(),
            'description'=>array(),
            'reg_flags'=>array(),
            'num_tickets'=>array(),
            'start_date'=>array(),
            'end_date'=>array(),
            'times'=>array(),
            'primary_image_id'=>array('ref'=>'ciniki.images.image'),
            'long_description'=>array(),
            'object'=>array('default'=>''),
            'object_id'=>array('default'=>''),
            'ticketmap1_image_id'=>array('name'=>'Ticket Map 1 Image', 'default'=>0),
            'ticketmap1_ptext'=>array('name'=>'Ticket Map 1 Name', 'default'=>''),
            'ticketmap1_btext'=>array('name'=>'Ticket Map 1 Button', 'default'=>''),
            'ticketmap1_ntext'=>array('name'=>'Ticket Map 1 None Selected', 'default'=>''),
            ),
        'history_table'=>'ciniki_event_history',
        );
    $objects['image'] = array(
        'name'=>'Image',
        'sync'=>'yes',
        'table'=>'ciniki_event_images',
        'fields'=>array(
            'event_id'=>array('ref'=>'ciniki.events.event'),
            'name'=>array(),
            'permalink'=>array(),
            'webflags'=>array(),
            'image_id'=>array('ref'=>'ciniki.images.image'),
            'description'=>array(),
            'url'=>array(),
            ),
        'history_table'=>'ciniki_event_history',
        );
    $objects['file'] = array(
        'name'=>'File',
        'sync'=>'yes',
        'table'=>'ciniki_event_files',
        'fields'=>array(
            'event_id'=>array('ref'=>'ciniki.events.event'),
            'extension'=>array(),
            'name'=>array(),
            'permalink'=>array(),
            'webflags'=>array(),
            'description'=>array(),
            'org_filename'=>array(),
            'publish_date'=>array(),
            'binary_content'=>array('history'=>'no', 'default'=>''),
            ),
        'history_table'=>'ciniki_event_history',
        );
    $objects['price'] = array(
        'name'=>'Price',
        'sync'=>'yes',
        'table'=>'ciniki_event_prices',
        'fields'=>array(
            'event_id'=>array('ref'=>'ciniki.events.event'),
            'name'=>array(),
            'available_to'=>array(),
            'valid_from'=>array(),
            'valid_to'=>array(),
            'unit_amount'=>array('name'=>'Unit Amount'),
            'unit_discount_amount'=>array('name'=>'Discount Amount', 'default'=>'0'),
            'unit_discount_percentage'=>array('name'=>'Discount Percentage', 'default'=>'0'),
            'unit_donation_amount'=>array('name'=>'Donation Portion', 'default'=>0),
            'taxtype_id'=>array('name'=>'Tax Type', 'ref'=>'ciniki.taxes.type', 'default'=>'0'),
            'webflags'=>array('name'=>'Options', 'default'=>'0'),
            'num_tickets'=>array('name'=>'Number of Available Tickets', 'default'=>'0'),
            'position_num'=>array('name'=>'Position Number', 'default'=>'1'),
            'position_x'=>array('name'=>'Position X', 'default'=>'0'),
            'position_y'=>array('name'=>'Position Y', 'default'=>'0'),
            'diameter'=>array('name'=>'Diameter', 'default'=>'0'),
            ),
        'history_table'=>'ciniki_event_history',
        );
    $objects['registration'] = array(
        'name'=>'Registration',
        'sync'=>'yes',
        'table'=>'ciniki_event_registrations',
        'fields'=>array(
            'event_id'=>array('name'=>'Event', 'ref'=>'ciniki.events.event'),
            'price_id'=>array('name'=>'Price', 'ref'=>'ciniki.events.price', 'default'=>'0'),
            'customer_id'=>array('name'=>'Customer', 'ref'=>'ciniki.customers.customer'),
            'invoice_id'=>array('name'=>'Invoice', 'ref'=>'ciniki.pos.invoice'),
            'status'=>array('name'=>'Status', 'default'=>'10'),
            'num_tickets'=>array('name'=>'Num Tickets'),
            'customer_notes'=>array('name'=>'Customer Notes', 'default'=>''),
            'notes'=>array('name'=>'Private Notes', 'default'=>''),
            ),
        'history_table'=>'ciniki_event_history',
        );
    $objects['tag'] = array(
        'name'=>'Tag',
        'sync'=>'yes',
        'table'=>'ciniki_event_tags',
        'fields'=>array(
            'event_id'=>array('name'=>'Event', 'ref'=>'ciniki.events.event'),
            'tag_type'=>array('name'=>'Tag Type',),
            'tag_name'=>array('name'=>'Tag Name',),
            'permalink'=>array('name'=>'Permalink'),
            ),
        'history_table'=>'ciniki_event_history',
        );
    $objects['link'] = array(
        'name'=>'Link',
        'sync'=>'yes',
        'table'=>'ciniki_event_links',
        'fields'=>array(
            'event_id'=>array('ref'=>'ciniki.events.event'),
            'name'=>array(),
            'url'=>array(),
            'description'=>array(),
            ),
        'history_table'=>'ciniki_event_history',
        );
    $objects['setting'] = array(
        'type'=>'settings',
        'name'=>'Event Settings',
        'table'=>'ciniki_event_settings',
        'history_table'=>'ciniki_event_history',
        );
    
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
