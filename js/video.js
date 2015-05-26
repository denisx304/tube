$(document).ready(function(){
	$.ajax({
		type: "GET",
		url: "tubeapi/video",
		async: false,
		data: { id: sessionStorage.id_video_to_play },
		statusCode: {
			200: function(response) {
					$("title").text(response.title);				
				$(".video-content").append(	
					"<div name=" + response.id + " class='mt10'>" + 
						"<div class='video-title'>" + response.title + "</div>" +  
						"<video id='main_video' controls class='video-view mt10'>" + 
							"<source src='" + response.path_of_video + "' type='video/mp4'>" + 
						"</video>" + 
						"<div class='like-button fleft mt10' style='display: none;'></div>" + 
						"<div class='dislike-button fleft mt10' style='display: none;'></div>" + 
						"<div class='favourite-button fleft unfav mt10' style='display: none;'></div>" + 
						"<div class='fright-text  mt10'>" + response.likes + " likes / " + response.dislikes + " dislikes</div>" + 
						"<div class='clear'></div>" + 
					"</div>" + 
					"<p class='desc-text mt10'>Description:</p>" + 
					"<div class='video-description mt10'>" + response.description.replace("\n", "<br>") + "</div>" +
					"<div class='comment-post' style='display: none;'>" + 
						"<textarea id='comment_area' class='text-area h150p mt10' placeholder='Insert comment here'></textarea>" +  
						"<button id='post_comment' class='pure-button fright-text button-error w400'>Post</button>" + 
					"</div>" + 
					"<div class='clear'></div>"
				);
			}		
		}
	});

	document.getElementById("main_video").play();
	
	if (localStorage.logged_in == "true") {
		$("#login_button").text("Log Out");
		$("#upload_button").show();
		$("#my_videos").show();
		$(".comment-post").show();
		$.ajax({
			url: "tubeapi/addToHistory",
			type: "POST",
			data: JSON.stringify({ user_id: localStorage.userid, video_id: sessionStorage.id_video_to_play }),
			async: false,
			headers: {"Authorization": localStorage.token},
			statusCode: {		
				200: function() {
					console.log("video added to history");			
				},
				400: function() {
					alert("Session expired");
					$("#login_button").text("Login");
					$("#upload_button").hide();
					localStorage.logged_in = "false";
					$(".like-button").hide();
					$(".dislike-button").hide();
				}
			}
		});		

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
				}
			}
		});		

		$(".like-button").show();
		$(".dislike-button").show();
		$(".favourite-button").show();
		
		$(".like-button").click(function(){
			var video_id = sessionStorage.id_video_to_play;
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

		$(".dislike-button").click(function(){
			var video_id = sessionStorage.id_video_to_play;
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
			var video_id = sessionStorage.id_video_to_play;
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
						}
					}
				});
			}
		});

		$("#post_comment").click(function(){
			if ($("#comment_area").val() == "") {
				$("#comment_area").css("border", "2px solid red");
			} else {
				$.ajax({
					url: "tubeapi/insertComment",
					type: "POST",
					data: JSON.stringify({ user_id: localStorage.userid, video_id: sessionStorage.id_video_to_play, text: $("#comment_area").val() }),
					async: false,
					headers: {"Authorization": localStorage.token},
					statusCode: {	
						200: function() {
							alert("Comment added successfully");
							location.reload();
						},
						400: function() {
							alert("Session expired");
							$("#login_button").text("Login");
							$("#upload_button").hide();
							localStorage.logged_in = "false";
						}
					}
				});
			}
		});		
	
	}

	$(".video-content").append(
		"<div class='comment-list mt10'>"
	);	

	$(".video-content").append(
		"</div>"
	);

	$.ajax({
		url: "tubeapi/comments",
		type: "GET",
		data: { video_id: sessionStorage.id_video_to_play },
		async: false,
		statusCode: {	
			200: function(response) {
				for (var i = 0; i < response.length; i ++) {
					var user_id = response[i].user_id;
					var username = "";
					$.ajax({
						url: "tubeapi/user",
						type: "GET",
						data: { id: user_id },
						async: false,
						statusCode: {
							200: function(response) {
								username = response.username;	
							}
						}
					});					
					$(".comment-list").append(
						"<div class='comment'>" + 
							"<p class='author'>" + username + ": </p>" + 
							"<p class='comment-text'>&nbsp" + response[i].text.replace("\n", "<br>") + "</p>" + 
						"</div>"						
					);
				}
			}
		}
	});
	
	var uploader_id = "";
	$.ajax({
		url: "tubeapi/video",
		type: "GET",
		data: { id: sessionStorage.id_video_to_play },
		async: false,
		statusCode: {
			200: function(response) {
				uploader_id = response.user_id;
			}
		}	
	});
	
	$.ajax({
		url: "tubeapi/videos",
		type: "GET",
		data: { user_id: uploader_id },
		async: false,
		statusCode: {
			200: function(response) {
				for (var i = 0; i < response.length; i ++) {
					if (response[i].id != sessionStorage.id_video_to_play) {					
					$(".related-videos").append(
						"<div name=" + response[i].id + " class='show-video'>" + 
							"<div class='clickable bold-font'>" + response[i].title + "</div>" +
							"<video controls class='clickable show-video'>" + 
								"<source src='" + response[i].path_of_video + "' type='video/mp4'>" + 
							"</video>" + 
							"<div class='mt10'>" + 
								"<div class='like-button fleft' style='display: none;'></div>" +
								"<div class='dislike-button fleft' style='display: none;'></div>" +
								"<div class='fright-text'>" + response[i].likes + " likes / " + response[i].dislikes + " dislikes</div>"	+
							"</div>" + 
						"</div>" + 
						"<div class='clear'></div>"								 
					);
					}
				}
			}		
		}
	});
	
	$(".clickable").click(function(){
		sessionStorage.id_video_to_play = $(this).parent().attr("name");
		window.location.href = "video.html";	
	});	
		
});

