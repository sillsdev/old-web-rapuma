<?php
require_once(dirname(__FILE__) . '/../TestConfig.php');
require_once(SimpleTestPath . 'autorun.php');

require_once(TestLibPath . 'jsonRPCClient.php');
require_once(TestPath . 'common/MongoTestEnvironment.php');

class UserAPITestEnvironment
{
	/**
	 * @var jsonRPCClient
	 */
	private $_api;
	
	/**
	 * @var array
	 */
	private $_idAdded = array();
	
	function __construct() {
		$this->_api = new jsonRPCClient("http://scriptureforge-publishing.local/api/sf", false);
		$e = new MongoTestEnvironment();
		$e->clean();
	}
	
	/**
	 * @param string $name
	 * @param string $username
	 * @param string $email
	 */
	function addUser($name = 'Some User', $username = 'someuser', $email = 'someuser@example.com') {
		$param = array(
			'id' => '',
			'name' => $name,
			'username' => $username,
			'email' => $email
		);
		$id = $this->_api->user_update($param);
		$this->_idAdded[] = $id;
		return $id;
	}
	
	function dispose() {
		$this->_api->user_delete($this->_idAdded);
	}
}

class TestUserAPI extends UnitTestCase {

	function __construct() {
	}
	
	function testUserCRUD_CRUDOK() {
		$api = new jsonRPCClient("http://scriptureforge-publishing.local/api/sf", false);
		
		// Create
		$param = array(
			'id' => '',
			'username' =>'SomeUser',
			'email' => 'user@example.com'
		);
		$id = $api->user_update($param);
		$this->assertNotNull($id);
		$this->assertEqual(24, strlen($id));
		
		// Read
		$result = $api->user_read($id);
		$this->assertNotNull($result['id']);
		$this->assertEqual('SomeUser', $result['username']);
		$this->assertEqual('user@example.com', $result['email']);
		
		// Update
		$result['email'] = 'other@example.com';
		$id = $api->user_update($result);
		$this->assertNotNull($id);
		$this->assertEqual($result['id'], $id);
		
		// Delete
 		$result = $api->user_delete(array($id));
 		$this->assertTrue($result);
	}
	
	function testUserList_Ok() {
		$api = new jsonRPCClient("http://scriptureforge-publishing.local/api/sf", false);
		$result = $api->user_list();
		
		$this->assertTrue($result['count'] > 0);
	}
	
	function testUserTypeahead_Ok() {
		$e = new UserAPITestEnvironment();
		$e->addUser('Some User');
		
		$api = new jsonRPCClient("http://scriptureforge-publishing.local/api/sf", false);
		$result = $api->user_typeahead('ome');
		
		$this->assertTrue($result['count'] > 0);
		
		$e->dispose();
	}
	
	function testChangePassword_Ok() {
		$e = new UserAPITestEnvironment();
		$userId = $e->addUser('Some User');
		
		$api = new jsonRPCClient("http://scriptureforge-publishing.local/api/sf", false);
		$result = $api->change_password($userId, '12345');
		$e->dispose();
		
	}

}

?>