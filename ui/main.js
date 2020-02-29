//
// This app will handle the listing, additions and deletions of events.  These are associated tenant.
//
function ciniki_events_main() {
    //
    // Panels
    //
    this.regFlags = {
        '1':{'name':'Track Registrations'},
        '2':{'name':'Online Registrations'},
        };
    //
    // events panel
    //
    this.menu = new M.panel('Events', 'ciniki_events_main', 'menu', 'mc', 'medium', 'sectioned', 'ciniki.events.main.menu');
    this.menu.sections = {
        'categories':{'label':'Categories', 'aside':'yes', 'visible':'no', 'list':{
            }},
        'upcoming':{'label':'Upcoming Events', 'type':'simplegrid', 'num_cols':2,
            'headerValues':null,
            'cellClasses':['multiline center nobreak', 'multiline'],
            'noData':'No upcoming events',
            'addTxt':'Add Event',
            'addFn':'M.ciniki_events_main.edit.open(\'M.ciniki_events_main.menu.open();\',0);',
            },
        'past':{'label':'Past Events', 'type':'simplegrid', 'num_cols':2,
            'headerValues':null,
            'cellClasses':['multiline center nobreak', 'multiline'],
            'noData':'No events'
            },
        };
    this.menu.sectionData = function(s) { return this.data[s]; }
    this.menu.listValue = function(s, i, d) {
        if( d.tag.permalink == '' ) {
            return d.tag.tag_name + ' <span class="count">' + d.tag.num_events + '</span>';
        }
        return d.tag.tag_name + ' <span class="count">' + d.tag.num_upcoming_events + '</span>';
    };
    this.menu.listFn = function(s, i, d) {
        return 'M.ciniki_events_main.menu.open(null,\'' + d.tag.permalink + '\');';
    };
    this.menu.noData = function(s) { return this.sections[s].noData; }
    this.menu.cellValue = function(s, i, j, d) {
        if( j == 0 ) {
            if( d.event.start_date != null && d.event.start_date != '' && d.event.end_date != null && d.event.end_date != '' ) {
                return '<span class="maintext">' + d.event.start_date.replace(' ', '&nbsp;') + '</span>'
                    + '<span class="subtext">' + d.event.end_date + '</span>';
            }
            if( d.event.start_date == null || d.event.start_date == '' ) {
                return '<span class="maintext">???</span><span class="subtext">&nbsp;</span>';
            }
            return '<span class="maintext">' + d.event.start_date.replace(' ', '&nbsp;') + '</span><span class="subtext">&nbsp;</span>';
        }
        if( j == 1 ) {
//              var reg = '';
//              if( d.event.tickets_sold != null && d.event.num_tickets != null ) {
//                  reg = ' [' + d.event.tickets_sold + '/' + d.event.num_tickets + ']';
//              }
            return '<span class="maintext">' + d.event.name + '</span>'
                + '<span class="subtext singleline"> </span>';
        }
    };
    this.menu.rowFn = function(s, i, d) {
        return 'M.ciniki_events_main.event.open(\'M.ciniki_events_main.menu.open();\',\'' + d.event.id + '\');';
    };
    this.menu.open = function(cb, cat) {
        this.data = {};
        if( cat != null ) {
            this.tag_type = 10;
            this.tag_permalink = cat;
        }
        if( this.rightbuttons.edit != null ) { delete(this.rightbuttons.edit); }
        if( (M.curTenant.modules['ciniki.events'].flags&0x10) > 0 ) {
            M.api.getJSONCb('ciniki.events.eventList', {'tnid':M.curTenantID, 'categories':'yes', 
                'tag_type':this.tag_type, 'tag_permalink':this.tag_permalink}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_events_main.menu;
                    p.data = rsp;
                    if( rsp.tag_name != null && rsp.tag_name != 'Uncategorized' ) {
                        p.addButton('edit', 'Edit', 'M.startApp(\'ciniki.events.tags\',null,\'M.ciniki_events_main.menu.show();\',\'mc\',{\'tag_type\':\'10\',\'tag_permalink\':\'' + rsp.tag_permalink + '\'});');
                    }
                    p.sections.upcoming.label = rsp.tag_name + ' - Upcoming Events';
                    p.sections.past.label = rsp.tag_name + ' - Past Events';
                    p.refreshSection('categories');
                    p.refreshSection('upcoming');
                    p.refreshSection('past');
                    p.show(cb);
                });
        } else {
            M.api.getJSONCb('ciniki.events.eventList', {'tnid':M.curTenantID}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_events_main.menu;
                p.data['upcoming'] = rsp.upcoming;
                p.data['past'] = rsp.past;
                p.refresh();
                p.show(cb);
            });
        }
    };
    this.menu.addButton('add', 'Add', 'M.ciniki_events_main.edit.open(\'M.ciniki_events_main.menu.open();\',0);');
    this.menu.addClose('Back');

    //
    // The event panel 
    //
    this.event = new M.panel('Event', 'ciniki_events_main', 'event', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.events.main.event');
    this.event.data = {};
    this.event.event_id = 0;
    this.event.sections = {
        '_image':{'label':'', 'aside':'yes', 'type':'imageform', 'fields':{
            'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'history':'no'},
            }},
        'info':{'label':'', 'aside':'yes', 'list':{
            'name':{'label':'Name'},
            'start_date':{'label':'Start'},
            'end_date':{'label':'End'},
            'times':{'label':'Hours'},
            'url':{'label':'Website'},
            'categories_text':{'label':'Categories', 'visible':'no'},
            'webcollections_text':{'label':'Web Collections'},
            }},
        '_registrations':{'label':'', 'aside':'yes', 'hidelabel':'yes', 
            'visible':function() {return (M.ciniki_events_main.event.data.reg_flags&0x03) > 0 ? 'yes' : 'no'; },
            'list':{
                'registrations':{'label':'Tickets'},
            }},
        '_ticketmap':{'label':'Ticket Map', 'aside':'yes', 'type':'html',
            'visible':function() {return (M.ciniki_events_main.event.data.reg_flags&0x04) == 0x04 ? 'yes' : 'no'; },
            },
/*        '_ticketmap':{'label':'', 'aside':'yes', 'type':'imageform', 
            'visible':function() {return (M.ciniki_events_main.event.data.reg_flags&0x04) == 0x04 ? 'yes' : 'no'; },
            'fields':{
                'ticketmap1_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'history':'no'},
            }}, */
        '_ticketmap_buttons':{'label':'', 'aside':'yes', 'hidelabel':'yes', 
            'visible':function() {return (M.ciniki_events_main.event.data.reg_flags&0x04) == 0x04 ? 'yes' : 'no'; },
            'buttons':{
                'ticketmapedit':{'label':'Edit Ticket Map', 'fn':'M.ciniki_events_main.ticketmap.open(\'M.ciniki_events_main.event.open();\',M.ciniki_events_main.event.event_id);'},
            }},
        'description':{'label':'Synopsis', 'type':'htmlcontent'},
        'long_description':{'label':'Description', 'type':'htmlcontent'},
        'prices':{'label':'Price List', 'type':'simplegrid', 'num_cols':2,
            'headerValues':null,
            'cellClasses':['', ''],
            'noData':'No prices',
            'addTxt':'Add Price',
            'addFn':'M.startApp(\'ciniki.events.prices\',null,\'M.ciniki_events_main.event.open();\',\'mc\',{\'event_id\':M.ciniki_events_main.event.event_id,\'price_id\':\'0\'});',
        },
        'links':{'label':'Links', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'cellClasses':['multiline'],
            'noData':'No event links',
            'addTxt':'Add Link',
            'addFn':'M.startApp(\'ciniki.events.links\',null,\'M.ciniki_events_main.event.open();\',\'mc\',{\'event_id\':M.ciniki_events_main.event.event_id,\'add\':\'yes\'});',
            },
        'files':{'label':'Files', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'cellClasses':['multiline'],
            'noData':'No event files',
            'addTxt':'Add File',
            'addFn':'M.startApp(\'ciniki.events.files\',null,\'M.ciniki_events_main.event.open();\',\'mc\',{\'event_id\':M.ciniki_events_main.event.event_id,\'add\':\'yes\'});',
        },
        'images':{'label':'Gallery', 'type':'simplethumbs'},
        '_images':{'label':'', 'type':'simplegrid', 'num_cols':1,
            'addTxt':'Add Additional Image',
            'addFn':'M.startApp(\'ciniki.events.images\',null,\'M.ciniki_events_main.event.open();\',\'mc\',{\'event_id\':M.ciniki_events_main.event.event_id,\'add\':\'yes\'});',
            },
        'sponsors':{'label':'Sponsors', 'type':'simplegrid', 'num_cols':1,
            'addTxt':'Manage Sponsors',
            'addFn':'M.startApp(\'ciniki.sponsors.ref\',null,\'M.ciniki_events_main.event.open();\',\'mc\',{\'object\':\'ciniki.events.event\',\'object_id\':M.ciniki_events_main.event.event_id});',
            },
        '_buttons':{'label':'', 'buttons':{
            'edit':{'label':'Edit', 'fn':'M.ciniki_events_main.edit.open(\'M.ciniki_events_main.event.open();\',M.ciniki_events_main.event.event_id);'},
            }},
    };
    this.event.addDropImage = function(iid) {
        var rsp = M.api.getJSON('ciniki.events.imageAdd',
            {'tnid':M.curTenantID, 'image_id':iid, 'event_id':M.ciniki_events_main.event.event_id});
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        return true;
    };
    this.event.addDropImageRefresh = function() {
        if( M.ciniki_events_main.event.event_id > 0 ) {
            M.api.getJSONCb('ciniki.events.eventGet', {'tnid':M.curTenantID, 'event_id':M.ciniki_events_main.event.event_id, 'images':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_events_main.event.data.images = rsp.event.images;
                M.ciniki_events_main.event.refreshSection('images');
            });
        }
    };
    this.event.sectionData = function(s) {
        if( s == 'description' || s == 'long_description' ) { return this.data[s].replace(/\n/g, '<br/>'); }
        if( s == 'info' || s == '_registrations' ) { return this.sections[s].list; }
        if( s == '_ticketmap' ) { return this.generateSVG(); }
        return this.data[s];
    };
    this.event.listLabel = function(s, i, d) { return d.label; };
    this.event.listValue = function(s, i, d) {
        if( i == 'registrations' ) {
            return this.data['tickets_sold'] + ' of ' + this.data['num_tickets'] + ' sold';
        }
        if( i == 'url' && this.data[i] != '' ) {
            return '<a target="_blank" href="http://' + this.data[i] + '">' + this.data[i] + '</a>';
        }
        return this.data[i];
    };
    this.event.listFn = function(s, i, d) {
        if( i == 'registrations' ) {
            return 'M.startApp(\'ciniki.events.registrations\',null,\'M.ciniki_events_main.event.open();\',\'mc\',{\'event_id\':\'' + M.ciniki_events_main.event.event_id + '\'});';
        }
        return null;
    };
    this.event.fieldValue = function(s, i, d) {
        return this.data[i];
    };
    this.event.cellValue = function(s, i, j, d) {
        if( s == 'prices' ) { 
            switch(j) {
                case 0: return d.price.name + ' <span class="subdue">(' + d.price.available_to_text + ')</span>';
                case 1: return d.price.unit_amount_display;
            }
        }
        if( s == 'links' && j == 0 ) {
            return '<span class="maintext">' + d.link.name + '</span><span class="subtext">' + d.link.url + '</span>';
        }
        if( s == 'files' && j == 0 ) { 
            return '<span class="maintext">' + d.file.name + '</span>';
        }
        if( s == 'sponsors' && j == 0 ) { 
            return '<span class="maintext">' + d.sponsor.title + '</span>';
        }
    };
    this.event.rowFn = function(s, i, d) {
        if( s == 'prices' ) {
            return 'M.startApp(\'ciniki.events.prices\',null,\'M.ciniki_events_main.event.open();\',\'mc\',{\'price_id\':\'' + d.price.id + '\',\'event_id\':\'0\'});';
        }
        if( s == 'links' ) {
            return 'M.startApp(\'ciniki.events.links\',null,\'M.ciniki_events_main.event.open();\',\'mc\',{\'link_id\':\'' + d.link.id + '\'});';
        }
        if( s == 'files' ) {
            return 'M.startApp(\'ciniki.events.files\',null,\'M.ciniki_events_main.event.open();\',\'mc\',{\'file_id\':\'' + d.file.id + '\'});';
        }
        if( s == 'sponsors' ) {
            return 'M.startApp(\'ciniki.sponsors.ref\',null,\'M.ciniki_events_main.event.open();\',\'mc\',{\'ref_id\':\'' + d.sponsor.ref_id + '\'});';
        }
    };
    this.event.thumbFn = function(s, i, d) {
        return 'M.startApp(\'ciniki.events.images\',null,\'M.ciniki_events_main.event.open();\',\'mc\',{\'event_image_id\':\'' + d.image.id + '\'});';
    };
    this.event.generateSVG = function() {
        var svg = '<svg id="' + this.panelUID + '_map_image" viewBox="0 0 993 697">';
        svg += '<image x=\'0\' y=\'0\' xlink:href=\'' + M.api.getBinaryURL('ciniki.images.get', {'tnid':M.curTenantID, 'image_id':this.data.ticketmap1_image_id, 'version':'original', 'maxwidth':'0', 'maxheight':'0'}) + '\' />';
        for(var i in this.data.tickets) {
            var t = this.data.tickets[i];
            svg += '<circle id="' + this.panelUID + '_price_' + t.id + '" '
                + 'cx="' + t.position_x + '" '
                + 'cy="' + t.position_y + '" '
                + 'r="' + t.diameter + '" '
                + 'stroke="green" stroke-width="0" '
                + 'fill="' + ((t.webflags&0x04) == 0x04 ? 'red' : 'blue') + '" />';
        }
        svg += '</svg>';

        return svg;
    }
    this.event.open = function(cb, eid) {
        this.reset();
        if( eid != null ) { this.event_id = eid; }
        M.api.getJSONCb('ciniki.events.eventGet', {'tnid':M.curTenantID, 
            'event_id':this.event_id, 'images':'yes', 'files':'yes', 'prices':'yes', 
            'sponsors':'yes', 'webcollections':'yes', 'categories':'yes', 'links':'yes', 'ticketmap':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_events_main.event;
                p.data = rsp.event;
                if( rsp.event.end_date != null && rsp.event.end_date != '' ) {
                    p.sections.info.list.end_date.visible = 'yes';
                } else {
                    p.sections.info.list.end_date.visible = 'no';
                }
                if( rsp.event.categories != null ) {
                    p.data.categories_text = rsp.event.categories.replace(/::/, ', ');
                }
                p.sections.info.list.times.visible=(rsp.event.times!=null&&rsp.event.times!='')?'yes':'no';
                if( rsp.event.url != null && rsp.event.url != '' ) {
                    p.sections.info.list.url.visible = 'yes';
                } else {
                    p.sections.info.list.url.visible = 'no';
                }
//                if( (rsp.event.reg_flags&0x03) > 0 ) {
//                    p.sections._registrations.visible = 'yes';
//                } else {
//                    p.sections._registrations.visible = 'no';
//                }
                p.refresh();
                p.show(cb);
            });
    };
    this.event.addButton('edit', 'Edit', 'M.ciniki_events_main.edit.open(\'M.ciniki_events_main.event.open();\',M.ciniki_events_main.event.event_id);');
    this.event.addClose('Back');
    this.event.addLeftButton('website', 'Preview', 'M.showWebsite(\'/events/\'+M.ciniki_events_main.event.data.permalink);');

    //
    // The panel for a site's menu
    //
    this.edit = new M.panel('Event', 'ciniki_events_main', 'edit', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.events.main.edit');
    this.edit.data = null;
    this.edit.event_id = 0;
    this.edit.sections = { 
        '_image':{'label':'Image', 'type':'imageform', 'aside':'yes', 'fields':{
            'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
            }},
        'general':{'label':'General', 'aside':'yes', 'fields':{
            'name':{'label':'Name', 'hint':'Events name', 'required':'yes', 'type':'text'},
            'url':{'label':'URL', 'hint':'Enter the http:// address for your events website', 'type':'text'},
            'start_date':{'label':'Start', 'required':'yes', 'type':'date'},
            'end_date':{'label':'End', 'type':'date'},
            'times':{'label':'Hours', 'type':'text'},
            'oidref':{'label':'Exhibition', 'active':'no', 'type':'select', 'options':{}},
            'flags':{'label':'Website', 'type':'flags', 'flags':{'1':{'name':'Visible'}}},
            'flags2':{'label':'Web Calendar', 'active':'no', 'type':'flagspiece', 'field':'flags', 'mask':0x02, 'flags':{'2':{'name':'Visible'}}},
            }}, 
        '_categories':{'label':'Categories', 'aside':'yes', 'active':'no', 'fields':{
            'categories':{'label':'', 'hidelabel':'yes', 'type':'tags', 'tags':[], 'hint':'Enter a new category: '},
            }},
        '_webcollections':{'label':'Web Collections', 'aside':'yes', 'active':'no', 'fields':{
            'webcollections':{'label':'', 'hidelabel':'yes', 'type':'collection'},
            }},
        '_registrations':{'label':'Registrations', 'aside':'yes', 'visible':'no', 'fields':{
            'reg_flags':{'label':'Options', 'active':'no', 'type':'flags', 'joined':'no', 'flags':this.regFlags},
            'num_tickets':{'label':'Number of Tickets', 'active':'no', 'type':'text', 'size':'small'},
            }},
        '_description':{'label':'Synopsis', 'fields':{
            'description':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'small', 'type':'textarea'},
            }},
        '_long_description':{'label':'Description', 'fields':{
            'long_description':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'large', 'type':'textarea'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_events_main.edit.save();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_events_main.edit.remove();'},
            }},
        };  
    this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
    this.edit.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.events.eventHistory', 'args':{'tnid':M.curTenantID, 'event_id':this.event_id, 'field':i}};
    }
    this.edit.addDropImage = function(iid) {
        M.ciniki_events_main.edit.setFieldValue('primary_image_id', iid, null, null);
        return true;
    };
    this.edit.deleteImage = function(fid) {
        this.setFieldValue(fid, 0, null, null);
        return true;
    };
    this.edit.open = function(cb, eid) {
        this.reset();
        if( eid != null ) { this.event_id = eid; }
        if( (M.curTenant.modules['ciniki.events'].flags&0x03) > 0 ) {
            this.sections._registrations.visible = 'yes';
            this.sections._registrations.fields.reg_flags.active = 'yes';
            this.sections._registrations.fields.num_tickets.active = 'yes';
        } else {
            this.sections._registrations.visible = 'no';
            this.sections._registrations.fields.reg_flags.active = 'no';
            this.sections._registrations.fields.num_tickets.active = 'no';
        }

        this.sections._buttons.buttons.delete.visible = (this.event_id>0?'yes':'no');
        this.reset();
        this.sections._buttons.buttons.delete.visible = 'yes';
        M.api.getJSONCb('ciniki.events.eventGet', {'tnid':M.curTenantID, 
            'event_id':this.event_id, 'webcollections':'yes', 'categories':'yes', 'objects':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_events_main.edit;
                p.data = rsp.event;
                p.sections._categories.fields.categories.tags = [];
                if( rsp.categories != null ) {
                    for(i in rsp.categories) {
                        p.sections._categories.fields.categories.tags.push(rsp.categories[i].tag.name);
                    }
                }
                p.sections.general.fields.oidref.options = {'':'None'};
                p.sections.general.fields.oidref.active = 'no';
                if( M.curTenant.modules['ciniki.artcatalog'] != null ) {
                    if( rsp.objects != null ) {
                        for(i in rsp.objects) {
                            p.sections.general.fields.oidref.options[rsp.objects[i].object.id] = rsp.objects[i].object.name;
                            p.sections.general.fields.oidref.active = 'yes';
                        }
                    }
                }
                p.refresh();
                p.show(cb);
            });
    };
    this.edit.save = function() {
        if( this.event_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.events.eventUpdate', {'tnid':M.curTenantID, 'event_id':M.ciniki_events_main.edit.event_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_events_main.edit.close();
                });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.events.eventAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    if( rsp.id > 0 ) {
                        var cb = M.ciniki_events_main.edit.cb;
                        M.ciniki_events_main.edit.close();
                        M.ciniki_events_main.event.open(cb,rsp.id);
                    } else {
                        M.ciniki_events_main.edit.close();
                    }
                });
            } else {
                this.close();
            }
        }
    };
    this.edit.remove = function() {
        if( confirm("Are you sure you want to remove '" + this.data.name + "' as an event ?") ) {
            M.api.getJSONCb('ciniki.events.eventDelete', {'tnid':M.curTenantID, 'event_id':this.event_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_events_main.event.close();
            });
        }
    }
    this.edit.addButton('save', 'Save', 'M.ciniki_events_main.edit.save();');
    this.edit.addClose('Cancel');

    //
    // The ticketmap edit panel
    //
    this.ticketmap = new M.panel('Ticket Map', 'ciniki_events_main', 'ticketmap', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.events.main.ticketmap');
    this.ticketmap.data = null;
    this.ticketmap.event_id = 0;
    this.ticketmap.sections = { 
//        '_image':{'label':'Ticket Map', 'type':'imageform', 'aside':'yes', 'fields':{
//            'ticketmap1_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
//            }},
        '_ticketmap':{'label':'Ticket Map', 'aside':'yes', 'type':'html'},
        '_buttons1':{'label':'', 'aside':'yes', 'buttons':{
            'changeimage':{'label':'Change Image', 'fn':'M.ciniki_events_main.ticketmap.uploadFile("ticketmap1_image_id");'},
            }},
        '_details':{'label':'Price Label', 'aside':'yes', 'fields':{
            'ticketmap1_ptext':{'label':'Price Name', 'type':'text'},
            'ticketmap1_btext':{'label':'Button Label', 'type':'text'},
            'ticketmap1_ntext':{'label':'None Selected', 'type':'text'},
            }},
        '_buttons2':{'label':'', 'aside':'yes', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_events_main.ticketmap.save();'},
            }},
        'tickets':{'label':'Tickets', 'type':'simplegrid', 'num_cols':2,
            'headerValues':null,
            'cellClasses':['', ''],
            'noData':'No tickets added',
            'addTxt':'Add Ticket',
            'addFn':'M.ciniki_events_main.ticketmap.save("M.ciniki_events_main.mapticket.open(\'M.ciniki_events_main.ticketmap.open();\',0,M.ciniki_events_main.ticketmap.event_id);");',
        },
    }  
    this.ticketmap.sectionData = function(s) {
        if( s == '_ticketmap' ) {
            return this.generateSVG();
        }
        return this.data[s];
    }
    this.ticketmap.generateSVG = function() {
        var svg = '<svg id="' + this.panelUID + '_map_image" viewBox="0 0 993 697">';
        svg += '<image x=\'0\' y=\'0\' xlink:href=\'' + M.api.getBinaryURL('ciniki.images.get', {'tnid':M.curTenantID, 'image_id':this.data.ticketmap1_image_id, 'version':'original', 'maxwidth':'0', 'maxheight':'0'}) + '\' />';
        for(var i in this.data.tickets) {
            var t = this.data.tickets[i];
            svg += '<circle id="' + this.panelUID + '_price_' + t.id + '" '
                + 'cx="' + t.position_x + '" '
                + 'cy="' + t.position_y + '" '
                + 'r="' + t.diameter + '" '
                + 'stroke="green" stroke-width="0" '
                + 'fill="' + ((t.webflags&0x04) == 0x04 ? 'red' : 'blue') + '" />';
        }
        svg += '</svg>';
        svg += '<input id="' + this.panelUID + '_ticketmap1_image_id_upload" class="image_uploader" name="ticketmap1_image_id" type="file" onchange="' + this.panelRef + '.uploadDropImages(\'ticketmap1_image_id\');"/>';

        return svg;
    }
    this.ticketmap.fieldValue = function(s, i, d) { return this.data[i]; }
