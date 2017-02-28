$(document).ready(function () {

	var is_touch_device = 'ontouchstart' in document.documentElement;    

	$('.flexmenu').click(function(){
		var wdth = $( window ).width();	
		var el = $(".flexmenu > ul");
		if (wdth < 768) {
	        el.animate({
	            "height": "toggle"
	        }, 
	        500,
	        function(){
	            if (el.is(':visible')) {
	                el.addClass("act");
	                if ($("body").hasClass("fp-viewing-0"))
	                	$("html").css({"overflow": "scroll"});
	            } else {
	                el.removeClass("act");
	                if ($("body").hasClass("fp-viewing-0"))
	                	$("html").css({"overflow": "hidden"});
	            }
	        });
		}
	});

	$('.flexmenu .opener').click(function(){
		var el = $(this).next('.dd-section');
		var switcher = $(this);
		var wdth = $( window ).width();			
		if (wdth < 768) {
	        el.animate({
	            "height": "toggle"
	        }, 
	        500,
	        function(){
	        	if (el.is(':visible')) {
	                el.addClass("act");
	                switcher.addClass('opn');
	            } else {
	            	switcher.removeClass('opn');
	                el.removeClass("act");
	            }
	        });
		}
		return false;
	});

	var wdth = $( window ).width();	
	if (wdth > 767) {

		$( ".main-section-sublinks > li" ).hover(
		  function() {
		    $(this).find("ul").stop().slideDown("fast");
		  }, function() {
    		$(this).find('.submenu').delay(100).fadeOut(100);
		  }
		);	

		/*$( ".main-section-links ul.level_0" ).hide();
		  $( ".main-section-links > li" ).hover(
		      function() {
		        $(this).find("ul").stop().slideDown("fast");
		      }, function() {
		        $(this).find('ul').delay(100).fadeOut(100);
		      }
		    );  
	*/
			

		$( ".flexmenuitem" ).hover(
	  	function() {
		    $(this).find('.submenu').fadeIn(400);
		  }, function() {
    		$(this).find('.submenu').delay(100).fadeOut(100);
		  }
		);
	}
	
});