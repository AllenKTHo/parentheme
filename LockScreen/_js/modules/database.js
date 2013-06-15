var dataBase = new function() {
	this.connected = false;
	this.connection;
    this.name;
    this.version;
	
	this.isConnected = function() {
		return ( this.connection != null && this.connected )
	};
	this.setName = function(name) {
		this.name = name;
		return true;
	};
	this.setVersion = function(version) {
		this.version = version;
		return true;
	};
    this.getInfo = function () {
        return { name: this.name, version: this.version, connected: this.connected };
    };
	this.Batch = function(query, data, callback) {
		if( query != null && this.connection != null && this.connected ) {
			this.connection.transaction(function (transaction) {
				for( key in data ) {
					transaction.executeSql(query, [key, data[key]]);
				}
				if( callback != null ) callback(transaction, true);
			}, function(error) {
				if( callback != null ) callback(null, error);
			});
		} else {
			if( callback != null ) callback(null, false);
		}
	};
	this.Query = function(query, callback) {
		if( query != null && this.connection != null && this.connected ) {
			this.connection.transaction(function (transaction) {
				transaction.executeSql(query, [], callback);
			}, function(error) {
				if( callback != null ) callback(null, error);
			});
		} else {
			if( callback != null ) callback(null, false);
		}
	};
	this.Connect = function(name, version) {
		if ( typeof name !== "undefined" && name !== null ) this.name = name;
		if ( typeof version !== "undefined" && version !== null ) this.version = version;
		
		this.connection = null;
		
		if( this.name != null && this.version != null )
			this.connection = openDatabase(this.name, this.version, this.name + " " + this.version, 65535);
		
		if( this.connection != null ) {
			this.connected = true;
			return true;
		} else {
			return false;
		}
	};
}