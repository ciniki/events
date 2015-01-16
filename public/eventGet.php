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
// business_id:		The ID of the business the event is attached to.
// event_id:		The ID of the event to get the details for.
// 
// Returns
// -------
// <event id="419" name="Event Name" url="http://myevent.com" 
//		description="Event description" start_date="July 18, 2012" end_date="July 19, 2012"
//		date_added="2012-07-19 03:08:05" last_updated="2012-07-19 03:08:05" />
//
function ciniki_events_eventGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'event_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Event'), 
		'images'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Images'),
		'files'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Files'),
		'prices'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Prices'),
		'sponsors'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sponsors'),
		'categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Categories'),
		'webcollections'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Web Collections'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'private', 'checkAccess');
    $rc = ciniki_events_checkAccess($ciniki, $args['business_id'], 'ciniki.events.eventGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];

	//
	// Load the business intl settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_timezone = $rc['settings']['intl-default-timezone'];
	$intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
	$intl_currency = $rc['settings']['intl-default-currency'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// Load event maps
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'private', 'maps');
	$rc = ciniki_events_maps($ciniki, $modules);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$maps = $rc['maps'];

	$strsql = "SELECT ciniki_events.id, "
		. "ciniki_events.name, "
		. "ciniki_events.permalink, "
		. "ciniki_events.url, "
		. "ciniki_events.description, "
		. "ciniki_events.num_tickets, "
		. "ciniki_events.reg_flags, "
		. "DATE_FORMAT(ciniki_events.start_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS start_date, "
		. "DATE_FORMAT(ciniki_events.end_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS end_date, "
		. "ciniki_events.times, "
		. "ciniki_events.primary_image_id, "
		. "ciniki_events.long_description ";
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
			. "AND ciniki_event_images.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") ";
	}
	$strsql .= "WHERE ciniki_events.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_events.id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
		. "";
	
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	if( isset($args['images']) && $args['images'] == 'yes' ) {
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.events', array(
			array('container'=>'events', 'fname'=>'id', 'name'=>'event',
				'fields'=>array('id', 'name', 'permalink', 'url', 'primary_image_id', 
					'start_date', 'end_date', 'times', 'description', 
					'num_tickets', 'reg_flags', 'long_description')),
			array('container'=>'images', 'fname'=>'img_id', 'name'=>'image',
				'fields'=>array('id'=>'img_id', 'name'=>'image_name', 'webflags'=>'image_webflags',
					'image_id', 'description'=>'image_description', 'url'=>'image_url')),
		));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['events']) || !isset($rc['events'][0]) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1330', 'msg'=>'Unable to find event'));
		}
		$event = $rc['events'][0]['event'];
		ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
		if( isset($event['images']) ) {
			foreach($event['images'] as $img_id => $img) {
				if( isset($img['image']['image_id']) && $img['image']['image_id'] > 0 ) {
					$rc = ciniki_images_loadCacheThumbnail($ciniki, $args['business_id'], $img['image']['image_id'], 75);
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
				'fields'=>array('id', 'name', 'permalink', 'url', 'primary_image_id', 
					'start_date', 'end_date', 'times',
					'description', 'num_tickets', 'reg_flags', 'long_description')),
		));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['events']) || !isset($rc['events'][0]) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1331', 'msg'=>'Unable to find event'));
		}
		$event = $rc['events'][0]['event'];
	}

	//
	// Get the categories and tags for the post
	//
	if( ($ciniki['business']['modules']['ciniki.events']['flags']&0x10) > 0 ) {
		$strsql = "SELECT tag_type, tag_name AS lists "
			. "FROM ciniki_event_tags "
			. "WHERE event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
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
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
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
		$strsql = "SELECT id, name, available_to, available_to AS available_to_text, unit_amount "
			. "FROM ciniki_event_prices "
			. "WHERE ciniki_event_prices.event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
			. "ORDER BY ciniki_event_prices.name "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.events', array(
			array('container'=>'prices', 'fname'=>'id', 'name'=>'price',
				'fields'=>array('id', 'name', 'available_to', 'available_to_text', 'unit_amount'),
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
	}

	//
	// Get any files if requested
	//
	if( isset($args['files']) && $args['files'] == 'yes' ) {
		$strsql = "SELECT id, name, extension, permalink "
			. "FROM ciniki_event_files "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
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
		&& isset($ciniki['business']['modules']['ciniki.sponsors']) 
		&& ($ciniki['business']['modules']['ciniki.sponsors']['flags']&0x02) == 0x02
		) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'hooks', 'sponsorList');
		$rc = ciniki_sponsors_hooks_sponsorList($ciniki, $args['business_id'], 
			array('object'=>'ciniki.events.event', 'object_id'=>$args['event_id']));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['sponsors']) ) {
			$event['sponsors'] = $rc['sponsors'];
		}
	}

	//
	// Check if all tags should be returned
	//
	$categories = array();
	if( ($ciniki['business']['modules']['ciniki.events']['flags']&0x10) > 0
		&& isset($args['categories']) && $args['categories'] == 'yes' 
		) {
		//
		// Get the available tags
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsList');
		$rc = ciniki_core_tagsList($ciniki, 'ciniki.events', $args['business_id'], 
			'ciniki_event_tags', 10);
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2165', 'msg'=>'Unable to get list of categories', 'err'=>$rc['err']));
		}
		if( isset($rc['tags']) ) {
			$categories = $rc['tags'];
		}
	}

	//
	// Get the list of web collections, and which ones this event is attached to
	//
	if( isset($args['webcollections']) && $args['webcollections'] == 'yes'
		&& isset($ciniki['business']['modules']['ciniki.web']) 
		&& ($ciniki['business']['modules']['ciniki.web']['flags']&0x08) == 0x08
		) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'hooks', 'webCollectionList');
		$rc = ciniki_web_hooks_webCollectionList($ciniki, $args['business_id'],
			array('object'=>'ciniki.events.event', 'object_id'=>$args['event_id']));
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
		if( isset($rc['collections']) ) {
			$event['_webcollections'] = $rc['collections'];
			$event['webcollections'] = $rc['selected'];
			$event['webcollections_text'] = $rc['selected_text'];
		}
	}

	return array('stat'=>'ok', 'event'=>$event, 'categories'=>$categories);
}
?>
