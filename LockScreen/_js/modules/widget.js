var widget = new function() {
	var _this = this;
	this.lang = {};
	this.swipe;
	this.slidetime;
	this.device;
	this.windSymbol = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'a' ];
	
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
	
	this.setLanguage = function() {
		switch(Settings.widgetLang) {
			case 'DE':
				this.lang['WEEKS'] = ['SO', 'MO', 'DI', 'MI', 'DO', 'FR', 'SA'];
				this.lang['MONTHS'] = ['JAN', 'FEB', 'M&Auml;R', 'APR', 'MAI', 'JUN', 'JUL', 'AUG', 'SEP', 'OKT', 'NOV', 'DEZ'];
				break;
			case 'FR':
				this.lang['WEEKS'] = ['DI', 'LU', 'MA', 'ME', 'JE', 'VE', 'SA'];
				this.lang['MONTHS'] = ['JAN', 'F&Eacute;V', 'MAR', 'AVR', 'MAI', 'JUI', 'JUI', 'AO&Ucirc;', 'SEP', 'OCT', 'NOV', 'D&Eacute;C']; 
				break;
			case 'IT':
				this.lang['WEEKS'] = ['DO', 'LU', 'MA', 'ME', 'GI', 'VE', 'SA'];
				this.lang['MONTHS'] = ['GEN', 'FEB', 'MAR', 'APR', 'MAG', 'GIU', 'LUG', 'AGO', 'SET', 'OTT', 'NOV', 'DIC'];
				break;
			case 'ES':
				this.lang['WEEKS'] = ['DO', 'LU', 'MA', 'MI', 'JU', 'VI', 'SA'];
				this.lang['MONTHS'] = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SET', 'OCT', 'NOV', 'DIC'];
				break;
			case 'DA':
				this.lang['WEEKS'] = ['S&Oslash;', 'MA', 'TI', 'ON', 'TO', 'FR', 'L&Oslash;'];
				this.lang['MONTHS'] = ['JAN', 'FEB', 'MAR', 'APR', 'MAJ', 'JUN', 'JUL', 'AUG', 'SEP', 'OKT', 'NOV', 'DEC'];
				break;
			default:
				this.lang['WEEKS'] = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
				this.lang['MONTHS'] = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC']; 
				break;	
		}
	};
	
	this.setOrientation = function() {
		//_DEBUG_START_
			window.orientation = 0;
		//_DEBUG_END_
		document.body.classList.remove('landscape');
		document.body.classList.remove('portrait');
		
		if( typeof window.orientation === 'undefined' || Math.abs( window.orientation ) === 90 ) {
			document.body.classList.add('landscape');
		} else {
			document.body.classList.add('portrait');
		}
	
	}

	this.setStyle = function() {
		document.body.classList.add(Settings.widgetColor);
		
		if( Settings.widgetBackground != 'none' )
			document.body.classList.add(Settings.widgetBackground);
		
		var height = window.screen.height;
		//_DEBUG_START_	
			if( location.hash == '#ipad' )
				height = 1024; // ipad test
			else if( location.hash == '#iphone4' )
				height = 480; // iphone 4 test
			else
				height = 568; // iphone 5 test
		//_DEBUG_END_
		
		if( height == 480 ) {
			document.body.classList.add('iphone-4');
			_this.device = 'iphone-4';
		} else if( height == 568 ) {
			document.body.classList.add('iphone-5');
			_this.device = 'iphone-5';
		} else { // if( height == 1024 ) {
			document.body.classList.add('ipad');
			_this.device = 'ipad';
		}
		
		if( _this.device == 'ipad' )
			document.body.classList.add('single-page');
		else
			document.body.classList.add(Settings.widgetLayout);
	};
	
	this.updateClock = function() {
		var timeEle = document.getElementById('time');
		var dateEle = document.getElementById('date');
	
		// date function
		var clockDate = new Date();
		var clockDay = clockDate.getDate();
		if (clockDay < 10){ clockDay = '0' + clockDay; }
		
		var dayString = widget.lang['WEEKS'][clockDate.getDay()]; // use global scope for updates!
		var monthString = widget.lang['MONTHS'][clockDate.getMonth()];

		dateEle.innerHTML = dayString + ' ' + clockDay + ' ' + monthString;
		
		// clock function
		var clockHour = clockDate.getHours();
		if( clockHour > 12 && Settings.clockFormat == '12' ) {
			clockHour = clockHour - 12;  // 12 hour mode!
		}
	
		if( clockHour < 10 ) {
			clockHour = '0' + clockHour;
		}

		var clockMinute = clockDate.getMinutes();
		if ( clockMinute < 10 ) {
			clockMinute = '0' + clockMinute;
		}

		timeEle.innerHTML = clockHour + ':' + clockMinute;

		// calculate next minute tick ( seconds left + 10ms )
		setTimeout(widget.updateClock, (60 - (new Date()).getSeconds()) * 1000 + 10);
	};
	
	this.renderError = function(message) {
		var error = document.getElementById('error')
			, table = document.getElementById('weather');
			
		table.innerHTML = '';
		table.style.display = 'none';
		
		error.innerHTML = message;
		error.style.display = 'block';
	};
	
	this.renderWeather = function(data) {
			
		var table = document.getElementById('weather');
		
		for(index in data) {
			var info = data[index];
			var row = document.createElement('tr');
			
			// time
			var timeDate = new Date(info['time']);
			var timeHours = timeDate.getHours();
			
			if( timeHours > 12 && Settings.clockFormat == '12' ) {
				timeHours = timeHours - 12;  // 12 hour mode!
			}
			
			var timeRow = document.createElement('th');
			var timeText = document.createTextNode( timeHours < 10 ? '0' + timeHours : timeHours );
			timeRow.appendChild(timeText);
			
			// symbol
			var imageSymbol = info['symbol'];
			
			if( imageSymbol < 10 )
				imageSymbol = '0' + imageSymbol;
				
			if( imageSymbol == '01'
				|| imageSymbol == '02'
				|| imageSymbol == '03'
				|| imageSymbol == '05'
				|| imageSymbol == '06'
				|| imageSymbol == '07'
				|| imageSymbol == '08' ) {
				
				if( timeHours >= 7 && timeHours <= 19 )
					imageSymbol = imageSymbol + "d";
				else
					imageSymbol = imageSymbol + "n";
			}
					
			var imageRow = document.createElement("td");
			imageRow.className = "image";
			
			var imageImage = document.createElement("img");
			imageImage.src = "resources/" + Settings.widgetColor + "/" + imageSymbol + ".png";
			imageRow.appendChild(imageImage);
			
			// rain
			var rainRow = document.createElement("td");
			rainRow.className = "ned";
			
			if( Settings.weatherFormat == "EU" )
				rainRow.innerHTML = info['rain'] + "<small><xs> </xs>mm</small>";
			else if( Settings.weatherFormat == "US" )
				rainRow.innerHTML = info['rain'] + "<xs> </xs>";
			
			// temperature
			var tempRow = document.createElement("td");
			tempRow.className = ( info['temperature'] >= 0 ) ? 'plus' : 'minus';
			tempRow.innerHTML = info['temperature'];
			
			// wind direction
			var windSymbol = this.windSymbol[info['winddirection']];
			var dirRow = document.createElement("td");
			dirRow.className = "v";
			
			var dirImage = document.createElement("img");
			dirImage.src = "resources/" + Settings.widgetColor + "/" + windSymbol + ".png";
			dirRow.appendChild(dirImage);
			
			// wind speed
			var speedRow = document.createElement("td");
			speedRow.className = "ms";
			
			if( Settings.weatherFormat == "EU" )
				speedRow.innerHTML = info['windspeed'] + "<small><xs> </xs>m/s<small>";
			else if( Settings.weatherFormat == "US" )
				speedRow.innerHTML = info['windspeed'] + "<small><xs> </xs>mph<small>";
			
			// append to main row
			row.appendChild(timeRow);
			row.appendChild(imageRow);
			row.appendChild(rainRow);
			row.appendChild(tempRow);
			row.appendChild(dirRow);
			row.appendChild(speedRow);

			table.appendChild(row);
		}
		
		table.style.display = 'block';
	};
	
	this.onSlide = function(page) {
		clearTimeout(this.slidetime);
		if( page == 1 ) {
			this.slidetime = setTimeout(function() {
				_this.swipe.slide(0);
			}, 9 * 1000 );
		}
	};
	
	this.randomString = function() {
		var chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz';
		var string_length = 12;
		var string = '';
		for( var i=0; i < string_length; i++ ) {
			var rnum = Math.floor( Math.random() * chars.length );
			string += chars.substring(rnum, rnum + 1);
		}
		return string;
	};
	
	this.getPlace = function() {
		return Settings.weatherPlace.replace(/^((http:\/\/)*([w|m]*)(\.)*yr\.no\/place\/)/gi, '').replace(/( )*/gi, '').replace(/^\/+/gi, '').replace(/\/+$/gi, '').toLowerCase();
	};
	
	this.getWeather = function() {
	
		var table = document.getElementById('weather');			
		table.innerHTML = ''; // empty table
		table.style.display = 'none';
	
		var xmlhttp = new XMLHttpRequest();
		xmlhttp.timeout = 4000;

		xmlhttp.ontimeout = function () {
			setTimeout(widget.getWeather, 2 * 60 * 1000);
		};
		
		xmlhttp.onerror = function() {
			widget.renderError('We could not connect to the WeatherAPI. Please check your connection.');
			setTimeout(widget.getWeather, 2 * 60 * 1000);
		};
		
		xmlhttp.onreadystatechange = function() {
			if (xmlhttp.readyState==4 && xmlhttp.status==200)
			{
				var response = JSON.parse(xmlhttp.responseText);
				if( response['status'] == 200 ) {
				
					widget.renderWeather(response.forecast);
					
					var currentTime = new Date().getTime();
					var nextUpdate = response.nextupdate - currentTime;
					
					if( nextUpdate > 120000 ) // 2 min
						setTimeout(widget.getWeather, nextUpdate);
					else
						setTimeout(widget.getWeather, 2 * 60 * 1000);
						
				} else {
					setTimeout(widget.getWeather, 2 * 60 * 1000);
					widget.renderError(response.message);
				}
			}
		};


		var weatherUrl = "http://api.nawuko.com/weather";
		weatherUrl += "/" + Settings.weatherFormat;
		weatherUrl += "/" + Settings.weatherLength;
		weatherUrl += "/" + widget.getPlace();
		weatherUrl += "?_r=" + widget.randomString();
		weatherUrl += "&_v=_VERSION_";
		
		xmlhttp.open('GET', weatherUrl, true);
		xmlhttp.send();
	};
	
	this.load = function() {
		document.body.style.backgroundImage = "url('/var/mobile/Library/SpringBoard/Converted-LockBackground.jpg?_r=" + _this.randomString() + "')";
		//_DEBUG_START_					
			document.body.style.backgroundImage = "url('//cdn.nawuko.com/images/LockBackground_iPhone5.png')";
		//_DEBUG_END_
		var loadScript = document.createElement('script'); loadScript.type = 'text/javascript'; loadScript.async = false; loadScript.src = 'options.js?_r=' + _this.randomString();
		var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(loadScript, s);
		
		_this.waitForSettings = setInterval(function() {
			if( typeof Settings != 'undefined' ) {
				clearInterval(_this.waitForSettings);
				_this.init();
			}
		}, 20)
	};

	this.init = function() {

		//_DEBUG_START_					
			Settings.weatherEnable = 'on';
			Settings.widgetBackground = 'translucent';
			Settings.widgetLayout = 'single-page';
		//_DEBUG_END_

		_this.setStyle();
		_this.setOrientation();
		_this.setLanguage();
		_this.updateClock();
		
		if( _this.device != 'ipad' && Settings.widgetLayout == 'single-page' && Settings.weatherLength > 8 )
			Settings.weatherLength = 8;
		
		if( _this.device != 'ipad' && Settings.widgetLayout != 'single-page' ) {
			_this.swipe = this.swipe = Swipe(document.getElementById('content'), {
				continuous: false,
				callback: _this.onSlide,
			});
		}

		if( Settings.weatherEnable == 'on' && _this.getPlace() != '') {
			document.body.classList.add('weather');
			_this.getWeather();
		} else {
			document.body.classList.add('no-weather');
			_this.swipe && _this.swipe.kill();
		}
	}
}

window.onerror = function(message, url, lineNumber) {
	widget.sendError('jsError', message + ' on line ' + lineNumber);
};

window.addEventListener('DOMContentLoaded', widget.load);
window.addEventListener('orientationchange', widget.setOrientation);
