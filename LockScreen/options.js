// () LS By Patrick Loewer and Andreas Larsen
// Weather forecast from <http://yr.no>, delivered by the Norwegian Meteorological Institute and the NRK

var Settings = new function() {
	
// Color: 'white' or 'black'
this.widgetColor = 'white';
 
// Background: 'none' or 'translucent'
this.widgetBackground = 'none';

// Layout: 'single-page' or 'two-page'
this.widgetLayout = 'two-page';
 
// Language: 'EN', 'FR', 'DE', 'IT', 'ES' or 'DA'
this.widgetLang = 'EN';
 
// Clock: '24' or '12'
this.clockFormat = '24';
 
// Weather forecast: 'on' or 'off'
this.weatherEnable = 'off';
 
// Weather forecast format:
// 'US' for F " mph
// 'EU' for C mm m/s
this.weatherFormat = 'EU';
 
// Forecast location
// Go to yr.no
// Search for your city
// Copy the country/state/city part of the URL
this.weatherPlace = 'United_Kingdom/England/London';
 
// Forecast length:
// '4' for 1 Day
// '8' for 2 Days
// '12' for 3 Days
this.weatherLength = '8';

}
