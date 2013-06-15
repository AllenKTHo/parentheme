var widget = new function() {
	var _this = this;
	this.lang = {};
	this.swipe;
	this.slidetime;
	this.windSymbol = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'a' ];
	
	this.setLanguage = function() {
		switch(settingsBridge.Get('widgetLang')) {
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

	this.setStyle = function() {
		document.body.classList.add(settingsBridge.Get('widgetColor'));
		
		if( settingsBridge.Get('widgetBackground') != 'none' )
			document.body.classList.add(settingsBridge.Get('widgetBackground'));
		
		var height = window.screen.height;
		
		if( height == 480 ) {
			document.body.style.height = '480px';
		} else if( height == 568 ) {
			document.body.style.height = '568px';
		} else { // if( height == 1024 ) {
			document.body.style.height = '1024px';
			document.body.style.width = '1024px';
			document.body.classList.add('ipad');
		}
		
	};
	
	this.updateClock = function() {
		var timeEle = document.getElementById('time');
		var dateEle = document.getElementById('date');
	
		// date function
		var clockDate = new Date();
		var clockDay = clockDate.getDate();
		if (clockDay < 10){ clockDay = "0" + clockDay; }
		
		var dayString = widget.lang['WEEKS'][clockDate.getDay()]; // use global scope for updates!
		var monthString = widget.lang['MONTHS'][clockDate.getMonth()];

		dateEle.innerHTML = dayString + " " + clockDay + " " + monthString;
		
		// clock function
		var clockHour = clockDate.getHours();
		if( clockHour > 12 && settingsBridge.Get('clockFormat') == 12 ) {
			clockHour = clockHour - 12;  // 12 hour mode!
		}
	
		if( clockHour < 10 ) {
			clockHour = "0" + clockHour;
		}

		var clockMinute = clockDate.getMinutes();
		if ( clockMinute < 10 ) {
			clockMinute = "0" + clockMinute;
		}

		timeEle.innerHTML = clockHour + ":" + clockMinute;

		// calculate next minute tick ( seconds left + 10ms )
		setTimeout(widget.updateClock, (60 - (new Date()).getSeconds()) * 1000 + 10);
	};
	
	this.renderError = function(message) {
		var error = document.getElementById("error");
		
		error.innerHTML = message;
		error.style.display = "block";
	};
	
	this.renderWeather = function(data) {

		var styleMode = settingsBridge.Get('widgetColor');
		var weatherFormat = settingsBridge.Get('weatherFormat');
			
		var table = document.getElementById("weather");
		
		for(index in data) {
			var info = data[index];
			var row = document.createElement("tr");
			
			// time
			var timeDate = new Date(info['time']);
			var timeHours = timeDate.getHours();
			
			var timeRow = document.createElement("th");
			var timeText = document.createTextNode( timeHours < 10 ? "0" + timeHours : timeHours );
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
			imageImage.src = "resources/" + styleMode + "/" + imageSymbol + ".png";
			imageRow.appendChild(imageImage);
			
			// rain
			var rainRow = document.createElement("td");
			rainRow.className = "ned";
			
			if( weatherFormat == "EU" )
				rainRow.innerHTML = info['rain'] + "<small><xs> </xs>mm</small>";
			else if( weatherFormat == "US" )
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
			dirImage.src = "resources/" + styleMode + "/" + windSymbol + ".png";
			dirRow.appendChild(dirImage);
			
			// wind speed
			var speedRow = document.createElement("td");
			speedRow.className = "ms";
			
			if( weatherFormat == "EU" )
				speedRow.innerHTML = info['windspeed'] + "<small><xs> </xs>m/s<small>";
			else if( weatherFormat == "US" )
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
		
		table.style.display = "block";
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
		var chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz";
		var string_length = 12;
		var string = '';
		for( var i=0; i < string_length; i++ ) {
			var rnum = Math.floor( Math.random() * chars.length );
			string += chars.substring(rnum, rnum + 1);
		}
		return string;
	}
	
	this.getWeather = function() {
	
		var table = document.getElementById("weather");			
		table.innerHTML = ''; // empty table
		table.style.display = "none";
	
		var xmlhttp = new XMLHttpRequest();
		xmlhttp.timeout = 4000;

		xmlhttp.ontimeout = function () {
			setTimeout(widget.getWeather, 2 * 60 * 1000);
		};
		
		xmlhttp.onerror = function() {
			widget.renderError("We could not connect to the WeatherAPI. Please check your connection.");
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
		weatherUrl += "/" + settingsBridge.Get('weatherFormat');
		weatherUrl += "/" + settingsBridge.Get('weatherLength');
		weatherUrl += "/" + settingsBridge.Get('weatherPlace');
		weatherUrl += "?_r=" + widget.randomString();
		weatherUrl += "&_v=_VERSION_";
		
		xmlhttp.open("GET", weatherUrl, true);
		xmlhttp.send();
	};

	this.init = function() {		
		settingsBridge.Load('parenthemeLS', '1.0', function(success, data) {
			if( success ) {
				//_DEBUG_START_
					settingsBridge.Set('widgetColor', 'white');
					settingsBridge.Set('widgetLang', 'EN');
					settingsBridge.Set('clockFormat', '24');
					settingsBridge.Set('weatherFormat', 'EU');
					settingsBridge.Set('weatherLength', '3');
					settingsBridge.Set('widgetBackground', 'none');
					settingsBridge.Set('weatherPlace', 'canada/quebec/montreal');

					if( location.hash == "#no-weather" )
						settingsBridge.Set('weatherEnable', 'false');
					else
						settingsBridge.Set('weatherEnable', 'true');

					settingsBridge.Save();
				//_DEBUG_END_
				_this.setStyle();
				_this.setLanguage();
				_this.updateClock();
				
				if( document.body.style.width != "1024px" ) {
					_this.swipe = window.swipe = Swipe(document.getElementById('content'), {
						continuous: false,
						callback: _this.onSlide,
					});
				}

				if(settingsBridge.Get('weatherEnable') == "true" && settingsBridge.Get('weatherPlace') != '') {
					document.body.classList.add('weather');
					_this.getWeather();
				} else {
					document.body.classList.add('no-weather');
					_this.swipe && _this.swipe.kill();
				}
			}
		});
	};
}

document.addEventListener('DOMContentLoaded', widget.init)
