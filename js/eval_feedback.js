var ajax_path = 'lib/feedback.php'; //Global var for access to other script

$(document).ready(function () {
      var comment_id = '';

      $("html, body").animate({
          scrollTop: 0
      }, 500);    

      $(function(){
        $("#sortable").tablesorter();
      });

      $('body').on('hover', 'span.topic').tooltip({
            show: {
            effect: "slideDown",
            delay: 200
        }
      });

      $('body').on('click', 'button.close', function() {
            document.location.href = "https://" + window.location.hostname + "/admin/evaluation_feedback.php";
      });

      //Create Excel Forum Report file and export asynchronously
      $('body').on('click', 'a#export', function(event) {
          event.preventDefault();

          $.ajax({
              url: "lib/feedback.php?action=exportfeedback",
              cache: false,
              type: "POST",
              dataType: "html"
            }) 
          .done(function( data ) {
              if (data === "exported"){
                 $.fileDownload('https://dxlink.ca/admin/Reports/dxLink_Evaluation_feedback_report.xlsx');
               }
          });
      });

      //Do not load records when a specific result is searched
      var search = location.search.split('search=')[1];
      if(!search){
        $('#content').scrollPagination({

            nop     : 20, // The number of posts per scroll to be loaded
            offset  : 10, // Initial offset, begins at 0 in this case
            error   : 'No More Evaluations!', // When the user reaches the end this is the message that is
                                        // displayed. You can change this if you want.
            delay   : 0, // When you scroll down the posts will load after a delayed amount of time.
                           // This is mainly for usability concerns. You can alter this as you see fit
            scroll  : true // The main bit, if set to false posts will not load as the user scrolls. 
                           // but will still load if the user clicks.
        });
      }

  });//end document.ready
