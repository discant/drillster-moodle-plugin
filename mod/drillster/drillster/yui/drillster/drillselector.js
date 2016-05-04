
M.form_drillselector = {templates:{}};

M.form_drillselector.views = {
    myrepertoire: 'myrepertoire',
    drillstore: 'drillstore'
};

M.form_drillselector.set_templates = function(Y, templates) {
    M.form_drillselector.templates = templates;
}

/**
 * This fucntion is called for each file picker on page.
 */
M.form_drillselector.init = function(Y, options) {
	
    var DrillSelectorHelper = function(options) {
        DrillSelectorHelper.superclass.constructor.apply(this, arguments);
    };
    
    DrillSelectorHelper.NAME = "DrillSelector";
    
    DrillSelectorHelper.ATTRS = {
        options: {},
        lang: {}
    };

    Y.extend(DrillSelectorHelper, Y.Base, {
    	
        api: M.cfg.wwwroot+'/mod/drillster/drillselector_ajax.php',
        initializer: function(options) {
			
			var scope = this;	
			this.options = options;
			
			this.set_first_render(true);
		
			this.dom = {
				drillselector: Y.one('#drillselector-'+options.client_id),
				form: {
					id: document.getElementsByName(this.options.elname+'[drillid]')[0],
					view: document.getElementsByName(this.options.elname+'[view]')[0],
					search: document.getElementsByName(this.options.elname+'[query]')[0]
				}
			};
			this.dom.drillscontainer = this.dom.drillselector.one('.ds-drills-container');
			this.dom.nav = {
				myrepertoire: this.dom.drillselector.one('.ds-btn-myrepertoire'),
				store: this.dom.drillselector.one('.ds-btn-drillstore'),
				search: {
					field: this.dom.drillselector.one('.ds-searchfield'),
					button: this.dom.drillselector.one('.ds-btn-search'),
					clear: this.dom.drillselector.one('.ds-btn-searchclear')
				}
			};
			
			this.dom.nav.myrepertoire.on('click', function(e){
				scope.set_view(M.form_drillselector.views.myrepertoire);
				scope.update_view();
				e.preventDefault();
			});
			
			this.dom.nav.store.on('click', function(e){
				scope.set_view(M.form_drillselector.views.drillstore);
				scope.update_view();
				e.preventDefault();
			});
			
			this.dom.nav.search.button.on('click', function(e){
				scope.set_query(scope.dom.nav.search.field.get('value'));
				scope.update_view();
				e.preventDefault();
			});

			this.dom.nav.search.field.on('key', function(e){
				scope.set_query(scope.dom.nav.search.field.get('value'));
				scope.update_view();
				e.preventDefault();
			}, 'enter');
			
			this.dom.nav.search.clear.on('click', function(e){
				scope.set_query('');
				scope.update_view();
				e.preventDefault();
			});
			
			this.set_query(this.dom.form.search.getAttribute('value'));
			this.set_view(this.dom.form.view.getAttribute('value'));
			
			// als laatste setten, anders worden query & view niet meer geaccepteerd
			this.set_drillid(this.dom.form.id.getAttribute('value'));

			this.update_view();
		},
		drillid_in_response: function(response){
			if(this.get_drillid()) for(var n in response.data.drills) if(response.data.drills[n].id == this.get_drillid()) return true;	
			return false;
		},
		get_drillid: function(){
			if(this._drillid === null || this._drillid === 'false' || this._drillid === 0 || this._drillid === '0' || this._drillid === '') return false;
			return this._drillid;
		},
		get_current_query: function(){
			if(typeof this._currentquery === 'undefined' || this._currentquery === null) return false;
			
			return this._currentquery;
		},
		get_current_view: function(){
			if(typeof this._currentview === 'undefined' || this._currentview === null) return false;
			return this._currentview;
		},
		get_query: function(){
			if(typeof this._query === 'undefined' || this._query === null) return '';
			return this._query;
		},
		get_view: function(){
			if(typeof this._view !== 'undefined') for(var n in M.form_drillselector.views) if(this._view === M.form_drillselector.views[n]) return this._view;
			return M.form_drillselector.views.myrepertoire;
		},
		get_first_render: function(){
			return this._first_render;
		},
		get_locked: function(){
			if(typeof this._locked == 'undefined') return false;
			return this._locked; 
		},
		must_change_view: function(){
			if(this.get_current_view() !== this.get_view() || this.get_current_query() !== this.get_query()) return true;
			return false;	
		},
		render_drill_node: function(drill){
			
			var scope = this;
			
			var html = this._template(M.form_drillselector.templates.drill, {
				name: 			drill.name,
				icon: 			drill.icon.url,
				description: 	drill.description,
				subject:		drill.subject,
				size: 			drill.size,
				id:				drill.id
			});
			
			node = Y.Node.create(html)
			node.setAttribute('data-id', drill.id); // kan niet via de template?
			node.one('.ds-btn-pickdrill').on('click', function(e){
				scope.set_drillid(e.currentTarget.ancestor('div.ds-drill').getData('id'));
				scope.update_view();
				e.preventDefault();
			});
			node.one('.ds-btn-removedrill').on('click', function(e){
				scope.set_drillid(0);
				scope.update_view();
				e.preventDefault();
			});
			
			return node;
		},
		render_drill: function(drill){
			
			var scope = this;
			this.dom.drillscontainer.setContent('');
			
			var node = this.render_drill_node(drill);
			this.dom.drillscontainer.appendChild(node);
		},
		render_drills: function(drills){
			
			var scope = this;
			this.dom.drillscontainer.setContent('');
			
			if(drills.length == 0) this.dom.drillscontainer.appendChild(this._template(M.form_drillselector.templates.noresults, {query: this.get_query()}));
			
			for(var n in drills){
				var drill = drills[n];
				var node = this.render_drill_node(drill);
				
				this.dom.drillscontainer.appendChild(node);
			}
		},
		reset: function(){
			this.set_current_view(false);
            this.set_locked(false);
			this.dom.drillscontainer.setContent(this._template(M.form_drillselector.templates.tryagain));
		},
		set_current_query: function(query){
			return this._currentquery = query;
		},
		set_current_view: function(view){
			return this._currentview = view;
		},
		set_drillid: function(drillid){
			
			if(this.get_locked()) return false;
			
			this._drillid = drillid;
			this.dom.form.id.setAttribute('value', this.get_drillid());
		},
		set_query: function(query){
			
			if(this.get_locked()) return false;
			// mag niet worden aangepast omdat de drill al gekozen is
			if(this.get_drillid()) return false;
			
			this._query = query;
			this.dom.form.search.setAttribute('value', this.get_query());
			this.dom.nav.search.field.set('value', this.get_query());
		},
		set_view: function(view){
			
			if(this.get_locked()) return false;
			// mag niet worden aangepast omdat de drill al gekozen is
			if(this.get_drillid()) return false;
			
			this._view = view;
			this.dom.form.view.setAttribute('value', this.get_view());
		},
		set_first_render: function(bool){
			this._first_render = bool;
		},
		set_locked: function(locked){
			this._locked = locked;
		},
		update_interface: function(){
			
			var drills = this.dom.drillscontainer.all('.ds-drill');
			var scope = this;
			
			drills.each(function(drill, index) {
				
				if(!scope.get_drillid()){
					drill.one('.ds-btn-pickdrill').removeClass('ds-btn-hide');
					drill.one('.ds-btn-removedrill').addClass('ds-btn-hide');
					drill.removeClass('ds-drill-inactive');
				} else {
					
					drill.one('.ds-btn-pickdrill').addClass('ds-btn-hide');
					drill.one('.ds-btn-removedrill').addClass('ds-btn-hide');
					drill.addClass('ds-drill-inactive');

					if(scope.get_drillid() == drill.getData('id')) {
						
						drill.one('.ds-btn-removedrill').removeClass('ds-btn-hide');
						drill.one('.ds-btn-removedrill').addClass('ds-btn-active');
						drill.removeClass('ds-drill-inactive');
						
						if(scope.get_first_render()) scope.dom.drillscontainer._node.scrollTop = drill._node.offsetTop - 80;
					}
				}
			});

			this.set_query(this.get_query());

			if(this.get_query()){
				this.dom.nav.search.clear.removeClass('ds-btn-searchclear-hide');				
			} else {
				this.dom.nav.search.clear.addClass('ds-btn-searchclear-hide');
			}
			
			switch(this.get_view()){
				case M.form_drillselector.views.myrepertoire:
					this.dom.nav.myrepertoire.addClass('ds-btn-active');			
					this.dom.nav.store.removeClass('ds-btn-active');
				break;
				case M.form_drillselector.views.drillstore:
					this.dom.nav.myrepertoire.removeClass('ds-btn-active');			
					this.dom.nav.store.addClass('ds-btn-active');
				break;
			}

			if(!this.get_drillid()){
				this.dom.nav.myrepertoire.removeClass('ds-btn-disabled').addClass('ds-btn-enabled');
				this.dom.nav.store.removeClass('ds-btn-disabled').addClass('ds-btn-enabled');
				this.dom.nav.search.button.removeClass('ds-btn-disabled').addClass('ds-btn-enabled');
				this.dom.nav.search.clear.removeClass('ds-btn-disabled').addClass('ds-btn-enabled');
				this.dom.nav.search.field.removeClass('ds-input-disabled').addClass('ds-btn-enabled');
				this.dom.nav.search.field.removeAttribute('disabled');
			} else {
				this.dom.nav.myrepertoire.addClass('ds-btn-disabled').removeClass('ds-btn-enabled');
				this.dom.nav.store.addClass('ds-btn-disabled').removeClass('ds-btn-enabled');
				this.dom.nav.search.button.addClass('ds-btn-disabled').removeClass('ds-btn-enabled');
				this.dom.nav.search.clear.addClass('ds-btn-disabled').removeClass('ds-btn-enabled');
				this.dom.nav.search.field.addClass('ds-input-disabled').removeClass('ds-btn-enabled');
				this.dom.nav.search.field.setAttribute('disabled', 'disabled');
			}
		},
		update_view: function(){
			
			var scope = this;
			this.update_interface();
			
			if(this.must_change_view()){
				
				if(this.get_view() == M.form_drillselector.views.drillstore && this.get_query() === ''){
					
					// empty store template
					this.dom.drillscontainer.setContent(this._template(M.form_drillselector.templates.emptystore));

				} else {
					
					this.dom.drillscontainer.setContent(this._template(M.form_drillselector.templates.viewloading));
					
					// eerst de drills 
					this.set_locked(true);
					
					this._request({
						action: 'drills',
						params:{
							view: this.get_view(),
							searchquery: this.get_query()
						},
						callback: function(id, response){
							

							if(this.drillid_in_response(response) || this.get_drillid() === false){
								
								// search the store screen
								this.render_drills(response.data.drills);
								this.update_interface();
								this.set_locked(false);
								
							} else {

								// zit de drill er niet bij? Bijv: niet meer met het zoekwoord te vinden (of bestaat niet meer)
								this.set_locked(true);
								
								this._request({
									action:'drill',
									params:{
										drillid: this.get_drillid()	
									},
									ignore_status_codes: [404],
									callback: function(id, response){
										
										if(response.status_code == 404){
											
											this.set_locked(false);	
											
											// 404? de drill bestaat niet meer
											this.set_drillid(false);
											this.set_query('');
											this.set_view(M.form_drillselector.views.myrepertoire);
											
											this._print_msg(response.error_data.description);
											
											this.update_view();
											
										} else {
											
											// bestaat nog wel, alleen die drill printen
											this.render_drill(response.data);
											this.update_interface();
											this.set_locked(false);	
										}										
									}
								});
							}
							
							this.set_first_render(false);
						}	
					});
				}
			}
			
			this.set_current_view(this.get_view());
			this.set_current_query(this.get_query());
			
		},
		_template: function(template, data){
			for(var n in data) if(typeof data[n] === "undefined") data[n] = false;
		    var t = new Y.Template();
		    return t.render(template, data);		
        },
        _request: function(args) {
        	
        	var args = Y.merge({
        		'callback': function(){},
        		'params': {},
        		'ignore_status_codes': [],       		
        	}, args);
        	
            var api = this.api + '?action='+args.action;
            var params = {};
            var scope = this;
            
            params['sesskey'] = M.cfg.sesskey;
            
            if (args['params']) {
                for (i in args['params']) {
                    params[i] = args['params'][i];
                }
            }
            var cfg = {
                method: 'POST',
                on: {
                    complete: function(id,o,p) {
                        if (!o) {
                            alert('IO FATAL');
                            return;
                        }
                        var data = null;
                        try {
                            json = Y.JSON.parse(o.responseText);
                        } catch(e) {
                            scope._print_msg(M.str.repository.invalidjson, 'error');
                            //Y.error(M.str.repository.invalidjson+":\n"+o.responseText);
                            scope.reset();
                            return;
                        }
						
                        if(typeof json.error != 'undefined'){
                        	
                        	scope._print_msg(json.error); // moodle error
                        	scope.reset();
                        	
                        } else if(json.status_code != '200' && args.ignore_status_codes.indexOf(json.status_code) === -1){
                        	
                        	// api error
                        	scope._print_msg(json.status+' ('+json.status_code+')'); // api error
                        	scope.reset();
                        	
                        } else {
                        	args.callback.apply(scope, [id,json,p]);
                        }
                    }
                },
                arguments: {
                    scope: scope
                },
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                data: build_querystring(params)
            };
            
            Y.io.queue(api, cfg);
        },
		_print_msg: function(msg, type){
			
            var header = M.str.moodle.error;
            if (type != 'error') {
                type = 'info'; // one of only two types excepted
                header = M.str.moodle.info;
            }
            if (!this.msg_dlg) {
            	
            	var html = this._template(M.form_drillselector.templates.message);
            	this.msg_dlg_node = Y.Node.create(html)
            	
                var nodeid = this.msg_dlg_node.generateID();

                this.msg_dlg = new M.core.dialogue({
                    draggable    : true,
                    bodyContent  : this.msg_dlg_node,
                    centered     : true,
                    modal        : true,
                    visible      : false,
                });
                this.msg_dlg_node.one('.ds-msg-butok').on('click', function(e) {
                    e.preventDefault();
                    this.msg_dlg.hide();
                }, this);
            }

            this.msg_dlg.set('headerContent', header);
            this.msg_dlg_node.removeClass('ds-msg-info').removeClass('ds-msg-error').addClass('ds-msg-'+type)
            this.msg_dlg_node.one('.ds-msg-text').setContent(Y.Escape.html(msg));
            this.msg_dlg.show();
		}
	});
    
    var drillselector = Y.one('#drillselector-'+options.client_id);
    drillselector.removeClass('ds-loading').addClass('ds-loaded');

    var drillselector = new DrillSelectorHelper(options);
};