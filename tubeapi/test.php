<?php

require 'vendor/autoload.php';

use \Curl\Curl;


$filename = "uploads/testfile.mp4";
if (file_exists($filename)) {
    unlink($filename);
}

// reset db
include 'import_db.php';

// terminate execution on assert fail
assert_options(ASSERT_BAIL, true);

$curl = new Curl();

$data = json_encode(array('username' => 'jonah', 'password' => 'hillisabadactor'));

// insert first user
$curl->post('http://localhost/tubeapi/signUp', $data);
assert($curl->response_headers['Status-Line'] == 'HTTP/1.1 200 OK', 'Failed to insert first user');


$data = json_encode(array('username' => 'strutter', 'password' => 'strutting'));
// insert second user
$curl->post('http://localhost/tubeapi/signUp', $data);
assert($curl->response_headers['Status-Line'] == 'HTTP/1.1 200 OK', 'Failed to insert second user');

$curl->get('http://localhost/tubeapi/users');
assert($curl->response_headers['Status-Line'] == 'HTTP/1.1 200 OK', 'Failed /tubeapi/users');

$curl->get('http://localhost/tubeapi/user', array(
    'id' => '1',
));
assert($curl->response_headers['Status-Line'] == 'HTTP/1.1 200 OK', 'Failed /tubeapi/user?id=1');


$data = json_encode(array('username' => 'jonah', 'password' => 'hillisabadactor'));
$curl->post('http://localhost/tubeapi/login', $data);

$resp = (array)$curl->response;
$auth = 'Bearer ' . $resp['token'];
$curl->setHeader('Authorization', $auth);
$file = new CURLFile('testfile.mp4');
$data = array('json' => json_encode(array(
        'user_id' => '1',
        'title' => 'random video',

    )), 
        'upfile' => $file );
$curl->post('http://localhost/tubeapi/insertVideo', $data);

assert($curl->response_headers['Status-Line'] == 'HTTP/1.1 200 OK', 'Failed to insert video.');

$curl->get('http://localhost/tubeapi/videos', array(
    'user_id' => '1'
));
assert($curl->response_headers['Status-Line'] == 'HTTP/1.1 200 OK', 'Failed /tubeapi/videos');

$data = json_encode(array('user_id' => '1','video_id' => '1','text' => 'Justin Biber sucks!'));
$curl->post('http://localhost/tubeapi/insertComment', $data);
assert($curl->response_headers['Status-Line'] == 'HTTP/1.1 200 OK', 'Failed to insert comment.');

$curl->get('http://localhost/tubeapi/comments', array(
    'video_id' => '1'
));
assert($curl->response_headers['Status-Line'] == 'HTTP/1.1 200 OK', 'Failed /tubeapi/comments');

$data = json_encode(array('user_id' => '1', 'video_id' => '1'));
$curl->post('http://localhost/tubeapi/addToHistory', $data);
assert($curl->response_headers['Status-Line'] == 'HTTP/1.1 200 OK', 'Failed to add video to history.');

$curl->get('http://localhost/tubeapi/history', array(
    'user_id' => '1'
));
assert($curl->response_headers['Status-Line'] == 'HTTP/1.1 200 OK', 'Failed /tubeapi/history');

$data = json_encode(array('user_id' => '1', 'video_id' => '1'));
$curl->post('http://localhost/tubeapi/addToFavorites', $data);
assert($curl->response_headers['Status-Line'] == 'HTTP/1.1 200 OK', 'Failed to add video to favorites.');


$curl->get('http://localhost/tubeapi/favorites', array(
    'user_id' => '1'
));
assert($curl->response_headers['Status-Line'] == 'HTTP/1.1 200 OK', 'Failed /tubeapi/favorites');

echo "Tests Passed\r\n";

?>
