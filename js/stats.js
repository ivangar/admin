$(document).ready(function () {

      (function(){ 
        var values = [];
        var titles = [];
        var program_titles = [];
        var program = 0;
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

        for(var index = 0; index < users.length; index++){

            var labels = users[index][0];
            var data = users[index][1];
            var year  = users[index][2];
            var canvas = "canvas" + year;
            var lineLegend = "lineLegend" + year;
           
            //Call barChart to create the chart
            barChart(labels, data, canvas, lineLegend);
        }

        for(var i = 0; i <= (programs.length - 1); i++){
          
            program = ++program;
            var programCanvas = "canvasPie" + program;
            var PieLegend = "PieLegend" + program;

            if( (typeof programs[i] !== 'undefined' ) && (programs[i].length > 0) ){
              
                for(var g = 0; g < programs[i].length; g++){
                     values.push( programs[i][g][0] );
                     titles.push( programs[i][g][1] );
                     program_titles.push( programs[i][g][2] );  
                }  

                pieChart(values, titles, program_titles, programCanvas, PieLegend);
            }

            values.length = 0;
            titles.length = 0;
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


  //Draw a bar chart
  function barChart(lbls, dt, cnvs, lnLegend) {

      var user_data = dt;  //first dataset to draw line chart
      var labels = lbls;  //Array of labels to be displayed in the x axis of the chart
      var canvas = cnvs;
      var line_legend = lnLegend;


      var barChartData = {
        labels : labels,
        datasets : [ 
          { label: "My First dataset",
            fillColor : "rgba(156, 184, 215, 0.5)",
            strokeColor : "rgba(156, 184, 215,0.8)",
            highlightFill : "rgba(156, 184, 215,0.75)",
            highlightStroke : "rgba(156, 184, 215,1)",
            data : user_data
          }
        ]

      }

      var ctx = document.getElementById(canvas).getContext("2d");
      var myBarChart = new Chart(ctx).Bar(barChartData, {
        responsive: true
      });
      
  }
  
  function pieChart(values, titles, program_titles, programCanvas, PieLegend){
    
      var values = values;  //values of each piechart section
      var titles = titles;  //Labels for each section
      var labels = program_titles;
      var programCanvas = programCanvas;
      var PieLegend = PieLegend;
      var pieData = [];
      var color = 0;
      var allColors = [];

      //Create an Object per each section
      for(var i = 0; i <= (values.length - 1); i++)
      {
          do{ color = getRandomColor(); }
          while (color_exists(allColors, color))
          allColors.push(color);
          var obj = new Object();
          obj.value = parseInt(values[i]);
          obj.color = color;
          obj.label = titles[i];
          obj.title = labels[i];

          pieData.push(obj);
      }
       
      var ctx = document.getElementById(programCanvas).getContext("2d");

      var myPieChart = new Chart(ctx).Pie(pieData, {
       animateScale : true,
       animationSteps : 60,
       animationEasing : "easeInOutSine"
      });

      legend(document.getElementById(PieLegend), pieData);

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
    paragraph.className = 'subtitle';
    paragraph.style.borderStyle = 'none';
    var node = document.createTextNode("Program IDs");
    paragraph.appendChild(node);
    parent.appendChild(paragraph);

    //Create a span element for each dataset
    datas.forEach(function(d) {
        var title = document.createElement('span');
        title.className = 'title topic';
        title.title = d.title;
        title.style.borderColor = d.hasOwnProperty('strokeColor') ? d.strokeColor : d.color;
        title.style.borderStyle = 'solid';
        parent.appendChild(title);

        var text = document.createTextNode(d.label);
        title.appendChild(text);
    });
}

function getRandomColor() {
    var letters = '0123456789ABCDEF'.split('');
    var color = '#';
    for (var i = 0; i < 6; i++ ) {
        color += letters[Math.floor(Math.random() * 16)];
    }
    return color;
}

function color_exists(colors, color){

    if(colors.length === 0) return false;

    for (i = 0; i < colors.length; i++) {

        if( (typeof colors[i] !== 'undefined') && (colors[i] === color) ){
            return true; //returns true if there is already a color in the array
        }
    } 

    return false;
}

  //Draw a bar chart for summary of all programs
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
  

//This function creates a legend node for Summary
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
