<?php

require 'vendor/autoload.php';
require 'helper.php';

use \Curl\Curl;

class TubeApiTest extends PHPUnit_Framework_TestCase {

    private static $curl;

    public static function setUpBeforeClass() {
        include_once 'db_config.php';
        $scriptPath = 'db.sql'; 
        // reset db
        Helper::sourceSql($scriptPath, dbServer, dbUser, dbPassword, dbName);
        self::$curl = new Curl();
    }

    public function testSignUp() {
        $data = json_encode(array('username' => 'jonah', 'password' => 'hillisabadactor'));

        // insert first user
        self::$curl->post('http://localhost/tubeapi/signUp', $data);
        $this->assertEquals(self::$curl->response_headers['Status-Line'] , 'HTTP/1.1 200 OK', 'Failed to insert first user');
        $data = json_encode(array('username' => 'strutter', 'password' => 'strutting'));
        // insert second user
        self::$curl->post('http://localhost/tubeapi/signUp', $data);
        $this->assertEquals(self::$curl->response_headers['Status-Line'] , 'HTTP/1.1 200 OK', 'Failed to insert second user');
    }
    
    /**
     * @depends testSignUp
     */

    public function testUsers() {
        self::$curl->get('http://localhost/tubeapi/users');
        $this->assertTrue(self::$curl->response_headers['Status-Line'] == 'HTTP/1.1 200 OK', 'Failed /tubeapi/users');
    }

    /**
     * @depends testSignUp
     */

    public function testUser() {
        self::$curl->get('http://localhost/tubeapi/user', array(
            'id' => '1',
        ));
        $this->assertTrue(self::$curl->response_headers['Status-Line'] == 'HTTP/1.1 200 OK', 'Failed /tubeapi/user?id=1');
    }

    /**
     * @depends testSignUp
     */

    public function testLoginUser() {
        $data = json_encode(array('username' => 'strutter', 'password' => 'strutting'));
        self::$curl->post('http://localhost/tubeapi/login', $data);
        $this->assertTrue(self::$curl->response_headers['Status-Line'] == 'HTTP/1.1 200 OK', 'Failed /tubeapi/login');
        $resp = (array)self::$curl->response;
        $auth = $resp['token'];
        self::$curl->setHeader('Authorization', $auth);
    }
    /**
     * @depends testLoginUser
     */

    public function testLoginUserS() {
        $data = json_encode(array('username' => 'jonah', 'password' => 'hillisabadactor'));
        self::$curl->post('http://localhost/tubeapi/login', $data);
        $this->assertTrue(self::$curl->response_headers['Status-Line'] == 'HTTP/1.1 200 OK', 'Failed /tubeapi/login');
        $resp = (array)self::$curl->response;
        $auth = $resp['token'];
        self::$curl->setHeader('Authorization', $auth);
    }


    /**
     * @depends testSignUp
     */

    public function testLogin() {
        $data = json_encode(array('username' => 'jonah', 'password' => 'hillisabadactor'));
        self::$curl->post('http://localhost/tubeapi/login', $data);
        $this->assertTrue(self::$curl->response_headers['Status-Line'] == 'HTTP/1.1 200 OK', 'Failed /tubeapi/login');
            
    
        $resp = (array)self::$curl->response;
        $auth = $resp['token'];
        self::$curl->setHeader('Authorization', $auth);
    
    }


    /**
     * @depends testLogin
    */

    public function testLoginTry() {
        $data = json_encode(array('username' => 'jonah', 'password' => 'hillisabadactor'));
        self::$curl->post('http://localhost/tubeapi/login', $data);
        $this->assertTrue(self::$curl->response_headers['Status-Line'] == 'HTTP/1.1 200 OK', 'Failed /tubeapi/login');
    }



    /*
     * @depends testLogin
     */

    public function testUpload() {
        $filename = "uploads/testfile.mp4";
        if (file_exists($filename)) {
            unlink($filename);
        }

        $file = new CURLFile('testfile.mp4');
        $data = array('json' => json_encode(array(
                'user_id' => '1',
                'title' => 'random video',)), 
                'upfile' => $file );
        self::$curl->post('http://localhost/tubeapi/insertVideo', $data);
        $this->assertTrue(self::$curl->response_headers['Status-Line'] == 'HTTP/1.1 200 OK', 'Failed to insert video.');
    }

    /*
     * @depends testUpload
     */

    public function testVideos() {
        self::$curl->get('http://localhost/tubeapi/videos', array(
            'user_id' => '1'
        ));
        $this->assertTrue(self::$curl->response_headers['Status-Line'] == 'HTTP/1.1 200 OK', 'Failed /tubeapi/videos');
    }


    /*
     * @depends testUpload
     */

    public function testVideo() {
        self::$curl->get('http://localhost/tubeapi/video', array(
            'id' => '1'
        ));
        $this->assertTrue(self::$curl->response_headers['Status-Line'] == 'HTTP/1.1 200 OK', 'Failed /tubeapi/video?id=1');
    }


    /*
     * @depends testUpload
     */

