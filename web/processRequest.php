<?php
//
// Description
// -----------
// This function will process a web request for the events module.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get events for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_events_web_processRequest(&$ciniki, $settings, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.events']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.events.63', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }
    $page = array(
        'title'=>$args['page_title'],
        'breadcrumbs'=>$args['breadcrumbs'],
        'article-class'=>'ciniki-events',
        'blocks'=>array(),
        );

    //
    // Check if a file was specified to be downloaded
    //
    $download_err = '';
    if( isset($args['uri_split'][0]) && $args['uri_split'][0] != ''
        && isset($args['uri_split'][1]) && $args['uri_split'][1] == 'download'
        && isset($args['uri_split'][2]) && $args['uri_split'][2] != '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'web', 'fileDownload');
        $rc = ciniki_events_web_fileDownload($ciniki, $ciniki['request']['tnid'], $args['uri_split'][0], $args['uri_split'][2]);
        if( $rc['stat'] == 'ok' ) {
            return array('stat'=>'ok', 'download'=>$rc['file']);
        }
        
        //
        // If there was an error locating the files, display generic error
        //
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.events.64', 'msg'=>'The file you requested does not exist.'));
    }

    $display_format = 'cilist';
    if( isset($settings['page-events-display-format']) && $settings['page-events-display-format'] == 'imagelist' ) {
        $display_format = 'imagelist';
    } elseif( isset($settings['site-theme']) && $settings['site-theme'] == 'twentyone' ) {
        if( isset($settings['page-events-display-format']) && $settings['page-events-display-format'] == 'tradingcards' ) {
            $display_format = 'tradingcards';
        } else {
            $display_format = 'imagelist';
        }
    }

    //
    // Setup titles
    //
    if( isset($settings['page-events-title']) && $settings['page-events-title'] !='' ) {
        $module_title = $settings['page-events-title'];
    } elseif( isset($args['page_title']) ) {
        $module_title = $args['page_title'];
    } else {
        $module_title = 'Events';
    }

    if( $module_title == '' ) {
        $module_title = 'Events';
    }
    if( count($page['breadcrumbs']) == 0 ) {
        $page['breadcrumbs'][] = array('name'=>$module_title, 'url'=>$args['base_url']);
    }

    //
    // Setup the multipage nav
    //
    if( !isset($args['post_limit']) || $args['post_limit'] == '' ) {
        $page_nav_limit = 10;
    } else {
        $page_nav_limit = $args['post_limit'];
    }
    if( isset($ciniki['request']['args']['page']) && $ciniki['request']['args']['page'] != '' ) {
        $page_nav_cur = intval($ciniki['request']['args']['page']);
    } else {
        $page_nav_cur = 1;
    }


    $ciniki['response']['head']['og']['url'] = $args['domain_base_url'];

    //
    // Setup the base url as the base url for this page. This may be altered below
    // as the uri_split is processed, but we do not want to alter the original passed in.
    //
    $base_url = $args['base_url'];

    //
    // Check for image format
    //
    $thumbnail_format = 'square-cropped';
    $thumbnail_padding_color = '#ffffff';
    if( isset($settings['page-events-thumbnail-format']) && $settings['page-events-thumbnail-format'] == 'square-padded' ) {
        $thumbnail_format = $settings['page-events-thumbnail-format'];
        if( isset($settings['page-events-thumbnail-padding-color']) && $settings['page-events-thumbnail-padding-color'] != '' ) {
            $thumbnail_padding_color = $settings['page-events-thumbnail-padding-color'];
        } 
    }

    //
    // Parse the url to determine what was requested
    //
    $display = '';
    $tag_type = 10;
    $tag_permalink = '';

    //
    // Check if we are to display a category
    //
    if( isset($args['uri_split'][0]) && $args['uri_split'][0] == 'category' 
        && isset($args['uri_split'][1]) && $args['uri_split'][1] != '' 
        ) {
        $display = 'list';
        $tag_type = 10;
        $tag_permalink = $args['uri_split'][1];
        if( $page['title'] == '' ) {
        //    $page['title'] = 'Upcoming ' . $module_title;
        }
        //$page['breadcrumbs'][] = array('name'=>'Upcoming ' . $module_title, 'url'=>$args['base_url']);
    }

    //
    // Check if we are to display an image, from the gallery, or latest images
    //
    elseif( isset($args['uri_split'][0]) && $args['uri_split'][0] != '' ) {
        $display = 'event';
        $event_permalink = $args['uri_split'][0];

        //
        // Check if gallery image was requested
        //
        if( isset($args['uri_split'][1]) && $args['uri_split'][1] == 'gallery'
            && isset($args['uri_split'][2]) && $args['uri_split'][2] != '' 
            ) {
            $image_permalink = $args['uri_split'][2];
            $display = 'eventpic';
        }
        $ciniki['response']['head']['og']['url'] .= '/' . $event_permalink;
        $base_url .= '/' . $event_permalink;
    }

    //
    // Display the list of upcoming events
    //
    elseif( $args['module_page'] == 'ciniki.events.upcoming' ) {
        $display = 'upcoming';
    }

    //
    // Display the list of past events
    //
    elseif( $args['module_page'] == 'ciniki.events.past' ) {
        $display = 'past';
    }


    //
    // Nothing selected, default to the event list
    //
    else {
        $display = 'list';
    }

    
    //
    // Setup the event blocks for event or event picture
    //
    if( $display == 'event' || $display == 'eventpic' ) {
        //
        // Load the event to get all the details, and the list of images.
        // It's one query, and we can find the requested image, and figure out next
        // and prev from the list of images returned
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'web', 'eventDetails');
        $rc = ciniki_events_web_eventDetails($ciniki, $settings, $tnid, $event_permalink);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $event = $rc['event'];
    
