
// Function to change slides
function slideChange(num) {

  // Hide all elements
  var slideElems = document.getElementsByClassName("slide");
  var i;
  for (i = 0; i < slideElems.length; i++) {
    slideElems[i].style.display = "none";
  }

  // Show the desired element
  var slideName = "slide" + num;
  var slideElem = document.getElementById(slideName);
  slideElem.style.display = "block";

  // If on video frame, show skip button with delay
  if(num == 2) {
    player.currentTime = 0;
    player.play();
    player.controls = false;
    setInterval(function(){ $('#skip-buttons-1').addClass('show-skip-button'); }, 1000);
    setInterval(function(){ $('#skip-buttons-1').removeClass('show-skip-button').addClass('show-play-button'); }, 25000);
  }

  // If on slide three, stop the video just in case and replace the background image with the reg bg image
  if(num == 3) {
    player.pause();
    document.getElementsByTagName("body")[0].style.backgroundImage = "url(/images/thecode/bg_room.jpg)";
  }

  // If on slide four, change the body background image
  if(num == 4 || num == 5 || num == 6) {
    document.getElementsByTagName("body")[0].style.backgroundImage = "url(/images/thecode/bg_room-enlarged.jpg)";
  }

  $('body').trigger('displaySlide',{slide:num});
}


$(document).ready(function(){
  console.log('READY!');

  // Master controller
  $('body').theCodeController();

  // Add handler for the flavour selectors
  $('.aroma-roll').flavourController();

  // Add Strength controller
  $('.strengths').strengthController();

  // Slide the aroma / flavour sets back and forth
  $('#slide4').setNavigationController();

  // do the API call and display the result
  $('#slide5').scoreController();

  // "Enter Your Name" controller
  $('#slide6').enterYourNameController();

  // Submit and then display the leaderboard
  $('#slide7').joinLeaderboardController();
});


