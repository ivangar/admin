$(document).ready(function () {

      (function(){ 

        for(index = 0; index < tests.length; index++){

            var pretest = tests[index][0];
            var postest = tests[index][1];
            var canvas = "canvas" + (index + 1);
            var lineLegend = "lineLegend" + (index + 1);
            var labels = [];
            var label;

            //Make labels for each question
            for(i = 0; i < pretest.length; i++){
                label = 'Question ' + (i + 1);
                labels.push(label);
            }
           
            //Call lineChart to create the chart
            lineChart(pretest, postest, canvas, lineLegend, labels);
        }

      })();

      $("html, body").animate({
          scrollTop: 0
      }, 500);    

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
                 $.fileDownload('https://www.dxlink.ca/Admin/Reports/dxLink_program-tests_report.xlsx');
               }
          });
      });


      //toggle sections and switch the id of the clicked item.
      $( "h2" ).click(function() {

          //Toggle the next div item
         $(this).next("div").toggle( "slow", function() {
         });

      });

  });//end document.ready


  //Draw a chart
  function lineChart(pretest, postest, cnvs, lnLegend, lbls) {
      var pretest_data = pretest;  //first dataset to draw line chart
      var postest_data = postest;  //second dataset to draw line chart
      var labels = lbls;  //Array of labels to be displayed in the x axis of the chart
      var canvas = cnvs;
      var line_legend = lnLegend;

      var lineChartData = {
        labels : labels,
        datasets : [
          {
          label: "My First dataset",
          fillColor : "rgba(151,187,205,0.2)",
          strokeColor : "rgba(151,187,205,1)",
          pointColor : "rgba(151,187,205,1)",
          pointStrokeColor : "#fff",
          pointHighlightFill : "#fff",
          pointHighlightStroke : "rgba(151,187,205,1)",
          data : postest_data,
          title : 'Post-test'
          },
          {
          label: "My Second dataset",
          fillColor : "rgba(220,220,220,0.2)",
          strokeColor : "rgba(220,220,220,1)",
          pointColor : "rgba(220,220,220,1)",
          pointStrokeColor : "#fff",
          pointHighlightFill : "#fff",
          pointHighlightStroke : "rgba(220,220,220,1)",
          data : pretest_data,
          title : 'Pre-test'
          }
        ]

      }

      var ctx = document.getElementById(canvas).getContext("2d");
      var myLineChart = new Chart(ctx).Line(lineChartData, {
        responsive: true,
      });

     legend(document.getElementById(line_legend), lineChartData);
  }

  //This function creates a legend node
  function legend(parent, data) {
    parent.className = 'legend';
    var datas = data.hasOwnProperty('datasets') ? data.datasets : data;

    // remove possible children of the parent element
    while(parent.hasChildNodes()) {
        parent.removeChild(parent.lastChild);
    }

    //Make a title for span
    var paragraph = document.createElement("span");
    paragraph.className = 'title';
    paragraph.style.borderStyle = 'none';
    var node = document.createTextNode("Correct answers");
    paragraph.appendChild(node);
    parent.appendChild(paragraph);

    //Create a span element for each dataset
    datas.forEach(function(d) {
        var title = document.createElement('span');
        title.className = 'title';
        title.style.borderColor = d.hasOwnProperty('strokeColor') ? d.strokeColor : d.color;
        title.style.borderStyle = 'solid';
        parent.appendChild(title);

        var text = document.createTextNode(d.title);
        title.appendChild(text);
    });
}

