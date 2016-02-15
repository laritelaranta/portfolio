/**
*
*  Site check and actions
*
**/

$(function() {

	// Show the site with delay so redirect can have time to take place
	$("body").delay(1000).fadeIn(1000);

	// Set epoch variable
	var epochDate;

	// Get epoch time
	$.ajax({
		type: "GET",
		dataType: "json",
		url: "https://www.co.uk/serverClock/serverTime",
		async: false
	})
	.done(function(data1, textStatus) {
		// Get epoch time
		epochDate = data1.timeStamp;
		console.log("epochDate: " + epochDate);
		return epochDate;
	})
	.fail(function() {
		alert('Error in epoch connection.');
	});
	
	// Let's get the current date time
	var d = new Date(epochDate);
	var nowDay = d.getDate();
	var nowMonth = d.getMonth();
	var nowYear = d.getFullYear();
	var nowHour = d.getHours();
	var nowMin = d.getMinutes();
	var compValue = 0;
	var slider;
	var currentSlide = 0;
	var sel;
	var selName;

	console.log("d: " + nowDay + "  m: " + nowMonth + " y: " + nowYear + " H: " + nowHour + " M: " + nowMin);
	
	// If promo has been expired, let's show the expiry message
	if(nowDay > 28 && nowMonth >= 11) {
		$("#vip-container").hide();
		$("#message-not-eligible").show();
		$('#message-not-eligible').html('This promotion has ended. Look out for other exciting ways to win.<br/><br/><a href="https://www.co.uk/"><img src="images/button_home.png" alt="" /></a>');
	} else {
		
		// Get the URL parameters
		var urlParams;
		(window.onpopstate = function () {
			var match,
				pl     = /\+/g,  // Regex for replacing addition symbol with a space
				search = /([^&=]+)=?([^&]*)/g,
				decode = function (s) { return decodeURIComponent(s.replace(pl, " ")); },
				query  = window.location.search.substring(1);
			
			urlParams = {};
			while (match = search.exec(query))
			   urlParams[decode(match[1])] = decode(match[2]);
		})();
		
		// Check for trustfulness and declare site var
		var site = "www.co.uk";
		var trusted = urlParams["m"];
		
		// Show default message if no GET is available, but else let's do some decoding
		if(trusted === undefined) {
			$("#message-not-eligible").show();
			$("#vip-container").hide();
			throw "This script has ended.";
		} else {
			var utd = Base64.decode(trusted);
		}
		
		// Slider init function
		function sliderInit() {
			var winHeight = 320;
			var slider = $(".royalSlider").height(winHeight).royalSlider({
			    // options go here
			    // as an example, enable keyboard arrows nav
				autoScaleSlider: true,
			    fadeinLoadedSlide: false,
			    imageScaleMode : 'fit-if-smaller',
		        imageScalePadding : 0,
		        slidesSpacing : 0,
		        loop: true,
			    usePreloader : true,
			    addActiveClass : true,
			    numImagesToPreload : 3,
			    visibleNearby: {
			        enabled: true,
			        centerArea: 0.32,
			        center: true,
			        breakpoint: 520,
			        breakpointCenterArea: 0.44,
			        navigateByCenterClick: false
			    },
			    controlNavigation: false,
			    imgWidth: 224
			});
			
		}
				
		// Declare home button URL var
		var homeURL = "https://" + site + "";
		
		// Declare redirect var
		var followURL = homeURL + "/promotions/cookies";
				
		// Hide T&C if close button is clicked and show the site
		$("#tc-closeBut").click(function(e) {
			e.preventDefault();
			$("#tc-overlay").hide();
			$("#vip-header").show();
			$("#vip-container").show();	
		});
		
		// Show T&C if button is clicked and hide everything else
		$(".tc-button").click(function(e) {
			e.preventDefault();
			$("#tc-overlay").show();
			$("#vip-header").hide();
			$("#vip-container").hide();
		});
				
		// Hide T&C if close button is clicked and show the site
		$("#tc-close").click(function(e) {
			e.preventDefault();
			$("#tc-overlay").hide();
			$("#vip-header").show();
			$("#vip-container").show();
		});
		
		// Click button activity
		$("#cta-button").click(function(e) {
			e.preventDefault();
			$("#vip-main-creative").hide();
			$("#vip-text").hide();
			$("#vip-text-2").show();
			sliderInit(); // Activate slider
			
			// Activate data variable
			var sliderData = $(".royalSlider").data('royalSlider');
			// Listen to slide change
			sliderData.ev.on('rsAfterSlideChange', function(event) {
			    currentSlide = sliderData.currSlideId;
			});
		});
		
		$("#select-button").click(function(e) {
			e.preventDefault();
			$("#vip-text-2").hide();
			$("#selectedText").show();
			
			if(currentSlide == 0) {
				sel = 1;
				selName = "Bonus";
			} else if(currentSlide == 1) {
				sel = 2;
				selName = "Cashback";
			} else if(currentSlide == 2) {
				sel = 3;
				selName = "Cashback on wins";
			}
			
			$(".sel").html(selName);
			
		});
		
		// No button activity
		$("#no-button").click(function(e) {
			e.preventDefault();
			$("#vip-text-2").show();
			$("#selectedText").hide();
		});
		
		// Detect the device and screen width
		var ua = navigator.userAgent;
		
		// Let's redirect if the user is on desktop
		if(ua.indexOf("iPhone") < 1 && ua.indexOf("Android") < 1 && ua.indexOf("Windows Phone") < 1 && ua.indexOf("iPad") < 1) {
			window.location.replace(followURL);		
		}		
		
		// Eligibility variables
		var dom = "https://" + site + "/bb/elig/";
		var prom = "https://" + site + "/bb/prom/";
		var pro = 11; // Promo ID
		
		// Complete the URLs
		var add = dom + pro + "/" + utd;
		var addrounds = prom + pro + "/" + utd + "/rou";
    	
    	// Check for eligibility
    	$.ajax({ 
    		type: "GET",
    		dataType: "json",
    		url: add,
    		async: false
    	})
    	.done(function(data, textStatus) {
			
			compValue = parseInt(data.compVal);
			payout = parseInt(data.payout);

			$("#compValue").val(compValue);
			$("#payout").val(payout);
    		
    	})
    	.fail(function() {
    		$("#vip-container").hide();
    		$("#message-not-eligible").show();
    	});
    	
    	
    	// Yes action - submit the form
    	$("#yes-button").click(function(e) {
    		e.preventDefault();
    		
    		// Harvest form inputs and put them into a JSON object
    		var jsonResult = $('#vip-form').serializeJSON();
    		
    		var jsonRounds = '{"r": [{"rId": "1", "sel": "' + sel + '"}]}';
    		
    		// POST rounds data
    		$.ajax({
    			type: "POST",
    			contentType: "application/json",
    			data: jsonRounds,
    			url: addrounds,
    			async: false
    		})
    		.done(function(data, textStatus) {
    			
    			$(".X").html(jsonResult.compVal);
    			$(".Y").html(jsonResult.payout);

    			if(sel == 1) {
    				$("#creative-img").attr("src", "images/cookie-1.png");
    				$("#result-1").show();
    			} else if (sel == 2) {
    				$("#creative-img").attr("src", "images/cookie-2.png");
    				$("#result-2").show();
    			} else if (sel == 3) {
    				$("#creative-img").attr("src", "images/cookie-3.png");
    				$("#result-3").show();
    			}

    			$("#vip-text").hide();
    			$("#vip-text-2").hide();
    			$("#vip-text-3").show();
    			$("#play-button-area").show();
    			$("#play-button").attr("href", "https://www.co.uk");
    			
    		})
    		.fail(function() {
    			$('#vip-container').hide();
    			$('#message-not-eligible').html("An error occured whilst sending your data. Error 1. Please try again later.");
    			$("#message-not-eligible").show();
    		});
    		
    	});
				
	}
	
});