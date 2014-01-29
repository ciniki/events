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
			'registrations':{'label':'Registrations', 'type':'simplegrid', 'num_cols':3,
				'sortable':'yes',
				'sortTypes':['text', 'number', 'text'],
				'cellClasses':['multiline', 'multiline', ''],
				'addTxt':'Add Registration',
				'addFn':'M.ciniki_events_registrations.showAdd(\'M.ciniki_events_registrations.showMenu();\',M.ciniki_events_registrations.menu.event_id);',
				},
			};
		this.menu.cellValue = function(s, i, j, d) {
			switch(j) {
				case 0: return '<span class="maintext">' + d.registration.customer_name + '</span>';
				case 1: return '<span class="maintext">' + d.registration.num_tickets + '</span>';
				case 2: return '<span class="maintext">' + d.registration.invoice_status_text + '</span>';
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
			'invoice':{'label':'Invoice', 'visible':'no', 'type':'simplegrid', 'num_cols':5,
				'headerValues':['Invoice #', 'Date', 'Customer', 'Amount', 'Status'],
				'cellClasses':['',''],
//				'addTxt':'',
//				'addFn':'M.ciniki_events_registrations.saveRegistration(\'yes\');',
//				'addFn':'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_events_registrations.showEdit();\',\'mc\',{\'customer_id\':M.ciniki_events_registrations.edit.customer_id});',
				},
			'registration':{'label':'Registration', 'fields':{
				'num_tickets':{'label':'Number of Tickets', 'type':'text', 'size':'small'},
				}},
			'_customer_notes':{'label':'Customer Notes', 'fields':{
				'customer_notes':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'small', 'type':'textarea'},
				}},
			'_notes':{'label':'Private Notes', 'fields':{
				'notes':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'small', 'type':'textarea'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_events_registrations.saveRegistration();'},
				'saveandinvoice':{'label':'Save and Invoice', 'fn':'M.ciniki_events_registrations.saveRegistration(\'yes\');'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_events_registrations.deleteRegistration();'},
				}},
            };  
		this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
		this.edit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.events.registrationHistory', 'args':{'business_id':M.curBusinessID, 
				'registration_id':this.registration_id, 'event_id':this.event_id, 'field':i}};
		}
		this.edit.sectionData = function(s) {
			if( s == 'invoice' ) { return this.data[s]!=null?{'invoice':this.data[s]}:{}; }
			return this.data[s];
		}
		this.edit.cellValue = function(s, i, j, d) {
			if( s == 'customer' ) {
				switch(j) {
					case 0: return d.detail.label;
					case 1: return d.detail.value.replace(/\n/, '<br/>');
				}
			} 
			if( s == 'invoice' ) {
				switch(j) {
					case 0: return d.invoice_number;
					case 1: return d.invoice_date;
					case 2: return (d.customer!=null&&d.customer.display_name!=null)?d.customer.display_name:'';
					case 3: return d.total_amount_display;
					case 4: return d.status_text;
				}
			}
		};
		this.edit.rowFn = function(s, i, d) { 
			if( s == 'invoice' ) { return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_events_registrations.showEdit();\',\'mc\',{\'invoice_id\':\'' + d.id + '\'});'; }
			return ''; 
		};
		this.edit.addButton('save', 'Save', 'M.ciniki_events_registrations.saveRegistration();');
		this.edit.addClose('Cancel');

		//
		// The add invoice panel, which display the price list for quantity
		//
		this.newinvoice = new M.panel('Create Invoice',
			'ciniki_events_registrations', 'newinvoice',
			'mc', 'medium', 'sectioned', 'ciniki.events.registrations.newinvoice');
		this.newinvoice.data = null;
		this.newinvoice.customer_id = 0;
		this.newinvoice.event_id = 0;
		this.newinvoice.registration_id = 0;
		this.newinvoice.quantity = 1;
        this.newinvoice.sections = {
			'prices':{'label':'Price List', 'fields':{
				'price_id':{'label':'Price', 'type':'select', 'options':{}},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Create Invoice', 'fn':'M.ciniki_events_registrations.createInvoice();'},
				}},
			};
		this.newinvoice.fieldValue = function(s, i, d) { return this.data[i]; }
		this.newinvoice.addClose('Cancel');
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
				if( M.curBusiness.modules['ciniki.sapos'] != null ) {
					M.ciniki_events_registrations.menu.sections.registrations.num_cols = 3;
				} else {
					M.ciniki_events_registrations.menu.sections.registrations.num_cols = 2;
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
			M.api.getJSONCb('ciniki.events.registrationGet', {'business_id':M.curBusinessID, 
				'registration_id':this.edit.registration_id, 'customer':'yes', 'invoice':'yes'}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_events_registrations.edit;
					p.data = rsp.registration;
					p.event_id = rsp.registration.event_id;
					p.customer_id = rsp.registration.customer_id;
					p.sections.invoice.visible=(M.curBusiness.modules['ciniki.sapos']!=null)?'yes':'no';
//					p.sections.invoice.addTxt=(rsp.registration.invoice_id==0)?'Invoice Customer':'';
//					p.sections._buttons.buttons.saveandinvoice.visible='no';
					p.sections._buttons.buttons.saveandinvoice.visible=(M.curBusiness.modules['ciniki.sapos']!=null&&rsp.registration.invoice_id==0)?'yes':'no';
					p.event_id = rsp.registration.event_id;
					p.refresh();
					p.show(cb);
				});
		} else if( this.edit.customer_id > 0 ) {
			this.edit.sections._buttons.buttons.delete.visible = 'no';
			M.api.getJSONCb('ciniki.customers.customerDetails', {'business_id':M.curBusinessID, 
				'customer_id':this.edit.customer_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_events_registrations.edit;
					p.data = {'customer':rsp.details};
//					p.sections.invoice.addTxt = '';
					p.sections._buttons.buttons.saveandinvoice.visible = (M.curBusiness.modules['ciniki.sapos']!=null)?'yes':'no';
					p.refresh();
					p.show(cb);
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
					var p = M.ciniki_events_registrations.edit;
					p.data.customer = rsp.details;
					p.refreshSection('customer');
					p.show();
				});
		}	
	};

	this.saveRegistration = function(inv) {
		var quantity = this.edit.formFieldValue(this.edit.sections.registration.fields.num_tickets, 'num_tickets');
		if( this.edit.registration_id > 0 ) {
			var c = this.edit.serializeForm('no');
			if( this.edit.data.customer_id != this.edit.customer_id ) {
				c += 'customer_id=' + this.edit.customer_id + '&';
			}
			if( c != '' ) {
				M.api.postJSONCb('ciniki.events.registrationUpdate', 
					{'business_id':M.curBusinessID, 
					'registration_id':M.ciniki_events_registrations.edit.registration_id}, c,
					function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
						var p = M.ciniki_events_registrations.edit;
						if( inv != null && inv == 'yes' ) {
							M.ciniki_events_registrations.newInvoice('M.ciniki_events_registrations.showEdit(null,null,'+p.registration_id+',null);', p.event_id, p.customer_id, p.registration_id, quantity);
						} else {
							p.close();
						}
					});
			} else {
				if( inv != null && inv == 'yes' ) {
					M.ciniki_events_registrations.newInvoice('M.ciniki_events_registrations.showEdit(null,null,'+this.edit.registration_id+',null);', this.edit.event_id, this.edit.customer_id, this.edit.registration_id, quantity);
				} else {
					this.edit.close();
				}
			}
		} else {
			var c = this.edit.serializeForm('yes');
			M.api.postJSONCb('ciniki.events.registrationAdd', 
				{'business_id':M.curBusinessID, 'event_id':this.edit.event_id,
					'customer_id':this.edit.customer_id}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					if( inv != null && inv == 'yes' ) {
						M.ciniki_events_registrations.newInvoice('M.ciniki_events_registration.showEdit(null,null,'+rsp.id+',null);', this.edit.event_id, this.edit.customer_id, rsp.id, quantity);
								
//						M.startApp('ciniki.sapos.invoice',null,'M.ciniki_events_registration.showEdit(null,null,' + rsp.id + ',null);','mc',{'object':'ciniki.events.registration','object_id':rsp.id});
					} else {
						M.ciniki_events_registrations.edit.close();
					}
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

	this.newInvoice = function(cb, eid, cid, rid, quantity) {
		if( eid != null ) { this.newinvoice.event_id = eid; }
		if( cid != null ) { this.newinvoice.customer_id = cid; }
		if( rid != null ) { this.newinvoice.registration_id = rid; }
		if( quantity != null ) { this.newinvoice.quantity = quantity; }
		M.api.getJSONCb('ciniki.events.eventPriceList', {'business_id':M.curBusinessID,
			'event_id':this.newinvoice.event_id}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_events_registrations.newinvoice;
				p.prices = rsp.prices;
				p.data = {'price_id':0};
				// Setup the price list
				p.sections.prices.fields.price_id.options = {};
				for(i in rsp.prices) {
					p.sections.prices.fields.price_id.options[rsp.prices[i].price.id] = rsp.prices[i].price.name + ' ' + rsp.prices[i].price.unit_amount_display;
					if( i == 0 ) {
						p.data.price_id = rsp.prices[i].price.id;
					}
				}
				p.refresh();
				p.show(cb);
			});
	};

	this.createInvoice = function() {
		var items = [];
		items[0] = {
			'status':0,
			'object':'ciniki.events.registration',
			'object_id':this.newinvoice.registration_id,
			'description':'',
			'quantity':this.newinvoice.quantity,
			'unit_amount':0,
			'unit_discount_amount':0,
			'unit_discount_percentage':0,
			'taxtype_id':0,
			'notes':'',
			};
		var price_id = this.newinvoice.formFieldValue(this.newinvoice.sections.prices.fields.price_id, 'price_id');
		var prices = this.newinvoice.prices;
		// Find the price selected
		for(i in prices) {
			if( prices[i].price.id == price_id ) {
				items[0].description = prices[i].price.event_name + (prices[i].price.name!=''?' - '+prices[i].price.name:'');
				items[0].unit_amount = prices[i].price.unit_amount;
				items[0].unit_discount_amount = prices[i].price.unit_discount_amount;
				items[0].unit_discount_percentage = prices[i].price.unit_discount_percentage;
			}
		}
		M.startApp('ciniki.sapos.invoice',null,'M.ciniki_events_registrations.showEdit(null,null,\'' + this.newinvoice.registration_id + '\',null);','mc',{'customer_id':this.newinvoice.customer_id,'items':items});
	};
}
