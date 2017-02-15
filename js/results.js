$(document).ready(function () {
      var comment_id = '';

      $("html, body").animate({
          scrollTop: 0
      }, 500);    

      $(function(){
        $(".sortable").tablesorter();
      });

      $('body').on('hover', 'span.topic').tooltip({
            show: {
            effect: "slideDown",
            delay: 200
        }
      });

      //Create Excel Forum Report file and export asynchronously
      $('body').on('click', 'a.export', function(event) {
          event.preventDefault();
          
          data = { "action": "exportTests" }; 

          $.ajax({
              url: "lib/export.php",
              cache: false,
              type: "POST",
              dataType: "html",
              data: data 
            }) 
          .done(function( data ) {
              if (data === "exported"){
                 $.fileDownload('https://dxlink.ca/admin/Reports/dxLink_program-tests_report.xlsx');
               }
          });
      });

            //Create Excel Forum Report file and export asynchronously
      $('body').on('click', 'a.export_eval', function(event) {
          event.preventDefault();
          
          data = { "action": "exportEvals" }; 

           $.ajax({
              url: "lib/export.php",
              cache: false,
              type: "POST",
              dataType: "html",
              data: data 
            }) 
          .done(function( data ) {
              if (data === "exported"){
                 $.fileDownload('https://dxlink.ca/admin/Reports/dxLink_program-evaluation_report.xlsx');
               }
          });
      });


      //toggle sections and switch the id of the clicked item.
      $( "h2" ).click(function() {

          //Toggle the next div item
         $(this).next("div").toggle( "slow", function() {
         });

      });

      $('body').on('click', 'button.close', function() {
            document.location.href = "https://" + window.location.hostname + "/admin/program_evaluations.php";
      });

  });//end document.ready