//    this.ticketmap.fieldHistoryArgs = function(s, i) {
//        return {'method':'ciniki.events.eventHistory', 'args':{'tnid':M.curTenantID, 'event_id':this.event_id, 'field':i}};
//    }
    this.ticketmap.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return d.name;
        }
    }
    this.ticketmap.rowFn = function(s, i, d) {
        return 'M.ciniki_events_main.ticketmap.save("M.ciniki_events_main.mapticket.open(\'M.ciniki_events_main.ticketmap.open();\',\'' + d.id + '\',M.ciniki_events_main.ticketmap.event_id);");';
    }
    this.ticketmap.addDropImage = function(iid) {
        var args = {
            'tnid':M.curTenantID, 
            'event_id':M.ciniki_events_main.ticketmap.event_id,
            'ticketmap1_image_id':iid,
            };
        M.api.getJSONCb('ciniki.events.eventUpdate', args, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            } 
            M.ciniki_events_main.ticketmap.open();
        });
    }
    this.ticketmap.deleteImage = function(fid) {
        var args = {
            'tnid':M.curTenantID, 
            'event_id':M.ciniki_events_main.ticketmap.event_id,
            'ticketmap1_image_id':i,
            };
        M.api.getJSONCb('ciniki.events.eventUpdate', args, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            } 
            M.ciniki_events_main.ticketmap.open();
        });
    }
    this.ticketmap.open = function(cb, eid) {
        if( eid != null ) { this.event_id = eid; }
        M.api.getJSONCb('ciniki.events.eventGet', {'tnid':M.curTenantID, 'event_id':this.event_id, 'ticketmap':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_events_main.ticketmap;
            p.data = rsp.event;
            p.refresh();
            p.show(cb);
        });
    }
    this.ticketmap.save = function(cb) {
        if( cb == null ) {
            cb = 'M.ciniki_events_main.ticketmap.close();';
        }
        var c = this.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.events.eventUpdate', {'tnid':M.curTenantID, 'event_id':M.ciniki_events_main.ticketmap.event_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                eval(cb);
            });
        } else {
            eval(cb);
        }
    };
    this.ticketmap.addButton('save', 'Save', 'M.ciniki_events_main.ticketmap.save();');
    this.ticketmap.addClose('Back');

    //
    // The panel to add/edit a mapped ticket
    //
    this.mapticket = new M.panel('Ticket', 'ciniki_events_main', 'mapticket', 'mc', 'large mediumaside', 'sectioned', 'ciniki.events.main.mapticket');
    this.mapticket.data = null;
    this.mapticket.event_id = 0;
    this.mapticket.price_id = 0;
    this.mapticket.sections = { 
        'price':{'label':'Price', 'aside':'yes', 'fields':{
            'name':{'label':'Name', 'type':'text'},
            'available_to':{'label':'Available', 'type':'flags', 'default':'1', 'flags':{}},
            'unit_amount':{'label':'Unit Amount', 'type':'text', 'size':'small'},
            'unit_discount_amount':{'label':'Discount Amount', 'type':'text', 'size':'small'},
            'unit_discount_percentage':{'label':'Discount Percent', 'type':'text', 'size':'small'},
            'unit_donation_amount':{'label':'Donation Portion', 'type':'text', 'size':'small',
                'visible':function() {return M.modFlagSet('ciniki.sapos', 0x04000000);},
                },
            'taxtype_id':{'label':'Taxes', 'active':'no', 'type':'select', 'options':{}},
            'webflags':{'label':'Web', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':{}},
            'webflags2':{'label':'Individual Ticket', 'type':'flagtoggle', 'bit':0x02, 'field':'webflags', 'default':'on',
                'visible':function() {return 'no';},
                },
            'webflags3':{'label':'Sold', 'type':'flagtoggle', 'bit':0x04, 'field':'webflags', 'default':'off',
                'onchange':'M.ciniki_events_main.mapticket.updateSVG',
//                'visible':function() {return M.modFlagSet('ciniki.events', 0x08);},
                },
            'webflags4':{'label':'Mapped Ticket', 'type':'flagtoggle', 'bit':0x08, 'field':'webflags', 'default':'on',
                'visible':function() {return 'no';},
                },
            }},
        'position':{'label':'Position', 'aside':'yes', 'fields':{
            'position_num':{'label':'Number', 'type':'text', 'size':'small'},
            'position_x':{'label':'X', 'type':'text', 'size':'small', 'onkeyup':'M.ciniki_events_main.mapticket.updateSVG'},
            'position_y':{'label':'Y', 'type':'text', 'size':'small', 'onkeyup':'M.ciniki_events_main.mapticket.updateSVG'},
            'diameter':{'label':'Diameter', 'type':'text', 'size':'small', 'onkeyup':'M.ciniki_events_main.mapticket.updateSVG'},
            }},
        '_buttons':{'label':'', 'aside':'yes', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_events_main.mapticket.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_events_main.mapticket.price_id > 0 ? 'yes' : 'no'},
                'fn':'M.ciniki_events_main.mapticket.remove();',
                },
            }},
        '_ticketmap':{'label':'Ticket Map', 'type':'html'},
