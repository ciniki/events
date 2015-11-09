//
// This app will handle the listing, additions and deletions of events.  These are associated business.
//
function ciniki_events_main() {
	//
	// Panels
	//
	this.regFlags = {
		'1':{'name':'Track Registrations'},
		'2':{'name':'Online Registrations'},
		};
	this.init = function() {
		//
		// events panel
		//
		this.menu = new M.panel('Events',
			'ciniki_events_main', 'menu',
			'mc', 'medium', 'sectioned', 'ciniki.events.main.menu');
        this.menu.sections = {
			'categories':{'label':'Categories', 'aside':'yes', 'visible':'no', 'list':{
				}},
			'upcoming':{'label':'Upcoming Events', 'type':'simplegrid', 'num_cols':2,
				'headerValues':null,
				'cellClasses':['multiline center nobreak', 'multiline'],
				'noData':'No upcoming events',
				'addTxt':'Add Event',
				'addFn':'M.ciniki_events_main.showEdit(\'M.ciniki_events_main.showMenu();\',0);',
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
			return 'M.ciniki_events_main.showMenu(null,\'' + d.tag.permalink + '\');';
		};
		this.menu.noData = function(s) { return this.sections[s].noData; }
		this.menu.cellValue = function(s, i, j, d) {
			if( j == 0 ) {
				if( d.event.end_date != '' && d.event.end_date != null ) {
					return '<span class="maintext">' + d.event.start_date.replace(' ', '&nbsp;') + '</span>'
						+ '<span class="subtext">' + d.event.end_date + '</span>';
				}
				if( d.event.start_date == null || d.event.start_date == '' ) {
					return '<span class="maintext">???</span><span class="subtext">&nbsp;</span>';
				}
				return '<span class="maintext">' + d.event.start_date.replace(' ', '&nbsp;') + '</span><span class="subtext">&nbsp;</span>';
			}
			if( j == 1 ) {
//				var reg = '';
//				if( d.event.tickets_sold != null && d.event.num_tickets != null ) {
//					reg = ' [' + d.event.tickets_sold + '/' + d.event.num_tickets + ']';
//				}
				return '<span class="maintext">' + d.event.name + '</span>'
					+ '<span class="subtext singleline"> </span>';
			}
		};
		this.menu.rowFn = function(s, i, d) {
			return 'M.ciniki_events_main.showEvent(\'M.ciniki_events_main.showMenu();\',\'' + d.event.id + '\');';
		};
		this.menu.addButton('add', 'Add', 'M.ciniki_events_main.showEdit(\'M.ciniki_events_main.showMenu();\',0);');
		this.menu.addClose('Back');

		//
		// The event panel 
		//
		this.event = new M.panel('Event',
			'ciniki_events_main', 'event',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.events.main.event');
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
			'_registrations':{'label':'', 'aside':'yes', 'hidelabel':'yes', 'visible':'no', 'list':{
				'registrations':{'label':'Tickets'},
				}},
			'description':{'label':'Synopsis', 'type':'htmlcontent'},
			'long_description':{'label':'Description', 'type':'htmlcontent'},
			'prices':{'label':'Price List', 'type':'simplegrid', 'num_cols':2,
				'headerValues':null,
				'cellClasses':['', ''],
				'noData':'No prices',
				'addTxt':'Add Price',
				'addFn':'M.startApp(\'ciniki.events.prices\',null,\'M.ciniki_events_main.showEvent();\',\'mc\',{\'event_id\':M.ciniki_events_main.event.event_id,\'price_id\':\'0\'});',
			},
			'links':{'label':'Links', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'cellClasses':['multiline'],
				'noData':'No event links',
				'addTxt':'Add Link',
				'addFn':'M.startApp(\'ciniki.events.links\',null,\'M.ciniki_events_main.showEvent();\',\'mc\',{\'event_id\':M.ciniki_events_main.event.event_id,\'add\':\'yes\'});',
				},
			'files':{'label':'Files', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'cellClasses':['multiline'],
				'noData':'No event files',
				'addTxt':'Add File',
				'addFn':'M.startApp(\'ciniki.events.files\',null,\'M.ciniki_events_main.showEvent();\',\'mc\',{\'event_id\':M.ciniki_events_main.event.event_id,\'add\':\'yes\'});',
			},
			'images':{'label':'Gallery', 'type':'simplethumbs'},
			'_images':{'label':'', 'type':'simplegrid', 'num_cols':1,
				'addTxt':'Add Additional Image',
				'addFn':'M.startApp(\'ciniki.events.images\',null,\'M.ciniki_events_main.showEvent();\',\'mc\',{\'event_id\':M.ciniki_events_main.event.event_id,\'add\':\'yes\'});',
				},
			'sponsors':{'label':'Sponsors', 'type':'simplegrid', 'num_cols':1,
				'addTxt':'Manage Sponsors',
				'addFn':'M.startApp(\'ciniki.sponsors.ref\',null,\'M.ciniki_events_main.showEvent();\',\'mc\',{\'object\':\'ciniki.events.event\',\'object_id\':M.ciniki_events_main.event.event_id});',
				},
			'_buttons':{'label':'', 'buttons':{
				'edit':{'label':'Edit', 'fn':'M.ciniki_events_main.showEdit(\'M.ciniki_events_main.showEvent();\',M.ciniki_events_main.event.event_id);'},
				}},
		};
		this.event.addDropImage = function(iid) {
			var rsp = M.api.getJSON('ciniki.events.imageAdd',
				{'business_id':M.curBusinessID, 'image_id':iid, 'event_id':M.ciniki_events_main.event.event_id});
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			return true;
		};
		this.event.addDropImageRefresh = function() {
			if( M.ciniki_events_main.event.event_id > 0 ) {
				var rsp = M.api.getJSONCb('ciniki.events.eventGet', {'business_id':M.curBusinessID, 
					'event_id':M.ciniki_events_main.event.event_id, 'images':'yes'}, function(rsp) {
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
				return 'M.startApp(\'ciniki.events.registrations\',null,\'M.ciniki_events_main.showEvent();\',\'mc\',{\'event_id\':\'' + M.ciniki_events_main.event.event_id + '\'});';
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
				return 'M.startApp(\'ciniki.events.prices\',null,\'M.ciniki_events_main.showEvent();\',\'mc\',{\'price_id\':\'' + d.price.id + '\',\'event_id\':\'0\'});';
			}
			if( s == 'links' ) {
				return 'M.startApp(\'ciniki.events.links\',null,\'M.ciniki_events_main.showEvent();\',\'mc\',{\'link_id\':\'' + d.link.id + '\'});';
			}
			if( s == 'files' ) {
				return 'M.startApp(\'ciniki.events.files\',null,\'M.ciniki_events_main.showEvent();\',\'mc\',{\'file_id\':\'' + d.file.id + '\'});';
			}
			if( s == 'sponsors' ) {
				return 'M.startApp(\'ciniki.sponsors.ref\',null,\'M.ciniki_events_main.showEvent();\',\'mc\',{\'ref_id\':\'' + d.sponsor.ref_id + '\'});';
			}
		};
//		this.event.thumbSrc = function(s, i, d) {
//			if( d.image.image_data != null && d.image.image_data != '' ) {
//				return d.image.image_data;
//			} else {
//				return '/ciniki-mods/core/ui/themes/default/img/noimage_75.jpg';
//			}
//		};
//		this.event.thumbTitle = function(s, i, d) {
//			if( d.image.name != null ) { return d.image.name; }
//			return '';
//		};
//		this.event.thumbID = function(s, i, d) {
//			if( d.image.id != null ) { return d.image.id; }
//			return 0;
//		};
		this.event.thumbFn = function(s, i, d) {
			return 'M.startApp(\'ciniki.events.images\',null,\'M.ciniki_events_main.showEvent();\',\'mc\',{\'event_image_id\':\'' + d.image.id + '\'});';
		};
		this.event.addButton('edit', 'Edit', 'M.ciniki_events_main.showEdit(\'M.ciniki_events_main.showEvent();\',M.ciniki_events_main.event.event_id);');
		this.event.addClose('Back');
		this.event.addLeftButton('website', 'Preview', 'M.showWebsite(\'/events/\'+M.ciniki_events_main.event.data.permalink);');

		//
		// The panel for a site's menu
		//
		this.edit = new M.panel('Event',
			'ciniki_events_main', 'edit',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.events.main.edit');
		this.edit.data = null;
		this.edit.event_id = 0;
        this.edit.sections = { 
			'_image':{'label':'Image', 'type':'imageform', 'aside':'yes',
				'gstep':1,
				'gtitle':function(p) { return (p.data.primary_image_id != null && p.data.primary_image_id > 0)?'Would you like to change the photo for this event?':'Do you have a photo for this event?';},
				'gmore':function(p) { return (p.data.primary_image_id != null && p.data.primary_image_id > 0)?
					'Use the <b>Change Photo</b> button below to select a new photo from your computer or tablet.'
					+ ' If you would like to save the original photo to your computer, press the <span class="icon">G</span> button.'
					:'The photo is optional, however it will look better on your website if you have one. Use the <b>Add Photo</b> button to select a photo from your computer or tablet.';},
				'fields':{
					'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
				}},
            'general':{'label':'General', 'aside':'yes', 
				'gstep':2,
				'gtitle':'Event Details',
				'fields':{
					'name':{'label':'Name', 'hint':'Events name', 'type':'text',
						'gtitle':'What is the name of this event?',
						'htext':'The name of your event must be unique. If this is an annual event, add the year or season to the name. Eg: Centreville Art Show 2015, Trade Show 2016, etc',
						},
					'url':{'label':'URL', 'hint':'Enter the http:// address for your events website', 'type':'text',
						'gtitle':'Does the event have a website?',
						'htext':"If the event has it's own website, enter the address here. It doesn't matter if it begins with http:// or not.  Examples: http://www.artbythelighthouse.ca/, www.oneofakindshow.com",
						},
					'start_date':{'label':'Start', 'type':'date',
						'gtitle':'What is the first day of the event?',
						},
					'end_date':{'label':'End', 'type':'date',
						'htext':'If the event is only one day, you can leave End blank.',
						},
					'times':{'label':'Hours', 'type':'text',
						'htext':'If the event is multiple days, each with different hours, it is recommended to add the hours to the description and leave this blank.',
						},
					'oidref':{'label':'Exhibition', 'active':'no', 'type':'select', 'options':{}},
					}}, 
			'_categories':{'label':'Categories', 'aside':'yes', 'active':'no', 
				'gstep':3,
				'gtitle':'What are the categories for this event?',
				'gmore':"Each event can be in multiple categories. To add a new category, press the <span class='icon'>+</span> button.",
				'fields':{
					'categories':{'label':'', 'hidelabel':'yes', 'type':'tags', 'tags':[], 'hint':'Enter a new category: '},
					}},
			'_webcollections':{'label':'Web Collections', 'aside':'yes', 'active':'no', 
				'gstep':4,
				'gtitle':'Should this event be included in a collection?',
				'fields':{
					'webcollections':{'label':'', 'hidelabel':'yes', 'type':'collection'},
					}},
			'_registrations':{'label':'Registrations', 'aside':'yes', 'visible':'no', 
				'gstep':5,
				'gtitle':'Are you going to track registrations for this event?',
				'fields':{
					'reg_flags':{'label':'Options', 'active':'no', 'type':'flags', 'joined':'no', 'flags':this.regFlags},
					'num_tickets':{'label':'Number of Tickets', 'active':'no', 'type':'text', 'size':'small'},
					}},
			'_description':{'label':'Synopsis', 
				'gstep':6,
				'gtitle':'Describe the event in 2 sentences:',
				'gmore':'This is used when listing the event on your homepage. This should be short and just enough to get them to click for more information.',
				'fields':{
					'description':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'small', 'type':'textarea'},
					}},
			'_long_description':{'label':'Description', 
				'gstep':7,
				'gtitle':'What are the complete details of the event?',
				'gmore':'The description should include some or all of the who, what, where and why about the event. Who is the event for? The general public, or only people with a specific interest. What is the event about? Where is the event and how do you get there? Why should people go to the event?',
				'fields':{
					'long_description':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'large', 'type':'textarea'},
					}},
			'_buttons':{'label':'', 
				'gstep':8,
				'gtext':function(p) { return (p.event_id>0?"Press the save button to save the changes you've made.":'Press the save button to add this event');},
				'gmore':function(p) { return (p.event_id>0?"If you want to remove the event, press delete.":null);},
				'buttons':{
					'save':{'label':'Save', 'fn':'M.ciniki_events_main.saveEvent();'},
					'delete':{'label':'Delete', 'fn':'M.ciniki_events_main.removeEvent();'},
					}},
            };  
		this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
		this.edit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.events.eventHistory', 'args':{'business_id':M.curBusinessID, 
				'event_id':this.event_id, 'field':i}};
		}
		this.edit.addDropImage = function(iid) {
			M.ciniki_events_main.edit.setFieldValue('primary_image_id', iid, null, null);
			return true;
		};
		this.edit.deleteImage = function(fid) {
			this.setFieldValue(fid, 0, null, null);
			return true;
		};
		this.edit.addButton('save', 'Save', 'M.ciniki_events_main.saveEvent();');
		this.edit.addClose('Cancel');
	}

	//
	// Arguments:
	// aG - The arguments to be parsed into args
	//
	this.start = function(cb, appPrefix, aG) {
		args = {};
		if( aG != null ) {
			args = eval(aG);
		}

		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_events_main', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		if( M.curBusiness.modules['ciniki.sponsors'] != null 
			&& (M.curBusiness.modules['ciniki.sponsors'].flags&0x02) ) {
			this.event.sections.sponsors.visible = 'yes';
		} else {
			this.event.sections.sponsors.visible = 'no';
		}

		if( M.curBusiness.modules['ciniki.artcatalog'] != null ) {
			this.edit.sections.general.fields.oidref.active = 'yes';
		} else {
			this.edit.sections.general.fields.oidref.active = 'no';
		}

		//
		// Check if event categories is enabled
		//
		if( M.curBusiness.modules['ciniki.events'] != null 
			&& (M.curBusiness.modules['ciniki.events'].flags&0x10) ) {
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
		if( M.curBusiness.modules['ciniki.sapos'] != null ) {
			this.event.sections.prices.visible = 'yes';
		} else {
			this.event.sections.prices.visible = 'no';
		}

		//
		// Check if web collections are enabled
		//
		if( M.curBusiness.modules['ciniki.web'] != null 
			&& (M.curBusiness.modules['ciniki.web'].flags&0x08) ) {
			this.event.sections.info.list.webcollections_text.visible = 'yes';
			this.edit.sections._webcollections.active = 'yes';
		} else {
			this.event.sections.info.list.webcollections_text.visible = 'no';
			this.edit.sections._webcollections.active = 'no';
		}

		this.menu.tag_type = '10';
		this.menu.tag_permalink = '';
		this.menu.sections.upcoming.label = 'Upcoming Events';
		this.menu.sections.past.label = 'Past Events';
		this.showMenu(cb);
	}

	this.showMenu = function(cb, cat) {
		this.menu.data = {};
		if( cat != null ) {
			this.menu.tag_type = 10;
			this.menu.tag_permalink = cat;
		}
		if( this.menu.rightbuttons.edit != null ) { delete(this.menu.rightbuttons.edit); }
		if( (M.curBusiness.modules['ciniki.events'].flags&0x10) > 0 ) {
			M.api.getJSONCb('ciniki.events.eventList', 
				{'business_id':M.curBusinessID, 'categories':'yes', 
					'tag_type':this.menu.tag_type, 'tag_permalink':this.menu.tag_permalink}, function(rsp) {
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
			M.api.getJSONCb('ciniki.events.eventList', {'business_id':M.curBusinessID}, function(rsp) {
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

	this.showEvent = function(cb, eid) {
		this.event.reset();
		if( eid != null ) {
			this.event.event_id = eid;
		}
		var rsp = M.api.getJSONCb('ciniki.events.eventGet', {'business_id':M.curBusinessID, 
			'event_id':this.event.event_id, 'images':'yes', 'files':'yes', 'prices':'yes', 
			'sponsors':'yes', 'webcollections':'yes', 'categories':'yes', 'links':'yes'}, function(rsp) {
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
				if( (rsp.event.reg_flags&0x03) > 0 ) {
					p.sections._registrations.visible = 'yes';
				} else {
					p.sections._registrations.visible = 'no';
				}
				p.refresh();
				p.show(cb);
			});
	};

	this.showEdit = function(cb, eid) {
		this.edit.reset();
		if( eid != null ) {
			this.edit.event_id = eid;
		}

		if( (M.curBusiness.modules['ciniki.events'].flags&0x03) > 0 ) {
			this.edit.sections._registrations.visible = 'yes';
			this.edit.sections._registrations.fields.reg_flags.active = 'yes';
			this.edit.sections._registrations.fields.num_tickets.active = 'yes';
		} else {
			this.edit.sections._registrations.visible = 'no';
			this.edit.sections._registrations.fields.reg_flags.active = 'no';
			this.edit.sections._registrations.fields.num_tickets.active = 'no';
		}

		this.edit.sections._buttons.buttons.delete.visible = (this.edit.event_id>0?'yes':'no');
//		if( this.edit.event_id > 0 ) {
			this.edit.reset();
			this.edit.sections._buttons.buttons.delete.visible = 'yes';
			M.api.getJSONCb('ciniki.events.eventGet', {'business_id':M.curBusinessID, 
				'event_id':this.edit.event_id, 'webcollections':'yes', 'categories':'yes', 'objects':'yes'}, function(rsp) {
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
					if( M.curBusiness.modules['ciniki.artcatalog'] != null ) {
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
//		} else if( this.edit.sections._categories.active == 'yes' 
//			|| this.edit.sections._webcollections.active == 'yes' 
//			) {
//			this.edit.reset();
//			this.edit.sections._buttons.buttons.delete.visible = 'no';
//			this.edit.data = {};
//			// Get the list of collections
//			M.api.getJSONCb('ciniki.events.eventNew', {'business_id':M.curBusinessID,
//				'categories':'yes', 'webcollections':'yes', 'objects':'yes'}, function(rsp) {
//				if( rsp.stat != 'ok' ) {
//					M.api.err(rsp);
//					return false;
//				}
//				var p = M.ciniki_events_main.edit;
//				p.data = {};
//				if( rsp.webcollections != null ) {
//					p.data['_webcollections'] = rsp.webcollections;
//				}
//				p.sections._categories.fields.categories.tags = [];
//				if( rsp.categories != null ) {
//					for(i in rsp.categories) {
//						p.sections._categories.fields.categories.tags.push(rsp.categories[i].tag.name);
//					}
//				}
//
//				p.refresh();
//				p.show(cb);
//			});
//		} else {
//			this.edit.reset();
//			this.edit.sections._buttons.buttons.delete.visible = 'no';
//			this.edit.data = {};
//			this.edit.show(cb);
//		}
	};

	this.saveEvent = function() {
		if( this.edit.event_id > 0 ) {
			var c = this.edit.serializeForm('no');
			if( c != '' ) {
				var rsp = M.api.postJSONCb('ciniki.events.eventUpdate', 
					{'business_id':M.curBusinessID, 'event_id':M.ciniki_events_main.edit.event_id}, c,
					function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
					M.ciniki_events_main.edit.close();
					});
			} else {
				this.edit.close();
			}
		} else {
			var c = this.edit.serializeForm('yes');
			if( c != '' ) {
				var rsp = M.api.postJSONCb('ciniki.events.eventAdd', 
					{'business_id':M.curBusinessID}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
						if( rsp.id > 0 ) {
							var cb = M.ciniki_events_main.edit.cb;
							M.ciniki_events_main.edit.close();
							M.ciniki_events_main.showEvent(cb,rsp.id);
						} else {
							M.ciniki_events_main.edit.close();
						}
					});
			} else {
				this.edit.close();
			}
		}
	};

	this.removeEvent = function() {
		if( confirm("Are you sure you want to remove '" + this.event.data.name + "' as an event ?") ) {
			var rsp = M.api.getJSONCb('ciniki.events.eventDelete', 
				{'business_id':M.curBusinessID, 'event_id':M.ciniki_events_main.event.event_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_events_main.event.close();
				});
		}
	}
};
