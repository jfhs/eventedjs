if (jQuery) {
	Evented = function(params) {
		params = jQuery.extend({
			'pull_interval': 0
		}, params);
		var result = {
			'_params': {},
			'_handlers': {},
			'_global_handlers': [],
			'_timeout': 0,
			'_last_state': false,
			'_pulling': false,
			_init: function(params) {
				this._params = params;
				var _this = this;
				if (params.pull_interval) {
					this._timeout = setTimeout(function() {
						_this.pull();
					}, params.pull_interval);
				}
			},
			_ajax_handler: function(data) {
				var parsed = JSON.parse(data.responseText);
				this._last_state = parsed.state;
				var events = parsed.events;
				for(var i in events) {
					var event_data = {
						'event': events[i].event,
						'data': events[i].data,
						'local': false
					}
					this._trigger(event_data);
				}
				var _this = this;
				this._timeout = setTimeout(function() {
					_this.pull();
				}, this._params.pull_interval);
				this._pulling = false;
			},
			pull: function() {
				if (this._pulling) {
					return;
				}
				this._pulling = true;
				jQuery.ajax({
					'type': 'GET',
					'url': this._params.url,
					'data': {
						'state': this._last_state
					},
					'context': this,
					'complete': this._ajax_handler
				});
			},
			bind: function(event, cb) {
				if (event == null) {
					this._global_handlers.push(cb);
					return;
				}
				if (typeof(this._handlers[event]) == 'undefined') {
					this._handlers[event] = [];
				}
				this._handlers[event].push(cb);
			},
			_trigger: function(event_data) {
				var event = event_data.event;
				for(var i in this._handlers[event]) {
					var h = this._handlers[event][i];
					if (typeof(h) == 'function') {
						if (h(event_data)) {
							return;
						}
					} else if(typeof(h) == 'object') {
						if (h.cb.apply(h.context, [event_data])) {
							return;
						}
					}
				}
				for(var i in this._global_handlers) {
					var h = this._global_handlers[i];
					if (typeof(h) == 'function') {
						if (h(event_data)) {
							return;
						}
					} else if(typeof(h) == 'object') {
						if (h.cb.apply(h.context, [event_data])) {
							return;
						}
					}
				}
			},
			trigger: function(event, data, send) {
				var event_data = {'event':event, 'data':data, 'local': true};
				this._trigger(event_data);
				if ((typeof(send) == 'undefined') || send) {
					jQuery.ajax({
						'type': 'POST',
						'url': this._params.url,
						'data': {
							'event': event,
							'data': JSON.stringify(data),
							'state': this._last_state
						},
						'context': this,
						'complete': this._ajax_handler
					});
				}
			}
		}
		result._init(params);
		return result;
	}
} else {
	console.log("Can't init evented.js since jQuery is not available");
}