/*        '_image':{'label':'Ticket Map', 'type':'imageform', 'fields':{
            'ticketmap1_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'size':'large', 'controls':'no', 
                'history':'no',
                'onclick':'M.ciniki_events_main.mapticket.selectCoords',
                },
            }},  */
        };  
    this.mapticket.fieldValue = function(s, i, d) { return this.data[i]; }
    this.mapticket.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.events.priceHistory', 'args':{'tnid':M.curTenantID, 'price_id':this.price_id, 'event_id':this.event_id, 'field':i}};
    }
    this.mapticket.sectionData = function(s) {
        if( s == '_ticketmap' ) {
            return this.generateSVG();
        }
        return this.data[s];
    }
    this.mapticket.generateSVG = function() {
        var svg = '<svg id="' + this.panelUID + '_map_image" viewBox="0 0 993 697" contenteditable="true">';
        svg += '<image x=\'0\' y=\'0\' xlink:href=\'' + M.api.getBinaryURL('ciniki.images.get', {'tnid':M.curTenantID, 'image_id':this.data.ticketmap1_image_id, 'version':'original', 'maxwidth':'0', 'maxheight':'0'}) + '\' onclick="M.ciniki_events_main.mapticket.selectCoords(event);" contenteditable="yes" />';
        var s = this.formValue('webflags3');
        for(var i in this.data.tickets) {
            var t = this.data.tickets[i];
            svg += '<circle id="' + this.panelUID + '_price_' + t.id + '" '
                + 'cx="' + t.position_x + '" '
                + 'cy="' + t.position_y + '" '
                + 'r="' + t.diameter + '" '
                + 'stroke="orange" stroke-width="' + (t.id == this.price_id ? 0 : 0) + '" '
                + (t.id != this.price_id ? 'onclick="M.ciniki_events_main.mapticket.switchTicket(' + t.id + ');" ' : '')
//                + 'fill="' + (s == 'off' ? 'blue' : 'red') + '" />';
                + 'fill="' + ((t.id == this.price_id && s == 'on') || (t.webflags&0x04) == 0x04 ? 'red' : 'blue') + '" />';
        }
        svg += '</svg>';

        return svg;
    }
    this.mapticket.rowFn = function(s, i, d) { return ''; }
    this.mapticket.switchTicket = function(id) {
        this.save('M.ciniki_events_main.mapticket.open(null,' + id + ');');
    }
    this.mapticket.selectCoords = function(e) {
        // Check for circle existing in SVG
        var dim = e.target.getBoundingClientRect();
        var x = Math.round((e.offsetX/dim.width)*993, 0);
        var y = Math.round((e.offsetY/dim.height)*697, 0);
        this.setFieldValue('position_x', x);
        this.setFieldValue('position_y', y);
        this.updateSVG();
    }
    this.mapticket.updateSVG = function(e) {
        console.log('update');
        if( e != null ) {
            e.stopPropagation();
        }
        var p = M.gE(this.panelUID + '_price_' + this.price_id);
        if( p == null ) {
            var s = M.gE(this.panelUID + '_map_image');
            var p = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            p.setAttribute('id', this.panelUID + '_price_' + this.price_id);
            s.appendChild(p);
        }

        var x = this.formValue('position_x');
        var y = this.formValue('position_y');
        var r = this.formValue('diameter');
        if( r == '' ) {
            r = 0;
        }
        if( e != null && e.keyCode >= 37 && e.keyCode <= 40 ) {
            if( e.target.id == this.panelUID + '_diameter' ) {
                if( e.keyCode == 38 && r != '' ) {
                    r++;
                } else if( e.keyCode == 40 && r > 0 ) {
                    r--;
                }
            } else {
                if( e.keyCode == 38 && y != '' && y > 1 ) {
                    y--;
                } else if( e.keyCode == 40 && y != '' ) {
                    y++;
                }
                if( e.keyCode == 37 && x != '' ) {
                    x--;
                } else if( e.keyCode == 39 && x != '' && x > 1 ) {
                    x++;
                }
            }
            this.setFieldValue('position_x', x);
            this.setFieldValue('position_y', y);
            this.setFieldValue('diameter', r);
        }
        p.setAttribute("cx", x);
        p.setAttribute("cy", y);
        p.setAttribute("stroke", "green");
        p.setAttribute("stroke-width", "0");
        var f = this.formValue('webflags3');
        if( f == 'on' ) {
            p.setAttribute("fill", "red");
        } else {
            p.setAttribute("fill", "blue");
        }
        p.setAttribute("r", r);

/*        var bordersize = ((e.target.clientWidth - e.target.width)/2);
        var x = (e.offsetX-bordersize);
        var y = (e.offsetY-bordersize);
        if( x < 0 ) { x = 0; }
        if( y < 0 ) { y = 0; }
        if( x > 0 ) {
            x = (x/e.target.clientWidth)*e.target.naturalWidth;
        }
        if( x > 0 ) {
            y = (y/e.target.clientHeight)*e.target.naturalHeight;
        } */
    }
    this.mapticket.open = function(cb, pid, eid) {
        this.reset();
        if( pid != null ) { this.price_id = pid; }
        if( eid != null ) { this.event_id = eid; }

        // Check if this is editing a existing price or adding a new one
        if( this.price_id > 0 ) {
            this.sections._buttons.buttons.delete.visible = 'yes';
        }
        M.api.getJSONCb('ciniki.events.priceGet', {'tnid':M.curTenantID, 
            'event_id':this.event_id, 'price_id':this.price_id, 'ticketmap':'1'}, 
            function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_events_main.mapticket;
                p.data = rsp.price;
                p.data.tickets = rsp.tickets;
                p.refresh();
                p.show(cb);
                var m = M.gE(p.panelUID + '_map_image');
                m.contenteditable = true;
                document.body.addEventListener('keyup', function(e) {
                    M.ciniki_events_main.mapticket.updateSVG(e);
                    });
            });
    };
    this.mapticket.save = function(cb) {
        if( cb == null ) {
            cb = 'M.ciniki_events_main.mapticket.close();';
        }
        if( this.price_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.events.priceUpdate', {'tnid':M.curTenantID, 'price_id':M.ciniki_events_main.mapticket.price_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.events.priceAdd', {'tnid':M.curTenantID, 'event_id':this.event_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                eval(cb);
            });
        }
    };
    this.mapticket.remove = function() {
        if( confirm("Are you sure you want to remove this price?") ) {
            M.api.getJSONCb('ciniki.events.priceDelete', {'tnid':M.curTenantID, 'price_id':this.price_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_events_main.mapticket.close();    
            });
        }
    };
    this.mapticket.addButton('save', 'Save', 'M.ciniki_events_main.mapticket.save();');
    this.mapticket.addClose('Cancel');

    //
    // Arguments:
    // aG - The arguments to be parsed into args
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_events_main', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        //
        // Check if ticket mapping and grouping is turned on
        //
        this.regFlags = {
            '1':{'name':'Track Registrations'},
            '2':{'name':'Online Registrations'},
            };
        if( M.modFlagOn('ciniki.events', 0x0100) ) {
            this.regFlags['3'] = {'name':'Seating Maps'};
        }
        if( M.modFlagOn('ciniki.events', 0x0200) ) {
            this.regFlags['4'] = {'name':'Ticket Groups'};
        }
        this.edit.sections._registrations.fields.reg_flags.flags = this.regFlags;
        if( M.curTenant.modules['ciniki.sponsors'] != null 
            && (M.curTenant.modules['ciniki.sponsors'].flags&0x02) ) {
            this.event.sections.sponsors.visible = 'yes';
        } else {
            this.event.sections.sponsors.visible = 'no';
        }

        if( M.curTenant.modules['ciniki.artcatalog'] != null ) {
            this.edit.sections.general.fields.oidref.active = 'yes';
        } else {
            this.edit.sections.general.fields.oidref.active = 'no';
        }
        if( M.curTenant.modules['ciniki.calendars'] != null ) {
            this.edit.sections.general.fields.flags2.active = 'yes';
        } else {
            this.edit.sections.general.fields.flags2.active = 'no';
        }

        //
        // Check if event categories is enabled
        //
        if( M.curTenant.modules['ciniki.events'] != null 
            && (M.curTenant.modules['ciniki.events'].flags&0x10) ) {
            this.menu.size = 'medium narrowaside';
            this.menu.sections.categories.visible = 'yes';
            this.event.sections.info.list.categories_text.visible = 'yes';
            this.edit.sections._categories.active = 'yes';
        } else {
            this.menu.size = 'medium';
            this.menu.sections.categories.visible = 'no';
            this.event.sections.info.list.categories_text.visible = 'no';
            this.edit.sections._categories.active = 'no';
        }
        //
        // Check if accounting is enabled
        //
        if( M.curTenant.modules['ciniki.sapos'] != null ) {
            this.event.sections.prices.visible = 'yes';
        } else {
            this.event.sections.prices.visible = 'no';
        }

        //
        // Check if web collections are enabled
        //
        if( M.curTenant.modules['ciniki.web'] != null && (M.curTenant.modules['ciniki.web'].flags&0x08) ) {
            this.event.sections.info.list.webcollections_text.visible = 'yes';
            this.edit.sections._webcollections.active = 'yes';
        } else {
            this.event.sections.info.list.webcollections_text.visible = 'no';
            this.edit.sections._webcollections.active = 'no';
        }

        //
        // Setup the tax types
        //
        if( M.modOn('ciniki.taxes') ) {
            this.mapticket.sections.price.fields.taxtype_id.active = 'yes';
            this.mapticket.sections.price.fields.taxtype_id.options = {'0':'No Taxes'};
            if( M.curTenant.taxes != null && M.curTenant.taxes.settings.types != null ) {
                for(i in M.curTenant.taxes.settings.types) {
                    this.mapticket.sections.price.fields.taxtype_id.options[M.curTenant.taxes.settings.types[i].type.id] = M.curTenant.taxes.settings.types[i].type.name;
                }
            }
        } else {
            this.mapticket.sections.price.fields.taxtype_id.active = 'no';
            this.mapticket.sections.price.fields.taxtype_id.options = {'0':'No Taxes'};
        }
        
        //
        // Setup the available_to flags and webflags
        //
        this.mapticket.sections.price.fields.available_to.flags = {'1':{'name':'Public'}};
        this.mapticket.sections.price.fields.webflags.flags = {'1':{'name':'Hidden'}};
        if( M.modFlagOn('ciniki.customers', 0x02) ) {
//        if( (M.curTenant.modules['ciniki.customers'].flags&0x02) > 0 ) {
            this.mapticket.sections.price.fields.available_to.flags['6'] = {'name':'Members'};
            this.mapticket.sections.price.fields.webflags.flags['6'] = {'name':'Show Members Price'};
        }
        if( M.modFlagOn('ciniki.customers', 0x10) ) {
//        if( (M.curTenant.modules['ciniki.customers'].flags&0x10) > 0 ) {
            this.mapticket.sections.price.fields.available_to.flags['7'] = {'name':'Dealers'};
            this.mapticket.sections.price.fields.webflags.flags['7'] = {'name':'Show Dealers Price'};
        }
        if( M.modFlagOn('ciniki.customers', 0x0100) ) {
//        if( (M.curTenant.modules['ciniki.customers'].flags&0x100) > 0 ) {
            this.mapticket.sections.price.fields.available_to.flags['8'] = {'name':'Distributors'};
            this.mapticket.sections.price.fields.webflags.flags['8'] = {'name':'Show Distributors Price'};
        }
        this.menu.tag_type = '10';
        this.menu.tag_permalink = '';
        this.menu.sections.upcoming.label = 'Upcoming Events';
        this.menu.sections.past.label = 'Past Events';
        this.menu.open(cb);
    }
};
