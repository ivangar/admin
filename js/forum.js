var ajax_path = 'lib/forum_topics.php'; //Global var for access to other script

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

      $( "#dialog-form" ).dialog({
         autoOpen: false,
         height: 200,
         width: 400,
         modal: true,
         buttons: {
           "Ok": function() {
              $( this ).dialog( "close" );

              $.ajax({
                  url: "lib/forum_topics.php?action=delete_post&comment_id=" + comment_id,
                  cache: false,
                  type: "POST",
                  dataType: "html",
                  data: comment_id
                }) 
              .done(function( data ) {
                  if (data === "deleted"){
                      var protocol = location.protocol;
                      var host = location.host;
                      var path = location.pathname;
                      var url = protocol + '//' + host + '' + path;
                      location.assign(url);
                  }
              })
           },
           "Cancel": function() {
             $( this ).dialog( "close" );
           }
         },

       });

      //Use body to handle the event for dynamic content
      $('body').on('click', 'a.btn-danger', function(event) {
          event.preventDefault();
          comment_id = $(this).attr( "id" );
          $( "#dialog-form" ).dialog( "open" );
      });

      $('body').on('click', 'button.close', function() {
            document.location.href = "https://" + window.location.hostname + "/admin/forum.php";
      });
      
      //Create Excel Forum Report file and export asynchronously
      $('body').on('click', 'a#export', function(event) {
          event.preventDefault();

          $.ajax({
              url: "lib/forum_topics.php?action=exportForum",
              cache: false,
              type: "POST",
              dataType: "html"
            }) 
          .done(function( data ) {
              $.unblockUI(); 
              if (data === "exported"){
                 $.fileDownload('https://www.dxlink.ca/admin/Reports/dxLink_Forum_report.xlsx');
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
            error   : 'No More Posts!', // When the user reaches the end this is the message that is
                                        // displayed. You can change this if you want.
            delay   : 0, // When you scroll down the posts will load after a delayed amount of time.
                           // This is mainly for usability concerns. You can alter this as you see fit
            scroll  : true // The main bit, if set to false posts will not load as the user scrolls. 
                           // but will still load if the user clicks.
        });
      }

  });//end document.ready
