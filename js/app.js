'use strict';

(function($){

	$(function(){		
		/************
			Init
		************/
		var $wrap = $('#publishing-action');
		if ( $wrap.length < 1 ) return;

		var $publishButton 		= $('#publish');
		var $buttonWrap			= $('<div id="ssrb-container"></div>');
		var $mainButton			= $('<button type="button" class="button button-large ssrb-main-button button-primary" data-ssrb-action=""></button>');
		var $dropdownMenuButton = $('<button type="button" class="button button-large ssrb-dropdown-button button-primary"></button>');
		var $dropdownMenu 		= $('<ul class="ssrb-dropdown-menu"></ul>');
		var $actionInput 		= $('<input type="hidden" name="ssrb-action-input" value="" />');

		$buttonWrap.append('<hr />', $mainButton, $dropdownMenuButton, $dropdownMenu, $actionInput).appendTo( $wrap );
		
		createDropdownMenu();
		function createDropdownMenu(){
			var dropdown_menu;
			var default_action = false;

			if ( ssrb_actions.dropdown_menu ){

				dropdown_menu = ssrb_actions.dropdown_menu;

				for ( var el in dropdown_menu ){
					var $el = $(dropdown_menu[el]);

					$el.text(function(index, text){
						return text.replace('%s', $publishButton.val());
					});

					$dropdownMenu.append( $el );

					if ( $el.data('ssrb-last-action') == 1 ){
						$mainButton.text( $el.text() );
						$mainButton.data('ssrb-action', $el.data('ssrb-action'));

						if ( $el.hasClass('disabled') ){
							$mainButton.addClass('disabled');
						}
						default_action = true;
					}
				}

				if ( !default_action ){
					$mainButton.text( $dropdownMenu.find(' > li:first').text() );
					$mainButton.data('ssrb-action', $dropdownMenu.find(' > li:first').data('ssrb-action'));
				}
			}
		};


		/***********************
			Events Listeners
		***********************/

		$dropdownMenuButton.click(function(event){
			$dropdownMenu.toggleClass('on');
			event.stopPropagation();
		});

		$(document).click(function() {
			if ( $dropdownMenu.hasClass('on') ){
				$dropdownMenu.removeClass('on');
			}
		});

		$mainButton.click(function(event){
			if ( $mainButton.hasClass('disabled') )
				return;

			$actionInput.val( $mainButton.data('ssrb-action') );
			$publishButton.click();
			$mainButton.addClass('disabled');
			$dropdownMenuButton.addClass('disabled');
			event.stopPropagation();

		});

		$dropdownMenu.find(' > li:not(.disabled)').click(function(){
			$actionInput.val( $(this).data('ssrb-action') );
			$publishButton.click();
			$mainButton.addClass('disabled');
			$dropdownMenuButton.addClass('disabled');
			event.stopPropagation();
		});

	});

})(jQuery);