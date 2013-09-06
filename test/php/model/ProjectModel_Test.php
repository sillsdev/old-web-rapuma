<?php
use models\rights\Operation;
use models\rights\Domain;
use models\rights\Roles;
use models\mapper\Id;
use models\mapper\MongoStore;
use models\UserModel;
use models\ProjectModel;

require_once(dirname(__FILE__) . '/../TestConfig.php');
require_once(SimpleTestPath . 'autorun.php');

require_once(TestPath . 'common/MongoTestEnvironment.php');

require_once(SourcePath . "models/UserModel.php");
require_once(SourcePath . "models/ProjectModel.php");

class TestProjectModel extends UnitTestCase {

	private $_someProjectId;
	
	function __construct() {
		$e = new MongoTestEnvironment();
		$e->clean();
	}
	
	function testWrite_ReadBackSame() {
		$model = new ProjectModel();
		$model->language = "SomeLanguage";
		$model->projectname = "SomeProject";
		//$model->users->refs = array('1234');
		$id = $model->write();
		$this->assertNotNull($id);
		$this->assertIsA($id, 'string');
		$this->assertEqual($id, $model->id->asString());
		$otherModel = new ProjectModel($id);
		$this->assertEqual($id, $otherModel->id->asString());
		$this->assertEqual('SomeLanguage', $otherModel->language);
		$this->assertEqual('SomeProject', $otherModel->projectname);
		//$this->assertEqual(array('1234'), $otherModel->users->refs);
		
		$this->_someProjectId = $id;
	}

	function testProjectList_HasCountAndEntries()
	{
		$model = new models\ProjectListModel();
		$model->read();
		
		$this->assertNotEqual(0, $model->count);
		$this->assertNotNull($model->entries);
	}
	
	function testProjectAddUser_ExistingProject_ReadBackAdded() {
		$e = new MongoTestEnvironment();
		
		// setup user and projects
		$userId = $e->createUser('jsmith', 'joe smith', 'joe@email.com');
		$userModel = new UserModel($userId);
		$projectModel = $e->createProject('new project');
		$projectId = $projectModel->id->asString();
		
		// create the reference
		$projectModel->addUser($userId, Roles::USER);
		$userModel->addProject($projectId);
		$projectModel->write();
		$userModel->write();
		
		$this->assertTrue(array_key_exists($userId, $projectModel->users->data), "'$userId' not found in project.");
		$otherProject = new ProjectModel($projectId);
		$this->assertTrue(array_key_exists($userId, $otherProject->users->data), "'$userId' not found in other project.");
	}
	
	
	// TODO move Project <--> User operations to a separate ProjectUserCommands tests
	
	function testProjectRemoveUser_ExistingProject_Removed() {
		$e = new MongoTestEnvironment();
		
		// setup user and projects
		$userId = $e->createUser('jsmith', 'joe smith', 'joe@email.com');
		$userModel = new UserModel($userId);
		$projectModel = $e->createProject('new project');
		$projectId = $projectModel->id->asString();

		// create the reference
		$projectModel->addUser($userId, Roles::USER);
		$userModel->addProject($projectId);
		$projectModel->write();
		$userModel->write();
		
		// assert the reference is there		
		$this->assertTrue(array_key_exists($userId, $projectModel->users->data), "'$userId' not found in project.");
		$otherProject = new ProjectModel($projectId);
		$this->assertTrue(array_key_exists($userId, $otherProject->users->data), "'$userId' not found in other project.");
		
		// remove the reference
		$projectModel->removeUser($userId);
		$userModel->removeProject($projectId);
		$projectModel->write();
		$userModel->write();
		
		// testing
		$this->assertFalse(array_key_exists($userId, $projectModel->users->data), "'$userId' not found in project.");
		$otherProject = new ProjectModel($this->_someProjectId);
		$this->assertFalse(array_key_exists($userId, $otherProject->users->data), "'$userId' not found in other project.");
		$project = new ProjectModel($this->_someProjectId);
	}
	
	function testProjectAddUser_TwiceToSameProject_AddedOnce() {
		// note I am not testing the reciprocal reference here - 2013-07-09 CJH
		$e = new MongoTestEnvironment();
		
		// setup user and projects
		$userId = $e->createUser('jsmith', 'joe smith', 'joe@email.com');
		$userModel = new UserModel($userId);
		$projectModel = $e->createProject('new project');
		$projectId = $projectModel->id;
		
		$projectModel->addUser($userId, Roles::USER);
		$this->assertEqual(1, count($projectModel->users));
		$projectModel->addUser($userId, Roles::USER);
		$this->assertEqual(1, count($projectModel->users));
	}
	
	function testProjectListUsers_TwoUsers_ListHasDetails() {
		$e = new MongoTestEnvironment();
		$userId1 = $e->createUser('user1', 'User One', 'user1@example.com');
		$um1 = new UserModel($userId1);
		$userId2 = $e->createUser('user2', 'User Two', 'user2@example.com');
		$um2 = new UserModel($userId2);
		$project = $e->createProject(RAPUMA_TEST_PROJECT);
		$projectId = $project->id->asString();
		
		// Check the list users is empty
		$result = $project->listUsers();
		$this->assertEqual(0, $result->count);
		$this->assertEqual(array(), $result->entries);
				
		// Add our two users
		$project->addUser($userId1, Roles::USER);
		$um1->addProject($projectId);
		$um1->write();
		
		$project->addUser($userId2, Roles::USER);
		$um2->addProject($projectId);
		$um2->write();
		$project->write();
		
		$otherProject = new ProjectModel($projectId);
		$result = $otherProject->listUsers();
		$this->assertEqual(2, $result->count);
		$this->assertEqual(
			array(
				array(
		          'email' => 'user1@example.com',
		          'name' => 'User One',
		          'username' => 'user1',
		          'id' => $userId1,
				  'role' => Roles::USER
				), 
				array(
		          'email' => 'user2@example.com',
		          'name' => 'User Two',
		          'username' => 'user2',
		          'id' => $userId2,
				  'role' => Roles::USER
				)
			), $result->entries
		);
		
	}
	
	function testRemove_RemovesProject() {
		$e = new MongoTestEnvironment();
		$project = new ProjectModel($this->_someProjectId);
		
		$this->assertTrue($project->exists($this->_someProjectId));
		
		$project->remove();
		
		$this->assertFalse($project->exists($this->_someProjectId));
	}
	
	function testDatabaseName_Ok() {
		$project = new ProjectModel();
		$project->projectname = 'Some Project';
		$result = $project->databaseName();
		$this->assertEqual('sf_some_project', $result);
	}
	
	function testHasRight_Ok() {
		$userId = MongoTestEnvironment::mockId();
		$project = new ProjectModel();
		$project->addUser($userId, Roles::PROJECT_ADMIN);
		$result = $project->hasRight($userId, Domain::QUESTIONS + Operation::CREATE);
		$this->assertTrue($result);
	}
	
	function testGetRightsArray_Ok() {
		$userId = MongoTestEnvironment::mockId();
		$project = new ProjectModel();
		$project->addUser($userId, Roles::PROJECT_ADMIN);
		$result = $project->getRightsArray($userId);
		$this->assertIsA($result, 'array');
		$this->assertTrue(in_array(Domain::QUESTIONS + Operation::CREATE, $result));
	}
		
}

?>