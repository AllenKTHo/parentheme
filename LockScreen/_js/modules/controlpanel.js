$(function() {

	var settingsForm = $('form');
	var saveButton = settingsForm.find('#saveButton');
	var urlOption = settingsForm.find('#urloption');

	var syncForm = function() {
		var setOption = function(name, option) {
			settingsForm.find("input[name*="+name+"]").each(function() {
				if( settingsBridge.Get(option) == $(this).val() ) {
					$(this).attr("checked", true).checkboxradio('enable').checkboxradio('refresh');
				} else {
					$(this).attr("checked", false).checkboxradio('enable').checkboxradio('refresh');
				}
			});	
		};
		
		urlOption.textinput('enable').val(settingsBridge.Get('weatherPlace'));
		
		setOption('coloroption', 'widgetColor');
		setOption('backgroundoption', 'widgetBackground');
		setOption('languageoption', 'widgetLang');
		setOption('timeoption', 'clockFormat');
		setOption('weatherdisplayoption', 'weatherEnable');
		setOption('weatherformatoption', 'weatherFormat');
		setOption('weatherdisplaylength', 'weatherLength');
		
		saveButton.button('enable').button('refresh');
	};
	
	var saveForm = function() {
		var saveOption = function(name, option) {
			settingsForm.find("input[name*="+name+"]:checked").each(function() {
				settingsBridge.Set(option, $(this).val());
			});	
		};

		settingsBridge.Set('weatherPlace', urlOption.val().replace(/^((http:\/\/)*([w|m]*)(\.)*yr\.no\/place\/)/gi, '').replace(/( )*/gi, '').replace(/^\/+/gi, '').replace(/\/+$/gi, '').toLowerCase());
		
		saveOption('coloroption', 'widgetColor');
		saveOption('backgroundoption', 'widgetBackground');
		saveOption('languageoption', 'widgetLang');
		saveOption('timeoption', 'clockFormat');
		saveOption('weatherdisplayoption', 'weatherEnable');
		saveOption('weatherformatoption', 'weatherFormat');
		saveOption('weatherdisplaylength', 'weatherLength');
		
		settingsBridge.Save();
		syncForm();
		
		$('#saveButton').parents('div<:nth(0)').removeClass("ui-btn-active");
		window.scrollTo(0, 1);
		
		setTimeout(function() { alert("Settings saved!") }, 100); // delay to save before alert.
	};
	
	setTimeout(function(){ // top bar hack
		window.scrollTo(0, 1);
	}, 0);
	
	$('#mainpage').fadeIn('fast');
	
	if( navigator.userAgent.indexOf("Safari") > -1 && navigator.userAgent.indexOf('Chrome') == -1 ) {
		settingsBridge.Load('parenthemeLS', '1.0', function(success, data) {
			if( success ) {
				syncForm();
				
				settingsForm.submit(function() {
					return false;
				});
				
				saveButton.live('click', function() {
					setTimeout(saveForm, 0);
					return true;
				});
			} else {
				alert(data);
			}
		});
	} else {
		alert('You must use Safari to change Settings!');
	}
});
