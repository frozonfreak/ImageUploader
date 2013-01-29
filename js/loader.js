$(document).ready(function(){
	//Clear all values
	$('#caption').val("");
	$('#desc').val("");
	$('#file').val("");
	$('#tags').val("");
	$('#error').remove();
	var hasError = false;
	$('#caption').focus();

	$('#formupload').on('submit', function(e){
		var caption = $('#caption').val();
		if(caption == '' && hasError == false){
			$("#caption").after('<span class="error" id="error">Enter caption</span>');
			hasError = true;
			//return false;
		}
		if(hasError == true)
			return false;
		else
			$('#error').remove();
		$("#submit").attr("disabled", true);
		$("#cancel").attr("disabled", true);

		var formData = new FormData($('.form-upload')[0]);
		$.ajax({
		    url: 'upload_server.php', 
		    type: 'POST',
		    success: function(result)
		    {
		    	$('#caption').val("");
				$('#desc').val("");
				$('#file').val("");
				$('#tags').val("");
				$("#submit").attr("disabled", false);
				$("#cancel").attr("disabled", false);

		        console.log(result);
		        if(result == "Success"){
		        	//$('#formupload').text('Upload SuccessFull');
		        	alert("Contest Entry Received");
			  		//window.location.replace("http://www.framez.in");
				}
		        else{
		        	console.log(result);
		        	$.ajax({
		    			url: 'administration.php', 
		    			type: 'POST',
		    			data : result,
		    			status: "error",
		    			dataType:"text"
		    		});
		        }
		    },
		    beforeSend: function(){
				$('#loader').show();
		    },
		    uploadProgress: function(event, position, total, percentComplete) {
		    	console.log("Inside");
		    	console.log(percentComplete);
		    	alert(total);
		    },
		   	error: function(e){
		   		console.log(e)
		   		$.ajax({
		    			url: 'administration.php', 
		    			type: 'POST',
		    			data : e,
		    			status: "error",
		    			dataType:"text"
		    	});
		   	},
		    data: formData,
		    cache: false,
		    contentType: false,
		    processData: false
		});

		e.preventDefault();
		
		return false;
	});
	
	$('#caption').keydown(function(e){
		if(hasError == true && $('#caption').val() == ""){
			hasError = false;
			$('#error').remove();
		}
	})
  $("#cancel").click(function(e){
	$('#caption').val("");
	$('#desc').val("");
	$('#file').val("");
	$('#tags').val("");
	$('#caption').focus();

 });
}); 
