//
// This app will display and update registrations for an event
//
function ciniki_events_registrations() {
    //
    // events panel
    //
    this.menu = new M.panel('Events', 'ciniki_events_registrations', 'menu', 'mc', 'medium', 'sectioned', 'ciniki.events.registrations.menu');
    this.menu.event_id = 0;
    this.menu.sections = {
//          'search':{'label':'', 'type':'livesearch'},
        'registrations':{'label':'Registrations', 'type':'simplegrid', 'num_cols':3,
            'sortable':'yes',
            'sortTypes':['text', 'number', 'text'],
            'cellClasses':['multiline', 'multiline', 'multiline'],
            'addTxt':'Add Registration',
            'addFn':'M.ciniki_events_registrations.edit.addCustomer(\'M.ciniki_events_registrations.menu.open();\',M.ciniki_events_registrations.menu.event_id);',
            },
        '_buttons':{'label':'', 'buttons':{
//            'registrationspdf':{'label':'Registration List (PDF)', 'fn':'M.ciniki_courses_registrations.offeringRegistrationsPDF(M.ciniki_courses_registrations.menu.offering_id);'},
            'registrationsexcel':{'label':'Registration List (Excel)', 'fn':'M.ciniki_events_registrations.menu.excel();'},
            'individualtickets':{'label':'Tickets List (Excel)', 
                'visible':function() { return M.modFlagOn('ciniki.events', 0x08); },
                'fn':'M.ciniki_events_registrations.menu.individualtickets();'},
            }},
        };
    this.menu.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return '<span class="maintext">' + d.registration.customer_name + '</span><span class="subtext">' + d.registration.customer_notes + '</span>';
            case 1: return '<span class="maintext">' + d.registration.num_tickets + '</span>';
            case 2: 
                var txt = '';
                if( (M.curTenant.modules['ciniki.events'].flags&0x04)>0 ) {
                    txt += '<span class="maintext">' + d.registration.status_text + '</span>';
                } 
                if( M.curTenant.modules['ciniki.sapos'] != null ) {
                    if( txt != '' ) {
                        txt += '<span class="subtext">' + d.registration.invoice_status_text + '</span>';
                    } else {
                        txt += '<span class="maintext">' + d.registration.invoice_status_text + '</span>';
                    }
                }
                return txt;
        }
    };
    this.menu.sectionData = function(s) {
        return this.data[s];
    };
    this.menu.rowFn = function(s, i, d) {
        return 'M.ciniki_events_registrations.edit.open(\'M.ciniki_events_registrations.menu.open();\',null,null,\'' + d.registration.id + '\');';
    };
    this.menu.open = function(cb, eid) {
        this.data = {};
        if( eid != null ) { this.event_id = eid; }
        M.api.getJSONCb('ciniki.events.registrationList', {'tnid':M.curTenantID, 'event_id':this.event_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.ciniki_events_registrations.menu.data.registrations = rsp.registrations;
            if( rsp.registrations.length > 0 ) {
                M.ciniki_events_registrations.menu.sections.registrations.headerValues = ['Name', 'Tickets', 'Status'];
            } else {
                M.ciniki_events_registrations.menu.sections.registrations.headerValues = null;
            }
            if( M.curTenant.modules['ciniki.sapos'] != null || (M.curTenant.modules['ciniki.events'].flags&0x04) > 0 ) {
                M.ciniki_events_registrations.menu.sections.registrations.num_cols = 3;
            } else {
                M.ciniki_events_registrations.menu.sections.registrations.num_cols = 2;
            }
            M.ciniki_events_registrations.menu.refresh();
            M.ciniki_events_registrations.menu.show(cb);
        });
    };
    this.menu.excel = function() {
        M.api.openFile('ciniki.events.eventRegistrations', {'tnid':M.curTenantID, 'output':'excel', 'event_id':this.event_id});
    }
    this.menu.individualtickets = function() {
        M.api.openFile('ciniki.events.eventRegistrations', {'tnid':M.curTenantID, 'output':'exceltickets', 'event_id':this.event_id});
    }
    this.menu.addButton('add', 'Add', 'M.ciniki_events_registrations.edit.addCustomer(\'M.ciniki_events_registrations.menu.open();\',M.ciniki_events_registrations.menu.event_id);');
    this.menu.addClose('Back');

    //
    // The panel for editing a registrant
    //
    this.edit = new M.panel('Registrant', 'ciniki_events_registrations', 'edit', 'mc', 'medium', 'sectioned', 'ciniki.events.registrations.edit');
    this.edit.data = null;
    this.edit.customer_id = 0;
    this.edit.event_id = 0;
    this.edit.registration_id = 0;
    this.edit.sections = { 
        'customer_details':{'label':'Customer', 'type':'simplegrid', 'num_cols':2,
            'cellClasses':['label',''],
            'addTxt':'Edit',
            'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_events_registrations.edit.updateCustomer(null);\',\'mc\',{\'next\':\'M.ciniki_events_registrations.edit.updateCustomer\',\'customer_id\':M.ciniki_events_registrations.edit.customer_id});',
            },
        'invoice':{'label':'Invoice', 'visible':'no', 'type':'simplegrid', 'num_cols':5,
            'headerValues':['Invoice #', 'Date', 'Customer', 'Amount', 'Status'],
            'cellClasses':['',''],
//              'addTxt':'',
//              'addFn':'M.ciniki_events_registrations.edit.save(\'yes\');',
//              'addFn':'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_events_registrations.edit.open();\',\'mc\',{\'customer_id\':M.ciniki_events_registrations.edit.customer_id});',
            },
        'registration':{'label':'Registration', 'fields':{
            'status':{'label':'Status', 'active':'no', 'type':'toggle', 'default':'10', 'toggles':{'10':'Reserved', '20':'Confirmed', '30':'Paid'}},
            'num_tickets':{'label':'Number of Tickets', 'type':'text', 'size':'small'},
            }},
        '_customer_notes':{'label':'Customer Notes', 'fields':{
            'customer_notes':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'small', 'type':'textarea'},
            }},
        '_notes':{'label':'Private Notes', 'fields':{
            'notes':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'small', 'type':'textarea'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_events_registrations.edit.save();'},
            'saveandinvoice':{'label':'Save and Invoice', 'fn':'M.ciniki_events_registrations.edit.save(\'yes\');'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_events_registrations.edit.remove();'},
            }},
        };  
    this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
    this.edit.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.events.registrationHistory', 'args':{'tnid':M.curTenantID, 
            'registration_id':this.registration_id, 'event_id':this.event_id, 'field':i}};
    }
    this.edit.sectionData = function(s) {
        if( s == 'invoice' ) { return this.data[s]!=null?{'invoice':this.data[s]}:{}; }
        return this.data[s];
    }
    this.edit.cellValue = function(s, i, j, d) {
        if( s == 'customer_details' ) {
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
                case 4: return d.payment_status_text;
            }
        }
    };
    this.edit.rowFn = function(s, i, d) { 
        if( s == 'invoice' ) { return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_events_registrations.edit.open();\',\'mc\',{\'invoice_id\':\'' + d.id + '\'});'; }
        return ''; 
    };
    this.edit.open = function(cb, cid, eid, rid) {
        this.reset();
        if( cid != null ) { this.customer_id = cid; }
        if( eid != null ) { this.event_id = eid; }
        if( rid != null ) { this.registration_id = rid; }

        // Check if this is editing a existing registration or adding a new one
        if( this.registration_id > 0 ) {
            this.sections._buttons.buttons.delete.visible = 'yes';
            M.api.getJSONCb('ciniki.events.registrationGet', {'tnid':M.curTenantID, 
                'registration_id':this.registration_id, 'customer':'yes', 'invoice':'yes'}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_events_registrations.edit;
                    p.data = rsp.registration;
                    p.event_id = rsp.registration.event_id;
                    p.customer_id = rsp.registration.customer_id;
                    p.sections.invoice.visible=(M.curTenant.modules['ciniki.sapos']!=null)?'yes':'no';
                    p.sections._buttons.buttons.saveandinvoice.visible=(M.curTenant.modules['ciniki.sapos']!=null&&rsp.registration.invoice_id==0)?'yes':'no';
                    p.event_id = rsp.registration.event_id;
                    p.refresh();
                    p.show(cb);
                });
        } else if( this.customer_id > 0 ) {
            this.sections._buttons.buttons.delete.visible = 'no';
            M.api.getJSONCb('ciniki.customers.customerDetails', {'tnid':M.curTenantID, 
                'customer_id':this.customer_id, 'phones':'yes', 'emails':'yes'}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_events_registrations.edit;
                    p.data = {'customer_details':rsp.details};
                    p.sections._buttons.buttons.saveandinvoice.visible = (M.curTenant.modules['ciniki.sapos']!=null)?'yes':'no';
                    p.refresh();
                    p.show(cb);
                });
        }
    };
    this.edit.save = function(inv) {
        var quantity = this.formFieldValue(this.sections.registration.fields.num_tickets, 'num_tickets');
        if( this.registration_id > 0 ) {
            var c = this.serializeForm('no');
            if( this.data.customer_id != this.customer_id ) {
                c += 'customer_id=' + this.customer_id + '&';
            }
            if( c != '' ) {
                M.api.postJSONCb('ciniki.events.registrationUpdate', 
                    {'tnid':M.curTenantID, 
                    'registration_id':M.ciniki_events_registrations.edit.registration_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                        var p = M.ciniki_events_registrations.edit;
                        if( inv != null && inv == 'yes' ) {
                            M.ciniki_events_registrations.newinvoice.open('M.ciniki_events_registrations.edit.open(null,null,'+p.registration_id+',null);', p.event_id, p.customer_id, p.registration_id, quantity);
                        } else {
                            p.close();
                        }
                    });
            } else {
                if( inv != null && inv == 'yes' ) {
                    M.ciniki_events_registrations.newinvoice.open('M.ciniki_events_registrations.edit.open(null,null,'+this.registration_id+',null);', this.event_id, this.customer_id, this.registration_id, quantity);
                } else {
                    this.close();
                }
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.events.registrationAdd', 
                {'tnid':M.curTenantID, 'event_id':this.event_id,
                    'customer_id':this.customer_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    if( inv != null && inv == 'yes' ) {
//                      M.ciniki_events_registrations.newinvoice.open(M.ciniki_events_registrations.edit.cb'M.ciniki_events_registrations.edit.open(null,null,'+rsp.id+',null);', M.ciniki_events_registrations.edit.event_id, M.ciniki_events_registrations.edit.customer_id, rsp.id, quantity);
                        M.ciniki_events_registrations.newinvoice.open(M.ciniki_events_registrations.edit.cb, M.ciniki_events_registrations.edit.event_id, M.ciniki_events_registrations.edit.customer_id, rsp.id, quantity);
                    } else {
                        M.ciniki_events_registrations.edit.close();
                    }
                });
        }
    };
    this.edit.remove = function() {
        M.confirm("Are you sure you want to remove this registration?",null,function() {
            M.api.getJSONCb('ciniki.events.registrationDelete', {'tnid':M.curTenantID, 'registration_id':M.ciniki_events_registrations.edit.registration_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_events_registrations.edit.close(); 
            });
        });
    };
    this.edit.updateCustomer = function(cid) {
        if( cid != null && this.customer_id != cid ) {
            this.customer_id = cid;
        }
        if( this.customer_id > 0 ) {
            M.api.getJSONCb('ciniki.customers.customerDetails', {'tnid':M.curTenantID, 'customer_id':this.customer_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_events_registrations.edit;
                p.data.customer = rsp.details;
                p.refreshSection('customer_details');
                p.show();
            });
        }   
    };
    this.edit.addCustomer = function(cb, eid) {
        // Setup the edit panel for when the customer edit returns
        if( cb != null ) { this.cb = cb; }
        if( eid != null ) { this.event_id = eid; }
        M.startApp('ciniki.customers.edit',null,cb,'mc',{'next':'M.ciniki_events_registrations.edit.openFromCustomer','customer_id':0});
    };
    this.edit.openFromCustomer = function(cid) {
        this.open(this.cb, cid, this.event_id, 0);
    };
    this.edit.addButton('save', 'Save', 'M.ciniki_events_registrations.edit.save();');
    this.edit.addClose('Cancel');

    //
    // The add invoice panel, which display the price list for quantity
    //
    this.newinvoice = new M.panel('Create Invoice', 'ciniki_events_registrations', 'newinvoice', 'mc', 'medium', 'sectioned', 'ciniki.events.registrations.newinvoice');
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
            'save':{'label':'Create Invoice', 'fn':'M.ciniki_events_registrations.newinvoice.createInvoice();'},
            }},
        };
    this.newinvoice.fieldValue = function(s, i, d) { return this.data[i]; }
    this.newinvoice.open = function(cb, eid, cid, rid, quantity) {
        if( eid != null ) { this.event_id = eid; }
        if( cid != null ) { this.customer_id = cid; }
        if( rid != null ) { this.registration_id = rid; }
        if( quantity != null ) { this.quantity = quantity; }
        M.api.getJSONCb('ciniki.events.eventPriceList', {'tnid':M.curTenantID, 'event_id':this.event_id}, function(rsp) {
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
    this.newinvoice.createInvoice = function() {
        var items = [];
        items[0] = {
            'status':0,
            'object':'ciniki.events.registration',
            'object_id':this.registration_id,
            'description':'',
            'quantity':this.quantity,
            'unit_amount':0,
            'unit_discount_amount':0,
            'unit_discount_percentage':0,
            'taxtype_id':0,
            'notes':'',
            };
        var price_id = this.formFieldValue(this.sections.prices.fields.price_id, 'price_id');
        var prices = this.prices;
        // Find the price selected
        for(i in prices) {
            if( prices[i].price.id == price_id ) {
                items[0].price_id = prices[i].price.id;
                items[0].code = '';
                items[0].description = prices[i].price.event_name + (prices[i].price.name!=''?' - '+prices[i].price.name:'');
                items[0].unit_amount = prices[i].price.unit_amount;
                items[0].unit_discount_amount = prices[i].price.unit_discount_amount;
                items[0].unit_discount_percentage = prices[i].price.unit_discount_percentage;
                items[0].taxtype_id = prices[i].price.taxtype_id;
                items[0].flags = 0x20;
            }
        }
        M.startApp('ciniki.sapos.invoice',null,this.cb,'mc',{'customer_id':this.customer_id,'items':items});
    };
    this.newinvoice.addClose('Cancel');

    //
    // Arguments:
    // aG - The arguments to be parsed into args
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        this.edit.sections.registration.fields.status.active = (M.curTenant.modules['ciniki.events'].flags&0x04)>0?'yes':'no';

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_events_registrations', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        this.menu.open(cb, args.event_id);
    }
}
