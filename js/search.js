var videos = [];
var popular_videos = [];
$(document).ready(function(){
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

$("#play_video").click(function(){
	if ($("#search_box").val() == sessionStorage.title_video_to_play) {
		window.location.href = "video.html";
	}
});

});
