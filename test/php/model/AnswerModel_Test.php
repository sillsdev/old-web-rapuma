<?php

use models\AnswerModel;
use models\CommentModel;
use models\QuestionListModel;

use models\mapper\MongoStore;
use models\ProjectModel;
use models\QuestionModel;

require_once(dirname(__FILE__) . '/../TestConfig.php');
require_once(SimpleTestPath . 'autorun.php');

require_once(TestPath . 'common/MongoTestEnvironment.php');

require_once(SourcePath . "models/ProjectModel.php");
require_once(SourcePath . "models/QuestionModel.php");


class TestAnswerModel extends UnitTestCase {

	function __construct() {
		$e = new MongoTestEnvironment();
		$e->clean();
	}

	function testAnswerCRUD_Works() {
		$e = new MongoTestEnvironment();
		$textRef = MongoTestEnvironment::mockId();
		$projectModel = new MockProjectModel();

		// Create Question
		$question = new QuestionModel($projectModel);
		$question->title = "Some Question";
		$question->textRef->id = $textRef;
		$questionId = $question->write();
		
		// List
		$question->read($questionId);
		$count = count($question->answers->data);
		$this->assertEqual(0, $count);
		
		// Create
		$answer = new AnswerModel();
		$answer->content = 'Some answer';
		$id = $question->writeAnswer($answer);
		$comment = new CommentModel();
		$comment->content = 'Some comment';
		$commentId = QuestionModel::writeComment($projectModel->databaseName(), $questionId, $id, $comment);
		$this->assertNotNull($id);
		$this->assertIsA($id, 'string');
		$this->assertEqual(24, strlen($id));
		$this->assertEqual($id, $answer->id->asString());
		
		// Read back
		$otherQuestion = new QuestionModel($projectModel, $questionId);
		$otherAnswer = $otherQuestion->answers->data[$id];
		$this->assertEqual($id, $otherAnswer->id->asString());
		$this->assertEqual('Some answer', $otherAnswer->content);
		$this->assertEqual(1, count($otherAnswer->comments->data));
// 		var_dump($id);
// 		var_dump($otherAnswer->id->asString());
		
		// Update
		$otherAnswer->content= 'Other answer';
		// Note: Updates to the AnswerModel should not clobber child nodes such as comments.  Hence this test.
		// See https://github.com/sillsdev/sfwebchecks/issues/39
		unset($otherAnswer->comments->data[$commentId]);
		$otherQuestion->read($otherQuestion->id->asString());
		$otherId = $otherQuestion->writeAnswer($otherAnswer);
		$this->assertEqual($id, $otherId);
		
		// Read back
		$otherQuestion = new QuestionModel($projectModel, $questionId);
		$otherAnswer = $otherQuestion->answers->data[$id];
		$this->assertEqual($id, $otherAnswer->id->asString());
		$this->assertEqual('Other answer', $otherAnswer->content);
		$this->assertEqual(1, count($otherAnswer->comments->data));
		
		// List
		$this->assertEqual(1, count($otherQuestion->answers->data));

		// Delete
		QuestionModel::removeAnswer($projectModel->databaseName(), $questionId, $id);
		
		// List
		$otherQuestion->read($questionId);
		$this->assertEqual(0, count($otherQuestion->answers->data));
		
	}

}

?>
