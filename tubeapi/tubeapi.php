<?php

    require 'vendor/autoload.php';
    require_once 'rest.php';

    class TubeApi extends Rest {

        // database connection
        private $conn = NULL;

        /**  
         *  Class constructor.
         *  
         *  Open a new connection to the database with
         *  the provided configuration from db_config.php.
         *
         *  @return void
         */

        public function __construct() {
            parent::__construct();
            include_once "db_config.php";
            $this->conn = new mysqli(dbServer, dbUser, dbPassword, dbName);          
        }

        /**
         *  Check if a token key is expired. 
         *
         *  @param array $r
         *  @param int $userId
         *  
         *  @return boolean
         */

        function isKeyExpired($r, $userId) {
            if (strtotime('now') > (int)$r['expire_date']) {
                $query = "delete from tokens where user_id = $userId;";
                $r = $this->conn->query($query) or die($this->conn->error.__LINE__);
                return true;
            }
            return false;
        }

        /**  
         *  Validate if user auhorization is valid.
         *  
         *  Check if the auth has the proper format, it decodes
         *  the jwt and will check if the key is expired and it will return true
         *  if the token is valid, false otherwise
         *
         *  @param int $userId
         *  @param string $auth
         *
         *  @return boolean
         */

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

        /**  
         * Process requests and redirect accordingly.
         *  
         *  If it's a valid request then it will process it, otherwise
         *  it returns 404.
         *  
         *  @return void
         */
        public function processApi() {
            $func = strtolower(trim(str_replace("/","",$_REQUEST['x'])));
            if((int)method_exists($this, $func) > 0)
                $this->$func();
            else
                $this->response('',404);
        }

        /**  
         *  Compute hash value for username and password.
         *  
         *  Compute the sha1 hash from the username concatenated 
         *  with the password, so we can avoid detecting users with
         *  the same password.
         *
         *  @param string $username
         *  @param string $password
         *  
         *  @return string
         */

        function getHash($username, $password) {
            return sha1(strtolower($username).$password);
        }

        /**  
         * Compute random string of bytes for token.
         *
         *  @return string
         */
        
        public static function nextToken() {
            return bin2hex(openssl_random_pseudo_bytes(10));
        }

        /**
         * Validate username and password format.
         *   
         *  @param string $username
         *  @param string $password
         *  
         *  @return boolean
         */

        static function validateUser($username, $password) {
            if (strlen($username) < 1 || strlen($password) < 8) {
                return false;
            }
            return true;
        }

        /**
         *  Sign up user.
         *
         *  Process username and password for a user, respond
         *  with code 200 if succesful, 406 if the request is invalid
         *  
         *
         *  @return void
         */
        
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
                $this->response(json_encode($success),200);
            } else {
                $this->response('',204);
            }
        }

        /**  
         *  Return token to user.
         *  
         *  Generate a random token which expires in one hour if the 
         *  user doesn't already have one generated in which case we return
         *  them their current token. The user will use the token for 
         *  authentifcation on subsequent requests. Respond with 406 for 
         *  an invalid request, with 204 if the user doesn't exist or 
         *  the password is wrong, respond with 200 if succesful and return 
         *  the token in json.
         *
         *  @return void
         */
        
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
                if ($r != null and $hasToken) {
                    $response = $r->fetch_assoc();
                    $key = $response["content"];
                    $token = array(
                        "iss" => "tubeapi"
                    );
                    $jwt = JWT::encode($token, $key);
                    $resp = array('token' => 'Bearer ' .$jwt);
                    $this->response(json_encode($resp), 200);
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
                    $resp = array('token' => 'Bearer ' .$jwt);
                    $this->response(json_encode($resp), 200);
                }
            } else {
                $this->response('',204);
            }
        }


        /**  Perform logout.
         *  
         *  If the user provides valid authentification 
         *  details delete the token from the db.
         *  Respond with 204 if the authorization is invalid, 
         *  with 200 if the logout succeeds.
         *
         *  @return void
         */
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
            $this->response(json_encode($success), 200);
        }

        /**  
         *  Get user.
         *  
         *  Respond with 200 and return user details if
         *  the user id is valid, respond with 406 if 
         *  the requst is invalid, 204 if the user doesn't exist.
         *
         *  @return void
         */
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
                    $this->response(json_encode($result), 200);
                } else {
                    $this->response('', 204);
                }
            } else {
                $this->response('',406);
            }
        }   


        /**  
         *  Get users.
         *   
         *  Respond with 204 if there are no users, respond with
         *  200 and return json with details for all users otherwise.
         *
         *  @return void
         */

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
                $this->response(json_encode($result), 200);
            } else {
                $this->response('',204);
            }
        }

      /**  
       *  Delete user.
       *
       *
       *  @return void
       */  

        function deleteUser() {
            if($this->getRequestMethod() != "DELETE") {
                $this->response('',406);
            }
            $id = (int)$this->_request['id'];
            if($id > 0) {                
                $query = "delete from users where id=$id;";
                $r = $this->conn->query($query) or die($this->conn->error.__LINE__);
                $success = array('status' => "Success", "msg" => "Successfully deleted one record.");
                $this->response(json_encode($success), 200);
            } else {
                $this->response('', 204);
            }    
        }

        /**  
        *  Delete video.
        *
        *
        *  @return void
        */ 

        function deleteVideo() {
            if($this->getRequestMethod() != "DELETE") {
                $this->response('',406);
            }
            $id = (int)$this->_request['id'];
            if($id > 0) {                
                $query = "delete from videos where id=$id;";
                $r = $this->conn->query($query) or die($this->conn->error.__LINE__);
                $success = array('status' => "Success", "msg" => "Successfully deleted one record.");
                $this->response(json_encode($success),200);
            } else {
                $this->response('',204);
            }    
        }

        /**  
         * Delete comment.
         *
         * 
         *  @return void
         */ 

        function deleteComment() {
            if($this->getRequestMethod() != "DELETE") {
                $this->response('',406);
            }
            $id = (int)$this->_request['id'];
            if($id > 0) {                
                $query = "delete from comments where id=$id;";
                $r = $this->conn->query($query) or die($this->conn->error.__LINE__);
                $success = array('status' => "Success", "msg" => "Successfully deleted one record.");
                $this->response(json_encode($success),200);
            } else {
                $this->response('',204);
            }    
        }

        /**  
         * Delete video from favorites.
        *
        *
        *  @return void
        */ 

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
                $this->response(json_encode($success),200);
            } else {
                $this->response('',204);
            }    
        }

        /**  
         * Comment on a video.
         *
         *  Respond with 406 if the authorization token
         *  is invalid. Respond with 200 if the comment
         *  was successfully posted.  
         *   
         *  @return void
         */ 

        function insertComment() {
            if ($this->getRequestMethod() != "POST") {
                $this->response('', 406);
            }
            $comment = json_decode(file_get_contents("php://input"),true);
            $auth = $this->getAuthorization();
            if (!$this->validateAuthorization($comment['user_id'], $auth)) {
                $this->response('', 406);
            }
            
            $columns = 'user_id, video_id, text';
            $values = $comment['user_id'] . ',' . $comment['video_id'] . ',\'' . $comment['text'] . "'";
            
            $query = "insert into comments(". $columns . ") VALUES(".  $values . ");";
            $r = $this->conn->query($query) or die($this->conn->error.__LINE__);
            $success = array('status' => "Success", "msg" => "Comment Posted.", "data" => $comment);
            $this->response(json_encode($success),200);
        }

        /**  
         *  Get comment.
         * 
         *  Respond with 200 and return json with comment 
         *  if the comment exists, respond with 204
         *  if it doesn't exist and with 400 if the
         *  request is invalid.
         *
         *  @return void
         */

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
                    $this->response(json_encode($result), 200);
                } else {
                    $this->response('', 204);
                }
            } else {
                $this->response('', 400);
            }
        }   

        /**  
         *  Get the comments for a video.
         * 
         *  Respond with 200 and return json with comments 
         *  if the video exists, respond with 204
         *  if the video doesn't exist and with 400 if the
         *  request is invalid.
         *
         * @return void
         */

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
                    $this->response(json_encode($result), 200); 
                } else {
                    $this->response('', 204);
                }
            } else {
                $this->response('', 400);
            }    
        }

        /**  
         * Upload video.
         * 
         * The request body should have the video file and a json
         * object containing the video details.
         * Checks if the user has authorization, check if the
         * file meets the requirements, insert the file path on the server
         * and the video details in the db and moves the file to the
         * uploads folder on the server. Respond with 400 if the
         * request is invalid, 200 if the video was succesfully posted.
         *
         * @return void
         */

        function insertVideo() {
            if ($this->getRequestMethod() != "POST") {
                $this->response('', 406);
            }
            $auth = $this->getAuthorization();
            $video = json_decode($_POST['json'], true);

            if (!$this->validateAuthorization($video['user_id'], $auth)) {
                $this->response('', 400);
            }

            if (!isset($_FILES['upfile']['error']) || is_array($_FILES['upfile']['error'])) {
                $this->response(json_encode(array("Error" => 'Invalid parameters')), 400);
            }

            // You should also check filesize here. 
            if ($_FILES['upfile']['size'] > 10000000) {
                $this->response(json_encode(array("Error" => 'Exceeded filesize limit.')), 400);
            }
         
            $videoFileType = pathinfo($_FILES['upfile']['name'],PATHINFO_EXTENSION);
            
            if (!in_array($videoFileType, array("mp4", "wmv"))) {
                $this->response(json_encode(array("Error" => "Invalid file format.")), 400);
            }

            $filePath = 'uploads/' . $_FILES['upfile']['name'];

            if ($_FILES['upfile']['name'] == "" or file_exists($filePath) ) {
                $this->response(json_encode(array("Error" => $_FILES['upfile']['name'])), 400);
            }
 
            if (!move_uploaded_file($_FILES['upfile']['tmp_name'], $filePath)) {
                $this->response(json_encode(array("Error" => "Failed to move uploaded file.")), 400);
            }
            
            $video['path_of_video'] = $filePath;
            $columns = 'user_id, title, path_of_video';
            $values = $video['user_id'] . ',\'' . $video['title'] . '\',\'' . $video['path_of_video'] . "'";
            $query = "insert into videos(". $columns . ") VALUES(".  $values . ");";
            
            $r = $this->conn->query($query) or die($this->conn->error.__LINE__);
            $success = array('status' => "Success", "msg" => "Video Posted.", "data" => $video);
            $this->response(json_encode($success),200);
        }


        /**  
         *  Add video to user favorites.
         *
         *  Respond with 200 if the video was succesfully
         *  added to favorites.
         *
         *  @return void
         */
        
        function addToFavorites() {
            if ($this->getRequestMethod() != "POST") {
                $this->response('', 406);
            }
            $data = json_decode(file_get_contents("php://input"),true);
            $userId = $data["user_id"];
            $videoId = $data["video_id"];
            $auth = $this->getAuthorization();
            if (!$this->validateAuthorization($userId, $auth)) {
                $this->response('', 400);
            }
            
            $query = "insert into favorites (user_id, video_id) values($userId, $videoId);";
            $r = $this->conn->query($query) or die($this->conn->error.__LINE__);
            $success = array('status' => "Success", "msg" => "Added to Favorites.", "data" => $data);
            $this->response(json_encode($success),200);
        }

        /**  
         *  Add video to user history.
         *
         *  Respond with 200 if the video was succesfully
         *  added to user history. Respond with 400 if the
         *  authorization is invalid.
         *
         *  @return void
         */
        
        function addToHistory() {
            if ($this->getRequestMethod() != "POST") {
                $this->response('', 406);
            }
            $data = json_decode(file_get_contents("php://input"),true);
            $userId = $data["user_id"];
            $videoId = $data["video_id"];
            $auth = $this->getAuthorization();
            if (!$this->validateAuthorization($userId, $auth)) {
                $this->response('', 400);
            }
            
            $query = "insert into history (user_id, video_id) values($userId, $videoId);";
            $r = $this->conn->query($query) or die($this->conn->error.__LINE__);
            $success = array('status' => "Success", "msg" => "Added to History.", "data" => $data);
            $this->response(json_encode($success),200);
        }

        /**  
         *  Get video
         *  
         *  Respond with 400 if the request is invalid, 
         *  with 204 if there is no video with the requested id.
         *  Respond with 200 and return json with video details
         *  if successful.
         *
         *  @return void
         */

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
                    $this->response(json_encode($result), 200);
                } else {
                    $this->response('', 204);
                }
            } else {
                $this->response('', 400);
            }
        }

        /**  
         * Get videos.
         *  
         *  Respond with 400 if the request is invalid, 
         *  with 204 if there is no video with the requested id.
         *  Respond with 200 and return json with all the videos 
         *  details for that user if successful.
         *
         *  @return void
         */
        
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
                    $this->response(json_encode($result), 200); 
                } else {
                    $this->response('',204);
                }
            } else {
                $this->response('', 400);
            }
        }   

        /**  
         *  Get favorites.
         *
         *  Respond with 400 if the request is invalid.
         *  Respond with 204 if no user with that id exists.
         *  Respond with 200 and return json with videos details for favorites for 
         *  that user.
         *
         *  @return void
         */

        function favorites() {
            if ($this->getRequestMethod() != "GET") {
                $this->response('', 406);
            }
            $id = (int)$this->_request['user_id'];
            $auth = $this->getAuthorization();
            if (!$this->validateAuthorization($id, $auth)) {
                $this->response('', 400);
            }
            if ($id > 0) {
                $query = "select * from favorites where user_id=$id;";    
                $r = $this->conn->query($query) or die($this->conn->error.__LINE__);
                if($r->num_rows > 0) {
                    $result = array();
                    while ($row = $r->fetch_assoc()) {
                        $result[] = $row;
                    }  
                    $this->response(json_encode($result), 200); 
                } else {
                    $this->response('',204);
                }
            } else {
                $this->response('', 400);
            }     
        }   
        
        /**  
         *  Get history.
         *
         *  Respond with 400 if the request is invalid.
         *  Respond with 204 if no user with that id exists.
         *  Respond with 200 and return json with videos details of history for 
         *  that user.
         *
         *  @return void
         */

        function history() {
            if ($this->getRequestMethod() != "GET") {
                $this->response('', 406);
            }
            $id = (int)$this->_request['user_id'];
            $auth = $this->getAuthorization();
            if (!$this->validateAuthorization($id, $auth)) {
                $this->response('', 400);
            }

            if ($id > 0) {
                $query = "select * from history where user_id=$id;";    
                $r = $this->conn->query($query) or die($this->conn->error.__LINE__);
                if($r->num_rows > 0) {
                    $result = array();
                    while ($row = $r->fetch_assoc()) {
                        $result[] = $row;
                    }  
                    $this->response(json_encode($result), 200); 
                } else {
                    $this->response('',204);
                }
            } else {
                $this->response('', 400);
            }
        }   
    }
    
    $api = new TubeApi;
    $api->processApi();

?>
