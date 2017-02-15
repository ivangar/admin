var ajax_path = 'lib/repzone_events.php'; //Global var for access to other script

$(document).ready(function () {

      $("html, body").animate({
          scrollTop: 0
      }, 500);
      
      //Create Excel Forum Report file and export asynchronously
      $('body').on('click', 'a#export', function(event) {
          event.preventDefault();

          $.ajax({
              url: "lib/repzone_events.php?action=exportRepzone",
              cache: false,
              type: "POST",
              dataType: "html"
            }) 
          .done(function( data ) {
              if (data === "exported"){
                 $.fileDownload('https://www.dxlink.ca/admin/Reports/dxLink_repzone.xlsx');
               }
          });
      });

  });//end document.ready
