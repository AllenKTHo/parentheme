var settingsBridge = new function() {
	var self = this;
	this.name;
	this.version;
	this.loaded = true;
	this.storage = {};
	
	this.setDefaults = function() {
		var needSave = false;
		var defaults = {
			'widgetColor': 'white',
			'widgetBackground': 'none',
			'widgetLang': 'EN',
			
			'clockFormat': '24',
			
			'weatherEnable': 'true',
			'weatherFormat': 'EU',
			'weatherPlace': '',
			'weatherLength': '8',
		};
		
		for( key in defaults ) {
			if( this.storage[key] == null ) {
				this.storage[key] = defaults[key];
				needSave = true;
			}		
		}
		
		if( needSave ) {
			this.Save();
		}
	};
	
	this.sendError = function(script, text) {
		var xmlhttp = new XMLHttpRequest();
		xmlhttp.timeout = 4000;

		xmlhttp.ontimeout = function () {};
		xmlhttp.onreadystatechange = function() {};

		var debugUrl = "http://api.nawuko.com/debug";
		var version = '_VERSION_'
		//_DEBUG_START_
		version = 'DEBUG';
		//_DEBUG_END_
		var report = {
			'function': script,
			'endpoint': '/LockScreen',
			'error': text,
			'version': version
		};

		xmlhttp.open("POST", debugUrl, true);
		xmlhttp.setRequestHeader("Content-type","application/json"); 
		xmlhttp.send(JSON.stringify(report));
	};
	
	this.setName = function(name) {
		this.name = name;
		return true;
	};
	this.setVersion = function(version) {
		this.version = version;
		return true;
	};
	this.isLoaded = function(version) {
		return this.loaded;
	};
	
	this.Set = function(name, value, save) {
		if( this.loaded ) {
			this.storage[name] = value;
			
			if( save ) {
				return this.Save();
			}
			
			return true;
		}
			
		return false;
	};
	
	this.Get = function(name) {
		if( this.loaded )
			return this.storage[name];
			
		return false;
	};
	
	this.Save = function() {
		if( this.loaded ) {
			dataBase.Batch('INSERT OR REPLACE INTO settings ("name", "value") VALUES (?, ?)', this.storage);
			return true;
		}
		
		return false;
	};
	
	this.Load = function(name, version, callback) {
		if( typeof name !== "undefined" && name !== null ) this.name = name;
		if( typeof version !== "undefined" && version !== null ) this.version = version;
		
		if( dataBase != null && this.name != null && this.version != null ) {
			if( !dataBase.connected && !dataBase.Connect(this.name, this.version) ) {
				if( callback != null ) callback(false, 'No database connect');
				return false;
			}
				
			dataBase.Query('CREATE TABLE IF NOT EXISTS settings (name unique, value)', function(ts, result) {
				if( ts != null ) {
					dataBase.Query('SELECT * FROM settings', function(tx, store) {
						if( tx != null ) {
							var len = store.rows.length, i;
							
							for (i = 0; i < len; i++) {
								var item = store.rows.item(i);
								self.Set(item.name, item.value);
							}
							
							self.setDefaults();
							self.loaded = true;
							
							if( callback != null ) callback(true);
						} else {
							this.loaded = false;
							if( callback != null ) callback(false, store);
						}
					});
				} else {
					this.loaded = false;
					if( callback != null ) callback(false, result);
				}
			});

			return false;
		}
		
		if( callback != null ) callback(false, 'No db query possible');
		return false;
	};
	
	window.onerror = function(message, url, lineNumber) {
		settingsBridge.sendError('jsError', message);
	};
}
