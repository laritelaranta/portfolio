// Declare stopwatch to avoid unnecessary GA push actions while scrolling
function Stopwatch(){
	var startTime, endTime, instance = this;

	this.start = function (){
		startTime = new Date();
	};

	this.stop = function (){
		endTime = new Date();
	}

	this.clear = function (){
		startTime = null;
		endTime = null;
	}

	this.getSeconds = function(){
		if (!endTime){
			return 0;
		}
		return Math.round((endTime.getTime() - startTime.getTime()) / 1000);
	}

	this.getMinutes = function(){
		return instance.getSeconds() / 60;
	}      
	this.getHours = function(){
		return instance.getSeconds() / 60 / 60;
	}    
	this.getDays = function(){
		return instance.getHours() / 24;
	}   
}

var st = new Stopwatch();
st.start(); // Start stopwatch

$(document).ready(function() {
	// This is run every time scrolling reaches a new waypoint
	$('div.header').bind('waypoint.reached', function(event, direction) {
		var $active = $(this);
		if (direction === "up") {
			$active = $active.prev();
		}
		if (!$active.length) $active = $active.end();
		
		$('.current').removeClass('current');
		$('a[href=#'+$active.attr('id')+']').addClass('current');
		
		st.stop(); // Stop stopwatch for GA push
		
		if(st.getSeconds() > 5 && st.getSeconds() < 180) { // Push GA only if visitor has been on a page more than 5 seconds and less than 3 minutes
			_gaq.push(['_trackEvent', 'Skrollatut sivut', $active.attr('id'), $active.attr('id'), st.getSeconds(), true]); // Push GA with page title and seconds spent on page
		}
		
		st.start(); // Start stopwatch again
		
	});
	
	// Register each section as a waypoint
	$('div.header').waypoint({ offset: '50%' });
});
