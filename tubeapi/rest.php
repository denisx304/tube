<?php
    class Rest {
        
        protected $_allow;
        protected $_content_type = "application/json";
        protected $_request;
        
        private $_method = "";        
        private $_code = 200;
        
        public function __construct() {
            $this->allow = array();
            $this->request = array();
            $this->inputs();
        }

        /*  Respond method.
         *
         *  Respond with the given arguments
         *  and exit.
         *
         *  @param array data
         *  @param int status
         *
         *  @return void
         */

        protected function response($data, $status) {
            $this->_code = ($status) ? $status : 200;
            $this->setHeaders();
            echo $data;
            exit;
        }

        /*  Get status message.
         *  
         *  Return appropriate status message.
         *
         * @return string
         */

        private function getStatusMessage() {
            $status = array(
                        200 => 'OK',
                        201 => 'Created',  
                        204 => 'No Content',  
                        400 => 'Bad Request',
                        404 => 'Not Found',  
                        406 => 'Not Acceptable');
            return ($status[$this->_code]) ? $status[$this->_code] : $status[500];
        }

        /* Get request method.
         *
         *   
         *  @return string
         */
        
        protected function getRequestMethod() {
            return isset($_SERVER['REQUEST_METHOD']) ? 
                         $_SERVER['REQUEST_METHOD'] : null;
        }

        /*  Get authorization token
         *  
         *  @return string
         */
        
        protected function getAuthorization() {
            $headers = (array)apache_request_headers();
            return $headers["Authorization"];
        }

        /*  Get input according to the request method.
         *   
         *  @return void
         */ 

        private function inputs() {
            switch($this->getRequestMethod()) {
                case "POST":
                    $this->_request = $this->cleanInputs($_POST);
                    break;
                case "GET":
                case "DELETE":
                    $this->_request = $this->cleanInputs($_GET);
                    break;
                case "PUT":
                    parse_str(file_get_contents("php://input"),$this->_request);
                    $this->_request = $this->cleanInputs($this->_request);
                    break;
                default:
                    $this->response('',406);
                    break;
            }
        }        
        
        /*  Clean inputs.
         *  
         *
         *  @param string
         *
         *  @return void
         */
     
        private function cleanInputs($data) {
            $clean_input = array();
            if (is_array($data)) {
                foreach ($data as $k => $v) {
                    $clean_input[$k] = $this->cleanInputs($v);
                }
            } else {
                if (get_magic_quotes_gpc()) {
                    $data = trim(stripslashes($data));
                }
                $data = strip_tags($data);
                $clean_input = trim($data);
            }
            return $clean_input;
        }        
        /* Set headers.
         *  
         * @return void
         */

        private function setHeaders() {
            header("HTTP/1.1 ".$this->_code." ".$this->getStatusMessage());
            header("Content-Type:".$this->_content_type);
        }
    }    
?>
