<?php
    require_once 'rest.php';
    require_once 'tube_db.php';

    class TubeApi extends Rest {

		private $conn = NULL;
        
        public function __construct() {
			parent::__construct();
            $db = new TubeDb();
            $conn = $this->dbConnect();	
		}
		
		public function processApi() {
			$func = strtolower(trim(str_replace("/","",$_REQUEST['x'])));
			if((int)method_exists($this, $func) > 0)
				$this->$func();
			else
				$this->response('',404);
		}

        function getHash($username, $password) {
            return sha1(strtolower($username).$password);
        }
        
        public static function nextToken() {
            return bin2hex(openssl_random_pseudo_bytes(10));
        }

        static function validateUser($username, $password) {
            if (strlen($username) < 1 || strlen($password) < 8) {
                return false;
            }
            return true;
        }
        
        function signUp() {
            if ($this->getRequestMethod() != 'POST') {
                $this->response('', 406);
            }
            
            $user = json_decode(file_get_contents('php://input'),true);
            if (!self::validateUser($user['username'], $user['password'])) {
                $this->response('', 406);
            }

            $user['password'] = self::getHash($user['username'], $user['password']);
            $keys = array_keys($user);
            $columns = 'username, password';
            $values =  "'" . $user['username'] . "','"  . $user['password'] . "'"; 
		    $query = 'insert into users (' . $columns . ') values ('. $values . ');';
			
            if(!empty($user)) {
				$r = $this->conn->query($query) or die($this->conn->error.__LINE__);
				$success = array('status' => 'Success', 'msg' => 'User Created Successfully.', 'data' => $user);
				$this->response($this->json($success),200);
			} else {
				$this->response('',204);
            }
        }

        function login() {
             if ($this->getRequestMethod() != 'POST') {
                $this->response('', 406);
            }
            
            $user = json_decode(file_get_contents('php://input'),true);
            if (!self::validateUser($user['username'], $user['password'])) {
                $this->response('', 406);
            }
            $user['password'] = self::getHash($user['username'], $user['password']);
            $keys = array_keys($user);

            $query = "select id from users where username = $user->username and password = " .
                     " $user->password;";

			$r = $this->conn->query($query) or die($this->conn->error.__LINE__);
            if (!empty($r)) {
                $userId = $r['id'];
			    $query = "select content, expire_date from tokens where user_id = $userId";
                $r = $this->conn->query($query) or die($this->conn->error.__LINE__);
                if (!empty($r) and strtotime('now') > (int)$r['expire_date']) {
			        $query = "delete from tokens where user_id = $userId";
                    $r = $this->conn->query($query) or die($this->conn->error.__LINE__);
                    $r = "";
                }
                if (!empty($r)) {
                    $this->response($this->json(array("authToken" => $r["content"])) ,200);
                } else {
                    $token = self::nextToken();
                    $expireDate = strtotime("+1 hour"); 
                    $query = "insert into tokens (content, user_id, expire_date) values('$token'," .
                        "'$userId','$expireDate');";
                    $r = $this->conn->query($query) or die($this->conn->error.__LINE__);
				    $resp = array('authToken' => $token);
				    $this->response($this->json($resp),200);
                }
            } else {
				$this->response('',204);
            }
        }

        function logout() {
            if ($this->getRequestMethod() != 'DELETE') {
                $this->response('', 406);
            }
			$query = "delete from tokens where user_id = $userId";
            $r = $this->conn->query($query) or die($this->conn->error.__LINE__);
			$success = array('status' => "Success", "msg" => "Successfully logged out.");

			$this->response($this->json($success),200);
        }

        function user() {
            if ($this->getRequestMethod() != "GET") {
                $this->response('', 406);
            }
            $id = (int)$this->_request['id']; 
            if ($id > 0) {
                $query = "select * from users where id=$id;";    
			    $r = $this->conn->query($query) or die($this->conn->error . __LINE__);
				if($r->num_rows > 0) {
					$result = $r->fetch_assoc();	
					$this->response($this->json($result, 200));
				}
			}
			$this->response('',204);
        }   

        function users() {
            if ($this->getRequestMethod() != "GET") {
                $this->response('', 406);
            }
            $query = "select * from users;";    
            $r = $this->conn->query($query) or die($this->conn->error.__LINE__);

            if ($r->num_rows > 0) {
                $result = array(); 
                while ($row = $r->fetch_assoc()) {
                    $result[] = $row;
                } 
				$this->response($this->json($result), 200);
			}
			$this->response('',204);
        }  

        function updateUser() {
            if ($this->getRequestMethod() != "POST") {
                $this->response('', 406);
            }
			$user = json_decode(file_get_contents("php://input"),true);

        }

	    function deleteUser() {
			if($this->getRequestMethod() != "DELETE") {
				$this->response('',406);
			}
			$id = (int)$this->_request['id'];
			if($id > 0) {				
                $query = "delete from users where id=$id;";
                $r = $this->conn->query($query) or die($this->conn->error.__LINE__);
				$success = array('status' => "Success", "msg" => "Successfully deleted one record.");
				$this->response($this->json($success),200);
			} else {
				$this->response('',204);
            }    
        }
        
        function deleteVideo() {
			if($this->getRequestMethod() != "DELETE") {
				$this->response('',406);
			}
			$id = (int)$this->_request['id'];
			if($id > 0) {				
                $query = "delete from videos where id=$id;";
                $r = $this->conn->query($query) or die($this->conn->error.__LINE__);
				$success = array('status' => "Success", "msg" => "Successfully deleted one record.");
				$this->response($this->json($success),200);
			} else {
				$this->response('',204);
            }    
        }

        function deleteComment() {
			if($this->getRequestMethod() != "DELETE") {
				$this->response('',406);
			}
			$id = (int)$this->_request['id'];
			if($id > 0) {				
                $query = "delete from comments where id=$id;";
                $r = $this->conn->query($query) or die($this->conn->error.__LINE__);
				$success = array('status' => "Success", "msg" => "Successfully deleted one record.");
				$this->response($this->json($success),200);
			} else {
				$this->response('',204);
            }    
        }

        function deleteFromFavorites() {
			if($this->getRequestMethod() != "DELETE") {
				$this->response('',406);
			}
            $userId = (int)$this->_request['user_id'];
            $videoId = (int)$this->_request['video_id'];

			if($id > 0) {				
                $query = "delete from videos where user_id=$userId and video_id=$videoId;";
                $r = $this->conn->query($query) or die($this->conn->error.__LINE__);
				$success = array('status' => "Success", "msg" => "Successfully deleted one record.");
				$this->response($this->json($success),200);
			} else {
				$this->response('',204);
            }    
        }

	
		function json($data) {
			if(is_array($data)){
				return json_encode($data);
			}
        }


        function insertComment() {
            if ($this->getRequestMethod() != "POST") {
                $this->response('', 406);
            }
			$comment = json_decode(file_get_contents("php://input"),true);
            $columns = 'user_id, video_id, text';
            $values = $comment['user_id'] . ',' . $comment['video_id'] . ',\'' . $comment['text'] . "'";
			$query = "insert into comments(". $columns . ") VALUES(".  $values . ");";
            if(!empty($comment)) {
				$r = $this->conn->query($query) or die($this->conn->error.__LINE__);
			    $success = array('status' => "Success", "msg" => "Comment Posted.", "data" => $comment);
				$this->response($this->json($success),200);
			} else {
				$this->response('',204);
            }
        }


        function comment() {
            if ($this->getRequestMethod() != "GET") {
                $this->response('', 406);
            }
            $id = (int)$this->_request['id'];
            if ($id > 0) {
                $query = "select * from comments where id=$id;";    
				$r = $this->conn->query($query) or die($this->conn->error.__LINE__);
				if($r->num_rows > 0) {
					$result = $r->fetch_assoc();	
					$this->response($this->json($result), 200);
				}
			}
			$this->response('',204);
        }   
        
        function comments() {
            if ($this->getRequestMethod() != "GET") {
                $this->response('', 406);
            }
            $id = (int)$this->_request['video_id'];
            if ($id > 0) {
                $query = "select * from comments where video_id=$id;";    
				$r = $this->conn->query($query) or die($this->conn->error.__LINE__);
				if($r->num_rows > 0) {
                    $result = array();
                    while ($row = $r->fetch_assoc()) {
                        $result[] = $row;
                    }  
					$this->response($this->json($result), 200); 
				}
			}
			$this->response('',204);
        }

        function insertVideo() {
            if ($this->getRequestMethod() != "POST") {
                $this->response('', 406);
            }
            /*
            $target_dir = "uploads/";
            $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
            $uploadOk = 1;
            $imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
            // Check if image file is a actual image or fake image
            if(isset($_POST["submit"])) {
                $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
                if($check !== false) {
                    echo "File is an image - " . $check["mime"] . ".";
                    $uploadOk = 1;
                } else {
                    echo "File is not an image.";
                    $uploadOk = 0;
                }
            }
            // Check if file already exists
            if (file_exists($target_file)) {
                echo "Sorry, file already exists.";
                $uploadOk = 0;
            }
            // Check file size
            if ($_FILES["fileToUpload"]["size"] > 500000) {
                echo "Sorry, your file is too large.";
                $uploadOk = 0;
            }
            // Allow certain file formats
            if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
            && $imageFileType != "gif" ) {
                echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
                $uploadOk = 0;
            }
            // Check if $uploadOk is set to 0 by an error
            if ($uploadOk == 0) {
                echo "Sorry, your file was not uploaded.";
            // if everything is ok, try to upload file
            } else {
                if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
                    echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.";
                } else {
                    echo "Sorry, there was an error uploading your file.";
                }
            }
            */
            $video = json_decode(file_get_contents("php://input"),true);
            $columns = 'user_id, title, path_of_video';
            $values = $video['user_id'] . ',\'' . $video['title'] . '\',\'' . $video['path_of_video'] . "'";
			$query = "insert into videos(". $columns . ") VALUES(".  $values . ");";
			//$this->response($this->json(array("query" => $query)),200);
            
            if(!empty($video)) {
				$r = $this->conn->query($query) or die($this->conn->error.__LINE__);
			    $success = array('status' => "Success", "msg" => "Video Posted.", "data" => $video);
				$this->response($this->json($success),200);
			} else {
				$this->response('',204);
            }
        }

        function addToFavorites() {
            if ($this->getRequestMethod() != "POST") {
                $this->response('', 406);
            }
			$data = json_decode(file_get_contents("php://input"),true);
            $userId = $data["user_id"];
            $videoId = $data["video_id"];
            $query = "insert into favorites (user_id, video_id) values($userId, $videoId);";
            if(!empty($data)) {
				$r = $this->conn->query($query) or die($this->conn->error.__LINE__);
			    $success = array('status' => "Success", "msg" => "Added to Favorites.", "data" => $data);
				$this->response($this->json($success),200);
			} else {
				$this->response('',204);
            }
        }

        function addToHistory() {
            if ($this->getRequestMethod() != "POST") {
                $this->response('', 406);
            }
			$data = json_decode(file_get_contents("php://input"),true);
            $userId = $data["user_id"];
            $videoId = $data["video_id"];
            $query = "insert into history (user_id, video_id) values($userId, $videoId);";
            if(!empty($data)) {
				$r = $this->conn->query($query) or die($this->conn->error.__LINE__);
			    $success = array('status' => "Success", "msg" => "Added to History.", "data" => $data);
				$this->response($this->json($success),200);
			} else {
				$this->response('',204);
            }
        }

        function video() {
            if ($this->getRequestMethod() != "GET") {
                $this->response('', 406);
            }
            $id = (int)$this->_request['id'];
            if ($id > 0) {
                $query = "select * from videos where id=$id;";    
				$r = $this->conn->query($query) or die($this->conn->error.__LINE__);
				if($r->num_rows > 0) {
					$result = $r->fetch_assoc();	
					$this->response($this->json($result), 200);
				}
			}
			$this->response('',204);
        }   
        
        function videos() {
            if ($this->getRequestMethod() != "GET") {
                $this->response('', 406);
            }
            $id = (int)$this->_request['user_id'];
            if ($id > 0) {
                $query = "select * videos where user_id=$id;";    
				$r = $this->conn->query($query) or die($this->conn->error.__LINE__);
				if($r->num_rows > 0) {
                    $result = array();
                    while ($row = $r->fetch_assoc()) {
                        $result[] = $row;
                    }  
					$this->response($this->json($result), 200); 
				}
			}
			$this->response('',204);
        }   
        
        function favorites() {
            if ($this->getRequestMethod() != "GET") {
                $this->response('', 406);
            }
            $id = (int)$this->_request['user_id'];
            if ($id > 0) {
                $query = "select * from favorites where user_id=$id;";    
				$r = $this->conn->query($query) or die($this->conn->error.__LINE__);
				if($r->num_rows > 0) {
                    $result = array();
                    while ($row = $r->fetch_assoc()) {
                        $result[] = $row;
                    }  
					$this->response($this->json($result), 200); 
				}
			}
			$this->response('',204);
        }   
        
        function history() {
            if ($this->getRequestMethod() != "GET") {
                $this->response('', 406);
            }
            $id = (int)$this->_request['user_id'];
            if ($id > 0) {
                $query = "select * from history where user_id=$id;";    
				$r = $this->conn->query($query) or die($this->conn->error.__LINE__);
				if($r->num_rows > 0) {
                    $result = array();
                    while ($row = $r->fetch_assoc()) {
                        $result[] = $row;
                    }  
					$this->response($this->json($result), 200); 
				}
			}
			$this->response('',204);
        }   
    }
	
    $api = new TubeApi;
    $api->processApi();

?>
