/* THIS WAS A TEST TO IMPLEMENT BAR CHART AND LINEAR CHART*/

/*
$(document).ready(function () {

      (function(){ 
        var Summary_values = [];
        var Summary_labels = [];
        var Summary_titles = [];
        var Summary_canvas = "canvasSummary";

        for(var index = 0; index < summary[0].length; index++){

            Summary_values.push( summary[0][index][0] );
            Summary_labels.push( summary[0][index][1] );
            Summary_titles.push( [summary[0][index][1],summary[0][index][2]] );

        }

        barChartSummary(Summary_labels, Summary_values, Summary_canvas, Summary_titles);

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

  });//end document.ready

  //Draw a bar chart
  function barChartSummary(lbls, dt, cnvs, titles) {

      var values = dt;  //first dataset to draw line chart
      var labels = lbls;  //Array of labels to be displayed in the x axis of the chart
      var canvas = cnvs;
      var program_titles = titles; //Array of program titles

      var barChartData = {
        labels : labels,
        datasets : [ 
          { label: "My First dataset",
            fillColor : "rgba(120, 223, 175, 0.5)",
            strokeColor : "rgba(120, 223, 175,0.8)",
            highlightFill : "rgba(120, 223, 175,0.75)",
            highlightStroke : "rgba(120, 223, 175,1)",
            data : values
          }
        ]

      }

      var ctx = document.getElementById(canvas).getContext("2d");
      var myBarChart = new Chart(ctx).Bar(barChartData, {
        responsive: true,
        scaleGridLineWidth : 1.2
      });
   
     legenda(document.getElementById('BarLegend'), program_titles);
  }
  

  //This function creates a legend node
  function legenda(parent, data) {
    parent.className = 'legend';
    var programs = data;

    //Make a title for span
    var paragraph = document.createElement("span");
    paragraph.className = 'subtitle';
    paragraph.style.borderStyle = 'none';
    var node = document.createTextNode("Program IDs");
    paragraph.appendChild(node);
    parent.appendChild(paragraph);


    for (var i = 0; i < programs.length; i++) {

        //Do something
        var title = document.createElement('span');
        title.className = 'title';
        title.style.borderStyle = 'none';
        title.title = programs[i][1];
        parent.appendChild(title);

        var text = document.createTextNode(programs[i][0]);
        title.appendChild(text);
    }

}

*/