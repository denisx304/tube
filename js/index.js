function compare(a,b) {
  if (a.views > b.views)
    return -1;
  if (a.views < b.views)
    return 1;
  return 0;
}

$(document).ready(function(){
	
	if (localStorage.logged_in == "true") {
		$("#login_button").text("Log Out");
		$("#upload_button").show();
		$("#my_videos").show();
		$.ajax({
			type: "GET",
			url: "tubeapi/videos",
			async: false,
			data: { user_id: localStorage.userid },
			statusCode: {
				200: function(response) {					
					for (var i = 0; i < response.length; i ++) {
						$("#my_videos").append(
							"<div name=" + response[i].id + " class='show-video mt10'>" + 
								"<a href=index.html class='bold-font'>" + response[i].title + "</a>" + 
								"<video controls class='show-video'>" + 
									"<source src='" + response[i].path_of_video + "' type='video/mp4'>" + 
								"</video>" + 
							"<div class='fright-text mt10'>" + response[i].likes + " likes / " + response[i].dislikes + " dislikes</div>" + 
							"</div>" + 
							"<div class='clear'></div>"
						);
					}
				}
			}
		});		
	} else {
		$("#login_button").text("Login");
		$("#upload_button").hide();
		$("#my_videos").hide();
	}
	
	var videos = [];
	var popular_videos = [];

	// get all users
	$.ajax({
		type: "GET",
		url: "tubeapi/users",
		async: false,
		statusCode: {
			200: function(response) {
				for (var i = 0; i < response.length; i ++) {
					user_id = response[i].id;
					
					// get all videos for a user					
					$.ajax({
						type: "GET",
						url: "tubeapi/videos",
						async: false,
						data: { user_id: user_id.toString() },
						statusCode: {
							200: function(response) {
								for (var j = 0; j < response.length; j ++) {	
									videos.push({ value: response[j].title, data: response[j].id });
												popular_videos.push(response[j]);					
								}
							},
							204: function() {
								console.log("there are no videos for user " + user_id);							
							}
						}
					});
				}
				$('#search_box').autocomplete({
					lookup: videos,
					onSelect: function (suggestion) {
						console.log("selected video: " + suggestion.value + " " + suggestion.data);
						sessionStorage.id_video_to_play = suggestion.data;
						sessionStorage.title_video_to_play = suggestion.value;
				    	}
				});		
			},
			204: function() {
				console.log("there are no users");
			}
		}
	});

	popular_videos.sort(compare);
	for (var i = 0; i < popular_videos.length; i ++) {
		$("#popular_videos").append(
			"<div name=" + popular_videos[i].id + " class='show-video'>" + 
				"<a href='video.html' class='bold-font'>" + popular_videos[i].title + "</a>" + 
				"<video controls class='show-video'>" + 
					"<source src='" + popular_videos[i].path_of_video + "' type='video/mp4'>" + 
				"</video>" + 
				"<div class='mt10'>" + 
					"<div class='like-button fleft'></div>" + 
					"<div class='dislike-button fleft'></div>" +
					"<div class='favourite-button fleft unfav'></div>" + 
					"<div class='fright-text-main'>" + popular_videos[i].likes + " likes / " + popular_videos[i].dislikes + " dislikes</div>" + 
				"</div>" + 
			"</div>" + 
			"<div class='clear'></div>"		
		);
	}
	
	if (localStorage.logged_in == "true") {
		$.ajax({
			url: "tubeapi/favorites",
			type: "GET",
			data: { user_id: localStorage.userid },
			async: false,
			headers: {"Authorization": localStorage.token},
			statusCode: {
				200: function(response) {
					for (var i = 0; i < response.length; i ++) {
						$("div[name='" + response[i].video_id + "']").find(".favourite-button").removeClass('unfav');
						$("div[name='" + response[i].video_id + "']").find(".favourite-button").addClass('fav');
					}
				},
				400: function() {
					alert("Session expired");
					$("#login_button").text("Login");
					$("#upload_button").hide();
					localStorage.logged_in = "false";
					$.modal.close();
				}
			}
		});
	}	
	
	$(".like-button").click(function() {
		var video_id = $(this).parent().parent().attr("name");
		$.ajax({
			type: "POST",
			url: "tubeapi/likeVideo",
			async: false,
			data: JSON.stringify({ id: video_id }),
			statusCode: {
				200: function() {
					alert("Thanks for liking");
				},
				204: function() {
					alert("An error occured");
				}
			}
		});
		location.reload();
	});

	$(".dislike-button").click(function() {
		var video_id = $(this).parent().parent().attr("name");
		$.ajax({
			type: "POST",
			url: "tubeapi/dislikeVideo",
			async: false,
			data: JSON.stringify({ id: video_id }),
			statusCode: {
				200: function() {
					alert(":-(");
				},
				204: function() {
					alert("An error occured");
				}
			}
		});
		location.reload();
	});

	$(".favourite-button").click(function() {
		var video_id = $(this).parent().parent().attr("name");
		var this_element = $(this);
		if ($(this).hasClass('fav')) {
			$.ajax({
				url: "tubeapi/deleteFromFavorites",
				type: "POST",
				data: { user_id: localStorage.userid, video_id: video_id },
				async: false,
				headers: {"Authorization": localStorage.token},
				statusCode: {
					200: function() {
						console.log("Video removed from favorites");
						this_element.removeClass('fav');
						this_element.addClass('unfav');
					},
					204: function() {
						alert("Session expired");
						$("#login_button").text("Login");
						$("#upload_button").hide();
						localStorage.logged_in = "false";
						$.modal.close();
					}
				}
			});
		} else {
			$.ajax({
				url: "tubeapi/addToFavorites",
				type: "POST",
				data: JSON.stringify({ user_id: localStorage.userid, video_id: video_id }),
				async: false,
				headers: {"Authorization": localStorage.token},
				statusCode: {
					200: function() {
						console.log("Video added to favorites");
						this_element.removeClass('unfav');
						this_element.addClass('fav');
					},
					400: function() {
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

	$("#play_video").click(function(){
		if ($("#search_box").val() == sessionStorage.title_video_to_play) {
			window.location.href = "video.html";
		}
	});

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
