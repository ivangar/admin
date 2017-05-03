var ajax_path = 'lib/feedback.php'; //Global var for access to other script

$(document).ready(function () {
      var comment_id = '';
      var values = [];
      var max_width = 0;

      $("html, body").animate({
          scrollTop: 0
      }, 500);    

      $( window ).resize(function() {
          $( window ).scroll();
      });

      $( window ).scroll(function() {

          var position = $(this).scrollTop();
          var container_width = ((document.getElementById('main').offsetWidth)-80);
          var sidebar_width = ((document.getElementById('sidebar').offsetWidth)+39);
          $( "#freeze-header" ).css({"width": container_width, "left": sidebar_width});

          $( "#sortable>thead>tr>th" ).each(function() {
              var cell_width = $( this ).css( "width" );
              values.push(cell_width);

              var temp = parseInt(cell_width.match(/\d+/g)[0]); //extract the number portion from px attribute
              max_width = max_width + temp;
          });

          for(var index = 0; index < values.length; index++){
            var column = index + 1;
            var col_width = values[index];
            $( "#freeze-header>div#col_" + column ).css("width", col_width);
          }

          if(max_width > container_width){
            var difference = (max_width - container_width);
            var new_width = (parseInt(values[5]) - difference);
            var new_container_width = (parseInt(container_width) + difference);
            $( "#freeze-header>div#col_6").css("width", new_width);
            $( "#freeze-header" ).css("width", new_container_width);
          }

          if (position > 135) {
             $( "#freeze-header" ).show("linear");
          } else {
            $( "#freeze-header" ).hide();
          }

          values = [];
          max_width = 0;
      });

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
              $.unblockUI(); 
              if (data === "exported"){
                 $.fileDownload('https://dxlink.ca/admin/Reports/dxLink_Evaluation_feedback_report.xlsx');
               }
          });

          $.blockUI({ message: "<img src='img/ajax-loader.gif' width='50' height='50' /> <p style='font-size: 18px;font-weight:500;'>Extracting data into Excel format ...</p>", 
              css: { 
              top:  ($(window).height() - 200) /2 + 'px', 
              left: ($(window).width() - 400) /2 + 'px', 
              cursor:'auto',
              width: '430px',
              height: '150px',
              color: '#3c763d',
              border: '4px solid #ccc',
              padding: '20px 0 0 0', 
              backgroundColor: '#dff0d8', 
              '-moz-border-radius': '10px',
              '-webkit-border-radius': '10px',
              'border-radius': '10px'},
             overlayCSS: { backgroundColor: '#000', opacity: .5, cursor:'not-allowed'}
          }); 
          $('.blockOverlay');

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
