$(document).ready(function(){		
	$("#login_button").click(function(){			
		if ($(this).text() == "Login") {
			$(".login").modal({
		  	    opacity: 80,
			    escClose: true,
			    overlayClose: true,
			    overlayCss: {backgroundColor:"rgb(177,177,177)"},
		   	    onOpen: function(dialog) {
			    	dialog.overlay.fadeIn("fast", function(){
					dialog.data.hide();
					dialog.container.fadeIn("fast", function(){
						dialog.data.fadeIn();
					});
				});
			    }
			});
		} else {
			$.ajax({
				type: "POST",
				url: "tubeapi/logout",
				async: false,
				//contentType: "application/json",
				data: { user_id: localStorage.userid },
				headers: {"Authorization": localStorage.token},
				statusCode: {
					200: function() {
						localStorage.logged_in = "false";
						$("#login_button").text("Login");
						$("#upload_button").hide();	
					},
					204: function() {
						localStorage.logged_in = "false";
						$("#login_button").text("Login");
						$("#upload_button").hide();	
					}
				}
			});
			location.reload();
		}	
	});

	$("#new_existing_user").click(function() {
		if ($(this).text() == "New user") {
			$(this).text("Existing user");
			$("#confirm_password").show();
			$("#login_register").text("Register");	
		} else {
			$(this).text("New user");
			$("#confirm_password").hide();
			$("#login_register").text("Login");		
		}
	});
	
	$("#login_register").click(function() {
		if ($(this).text() == "Login") {
			$.ajax({
				type: "POST",
				url: "tubeapi/login",
				contentType: "application/json",
				async: false,
				data: JSON.stringify({ username: $("#username_input").val(), password: $("#password_input").val() }),
				statusCode: {
					200: function(response) {
						localStorage.token = response.token;
						localStorage.userid = response.id;
						localStorage.username = $("#username_input").val();
						localStorage.logged_in = "true";
						$("#login_button").text("Log Out");
						$("#upload_button").show();
						$.modal.close();
						location.reload();
					},
					204: function() {
						alert("Wrong username or password");
					},
					406: function() {
						alert("Wrong username or password");			
					}
				}
			});
		} else {
			var valid_inputs = true;
			if ($("#username_input").val().length < 3 || $("#username_input").val().length > 20) {
				valid_inputs = false;			
			}
			if ($("#password_input").val().length < 8 || $("#password_input").val().length > 20) {
				valid_inputs = false;
			}
			if ($("#password_input").val() != $("#confirm_password_input").val()) {
				valid_inputs = false;
			}
			if (valid_inputs) {
				$.ajax({
					type: "POST",
					url: "tubeapi/signUp",
					contentType: "application/json",
					async: false,
					data: JSON.stringify({ username: $("#username_input").val(), password: $("#password_input").val()}),
					statusCode: {
						200: function(response) {
							alert("Thanks for registering!");
							$.modal.close();	
						},
						204: function() {
							alert("An error occured. Please try again later");
														$.modal.close();
						},
						406: function() {
							alert("This username is already taken");
							$("#username_input").val("");
							$("#password_input").val("");
							$("#confirm_password_input").val("");
						}
					}
				});
			}	
		}
	});

	$("#upload_button").click(function() {
		$("#video_title").css("border", "1px solid #000000");
		$("#video_description").css("border", "1px solid #000000");		
		$(".upload").modal({
	  	    opacity: 80,
		    escClose: true,
		    overlayClose: true,
		    overlayCss: {backgroundColor:"rgb(177,177,177)"},
	   	    onOpen: function(dialog) {
		    	dialog.overlay.fadeIn("fast", function(){
				dialog.data.hide();
				dialog.container.fadeIn("fast", function(){
					dialog.data.fadeIn();
				});
			});
		    }
		});
	});
	$("#upload_video").click(function(){
		$("#video_title").css("border", "1px solid #000000");
		$("#video_description").css("border", "1px solid #000000");
		$("#my_video").css("border", "0");
		var upload_inputs = true;
		if ($("#video_title").val().length == 0) {
			upload_inputs = false;
			$("#video_title").css("border", "1px solid #ff0000");	
		}
		if ($("#video_description").val().length == 0) {
			upload_inputs = false;
			$("#video_description").css("border", "1px solid #ff0000");
		}		
		var input = document.getElementById("my_video");
		if (input.files.length == 0) {
			upload_inputs = false;
			$("#my_video").css("border", "1px solid #ff0000");		
		}
		if (upload_inputs == true) {		
			file = input.files[0];
			formdata = new FormData();
			formdata.append("upfile", file);
			formdata.append("json", JSON.stringify({ user_id: localStorage.userid, title: $("#video_title").val(), description: $("#video_description").val() })); 	
			$.ajax({
				url: "tubeapi/insertVideo",
				type: "POST",
				data: formdata,
				async: false,
				headers: {"Authorization": localStorage.token},
				processData: false,
				contentType: false,
				statusCode: {
					200: function() {
						alert("Your video has been uploaded");
						$.modal.close();
						location.reload();	
					},
					400: function(response) {
						alert("Invalid file");	
					},
					406: function() {
						alert("Session expired");
						$("#login_button").text("Login");
						$("#upload_button").hide();
						localStorage.logged_in = "false";
						$.modal.close();
					}
				}
			});	
		}
	});
});
