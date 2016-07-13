jQuery( document ).ready(function() {
    var banner = pronto_ads_js_strings;
	var sidebar_section = '<div id="sidebar_section">'
	var banner_image = '<a target="_blank" href="https://www.prontomarketing.com/ipm-report/?utm_source=ConnectWise%20Plugin&utm_medium=banner&utm_campaign=ConnectWise%20IPM%20Report" style="float:right;"><img src="' + banner.path + '"></a><br>';
	var sidebar_text = '<strong>Need help?</strong><br><a target="_blank" href="https://pronto.zendesk.com/hc/en-us/articles/208460256">Get support and documentation</a>';
	jQuery('.gform_tab_container > #tab_connectwise').after(sidebar_section + banner_image + sidebar_text + '</div>');
});
