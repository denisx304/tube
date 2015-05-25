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
					"<div class='mt10'>" + 
						"<div class='video-title'>" + response.title + "</div>" +  
						"<video controls class='video-view mt10'>" + 
							"<source src='" + response.path_of_video + "' type='video/mp4'>" + 
						"</video>" + 
						"<div class='like-button fleft mt10' style='display: none;'></div>" + 
						"<div class='dislike-button fleft mt10' style='display: none;'></div>" + 
						"<div class='fright-text  mt10'>" + response.likes + " likes / " + response.dislikes + " dislikes</div>" + 
						"<div class='clear'></div>" + 
					"</div>" + 
					"<p class='desc-text mt10'>Description:</p>" + 
					"<div class='video-description mt10'>" + response.description + "</div>" +
					"<div class='comment-post'>" + 
						"<textarea class='text-area h150p mt10' placeholder='Insert comment here'></textarea>" +  
						"<button class='pure-button fright-text button-error w400'>Post</button>" + 
					"</div>" + 
					"<div class='clear'></div>"
				);
			}		
		}
	});
	
	if (localStorage.logged_in == "true") {
		$(".like-button").show();
		$(".dislike-button").show();
		
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
	}		
});

/*
			<div class='comment-list mt10'>
				<div class='comment'>
					<p class='author'>Comment author:</p>
					<p class='comment-text'>comment1</p>
				</div>
				<div class='comment'>
					<p class='author'>Comment author:</p>
					<p class='comment-text'>comment1</p>
				</div>
				<div class='comment'>
					<p class='author'>Comment author:</p>
					<p class='comment-text'>comment1</p>
				</div>
				<div class='comment'>
					<p class='author'>Comment author:</p>
					<p class='comment-text'>comment1</p>
				</div>
			</div>
*/
