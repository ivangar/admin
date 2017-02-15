$(document).ready(function () {
    
    var countChecked = function() {
        var remember = $( "input:checked" ).val();
        if(remember === '1'){
             $("#remember_submitted").val("1");
        }

        else $("#remember_submitted").val("0");
        console.log(remember + ' remember ');
    };

    countChecked();
    
    $( "#remember_me:checkbox" ).on( "click", countChecked );

    $('body').on('click', 'a.button', function(event) {
        event.preventDefault();
         
        var form = document.getElementById("login_form");
        var jsonData = {};

        for (i = 0; i < form.length ;i++) { 
            var columnName = form.elements[i].name;
            jsonData[columnName] = form.elements[i].value;
        } 
        
        console.log(jsonData);

        $.ajax({
            url: "lib/login.php",
            cache: false,
            type: "POST",
            dataType: "html",
            data: jsonData 
          }) 
        .done(function( data ) { 
             if (data === 'access'){
                window.location.replace('users.php');
             }
             else{
                window.location.reload(true);   
             }

        }); //end Ajax call
       
    });

    $("body").keydown(function(e) {
         if(e.keyCode == 13) { // enter

            var form = document.getElementById("login_form");
            var jsonData = {};

            for (i = 0; i < form.length ;i++) { 
                var columnName = form.elements[i].name;
                jsonData[columnName] = form.elements[i].value;
            } 
            
            $.ajax({
                url: "lib/login.php",
                cache: false,
                type: "POST",
                dataType: "html",
                data: jsonData 
              }) 
            .done(function( data ) {
                 if (data === 'access'){
                    window.location.replace('users.php');
                 }
                 else
                    window.location.reload(true);
            }); //end Ajax call
        }
    });

});//end document.ready