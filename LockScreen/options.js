var settingsOverride = new function() {

	// Set from 'false' to 'true' to enable the config file
	this.Enable = 'true';
	
	// Font and Icon color, options are: 'white', 'black'
	this.widgetColor = 'white';
	
	// Widget Background, options are: 'none', 'translucent'
	this.widgetBackground = 'none';
	
	// Widget Languages, options are; 'EN', 'FR', 'DE', 'IT', 'ES', 'DA'
	this.widgetLang = 'EN';
	
	// Clock 24h-mode, options are '24', '12'
	this.clockFormat = '24';
	
	// Enables the weather widget, options are: 'true', 'false'
	this.weatherEnable = 'true';
	
	// Set the weather format, options are:
	// 'US' for F " mph
	// 'EU' for C mm m/s
	this.weatherFormat = 'EU';
	
	// Weather URL - go to http://m.yr.no/, search for your city and copy the country/state/city/ part of the URL
	// Example: canada/ontario/ottawa
	this.weatherPlace = '';
	
	// Forecast length, options are:
	// '4' for 1 Day
	// '8' for 2 Days
	// '12' for 3 Days
	this.weatherLength = '8';

}