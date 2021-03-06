function compare(a,b) {
  if (parseInt(a.views) > parseInt(b.views))
    return -1;
  if (parseInt(a.views) < parseInt(b.views))
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
								"<div class='clickable bold-font'>" + response[i].title + "</div>" + 
								"<video controls class='clickable show-video'>" + 
									"<source src='" + response[i].path_of_video + "' type='video/mp4'>" + 
								"</video>" + 
								"<div class='pure-button button-error fleft delete-video-button'>Delete</div>" + 
							"<div class='fright-text mt10'>" + response[i].likes + " likes / " + response[i].dislikes + " dislikes</div>" + 
							"</div>" + 
							"<div class='clear'></div>"
						);
					}
				}
			}
		});
	
		$(".delete-video-button").click(function(){
			var video_id = $(this).parent().attr("name");
			$.ajax({
				type: "POST",
				url: "tubeapi/deleteVideo",
				async: false,
				data: { id: video_id },
				statusCode: {
					200: function() {
						alert("Video successfullly deleted");
						location.reload();
					}
				}
			});
		});		
				
	} else {
		$("#login_button").text("Login");
		$("#upload_button").hide();
		$("#my_videos").hide();
	}
	
	popular_videos.sort(compare);
	for (var i = 0; i < popular_videos.length; i ++) {
		$("#popular_videos").append(
			"<div name=" + popular_videos[i].id + " class='show-video'>" + 
				"<div class='clickable bold-font'>" + popular_videos[i].title + "</div>" + 
				"<video controls class='clickable show-video'>" + 
					"<source src='" + popular_videos[i].path_of_video + "' type='video/mp4'>" + 
				"</video>" + 
				"<div class='mt10'>" + 
					"<div class='like-button fleft' style='display: none;'></div>" + 
					"<div class='dislike-button fleft' style='display: none;'></div>" +
					"<div class='favourite-button fleft unfav' style='display: none;'></div>" + 
					"<div class='fright-text-main'>" + popular_videos[i].likes + " likes / " + popular_videos[i].dislikes + " dislikes</div>" + 
				"</div>" + 
			"</div>" + 
			"<div class='clear'></div>"		
		);
	}

	if (localStorage.logged_in == "true") {
		$(".history-videos").show();
		$(".like-button").show();
		$(".dislike-button").show();
		$(".favourite-button").show();		
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
		
		$.ajax({
			url: "tubeapi/history",
			type: "GET",
			data: { user_id: localStorage.userid },
			async: false,
			headers: {"Authorization": localStorage.token},
			statusCode: {
				200: function(response) {
					for (var i = 0; i < response.length; i ++) {
						$(".history-videos").append(
							"<div name=" + response[i].id + " class='show-video'>" + 
								"<div class='clickable bold-font'>" + response[i].title + "</div>" + 
								"<video controls class='clickable show-video'>" + 									"<source src='" + response[i].path_of_video + "' type='video/mp4'>" + 
								"</video>" + 
								"<div class='mt10'>" + 
									"<div class='like-button fleft'></div>" + 
									"<div class='dislike-button fleft'></div>" + 
									"<div class='fright-text'>" + response[i].likes + " likes / " + response[i].dislikes + " dislikes</div>" + 
								"</div>" + 
							"</div>" + 
							"<div class='clear'></div>"
						);
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
		
		$(".clickable").click(function(){
			sessionStorage.id_video_to_play = $(this).parent().attr("name");
			window.location.href = "video.html";	
		});	


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
	}
});
