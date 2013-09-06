<?php


use models\dto\QuestionListDto;

use models\TextModel;
use models\QuestionModel;
use models\AnswerModel;
use models\CommentModel;

require_once(dirname(__FILE__) . '/../TestConfig.php');
require_once(SimpleTestPath . 'autorun.php');
require_once(TestPath . 'common/MongoTestEnvironment.php');

class TestQuestionListDto extends UnitTestCase {

	function __construct()
	{
		$e = new MongoTestEnvironment();
		$e->clean();
	}

	function testEncode_QuestionWithAnswers_DtoReturnsExpectedData() {
		$e = new MongoTestEnvironment();

		$project = $e->createProject(RAPUMA_TEST_PROJECT);
		$projectId = $project->id->asString();

		$text = new TextModel($project);
		$text->title = "Chapter 3";
		$text->content = "I opened my eyes upon a strange and weird landscape. I knew that I was on Mars; …";
		$textId = $text->write();

		// Answers are tied to specific users, so let's create some sample users
		$user1Id = $e->createUser("jcarter", "John Carter", "johncarter@example.com");
		$user2Id = $e->createUser("dthoris", "Dejah Thoris", "princess@example.com");

		// Two questions, with different numbers of answers
		$question1 = new QuestionModel($project);
		$question1->title = "Who is speaking?";
		$question1->description = "Who is telling the story in this text?";
		$question1->textRef->id = $textId;
		$question1Id = $question1->write();

		$question2 = new QuestionModel($project);
		$question2->title = "Where is the storyteller?";
		$question2->description = "The person telling this story has just arrived somewhere. Where is he?";
		$question2->textRef->id = $textId;
		$question2Id = $question2->write();

		// One answer for question 1...
		$answer1 = new AnswerModel();
		$answer1->content = "Me, John Carter.";
		$answer1->score = 10;
		$answer1->userRef->id = $user1Id;
		$answer1->textHightlight = "I knew that I was on Mars";
		$answer1Id = $question1->writeAnswer($answer1);

		// ... and two answers for question 2
		$answer2 = new AnswerModel();
		$answer2->content = "On Mars.";
		$answer2->score = 1;
		$answer2->userRef->id = $user1Id;
		$answer2->textHightlight = "I knew that I was on Mars";
		$answer2Id = $question2->writeAnswer($answer2);

		$answer3 = new AnswerModel();
		$answer3->content = "On the planet we call Barsoom, which you inhabitants of Earth normally call Mars.";
		$answer3->score = 7;
		$answer3->userRef->id = $user2Id;
		$answer3->textHightlight = "I knew that I was on Mars";
		$answer3Id = $question2->writeAnswer($answer3);

		// Comments should NOT show up in the answer count; let's test this.
		$comment1 = new CommentModel();
		$comment1->content = "By the way, our name for Earth is Jasoom.";
		$comment1->userRef->id = $user2Id;
		$comment1Id = QuestionModel::writeComment($project->databaseName(), $question2Id, $answer3Id, $comment1);

		$dto = QuestionListDto::encode($projectId, $textId, $user1Id);

		// Now check that it all looks right
		$this->assertEqual($dto['count'], 2);
		$this->assertIsa($dto['entries'], 'array');
		$this->assertEqual($dto['entries'][0]['id'], $question1Id);
		$this->assertEqual($dto['entries'][1]['id'], $question2Id);
		$this->assertEqual($dto['entries'][0]['title'], "Who is speaking?");
		$this->assertEqual($dto['entries'][1]['title'], "Where is the storyteller?");
		$this->assertEqual($dto['entries'][0]['answerCount'], 1);
		$this->assertEqual($dto['entries'][1]['answerCount'], 2);
		// Specifically check if comments got included in answer count
		$this->assertNotEqual($dto['entries'][1]['answerCount'], 3, "Comments should not be included in answer count.");

	}

}
/*
class TestProjectListDto extends UnitTestCase {

	function __construct()
	{
		$e = new MongoTestEnvironment();
		$e->clean();
	}

	function testEncode_ProjectWithTexts_DtoReturnsExpectedData() {
		$e = new MongoTestEnvironment();

		$project = $e->createProject(RAPUMA_TEST_PROJECT);

		$text1 = new TextModel($project);
		$text1->title = "Chapter 3";
		$text1->content = "I opened my eyes upon a strange and weird landscape. I knew that I was on Mars; …";
		$text1Id = $text1->write();

		$text2 = new TextModel($project);
		$text2->title = "Chapter 4";
		$text2->content = "We had gone perhaps ten miles when the ground began to rise very rapidly. …";
		$text2Id = $text2->write();

		$dto = ProjectListDto::encode();

		$this->assertEqual($dto['count'], 1);
		$this->assertIsA($dto['entries'], 'array');
		$this->assertEqual($dto['entries'][0]['id'], $project->id);
		$this->assertEqual($dto['entries'][0]['projectname'], RAPUMA_TEST_PROJECT);
		$this->assertEqual($dto['entries'][0]['textCount'], 2);

	}
}
*/

?>
