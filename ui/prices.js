//
// This app will display and update prices for an event
//
function ciniki_events_prices() {
	this.init = function() {
		//
		// The panel for editing a registrant
		//
		this.edit = new M.panel('Registrant',
			'ciniki_events_prices', 'edit',
			'mc', 'medium', 'sectioned', 'ciniki.events.prices.edit');
		this.edit.data = null;
		this.edit.event_id = 0;
		this.edit.price_id = 0;
        this.edit.sections = { 
			'price':{'label':'Price', 'fields':{
				'name':{'label':'Name', 'type':'text'},
//				'valid_from':{'label':'Valid From', 'hint':'', 'type':'text'},
//				'valid_to':{'label':'Valid To', 'hint':'', 'type':'text'},
				'unit_amount':{'label':'Unit Amount', 'type':'text', 'size':'small'},
				'unit_discount_amount':{'label':'Discount Amount', 'type':'text', 'size':'small'},
				'unit_discount_percentage':{'label':'Discount Percent', 'type':'text', 'size':'small'},
				'taxtype_id':{'label':'Taxes', 'active':'no', 'type':'select', 'options':{}},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_events_prices.savePrice();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_events_prices.deletePrice();'},
				}},
            };  
		this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
		this.edit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.events.priceHistory', 'args':{'business_id':M.curBusinessID, 
				'price_id':this.price_id, 'event_id':this.event_id, 'field':i}};
		}
		this.edit.sectionData = function(s) {
			return this.data[s];
		}
		this.edit.rowFn = function(s, i, d) { return ''; }
		this.edit.addButton('save', 'Save', 'M.ciniki_events_prices.savePrice();');
		this.edit.addClose('Cancel');
	}

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
		var appContainer = M.createContainer(appPrefix, 'ciniki_events_prices', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		//
		// Setup the tax types
		//
		if( M.curBusiness.modules['ciniki.taxes'] != null ) {
			this.edit.sections.price.fields.taxtype_id.active = 'yes';
			this.edit.sections.price.fields.taxtype_id.options = {'0':'No Taxes'};
			console.log(M.curBusiness.taxes);
			if( M.curBusiness.taxes != null && M.curBusiness.taxes.settings.types != null ) {
				for(i in M.curBusiness.taxes.settings.types) {
					this.edit.sections.price.fields.taxtype_id.options[M.curBusiness.taxes.settings.types[i].type.id] = M.curBusiness.taxes.settings.types[i].type.name;
				}
			}
		} else {
			this.edit.sections.price.fields.taxtype_id.active = 'no';
			this.edit.sections.price.fields.taxtype_id.options = {'0':'No Taxes'};
		}

		this.showEdit(cb, args.price_id, args.event_id);
	}

	this.showEdit = function(cb, pid, eid) {
		this.edit.reset();
		if( pid != null ) {
			this.edit.price_id = pid;
		}
		if( eid != null ) {
			this.edit.event_id = eid;
		}

		// Check if this is editing a existing price or adding a new one
		if( this.edit.price_id > 0 ) {
			this.edit.sections._buttons.buttons.delete.visible = 'yes';
			M.api.getJSONCb('ciniki.events.priceGet', {'business_id':M.curBusinessID, 
				'price_id':this.edit.price_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_events_prices.edit;
					p.data = rsp.price;
					p.event_id = rsp.price.event_id;
					p.refresh();
					p.show(cb);
				});
		} else {
			this.edit.sections._buttons.buttons.delete.visible = 'no';
			this.edit.data = {};
			this.edit.refresh();
			this.edit.show(cb);
		}
	};

	this.savePrice = function() {
		if( this.edit.price_id > 0 ) {
			var c = this.edit.serializeForm('no');
			if( c != '' ) {
				M.api.postJSONCb('ciniki.events.priceUpdate', 
					{'business_id':M.curBusinessID, 
					'price_id':M.ciniki_events_prices.edit.price_id}, c,
					function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
					M.ciniki_events_prices.edit.close();
					});
			} else {
				this.edit.close();
			}
		} else {
			var c = this.edit.serializeForm('yes');
			M.api.postJSONCb('ciniki.events.priceAdd', 
				{'business_id':M.curBusinessID, 'event_id':this.edit.event_id}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					M.ciniki_events_prices.edit.close();
				});
		}
	};

	this.deletePrice = function() {
		if( confirm("Are you sure you want to remove this price?") ) {
			M.api.getJSONCb('ciniki.events.priceDelete', 
				{'business_id':M.curBusinessID, 
				'price_id':this.edit.price_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_events_prices.edit.close();	
				});
		}
	};
}