//        $page['title'] = $module_title;
//        $page['breadcrumbs'][] = array('name'=>$module_title, 'url'=>$args['base_url']);

        //
        // Setup sharing information
        //
        if( isset($event['short_description']) && $event['short_description'] != '' ) {
            $ciniki['response']['head']['og']['description'] = strip_tags($event['short_description']);
        } elseif( isset($event['description']) && $event['description'] != '' ) {
            $ciniki['response']['head']['og']['description'] = strip_tags($event['description']);
        }

        //
        // Reset page title to be the event name
        //
        $page['title'] .= ($page['title']!=''?' - ':'') . $event['name'];

//      if( isset($tag_permalink) && $tag_permalink != '' ) {
//          $page['breadcrumbs'][] = array('name'=>$tag_types[$type_permalink]['name'], 'url'=>$args['base_url'] . '/' . $type_permalink);
//          $page['breadcrumbs'][] = array('name'=>$tag_name, 'url'=>$args['base_url'] . '/' . $type_permalink . '/' . urlencode($tag_name));
//      }
        $page['breadcrumbs'][] = array('name'=>$event['name'], 'url'=>$base_url);

        //
        // Meta content for under title
        //
        $page['meta_content'] = '';
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'processDateRange');
        $rc = ciniki_core_processDateRange($ciniki, $event);
        $page['article_meta'] = array();
        $page['article_meta'][] = $rc['dates'];
        if( isset($event['times']) && $event['times'] != '' ) {
            $page['article_meta'][] = $event['times'];
        }

        //
        // Setup the blocks to display the event gallery image
        //
        if( $display == 'eventpic' ) {
            
            if( !isset($event['images']) || count($event['images']) < 1 ) {
                $page['blocks'][] = array('type'=>'message', 'section'=>'event-image', 'content'=>"I'm sorry, but we can't seem to find the image you requested.");
            } else {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'galleryFindNextPrev');
                $rc = ciniki_web_galleryFindNextPrev($ciniki, $event['images'], $image_permalink);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( $rc['img'] == NULL ) {
                    $page['blocks'][] = array('type'=>'message', 'section'=>'event-image', 'content'=>"I'm sorry, but we can't seem to find the image you requested.");
                } else {
                    $page['breadcrumbs'][] = array('name'=>$rc['img']['title'], 'url'=>$base_url . '/gallery/' . $image_permalink);
                    if( $rc['img']['title'] != '' ) {
                        $page['title'] .= ' - ' . $rc['img']['title'];
                    }
                    $block = array('type'=>'galleryimage', 'section'=>'event-image', 'primary'=>'yes', 'image'=>$rc['img']);
                    if( $rc['prev'] != null ) {
                        $block['prev'] = array('url'=>$base_url . '/gallery/' . $rc['prev']['permalink'], 'image_id'=>$rc['prev']['image_id']);
                    }
                    if( $rc['next'] != null ) {
                        $block['next'] = array('url'=>$base_url . '/gallery/' . $rc['next']['permalink'], 'image_id'=>$rc['next']['image_id']);
                    }
                    $page['blocks'][] = $block;
                }
            }
        } 

        //
        // Setup the blocks to display the event
        //
        else {
            //
            // Add primary image
            //
            if( isset($event['image_id']) && $event['image_id'] > 0 ) {
                $page['blocks'][] = array('type'=>'asideimage', 'section'=>'primary-image', 'primary'=>'yes', 'image_id'=>$event['image_id'], 'title'=>$event['name'], 'caption'=>'');
            }

            //
            // Add description
            //
            $content = '';
            if( isset($event['description']) && $event['description'] != '' ) {
                $content = $event['description'];
            } elseif( isset($event['short_description']) ) {
                $content = $event['short_description'];
            }

            if( $event['url'] != '' ) {
                $content .= "\n\nWebsite: " . $event['url'];
            }

            $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>'', 'content'=>$content);

            //
            // Check if mapped tickets, otherwise add prices as list
            //
            if( ($event['reg_flags']&0x04) == 0x04 && isset($event['mappedtickets']) && count($event['mappedtickets']) > 0 ) {
                $page['blocks'][] = array('type'=>'mappedtickets', 
                    'section'=>'mappedtickets',
                    'image_id'=>$event['ticketmap1_image_id'],
                    'intro-text'=>$event['ticketmap1_ptext'],
                    'button-label'=>$event['ticketmap1_btext'],
                    'empty-text'=>$event['ticketmap1_ntext'],
                    'object'=>'ciniki.events.event',
                    'object_id'=>$event['id'],
                    'numbers'=>'yes',
                    'mappedtickets'=>$event['mappedtickets'],
                    'addons'=>(isset($event['prices']) ? $event['prices'] : array()),
                    );
            } 
            if( isset($event['prices']) && count($event['prices']) > 0 ) {
                $page['blocks'][] = array('type'=>'prices', 'section'=>'prices', 'title'=>'', 'prices'=>$event['prices']);
            }
            if( isset($event['links']) && count($event['links']) > 0 ) {
                $page['blocks'][] = array('type'=>'links', 'section'=>'links', 'title'=>'', 'links'=>$event['links']);
            }
            if( isset($event['files']) && count($event['files']) > 0 ) {
                $page['blocks'][] = array('type'=>'files', 'section'=>'files', 'title'=>'', 'base_url'=>$base_url . '/download', 'files'=>$event['files']);
            }
            if( !isset($settings['page-events-share-buttons']) || $settings['page-events-share-buttons'] == 'yes' ) {
                $tags = array();
                $page['blocks'][] = array('type'=>'sharebuttons', 'section'=>'share', 'pagetitle'=>$event['name'], 'tags'=>$tags);
            }
            if( isset($event['images']) && count($event['images']) > 0 ) {
                $page['blocks'][] = array('type'=>'gallery', 'section'=>'gallery', 'title'=>'Additional Images', 'base_url'=>$base_url . '/gallery', 'images'=>$event['images']);
            }
            if( isset($event['sponsors']) && count($event['sponsors']) > 0 ) {
                $page['blocks'][] = array('type'=>'sponsors', 'section'=>'sponsors', 'title'=>'', 'sponsors'=>$event['sponsors']);
            }
        }
    }

    //
    // Display the list of upcoming events
    //
    elseif( $display == 'upcoming' ) {
        //
        // Get any current events
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'web', 'list');
        $rc = ciniki_events_web_list($ciniki, $settings, $ciniki['request']['tnid'], 
            array('tag_type'=>$tag_type, 'tag_permalink'=>$tag_permalink, 'format'=>$display_format));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $num_upcoming = -1;
        $total_num_items = $rc['total_num_items'];
        if( isset($rc['events']) && count($rc['events']) > 0 ) {
            if( !isset($settings['page-events-single-list']) || $settings['page-events-single-list'] != 'yes' ) {
                $num_upcoming = count($rc['events']);
            }
            if( $display_format == 'imagelist' ) {
                $page['blocks'][] = array('type'=>'imagelist', 'section'=>'upcoming-events', 'noimage'=>'yes', 'base_url'=>$args['base_url'], 'list'=>$rc['events'],
                    'thumbnail_format'=>$thumbnail_format, 'thumbnail_padding_color'=>$thumbnail_padding_color);
            } elseif( $display_format == 'tradingcards' ) {
                $page['blocks'][] = array('type'=>'tradingcards', 'section'=>'upcoming-events', 'noimage'=>'yes', 'base_url'=>$args['base_url'], 'cards'=>$rc['events'],
                    'thumbnail_format'=>$thumbnail_format, 'thumbnail_padding_color'=>$thumbnail_padding_color);
            } else {
                $page['blocks'][] = array('type'=>'cilist', 'section'=>'upcoming-events', 'base_url'=>$args['base_url'], 'categories'=>$rc['events'],
                    'thumbnail_format'=>$thumbnail_format, 'thumbnail_padding_color'=>$thumbnail_padding_color);
            }
        } elseif( isset($settings['page-events-single-list']) && $settings['page-events-single-list'] == 'yes' ) {
            $page['blocks'][] = array('type'=>'message', 'section'=>'upcoming-events', 'content'=>"Currently no " . strtolower($module_title) . ".");
        } else {
            $page['blocks'][] = array('type'=>'message', 'section'=>'upcoming-events', 'content'=>"Currently no upcoming " . strtolower($module_title) . ".");
        }
    }

    //
    // Past events
    //
    elseif( $display == 'past' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'web', 'list');
        $rc = ciniki_events_web_list($ciniki, $settings, $ciniki['request']['tnid'], 
            array('type'=>'past', 'tag_type'=>$tag_type, 'tag_permalink'=>$tag_permalink, 'format'=>$display_format,
                'offset'=>(($page_nav_cur-1)*$page_nav_limit), 
                'limit'=>$page_nav_limit+1,
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $total_num_items = $rc['total_num_items'];
        if( isset($rc['events']) && count($rc['events']) > 0 ) {
            if( $display_format == 'imagelist' ) {
                $page['blocks'][] = array('type'=>'imagelist', 'section'=>'past-events', 'noimage'=>'yes', 
                    'base_url'=>$args['base_url'], 'list'=>$rc['events'], 
                    'limit'=>$page_nav_limit,
                    'thumbnail_format'=>$thumbnail_format, 'thumbnail_padding_color'=>$thumbnail_padding_color);
            } elseif( $display_format == 'tradingcards' ) {
                $page['blocks'][] = array('type'=>'tradingcards', 'section'=>'past-events', 'noimage'=>'yes', 
                    'base_url'=>$args['base_url'], 'cards'=>$rc['events'], 
                    'limit'=>$page_nav_limit,
                    'thumbnail_format'=>$thumbnail_format, 'thumbnail_padding_color'=>$thumbnail_padding_color);
            } else {
                $page['blocks'][] = array('type'=>'cilist', 'section'=>'past-events', 'base_url'=>$args['base_url'], 'categories'=>$rc['events'],
                    'thumbnail_format'=>$thumbnail_format, 'thumbnail_padding_color'=>$thumbnail_padding_color);
            }
        } else {
            $page['blocks'][] = array('type'=>'message', 'section'=>'past-events', 'content'=>"No past " . strtolower($module_title) . ".");
        }
        if( $total_num_items > $page_nav_limit ) {
            $page['blocks'][] = array('type'=>'multipagenav', 
                'cur_page'=>$page_nav_cur, 
                'total_pages'=>ceil($total_num_items/$page_nav_limit),
                'base_url'=>$args['base_url']);
        }
    }

    elseif( $display == 'list' ) {
        if( $page['title'] == '' ) {
//            $page['title'] = $module_title;
        }
//        $page['breadcrumbs'][] = array('name'=>$module_title, 'url'=>$args['base_url']);
    
        ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'web', 'list');
    
        $list_title = '';
        $current_list_title = '';
        if( isset($settings['page-events-content']) && $settings['page-events-content'] != '' && $tag_permalink == '' ) {
            if( isset($settings['page-events-image']) && $settings['page-events-image'] > 0 ) {
                $page['blocks'][] = array('type'=>'asideimage', 'section'=>'primary-image', 'primary'=>'yes', 'image_id'=>$settings['page-events-image'], 'title'=>$page['title'], 'caption'=>'');
            }
            $list_title = 'Upcoming ' . $module_title;
            $current_list_title = 'Current ' . $module_title;
            $page['title'] = $module_title;
            $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'content'=>$settings['page-events-content']);
        }

        //
        // Get any current events
        //
        $num_current = -1;
        if( isset($settings['page-events-current']) && $settings['page-events-current'] == 'yes' 
            && (!isset($settings['page-events-single-list']) || $settings['page-events-single-list'] != 'yes')
            ) {
            $rc = ciniki_events_web_list($ciniki, $settings, $ciniki['request']['tnid'], 
                array('type'=>'current', 'tag_type'=>$tag_type, 'tag_permalink'=>$tag_permalink, 
                    'format'=>$display_format,
                    ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['events']) && count($rc['events']) > 0 ) {
                $num_current = count($rc['events']);
                if( $display_format == 'imagelist' ) {
                    $page['blocks'][] = array('type'=>'imagelist', 
                        'section'=>'current-events', 
                        'noimage'=>'yes', 
                        'base_url'=>$args['base_url'], 
                        'title'=>$current_list_title,
                        'list'=>$rc['events'],
                        'thumbnail_format'=>$thumbnail_format, 
                        'thumbnail_padding_color'=>$thumbnail_padding_color,
                        );
                } elseif( $display_format == 'tradingcards' ) {
                    $page['blocks'][] = array('type'=>'tradingcards', 
                        'section'=>'current-events', 
                        'noimage'=>'yes', 
                        'base_url'=>$args['base_url'], 
                        'title'=>$current_list_title,
                        'cards'=>$rc['events'],
                        'thumbnail_format'=>$thumbnail_format, 
                        'thumbnail_padding_color'=>$thumbnail_padding_color,
                        );
                } else {
                    $page['blocks'][] = array('type'=>'cilist', 
                        'section'=>'current-events', 
                        'base_url'=>$args['base_url'], 
                        'title'=>$current_list_title,
                        'categories'=>$rc['events'],
                        'thumbnail_format'=>$thumbnail_format, 
                        'thumbnail_padding_color'=>$thumbnail_padding_color,
                        );
                }
            } else {
                $num_current = 0;
            }
        }

        //
        // Get the events
        //
        if( isset($settings['page-events-past']) && $settings['page-events-past'] == 'yes' 
            && isset($settings['page-events-single-list']) && $settings['page-events-single-list'] == 'yes' 
            ) {
            $rc = ciniki_events_web_list($ciniki, $settings, $ciniki['request']['tnid'], 
                array('type'=>'all', 'tag_type'=>$tag_type, 'tag_permalink'=>$tag_permalink, 'format'=>$display_format));
        } else {
            if( isset($settings['page-events-current']) && $settings['page-events-current'] == 'yes' ) {
                $rc = ciniki_events_web_list($ciniki, $settings, $ciniki['request']['tnid'], 
                    array('type'=>'future', 'tag_type'=>$tag_type, 'tag_permalink'=>$tag_permalink, 'format'=>$display_format));
            } else {
                $rc = ciniki_events_web_list($ciniki, $settings, $ciniki['request']['tnid'], 
                    array('type'=>'upcoming', 'tag_type'=>$tag_type, 'tag_permalink'=>$tag_permalink, 'format'=>$display_format));
            }
        }
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $num_upcoming = -1;
        if( $page_nav_cur == 1 ) {
            if( isset($rc['events']) && count($rc['events']) > 0 ) {
                if( !isset($settings['page-events-single-list']) || $settings['page-events-single-list'] != 'yes' ) {
                    $num_upcoming = count($rc['events']);
                }
                if( $display_format == 'imagelist' ) {
                    if( $num_current > 0 ) {
                        $page['blocks'][] = array('type'=>'imagelist', 'section'=>'upcoming-events', 'noimage'=>'yes', 'title'=>'Upcoming ' . $module_title, 'base_url'=>$args['base_url'], 'list'=>$rc['events']);
                    } else {
                        $page['blocks'][] = array('type'=>'imagelist', 'section'=>'upcoming-events', 'noimage'=>'yes', 'base_url'=>$args['base_url'], 'cards'=>$rc['events'],
                            'thumbnail_format'=>$thumbnail_format, 'thumbnail_padding_color'=>$thumbnail_padding_color);
                    }
                } elseif( $display_format == 'tradingcards' ) {
                    if( $num_current > 0 ) {
                        $page['blocks'][] = array('type'=>'tradingcards', 'section'=>'upcoming-events', 'noimage'=>'yes', 'title'=>'Upcoming ' . $module_title, 'base_url'=>$args['base_url'], 'cards'=>$rc['events']);
                    } else {
                        $page['blocks'][] = array('type'=>'tradingcards', 'section'=>'upcoming-events', 'noimage'=>'yes', 'base_url'=>$args['base_url'], 'cards'=>$rc['events'],
                            'thumbnail_format'=>$thumbnail_format, 'thumbnail_padding_color'=>$thumbnail_padding_color);
                    }
                } else {
                    // $page['blocks'][] = array('type'=>'cilist', 'section'=>'upcoming-events', 'title'=>'Upcoming ' . $module_title, 'base_url'=>$args['base_url'], 'categories'=>$rc['events']);
                    if( $num_current > 0 ) {
                        $page['blocks'][] = array('type'=>'cilist', 'section'=>'upcoming-events', 'title'=>'Upcoming ' . $module_title, 'base_url'=>$args['base_url'], 'categories'=>$rc['events'],
                            'thumbnail_format'=>$thumbnail_format, 'thumbnail_padding_color'=>$thumbnail_padding_color);
                    } else {
                        $page['blocks'][] = array('type'=>'cilist', 'section'=>'upcoming-events', 'title'=>$list_title, 'base_url'=>$args['base_url'], 'categories'=>$rc['events'],
                            'thumbnail_format'=>$thumbnail_format, 'thumbnail_padding_color'=>$thumbnail_padding_color);
                    }
                }
            } elseif( isset($settings['page-events-single-list']) && $settings['page-events-single-list'] == 'yes' ) {
                $page['blocks'][] = array('type'=>'message', 'section'=>'upcoming-events', 'content'=>"Currently no " . strtolower($module_title) . ".");
            } else {
                if( $num_current > 0 ) {
                    //$page['blocks'][] = array('type'=>'message', 'section'=>'upcoming-events', 'title'=>'Upcoming ' . $module_title, 'content'=>"Currently no " . strtolower($module_title) . ".");
                    $page['blocks'][] = array('type'=>'message', 'section'=>'upcoming-events', 'title'=>'Upcoming ' . $module_title, 'content'=>"Currently no upcoming " . strtolower($module_title) . ".");
                } else {
                    $page['blocks'][] = array('type'=>'message', 'section'=>'upcoming-events', 'content'=>"Currently no upcoming " . strtolower($module_title) . ".");
                }
            }

            //
            // Setup the proper title for the page, Only if not introduction content
            //
            if( !isset($settings['page-events-content']) || $settings['page-events-content'] == '' ) {
                if( $page['title'] == '' ) {
                    if( $num_current > 0 ) {
                        $page['title'] = 'Current ' . $module_title;
                    } elseif( $num_upcoming > 0 ) {
                        $page['title'] = 'Upcoming ' . $module_title;
                    } else {
                        $page['title'] = $module_title;
                    }
                } else {
                    if( $num_current > 0 ) {
                        $page['title'] = 'Current ' . $module_title;
                    } elseif( $num_upcoming > 0 ) {
                        $page['title'] = 'Upcoming ' . $module_title;
                    }
                }
            }
            if( $num_current > 0 ) {
                if( count($page['breadcrumbs']) > 0 ) {
                    if( !isset($settings['page-events-content']) || $settings['page-events-content'] == '' ) {
                        $page['breadcrumbs'][count($page['breadcrumbs'])-1]['name'] = 'Current ' . $page['breadcrumbs'][count($page['breadcrumbs'])-1]['name'];
                    } else {
                        $page['breadcrumbs'][count($page['breadcrumbs'])-1]['name'] = $page['breadcrumbs'][count($page['breadcrumbs'])-1]['name'];
                    }
                }
            } elseif( $num_upcoming > 0 ) {
                if( count($page['breadcrumbs']) > 0 
                    && (!isset($settings['page-events-content']) || $settings['page-events-content'] == '') 
                    ) {
                    $page['breadcrumbs'][count($page['breadcrumbs'])-1]['name'] = 'Upcoming ' . $page['breadcrumbs'][count($page['breadcrumbs'])-1]['name'];
                }
            }
        } else {
            $page['title'] = 'Past ' . $module_title;
            $page['breadcrumbs'][count($page['breadcrumbs'])-1]['name'] = 'Past ' . $page['breadcrumbs'][count($page['breadcrumbs'])-1]['name'];
        }
    
        //
        // Past events
        //
        if( isset($settings['page-events-past']) && $settings['page-events-past'] == 'yes' 
            && (!isset($settings['page-events-single-list']) || $settings['page-events-single-list'] != 'yes')
            ) {
            $rc = ciniki_events_web_list($ciniki, $settings, $ciniki['request']['tnid'], 
                array('type'=>'past', 'tag_type'=>$tag_type, 'tag_permalink'=>$tag_permalink, 
                    'format'=>$display_format,
                    'offset'=>(($page_nav_cur-1)*$page_nav_limit), 
                    'limit'=>$page_nav_limit+1,
                    ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $total_num_items = $rc['total_num_items'];
            if( isset($rc['events']) && count($rc['events']) > 0 ) {
                if( $display_format == 'imagelist' ) {
                    $page['blocks'][] = array('type'=>'imagelist', 'section'=>'past-events', 'noimage'=>'yes', 
                        'title'=>($page_nav_cur > 1 ? '' : 'Past ' . $module_title), 
                        'base_url'=>$args['base_url'], 'list'=>$rc['events'],
                        'limit'=>$page_nav_limit,
                        'thumbnail_format'=>$thumbnail_format, 
                        'thumbnail_padding_color'=>$thumbnail_padding_color,
                        );
                } elseif( $display_format == 'tradingcards' ) {
                    $page['blocks'][] = array('type'=>'tradingcards', 'section'=>'past-events', 'noimage'=>'yes', 
                        'title'=>($page_nav_cur > 1 ? '' : 'Past ' . $module_title), 
                        'base_url'=>$args['base_url'], 
                        'cards'=>$rc['events'],
                        'limit'=>$page_nav_limit,
                        'thumbnail_format'=>$thumbnail_format, 
                        'thumbnail_padding_color'=>$thumbnail_padding_color,
                        );
                } else {
                    $page['blocks'][] = array('type'=>'cilist', 'section'=>'past-events', 
                        'title'=>($page_nav_cur > 1 ? '' : 'Past ' . $module_title), 
                        'base_url'=>$args['base_url'], 
                        'categories'=>$rc['events'],
                        'limit'=>$page_nav_limit,
                        'thumbnail_format'=>$thumbnail_format, 
                        'thumbnail_padding_color'=>$thumbnail_padding_color,
                        );
                }
                if( $total_num_items > $page_nav_limit ) {
                    $page['blocks'][] = array('type'=>'multipagenav', 
                        'cur_page'=>$page_nav_cur, 
                        'total_pages'=>ceil($total_num_items/$page_nav_limit),
                        'base_url'=>$args['base_url']);
                }
            } else {
                $page['blocks'][] = array('type'=>'message', 'section'=>'past-events', 'content'=>"No past " . strtolower($module_title) . ".");
            }
        }
    }

    //
    // Decide what items should be in the submenu
    //
    if( ($ciniki['tenant']['modules']['ciniki.events']['flags']&0x10) > 0 
        && isset($settings['page-events-categories-display']) && $settings['page-events-categories-display'] == 'submenu'
        ) {
        if( !isset($categories) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'web', 'tags');
            $rc = ciniki_events_web_tags($ciniki, $settings, $ciniki['request']['tnid'], '10');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $categories = $rc['tags'];
        }
        if( count($categories) > 1 ) {
            $page['submenu'] = array();
            foreach($categories as $cid => $cat) {
                $page['submenu'][$cid] = array('name'=>$cat['tag_name'], 'url'=>$args['base_url'] . "/category/" . $cat['permalink']);
            }
        }
    }

    return array('stat'=>'ok', 'page'=>$page);
}
?>
