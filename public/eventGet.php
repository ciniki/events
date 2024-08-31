<?php
//
// Description
// ===========
// This method will return all the information about an event.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant the event is attached to.
// event_id:        The ID of the event to get the details for.
// 
// Returns
// -------
// <event id="419" name="Event Name" url="http://myevent.com" 
//      description="Event description" start_date="July 18, 2012" end_date="July 19, 2012"
//      date_added="2012-07-19 03:08:05" last_updated="2012-07-19 03:08:05" />
//
function ciniki_events_eventGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'event_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Event'), 
        'images'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Images'),
        'files'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Files'),
        'prices'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Prices'),
        'sponsors'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sponsors'),
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Categories'),
        'webcollections'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Web Collections'),
        'objects'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Objects'),
        'ticketmap'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Ticket Map'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'private', 'checkAccess');
    $rc = ciniki_events_checkAccess($ciniki, $args['tnid'], 'ciniki.events.eventGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');

    //
    // Load the tenant intl settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    numfmt_set_attribute($intl_currency_fmt, NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Load event maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'private', 'maps');
    $rc = ciniki_events_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    if( $args['event_id'] == 0 ) {
        $event = array('id'=>0,
            'name'=>'',
            'flags'=>0x03,
            'url'=>'',
            'description'=>'',
            'num_tickets'=>0,
            'reg_flags'=>0,
            'start_date'=>'',
            'end_date'=>'',
            'times'=>'',
            'primary_image_id'=>0,
            'long_description'=>'',
            'oidref'=>'',
            'object'=>'',
            'object_id'=>'',
            'images'=>array(),
            'prices'=>array(),
            );
    } else {
        $strsql = "SELECT ciniki_events.id, "
            . "ciniki_events.name, "
            . "ciniki_events.permalink, "
            . "ciniki_events.flags, "
            . "ciniki_events.url, "
            . "ciniki_events.description, "
            . "ciniki_events.num_tickets, "
            . "ciniki_events.reg_flags, "
            . "DATE_FORMAT(ciniki_events.start_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS start_date, "
            . "DATE_FORMAT(ciniki_events.end_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS end_date, "
            . "ciniki_events.times, "
            . "ciniki_events.primary_image_id, "
            . "ciniki_events.long_description, "
            . "CONCAT_WS(':', ciniki_events.object, ciniki_events.object_id) AS oidref, "
            . "ciniki_events.object, "
            . "ciniki_events.object_id, "
            . "ciniki_events.ticketmap1_image_id, "
            . "ciniki_events.ticketmap1_ptext, "
            . "ciniki_events.ticketmap1_btext, "
            . "ciniki_events.ticketmap1_ntext "
            . "";
        if( isset($args['images']) && $args['images'] == 'yes' ) {
            $strsql .= ", "
                . "ciniki_event_images.id AS img_id, "
                . "ciniki_event_images.name AS image_name, "
                . "ciniki_event_images.webflags AS image_webflags, "
                . "ciniki_event_images.image_id, "
                . "ciniki_event_images.description AS image_description, "
                . "ciniki_event_images.url AS image_url "
                . "";
        }
        $strsql .= "FROM ciniki_events ";
        if( isset($args['images']) && $args['images'] == 'yes' ) {
            $strsql .= "LEFT JOIN ciniki_event_images ON (ciniki_events.id = ciniki_event_images.event_id "
                . "AND ciniki_event_images.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") ";
        }
        $strsql .= "WHERE ciniki_events.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_events.id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
            . "";
        
        if( isset($args['images']) && $args['images'] == 'yes' ) {
            $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.events', array(
                array('container'=>'events', 'fname'=>'id', 'name'=>'event',
                    'fields'=>array('id', 'name', 'permalink', 'flags', 'url', 'primary_image_id', 
                        'start_date', 'end_date', 'times', 'description', 
                        'num_tickets', 'reg_flags', 'long_description', 'oidref', 'object', 'object_id',
                        'ticketmap1_image_id', 'ticketmap1_ptext', 'ticketmap1_btext', 'ticketmap1_ntext')),
                array('container'=>'images', 'fname'=>'img_id', 'name'=>'image',
                    'fields'=>array('id'=>'img_id', 'name'=>'image_name', 'webflags'=>'image_webflags',
                        'image_id', 'description'=>'image_description', 'url'=>'image_url')),
            ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( !isset($rc['events']) || !isset($rc['events'][0]) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.8', 'msg'=>'Unable to find event'));
            }
            $event = $rc['events'][0]['event'];
            ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
            if( isset($event['images']) ) {
                foreach($event['images'] as $img_id => $img) {
                    if( isset($img['image']['image_id']) && $img['image']['image_id'] > 0 ) {
                        $rc = ciniki_images_loadCacheThumbnail($ciniki, $args['tnid'], $img['image']['image_id'], 75);
                        if( $rc['stat'] != 'ok' ) {
                            return $rc;
                        }
                        $event['images'][$img_id]['image']['image_data'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
                    }
                }
            }
        } else {
            $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.events', array(
                array('container'=>'events', 'fname'=>'id', 'name'=>'event',
                    'fields'=>array('id', 'name', 'permalink', 'flags', 'url', 'primary_image_id', 
                        'start_date', 'end_date', 'times',
                        'description', 'num_tickets', 'reg_flags', 'long_description', 'oidref', 'object', 'object_id', 
                        'ticketmap1_image_id', 'ticketmap1_ptext', 'ticketmap1_btext', 'ticketmap1_ntext')),
            ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( !isset($rc['events']) || !isset($rc['events'][0]) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.9', 'msg'=>'Unable to find event'));
            }
            $event = $rc['events'][0]['event'];
        }

        //
        // Get the categories and tags for the post
        //
        if( ($ciniki['tenant']['modules']['ciniki.events']['flags']&0x10) > 0 ) {
            $strsql = "SELECT tag_type, tag_name AS lists "
                . "FROM ciniki_event_tags "
                . "WHERE event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
                . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "ORDER BY tag_type, tag_name "
                . "";
            $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.events', array(
                array('container'=>'tags', 'fname'=>'tag_type', 'name'=>'tags',
                    'fields'=>array('tag_type', 'lists'), 'dlists'=>array('lists'=>'::')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['tags']) ) {
                foreach($rc['tags'] as $tags) {
                    if( $tags['tags']['tag_type'] == 10 ) {
                        $event['categories'] = $tags['tags']['lists'];
                    }
                }
            }
        }
        
        //
        // Check how many registrations
        //
        if( ($event['reg_flags']&0x03) > 0 ) {
            $event['tickets_sold'] = 0;
            $strsql = "SELECT 'num_tickets', SUM(num_tickets) AS num_tickets "  
                . "FROM ciniki_event_registrations "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_event_registrations.event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
            $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.events', 'num');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['num']['num_tickets']) ) {
                $event['tickets_sold'] = $rc['num']['num_tickets'];
            }
        }

        if( isset($args['prices']) && $args['prices'] == 'yes' ) {
            //
            // Get the price list for the event
            //
            $strsql = "SELECT prices.id, "
                . "prices.name, "
                . "prices.available_to, "
                . "prices.available_to AS available_to_text, "
                . "prices.unit_amount, "
                . "prices.webflags, "
                . "prices.num_tickets, "
                . "IFNULL(SUM(registrations.num_tickets), 0) AS num_registrations "
                . "FROM ciniki_event_prices AS prices "
                . "LEFT JOIN ciniki_event_registrations AS registrations ON ("
                    . "prices.id = registrations.price_id "
                    . "AND prices.event_id = registrations.event_id "
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE prices.event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
                . "AND (prices.webflags&0x08) = 0 "   // Skip mapped ticket prices
                . "GROUP BY prices.id "
                . "ORDER BY prices.name COLLATE latin1_general_cs "
                . "";
            $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.events', array(
                array('container'=>'prices', 'fname'=>'id', 'name'=>'price',
                    'fields'=>array('id', 'name', 'available_to', 'available_to_text', 'unit_amount', 
                        'webflags', 'num_tickets', 'num_registrations'),
                    'flags'=>array('available_to_text'=>$maps['prices']['available_to'])),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['prices']) ) {
                $event['prices'] = $rc['prices'];
                foreach($event['prices'] as $pid => $price) {
                    $event['prices'][$pid]['price']['unit_amount_display'] = numfmt_format_currency(
                        $intl_currency_fmt, $price['price']['unit_amount'], $intl_currency);
                }
            } else {
                $event['prices'] = array();
            }
            usort($event['prices'], function($a, $b) {
                return strnatcmp($a['price']['name'], $b['price']['name']);
                });
        }

        if( isset($args['ticketmap']) && $args['ticketmap'] == 'yes' ) {
            //
            // FIXME: Get the image size
            //

            //
            // Get the price list for the event
            //
            $strsql = "SELECT id, name, available_to, available_to AS available_to_text, unit_amount, webflags, "
                . "position_num, position_x, position_y, diameter "
                . "FROM ciniki_event_prices "
                . "WHERE ciniki_event_prices.event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
                . "AND (ciniki_event_prices.webflags&0x08) = 0x08 "
                . "ORDER BY ciniki_event_prices.name COLLATE latin1_general_cs "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.events', array(
                array('container'=>'tickets', 'fname'=>'id',
                    'fields'=>array('id', 'name', 'available_to', 'available_to_text', 'unit_amount', 'webflags',
                        'position_num', 'position_x', 'position_y', 'diameter'),
                    'flags'=>array('available_to_text'=>$maps['prices']['available_to'])),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['tickets']) ) {
                $event['tickets'] = $rc['tickets'];
                foreach($event['tickets'] as $pid => $ticket) {
                    $event['tickets'][$pid]['unit_amount_display'] = numfmt_format_currency(
                        $intl_currency_fmt, $ticket['unit_amount'], $intl_currency);
                }
            } else {
                $event['tickets'] = array();
            }
            usort($event['tickets'], function($a, $b) {
                return strnatcmp($a['name'], $b['name']);
                });
        }

        //
        // Get the links for the post
        //
        if( isset($args['files']) && $args['files'] == 'yes' ) {
            $strsql = "SELECT id, name, url, description "
                . "FROM ciniki_event_links "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_event_links.event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
                . "";
            $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.events', array(
                array('container'=>'links', 'fname'=>'id', 'name'=>'link',
                    'fields'=>array('id', 'name', 'url', 'description')),
            ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['links']) ) {
                $event['links'] = $rc['links'];
            } else {
                $event['links'] = array();
            }
        }

        //
        // Get any files if requested
        //
        if( isset($args['files']) && $args['files'] == 'yes' ) {
            $strsql = "SELECT id, name, extension, permalink "
                . "FROM ciniki_event_files "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_event_files.event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
                . "";
            $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.events', array(
                array('container'=>'files', 'fname'=>'id', 'name'=>'file',
                    'fields'=>array('id', 'name', 'extension', 'permalink')),
            ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['files']) ) {
                $event['files'] = $rc['files'];
            }
        }

        //
        // Get any sponsors for this event, and that references for sponsors is enabled
        //
        if( isset($args['sponsors']) && $args['sponsors'] == 'yes' 
            && isset($ciniki['tenant']['modules']['ciniki.sponsors']) 
            && ($ciniki['tenant']['modules']['ciniki.sponsors']['flags']&0x02) == 0x02
            ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'hooks', 'sponsorList');
            $rc = ciniki_sponsors_hooks_sponsorList($ciniki, $args['tnid'], 
                array('object'=>'ciniki.events.event', 'object_id'=>$args['event_id']));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['sponsors']) ) {
                $event['sponsors'] = $rc['sponsors'];
            }
        }

        //
        // Get any sponsorships for event
        //
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sponsors', 0x10) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'hooks', 'objectSponsorships');
            $rc = ciniki_sponsors_hooks_objectSponsorships($ciniki, $args['tnid'], array(
                'object' => 'ciniki.events.event',
                'object_id' => $args['event_id'],
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.280', 'msg'=>'', 'err'=>$rc['err']));
            }
            $event['sponsorships'] = isset($rc['sponsorships']) ? $rc['sponsorships'] : array();
            $event['sponsorships_total'] = isset($rc['total']) ? '$' . number_format($rc['total'], 2) : '$0';
        }

        //
        // Get any sponsorship Packages
        //
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sponsors', 0x10) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'hooks', 'objectPackages');
            $rc = ciniki_sponsors_hooks_objectPackages($ciniki, $args['tnid'], array(
                'object' => 'ciniki.events.event',
                'object_id' => $args['event_id'],
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.279', 'msg'=>'', 'err'=>$rc['err']));
            }
            $event['sponsorshippackages'] = isset($rc['packages']) ? $rc['packages'] : array();
        }

        //
        // Get any expenses for offering
        //
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.events', 0x0400) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'objectExpenses');
            $rc = ciniki_sapos_hooks_objectExpenses($ciniki, $args['tnid'], array(
                'object' => 'ciniki.events.event',
                'object_id' => $args['event_id'],
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.107', 'msg'=>'Unable to load expenses', 'err'=>$rc['err']));
            }
            $event['expenses'] = isset($rc['expenses']) ? $rc['expenses'] : array();
            $event['expenses_total'] = isset($rc['total']) ? '$' . number_format($rc['total'], 2) : '$0';
        }
    }

    $rsp = array('stat'=>'ok', 'event'=>$event);

    //
    // Check if all tags should be returned
    //
    $rsp['categories'] = array();
    if( ($ciniki['tenant']['modules']['ciniki.events']['flags']&0x10) > 0
        && isset($args['categories']) && $args['categories'] == 'yes' 
        ) {
        //
        // Get the available tags
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsList');
        $rc = ciniki_core_tagsList($ciniki, 'ciniki.events', $args['tnid'], 
            'ciniki_event_tags', 10);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.10', 'msg'=>'Unable to get list of categories', 'err'=>$rc['err']));
        }
        if( isset($rc['tags']) ) {
            $rsp['categories'] = $rc['tags'];
        }
    }

    //
    // Get the list of web collections, and which ones this event is attached to
    //
    if( isset($args['webcollections']) && $args['webcollections'] == 'yes'
        && isset($ciniki['tenant']['modules']['ciniki.web']) 
        && ($ciniki['tenant']['modules']['ciniki.web']['flags']&0x08) == 0x08
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'hooks', 'webCollectionList');
        $rc = ciniki_web_hooks_webCollectionList($ciniki, $args['tnid'],
            array('object'=>'ciniki.events.event', 'object_id'=>$args['event_id']));
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( isset($rc['collections']) ) {
            $rsp['event']['_webcollections'] = $rc['collections'];
            $rsp['event']['webcollections'] = $rc['selected'];
            $rsp['event']['webcollections_text'] = $rc['selected_text'];
        }
    }

    //
    // Get the object available to link with events
    //
    if( isset($args['objects']) && $args['objects'] == 'yes' ) {
        $rsp['objects'] = array();
        foreach($ciniki['tenant']['modules'] as $module) {
            $rc = ciniki_core_loadMethod($ciniki, $module['package'], $module['module'], 'hooks', 'eventObjects');
            if( $rc['stat'] == 'ok' ) {
                $fn = $rc['function_call'];
                $rc = $fn($ciniki, $args['tnid'], array('event_id'=>$rsp['event']['id']));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( isset($rc['objects']) ) {
                    $rsp['objects'] = array_merge($rsp['objects'], $rc['objects']);
                }
            }
        }
    }

    return $rsp;
}
?>