    public function testInsertComment() {
        $data = json_encode(array('user_id' => '1','video_id' => '1','text' => 'Justin Biber sucks!'));
        self::$curl->post('http://localhost/tubeapi/insertComment', $data);
        $this->assertTrue(self::$curl->response_headers['Status-Line'] == 'HTTP/1.1 200 OK', 'Failed to insert comment.');
    }

    /*
     * @depends testUpload
     */

    public function testComments() {
        self::$curl->get('http://localhost/tubeapi/comments', array(
            'video_id' => '1'
        ));
        $this->assertTrue(self::$curl->response_headers['Status-Line'] == 'HTTP/1.1 200 OK', 'Failed /tubeapi/comments');
    }
   
    /*
     * @depends testUpload
     */

    public function testComment() {
        self::$curl->get('http://localhost/tubeapi/comment', array(
            'id' => '1'
        ));
        $this->assertTrue(self::$curl->response_headers['Status-Line'] == 'HTTP/1.1 200 OK', 'Failed /tubeapi/comment?id=1');
    } 

    /*
     * @depends testComment
     */

    public function testCommentLike() {
        $data = json_encode(array('id' => '1'));
        self::$curl->post('http://localhost/tubeapi/likeComment', $data);
        $this->assertTrue(self::$curl->response_headers['Status-Line'] == 'HTTP/1.1 200 OK', 'Failed /tubeapi/likeComment');
        
        self::$curl->get('http://localhost/tubeapi/comment', array(
            'id' => '1'
        ));
        $resp = (array)self::$curl->response;
        $likes = (int)$resp['likes'];
        $this->assertTrue($likes == 1, 'Incorrect number of likes');
    }

    /*
     * @depends testComment
     */

    public function testCommentDislike() {
        $data = json_encode(array('id' => '1'));
        self::$curl->post('http://localhost/tubeapi/dislikeComment', $data);
        $this->assertTrue(self::$curl->response_headers['Status-Line'] == 'HTTP/1.1 200 OK', 'Failed /tubeapi/dislikeComment');
        
        self::$curl->get('http://localhost/tubeapi/comment', array(
            'id' => '1'
        ));
        $resp = (array)self::$curl->response;
        $dislikes = (int)$resp['dislikes'];
        $this->assertTrue($dislikes == 1, 'Incorrect number of dislikes');
    }

    /*
     * @depends testComment
     */

    public function testVideoLike() {
        $data = json_encode(array('id' => '1'));
        self::$curl->post('http://localhost/tubeapi/likeVideo', $data);
        $this->assertTrue(self::$curl->response_headers['Status-Line'] == 'HTTP/1.1 200 OK', 'Failed /tubeapi/likeVideo');
        
        self::$curl->get('http://localhost/tubeapi/comment', array(
            'id' => '1'
        ));
        $resp = (array)self::$curl->response;
        $likes = (int)$resp['likes'];
        $this->assertTrue($likes == 1, 'Incorrect number of likes');
    }

    /*
     * @depends testVideo
     */

    public function testVideoDislike() {
        $data = json_encode(array('id' => '1'));
        self::$curl->post('http://localhost/tubeapi/dislikeVideo', $data);
        $this->assertTrue(self::$curl->response_headers['Status-Line'] == 'HTTP/1.1 200 OK', 'Failed /tubeapi/dislikeVideo');
        
        self::$curl->get('http://localhost/tubeapi/comment', array(
            'id' => '1'
        ));
        $resp = (array)self::$curl->response;
        $dislikes = (int)$resp['dislikes'];
        $this->assertTrue($dislikes == 1, 'Incorrect number of dislikes');
    }
    /*
     * @depends testUpload
     */

    public function testAddToHistory() {
        $data = json_encode(array('user_id' => '1', 'video_id' => '1'));
        self::$curl->post('http://localhost/tubeapi/addToHistory', $data);
        $this->assertTrue(self::$curl->response_headers['Status-Line'] == 'HTTP/1.1 200 OK', 'Failed to add video to history.');
    }

    /*
     * @depends testAddToHistory
     */

    public function testHistory() {
        self::$curl->get('http://localhost/tubeapi/history', array(
            'user_id' => '1'
        ));
        $this->assertTrue(self::$curl->response_headers['Status-Line'] == 'HTTP/1.1 200 OK', 'Failed /tubeapi/history');
    }
    
    /*
     * @depends testUpload
     */

    public function testAddToFavorites() {
        $data = json_encode(array('user_id' => '1', 'video_id' => '1'));
        self::$curl->post('http://localhost/tubeapi/addToFavorites', $data);
        $this->assertTrue(self::$curl->response_headers['Status-Line'] == 'HTTP/1.1 200 OK', 'Failed to add video to favorites.');
    }

    /*
     * @depends testAddToFavorites
     */

    public function testFavorites() {
        self::$curl->get('http://localhost/tubeapi/favorites', array(
            'user_id' => '1'
        ));
        $this->assertTrue(self::$curl->response_headers['Status-Line'] == 'HTTP/1.1 200 OK', 'Failed /tubeapi/favorites');
    }

}
    
?>
