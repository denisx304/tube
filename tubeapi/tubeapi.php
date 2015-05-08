<?php

    require 'vendor/autoload.php';
 	require_once 'rest.php';

    class TubeApi extends Rest {

		private $conn = NULL;
        
        public function __construct() {
			parent::__construct();
            include_once "db_config.php";
            $this->conn = new mysqli(dbServer, dbUser, dbPassword, dbName);          
        }

        function isKeyExpired($r, $userId) {
            if (strtotime('now') > (int)$r['expire_date']) {
                $query = "delete from tokens where user_id = $userId;";
                $r = $this->conn->query($query) or die($this->conn->error.__LINE__);
                return true;
            }
            return false;
        }

        function validateAuthorization($userId, $auth) {
            $jwt = "";

            if (substr($auth, 0, 6) == "Bearer") {
                $jwt = substr($auth, 7, strlen($auth));
            } else {
                return false;
            }
        
            $query = "select content, expire_date from tokens where user_id = $userId ;";
            $r = $this->conn->query($query) or die($this->conn->error.__LINE__);
            
            if ($r->num_rows == 0)
                return false;
            $response = $r->fetch_assoc();

            if ($this->isKeyExpired($response, $userId)) 
                return false;

            $key = $response['content'];
            $decoded = JWT::decode($jwt, $key, array('HS256'));
            $decodedArray = (array) $decoded;
            return $decodedArray['iss'] == 'tubeapi';
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

            $query = "select id from users where username = '" . $user['username'] . "' and password = '" .
                      $user['password'] . "';";
        
            $r = $this->conn->query($query) or die($this->conn->error.__LINE__);
            
            if ($r->num_rows > 0) {
				$response = $r->fetch_assoc();	
                $userId = $response['id'];
			    $query = "select content, expire_date from tokens where user_id = $userId ;";

                $r = $this->conn->query($query) or die($this->conn->error.__LINE__);
                $hasToken = false;
                // check if the token expired
                if ($r->num_rows > 0) {
                    $hasToken = true;
                    $response = $r->fetch_assoc();
                    if ( strtotime('now') > (int)$response['expire_date']) {
                        $query = "delete from tokens where user_id = $userId";
                        $r = $this->conn->query($query) or die($this->conn->error.__LINE__);
                        $r = null;
                    }
                }
                if (r != null and $hasToken) {
                    $response = $r->fetch_assoc();
                    $key = $response["content"];
                    $token = array(
                        "iss" => "tubeapi"
                    );
                    $jwt = JWT::encode($token, $key);
                    $this->response($this->json(array("token" => $jwt)) ,200);
                } else {
                    $key = self::nextToken();
                    $token = array(
                        "iss" => "tubeapi"
                    );
                    $jwt = JWT::encode($token, $key); 

                    $expireDate = strtotime("+1 hour"); 
                    $query = "insert into tokens (user_id, content, expire_date) values('$userId'," .
                        "'$key','$expireDate');";

                    $r = $this->conn->query($query) or die($this->conn->error.__LINE__);
				    $resp = array('token' => $jwt);
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
            $userId = (int)$this->_request['userId']; 
            $auth = $this->getAuthorization();
            if (!$this->validateAuthorization($userId, $auth)) {
                $this->response('',204);
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
            $auth = $this->getAuthorization();
            if (!$this->validateAuthorization($userId, $auth)) {
                $this->response('',204);
            }
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
            $auth = $this->getAuthorization();
            if (!$this->validateAuthorization($comment['user_id'], $auth)) {
                $this->response('',204);
            }
            
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
            $auth = $this->getAuthorization();
            
            $video = json_decode($_POST['json'], true); 


            if (!$this->validateAuthorization($video['user_id'], $auth)) {
                $this->response('',204);
            }


            if (!isset($_FILES['upfile']['error']) || is_array($_FILES['upfile']['error'])) {
                $this->response($this->json(array("Error" => 'Invalid parameters')), 400);
            }

            // You should also check filesize here. 
            if ($_FILES['upfile']['size'] > 10000000) {
                $this->response($this->json(array("Error" => 'Exceeded filesize limit.')), 400);
            }
         
            $videoFileType = pathinfo($_FILES['upfile']['name'],PATHINFO_EXTENSION);

            
            if (!in_array($videoFileType, array("mp4", "wmv"))) {
                $this->response($this->json(array("Error" => "Invalid file format.")), 400);
            }



            $filePath = 'uploads/' . $_FILES['upfile']['name'];

            if ($_FILES['upfile']['name'] == "" or file_exists($filePath) ) {
                $this->response($this->json(array("Error" => $_FILES['upfile']['name'])), 400);
            }
 
            if (!move_uploaded_file($_FILES['upfile']['tmp_name'], $filePath)) {
                $this->response($this->json(array("Error" => "Failed to move uploaded file.")), 400);
            }
            
            $video['path_of_video'] = $filePath;
            $columns = 'user_id, title, path_of_video';
            $values = $video['user_id'] . ',\'' . $video['title'] . '\',\'' . $video['path_of_video'] . "'";
			$query = "insert into videos(". $columns . ") VALUES(".  $values . ");";
			 
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
            $auth = $this->getAuthorization();
            if (!$this->validateAuthorization($userId, $auth)) {
                $this->response('',204);
            }
            
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
            $auth = $this->getAuthorization();
            if (!$this->validateAuthorization($userId, $auth)) {
                $this->response('',204);
            }
            
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
            $auth = $this->getAuthorization();
            if (!$this->validateAuthorization($id, $auth)) {
                $this->response('',204);
            }
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
            $auth = $this->getAuthorization();
            if (!$this->validateAuthorization($id, $auth)) {
                $this->response('',204);
            }

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