jQuery.fn.extend({

  theCodeController: function() {
    if (!$(this).length) return this;

    $container = $(this);
    window.theCodeAnswerObject = {};

    // Add the default aroma values
    $('.aroma-roll').each(function(){
      theCodeAnswerObject[$(this).attr('data-question-id')] = $(this).find('.sel').index();
    });

    // Add the default strength values
    $('.strengths').each(function(){
      theCodeAnswerObject[$(this).attr('data-question-id')] = null;
    });

    // Event listener for when any data is changed, ready to submit to the API
    $container.on('codeChanged',function(event,eventData) {
      if (
        typeof eventData == 'object'
        && eventData.hasOwnProperty('questionId')
        && eventData.questionId
        && eventData.hasOwnProperty('answerIndex')
      ) {
        theCodeAnswerObject[eventData.questionId] = eventData.answerIndex;
      }
    });

    // When the user starts the game for the first time, start the timer
    // Listen for when this slide is displayed, and do the call to the API to get the score
    $('body').on('displaySlide',function(event,data){
      if (
        data.slide == 4
        && !window.theCodeAnswerObject.hasOwnProperty('startTime')
      ) {
        console.log('Starting the game for the first time. Start the clock.');
        window.theCodeAnswerObject.startTime = Math.floor(Date.now() / 1000);
      }
    });

    return this;
  },

  flavourController: function() {
    if (!$(this).length) return this;

    $container = $(this);

    // Click handler for the up and down arrows
    $container.find('.aroma-arrow').on('click', function(event){
      event.preventDefault();

      // Clicking on a disabled arrow does nothing
      if ($(this).hasClass('disabled')) {
        return;
      }

      // Get some vars to work with
      var direction = $(this).attr('data-direction');
      var id = parseInt($(this).attr('data-flavour-id'))-1;
      var $container = $('.aroma-area').eq(id);
      var currentIndex = $container.find('.sel').index();
      var newIndex;

      // Remove the disabled class from the arrows, and the selected class from the items
      $container.find('.disabled').removeClass('disabled');
      $container.find('.sel').removeClass('sel');

      if (direction == 'up') {
        newIndex = currentIndex - 1;
      } else {
        newIndex = currentIndex + 1;
      }

      // Disable the correct up/down arrow (if applicable)
      if (newIndex >= 2) {
        newIndex = 2;
        $container.find('.aroma-arrow-down').addClass('disabled');
      } else if (newIndex <= 0) {
        newIndex = 0;
        $container.find('.aroma-arrow-up').addClass('disabled');
      }

      // Add the "sel" class to the right element
      $container.find('.item').eq(newIndex).addClass('sel');

      $('body').trigger(
        'codeChanged',
        {
          questionId:$container.find('.aroma-roll').attr('data-question-id'),
          answerIndex:newIndex
        }
      );
    });

    return this;
  },

  strengthController: function() {
    if(!$(this).length) return this;

    $(this).each(function(){
      var $container = $(this);

      $container.find('a').on('click',function(event){
        event.preventDefault();
        var val = parseInt($(this).attr('data-value'));

        switch (val) {
          case 0:
            $container
              .removeClass('medium-selected')
              .removeClass('intense-selected')
              .addClass('mild-selected');
            break;

          case 1:
            $container
              .removeClass('mild-selected')
              .removeClass('intense-selected')
              .addClass('medium-selected');
            break;

          case 2:
            $container
              .removeClass('mild-selected')
              .removeClass('medium-selected')
              .addClass('intense-selected');
            break;
        }

        $('body').trigger(
          'codeChanged',
          {
            questionId:$container.attr('data-question-id'),
            answerIndex:val
          }
        );
      });
    });

    return this;
  },

  setNavigationController: function() {
    if(!$(this).length) return this;

    $container = $(this);

    var currentSet = 1;
    var alreadyCompleted;

    // Sets the correct "Aroma" / "Flavour" title and description, also change the Next button to Submit if on the last set
    var setTitleAndDescription = function() {
      if (currentSet < 3) {
        $container.find('h1').html(
          $container.attr('data-aroma-title')
          +': <span>'+$container.attr('data-aroma-description')+'</span>'
        );
      } else {
        $container.find('h1').html(
          $container.attr('data-taste-title')
          +': <span>'+$container.attr('data-taste-description')+'</span>'
        );
      }
      if (currentSet == 4) {
        $container.find('.disabled').hide();
        $container.find('.hidden').css("display","inline-block");
      }
    };
    setTitleAndDescription();

    // Tests whether the current set has been filled in, and therefor the user can move to the next one
    var isCurrentSetCompleted = function() {
      var setIsComplete = true;

      $('.aroma-set-area').eq(currentSet-1).find('[data-question-id]').each(function() {
        var questionId = $(this).attr('data-question-id');

        if (
          !window.theCodeAnswerObject.hasOwnProperty(questionId)
          || window.theCodeAnswerObject[questionId] === null
        ) {
          setIsComplete = false;
          alreadyCompleted = false;
        }
      });

      if (setIsComplete) {
        if(!alreadyCompleted) {
          $container.find('.btn-next').removeClass('disabled');
          $('.aroma-bottle-area img').attr('src','/images/thecode/bottle_anim_'+(currentSet*2)+'.gif');
          alreadyCompleted = true;
        }
      } else {
        $container.find('.btn-next').addClass('disabled');
      }
    };

    // Whenever an answer is changed we should check if the set is complete
    $('body').on('codeChanged',isCurrentSetCompleted);

    // Click handler for the next / previous buttons
    $container.find('.btn').on('click', function(event) {
      event.preventDefault();

      if ($(this).hasClass('disabled')) {
        return;
      }

      var direction = parseInt($(this).attr('data-direction'));
      var targetSet = currentSet + direction;

      if (targetSet <= 0) {
        currentSet = 1;
        slideChange(3);
        return;
      } else if (targetSet >= 5) {
        slideChange(5);
        return;
      }

      if (direction > 0) {
        // Hide the previous element
        $('#set'+currentSet).animate({left: "-600px"}, 300);
      } else {
        // Hide the previous element
        $('#set'+currentSet).animate({left: "600px"}, 300);
      }

      // Show the desired element
      $('#set'+targetSet).animate({left: "0px"}, 300);

      currentSet = targetSet;
      isCurrentSetCompleted();
      setTitleAndDescription();
    });

    return this;
  },

  scoreController: function() {
    if(!$(this).length) return this;

    // Shortcut to the container, and the circumference as it isn't going to change
    var $container = $(this);
    var circumference = 2 * 3.14 * parseInt($container.find('.bar').attr("r"));
    var timer;
    var currentPercent = 0;

    // set the stroke-dasharray as that isn't going to change either
    $container.find('.bar').attr({
      'stroke-dasharray':circumference,
      'stroke-dashoffset':circumference
    });

    // Animate the bar % width
    var setProgress = function(targetPercent, scoreDescription, scoreExplanation) {
      $container.find("#scoreDescription").html("â€œ" + scoreDescription + "â€");
      $container.find("#scoreExplanation").html("â€œ" + scoreExplanation + "â€");

      timer = setInterval(
        function() {
          // Move 10% closer to the target percentage
          currentPercent += (targetPercent - currentPercent) / 10;

          // If we're close enough, just go to the target % directly
          if(Math.abs(currentPercent - targetPercent) <= 1) {
            currentPercent = targetPercent;
            clearInterval(timer);
          }

          // Set the bat %
          $container.find('.bar').attr({'stroke-dashoffset': circumference - ((circumference / 100) * currentPercent)});

          // And set the text
          $container.find('#score').html(Math.ceil(currentPercent)+'%');
        },
        40
      );
    };
    
    // Listen for when this slide is displayed, and do the call to the API to get the score
    $('body').on('displaySlide',function(event,data){
      if (data.slide == 5) {
        console.log('Getting score from the API.');
        window.theCodeAnswerObject.timeTakenToComplete = Math.floor(Date.now() / 1000) - window.theCodeAnswerObject.startTime;

        $.ajax({
          url: '/en-EN/the-code/api/calculate-score',
          dataType: 'json',
          type: 'post',
          contentType: 'application/json',
          data: JSON.stringify(window.theCodeAnswerObject),
          success: function(data, textStatus, jQxhr ) {
            setProgress(data.score, data.scoreDescription, data.scoreExplanation);
          },
          error: function( jqXhr, textStatus, errorThrown ){
            console.log( errorThrown );
          }
        });
      }
    });

    return this;
  },

  enterYourNameController: function() {
    if(!$(this).length) return this;

    // Shortcut to the container
    var $container = $(this);

    $container.find('[name=leaderboard-name]').on('change keyup',function(event){
      var name = $(this).val().toUpperCase().replace(/[^A-Z0-9]/,'');
      $(this).val(name);
      window.theCodeAnswerObject.firstName = name;
      
      if (name.length > 2) {
        $container.find('.btn-submit').removeClass('disabled');
      } else {
        $container.find('.btn-submit').addClass('disabled');
      }
    });

    // Prevent moving on until we have a name
    $container.find('.btn-submit').on('click',function(event) {
      if ($(this).hasClass('disabled')) {
        return;
      }

      slideChange(7);
    });

    return this;
  },

  joinLeaderboardController: function() {
    if(!$(this).length) return this;

    // Shortcut to the container
    var $container = $(this);

    // set both the arrows to disabled initially
    $container.find('.vertical-arrow').addClass('disabled');

    // Does the rendering of the leaderboard
    var renderLeaderboard = function(leaderboardData) {
      $container.find('tbody').empty();

      var i = 0;
      leaderboardData.forEach(function(row) {
        i++;
        $container
          .find('tbody')
          .append(
            '<tr>\
              <td>'+i+'</td>\
              <td>'+row.first_name+'</td>\
              <td>'+(Math.round(row.score*10)/10)+'%</td>\
              <td>'+row.scoreDescription+'</td>\
            </tr>'
          )
        ;
      });

      // Allow scrolling down
      $container.find('.vertical-arrow.down').removeClass('disabled');
    };

    // Listen for when this slide is displayed, and do the call to the API to get the score
    $('body').on('displaySlide',function(event,data){
      if (data.slide == 7) {
        console.log('Submit to the API and get the leaderboard');

        $.ajax({
          url: '/en-EN/the-code/api/submit',
          dataType: 'json',
          type: 'post',
          contentType: 'application/json',
          data: JSON.stringify(window.theCodeAnswerObject),
          success: function(data, textStatus, jQxhr ) {
            if (data && data.success) {
              renderLeaderboard(data.leaderboard);
            } else {
              console.log('BAD DATA');
              console.log(data);
            }
          },
          error: function( jqXhr, textStatus, errorThrown ){
            console.log( errorThrown );
          }
        });
      }
    });

    var tableCurrentScrollPosition = 0;

    // Controller to scroll the table itself
    $container.find('.vertical-arrow').on('click',function(event){
      event.preventDefault();

      if($(this).hasClass('disabled')) {
        return;
      }

      var direction = $(this).hasClass('up')?'up':'down';
      var containerHeight = $container.find('.leaderboard-container').height();
      var tableHeight = $container.find('.leaderboard-container table').height();
      var maxOffset = (tableHeight - containerHeight) * -1;
      var newOffset;

      if (direction == 'up') {
        newOffset = tableCurrentScrollPosition + containerHeight;
      } else {
        newOffset = tableCurrentScrollPosition - containerHeight;
      }

      // Check we're not going off the bottom
      if (newOffset <= maxOffset) {
        $container.find('.vertical-arrow.down').addClass('disabled');
        newOffset = maxOffset;
      } else {
        $container.find('.vertical-arrow.down').removeClass('disabled');
      }

      // Check we're not going off the top
      if (newOffset >= 0) {
        $container.find('.vertical-arrow.up').addClass('disabled');
        newOffset = 0;
      } else {
        $container.find('.vertical-arrow.up').removeClass('disabled');
      }

      $container.find('.leaderboard-container table').animate(
        {marginTop:newOffset},
        300
      );

      tableCurrentScrollPosition = newOffset;
    });
    
    return this;
  }
});
