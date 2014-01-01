//
// This app will display and update registrations for an event
//
function ciniki_events_registrations() {
	this.init = function() {
		//
		// events panel
		//
		this.menu = new M.panel('Events',
			'ciniki_events_registrations', 'menu',
			'mc', 'medium', 'sectioned', 'ciniki.events.registrations.menu');
		this.menu.event_id = 0;
        this.menu.sections = {
//			'search':{'label':'', 'type':'livesearch'},
			'registrations':{'label':'Registrations', 'type':'simplegrid', 'num_cols':2,
				'sortable':'yes',
				'sortTypes':['text', 'number', 'text'],
				'cellClasses':['multiline', 'multiline', ''],
				'addTxt':'Add Registration',
				'addFn':'M.ciniki_events_registrations.showAdd(\'M.ciniki_events_registrations.showMenu();\',M.ciniki_events_registrations.menu.event_id);',
				},
			};
		this.menu.cellValue = function(s, i, j, d) {
			if( j == 0 ) {
				return '<span class="maintext">' + d.registration.customer_name + '</span>';
			}
			if( j == 1 ) {
				return '<span class="maintext">' + d.registration.num_tickets + '</span>';
			}
		};
		this.menu.sectionData = function(s) {
			return this.data[s];
		};
		this.menu.rowFn = function(s, i, d) {
			return 'M.ciniki_events_registrations.showEdit(\'M.ciniki_events_registrations.showMenu();\',null,null,\'' + d.registration.id + '\');';
		};
		this.menu.addButton('add', 'Add', 'M.ciniki_events_registrations.showAdd(\'M.ciniki_events_registrations.showMenu();\',M.ciniki_events_registrations.menu.event_id);');
		this.menu.addClose('Back');

		//
		// The panel for editing a registrant
		//
		this.edit = new M.panel('Registrant',
			'ciniki_events_registrations', 'edit',
			'mc', 'medium', 'sectioned', 'ciniki.events.registrations.edit');
		this.edit.data = null;
		this.edit.customer_id = 0;
		this.edit.event_id = 0;
		this.edit.registration_id = 0;
        this.edit.sections = { 
			'customer':{'label':'Customer', 'type':'simplegrid', 'num_cols':2,
				'cellClasses':['label',''],
				'addTxt':'Edit',
				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_events_registrations.updateEditCustomer(null);\',\'mc\',{\'next\':\'M.ciniki_events_registrations.updateEditCustomer\',\'customer_id\':M.ciniki_events_registrations.edit.customer_id});',
				},
			'registration':{'label':'Registration', 'fields':{
				'num_tickets':{'label':'Number of Tickets', 'type':'text', 'size':'small'},
				}},
//			'questions':{'label':'Questions', 'fields':{
//				}},
			'_customer_notes':{'label':'Customer Notes', 'fields':{
				'customer_notes':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'small', 'type':'textarea'},
				}},
			'_notes':{'label':'Private Notes', 'fields':{
				'notes':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'small', 'type':'textarea'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_events_registrations.saveRegistration();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_events_registrations.deleteRegistration();'},
				}},
            };  
		this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
		this.edit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.events.registrationHistory', 'args':{'business_id':M.curBusinessID, 
				'registration_id':this.registration_id, 'event_id':this.event_id, 'field':i}};
		}
		this.edit.sectionData = function(s) {
			return this.data[s];
		}
		this.edit.cellValue = function(s, i, j, d) {
			switch(j) {
				case 0: return d.detail.label;
				case 1: return d.detail.value.replace(/\n/, '<br/>');
			}
		};
		this.edit.rowFn = function(s, i, d) { return ''; }
		this.edit.addButton('save', 'Save', 'M.ciniki_events_registrations.saveRegistration();');
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
		var appContainer = M.createContainer(appPrefix, 'ciniki_events_registrations', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		this.showMenu(cb, args.event_id);
	}

	this.showMenu = function(cb, eid) {
		this.menu.data = {};
		if( eid != null ) {
			this.menu.event_id = eid;
		}
		var rsp = M.api.getJSONCb('ciniki.events.registrationList', 
			{'business_id':M.curBusinessID, 'event_id':this.menu.event_id}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_events_registrations.menu.data.registrations = rsp.registrations;
				if( rsp.registrations.length > 0 ) {
					M.ciniki_events_registrations.menu.sections.registrations.headerValues = ['Name', 'Tickets', 'Paid'];
				} else {
					M.ciniki_events_registrations.menu.sections.registrations.headerValues = null;
				}
				M.ciniki_events_registrations.menu.refresh();
				M.ciniki_events_registrations.menu.show(cb);
			});
	};

	this.showAdd = function(cb, eid) {
		// Setup the edit panel for when the customer edit returns
		if( cb != null ) {
			this.edit.cb = cb;
		}
		if( eid != null ) {
			this.edit.event_id = eid;
		}
		M.startApp('ciniki.customers.edit',null,cb,'mc',{'next':'M.ciniki_events_registrations.showFromCustomer','customer_id':0});
	};

	this.showFromCustomer = function(cid) {
		this.showEdit(this.edit.cb, cid, this.edit.event_id, 0);
	};

	this.showEdit = function(cb, cid, eid, rid) {
		this.edit.reset();
		if( cid != null ) {
			this.edit.customer_id = cid;
		}
		if( eid != null ) {
			this.edit.event_id = eid;
		}
		if( rid != null ) {
			this.edit.registration_id = rid;
		}

		// Check if this is editing a existing registration or adding a new one
		if( this.edit.registration_id > 0 ) {
			this.edit.sections._buttons.buttons.delete.visible = 'yes';
			var rsp = M.api.getJSONCb('ciniki.events.registrationGet', {'business_id':M.curBusinessID, 
				'registration_id':this.edit.registration_id, 'customer':'yes'}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_events_registrations.edit.data = rsp.registration;
					M.ciniki_events_registrations.edit.customer_id = rsp.registration.customer_id;
					M.ciniki_events_registrations.edit.event_id = rsp.registration.event_id;
					M.ciniki_events_registrations.edit.refresh();
					M.ciniki_events_registrations.edit.show(cb);
				});
		} else if( this.edit.customer_id > 0 ) {
			this.edit.sections._buttons.buttons.delete.visible = 'no';
			var rsp = M.api.getJSONCb('ciniki.customers.customerDetails', {'business_id':M.curBusinessID, 
				'customer_id':this.edit.customer_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_events_registrations.edit.data = {'customer':rsp.details};
					M.ciniki_events_registrations.edit.refresh();
					M.ciniki_events_registrations.edit.show(cb);
				});
		}
	};

	this.editCustomer = function(cb, cid) {
		M.startApp('ciniki.customers.edit',null,cb,'mc',{'customer_id':cid});
	};

	this.updateEditCustomer = function(cid) {
		if( cid != null && this.edit.customer_id != cid ) {
			this.edit.customer_id = cid;
		}
		if( this.edit.customer_id > 0 ) {
			var rsp = M.api.getJSONCb('ciniki.customers.customerDetails', {'business_id':M.curBusinessID, 
				'customer_id':this.edit.customer_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_events_registrations.edit.data.customer = rsp.details;
					M.ciniki_events_registrations.edit.refreshSection('customer');
					M.ciniki_events_registrations.edit.show();
				});
		}	
	};

	this.saveRegistration = function() {
		if( this.edit.registration_id > 0 ) {
			var c = this.edit.serializeForm('no');
			if( this.edit.data.customer_id != this.edit.customer_id ) {
				c += 'customer_id=' + this.edit.customer_id + '&';
			}
			if( c != '' ) {
				var rsp = M.api.postJSONCb('ciniki.events.registrationUpdate', 
					{'business_id':M.curBusinessID, 
					'registration_id':M.ciniki_events_registrations.edit.registration_id}, c,
					function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
					M.ciniki_events_registrations.edit.close();
					});
			} else {
				this.edit.close();
			}
		} else {
			var c = this.edit.serializeForm('yes');
			var rsp = M.api.postJSONCb('ciniki.events.registrationAdd', 
				{'business_id':M.curBusinessID, 'event_id':this.edit.event_id,
					'customer_id':this.edit.customer_id}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					M.ciniki_events_registrations.edit.close();
				});
		}
	};

	this.deleteRegistration = function() {
		if( confirm("Are you sure you want to remove this registration?") ) {
			var rsp = M.api.getJSONCb('ciniki.events.registrationDelete', 
				{'business_id':M.curBusinessID, 
				'registration_id':this.edit.registration_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_events_registrations.edit.close();	
				});
		}
	};
}
