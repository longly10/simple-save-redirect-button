'use strict';

(function($){

	function getUrlVars() {
	    var vars = {};
	    var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi,    
	    function(m,key,value) {
	      vars[key] = value;
	    });
	    return vars;
	}

	$(function(){		
		
		var scroll_id = getUrlVars()['scrollto'];

		if ( scroll_id ){
			$('html, body').animate({
				scrollTop: $('#post-'+scroll_id).offset().top - 35
			}, 500);

			$('#post-'+scroll_id).addClass('scroll-highlight');
		}
	});

})(jQuery);