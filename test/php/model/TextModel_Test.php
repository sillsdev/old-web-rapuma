<?php

use models\dto\UsxHelper;

use models\TextListModel;

use models\mapper\MongoStore;
use models\ProjectModel;
use models\TextModel;

require_once(dirname(__FILE__) . '/../TestConfig.php');
require_once(SimpleTestPath . 'autorun.php');

require_once(TestPath . 'common/MongoTestEnvironment.php');
require_once(TestPath . 'common/MockProjectModel.php');

require_once(SourcePath . "models/ProjectModel.php");
require_once(SourcePath . "models/TextModel.php");

class TestTextModel extends UnitTestCase {

	private $_someTextId;

	function __construct() {
		$e = new MongoTestEnvironment();
		$e->clean();
	}

	function testCRUD_Works() {
		$e = new MongoTestEnvironment();
		$projectModel = new MockProjectModel();
		
		// List
		$list = new TextListModel($projectModel);
		$list->read();
		$this->assertEqual(0, $list->count);
		
		// Create
		$text = new TextModel($projectModel);
		$text->title = "Some Text";
		$usx = MongoTestEnvironment::usxSample();
		$text->content = $usx;
		$id = $text->write();
		$this->assertNotNull($id);
		$this->assertIsA($id, 'string');
		$this->assertEqual($id, $text->id->asString());
		
		// Read back
		$otherText = new TextModel($projectModel, $id);
		$this->assertEqual($id, $otherText->id->asString());
		$this->assertEqual('Some Text', $otherText->title);
		$this->assertEqual($usx, $otherText->content);
		
		// Update
		$otherText->title = 'Other Text';
		$otherText->write();
		
		// Read back
		$otherText = new TextModel($projectModel, $id);
		$this->assertEqual('Other Text', $otherText->title);
		
		// List
		$list->read();
		$this->assertEqual(1, $list->count);
		
		// Delete
		TextModel::remove($projectModel->databaseName(), $id);
		
		// List
		$list->read();
		$this->assertEqual(0, $list->count);
		
	}
	
	function testUpdateThenRemove_NewProject_CreatesThenRemovesProjectDatabase() {
		$e = new MongoTestEnvironment();
		$e->clean();
		
		$projectModel = $e->createProject(RAPUMA_TEST_PROJECT);
		$databaseName = $projectModel->databaseName();
		
		$this->assertFalse(MongoStore::hasDB($databaseName));
					
		$text = new TextModel($projectModel);
		$text->title = 'Some Title';
		$text->write();
		
		$this->assertTrue(MongoStore::hasDB($databaseName));
		
		$projectModel->remove();
		
		$this->assertFalse(MongoStore::hasDB($databaseName));
	}

}

?>
