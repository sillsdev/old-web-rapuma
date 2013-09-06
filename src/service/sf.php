<?php

use models\dto\ProjectSettingsDto;

use models\ProjectModel;

use models\dto\ActivityListDto;

use models\commands\ActivityCommands;

use models\AnswerModel;

use models\QuestionModel;

use libraries\palaso\CodeGuard;

use libraries\palaso\JsonRpcServer;
use models\commands\ProjectCommands;
use models\commands\QuestionCommands;
use models\commands\TextCommands;
use models\commands\UserCommands;
use models\mapper\Id;
use models\mapper\JsonEncoder;
use models\mapper\JsonDecoder;
use models\mapper\MongoStore;

require_once(APPPATH . 'config/sf_config.php');

require_once(APPPATH . 'models/ProjectModel.php');
require_once(APPPATH . 'models/QuestionModel.php');
require_once(APPPATH . 'models/TextModel.php');
require_once(APPPATH . 'models/UserModel.php');

class Sf
{
	/**
	 * @var string
	 */
	private $_userId;
	
	public function __construct($controller) {
		$this->_userId = (string)$controller->session->userdata('user_id');

		// TODO put in the LanguageForge style error handler for logging / jsonrpc return formatting etc. CP 2013-07
 		ini_set('display_errors', 0);
	}
	
	//---------------------------------------------------------------
	// USER API
	//---------------------------------------------------------------
	
	/**
	 * Create/Update a User
	 * @param UserModel $json
	 * @return string Id of written object
	 */
	public function user_update($params) {
		$user = new \models\UserModel();
		JsonDecoder::decode($user, $params);
		$result = $user->write();
		return $result;
	}

	/**
	 * Read a user from the given $id
	 * @param string $id
	 */
	public function user_read($id) {
		$user = new \models\UserModel($id);
		return JsonEncoder::encode($user);
	}
	
	/**
	 * Delete users
	 * @param array<string> $userIds
	 * @return int Count of deleted users
	 */
 	public function user_delete($userIds) {
 		return UserCommands::deleteUsers($userIds);
 	}

	// TODO Pretty sure this is going to want some paging params
	public function user_list() {
		$list = new \models\UserListModel();
		$list->read();
		return $list;
	}
	
	public function user_typeahead($term) {
		$list = new \models\UserTypeaheadModel($term);
		$list->read();
		return $list;
	}
	
	public function change_password($userId, $newPassword) {
		if (!is_string($userId) && !is_string($newPassword)) {
			throw new \Exception("Invalid args\n" . var_export($userId, true) . "\n" . var_export($newPassword, true));
		}
		$user = new \models\PasswordModel($userId);
		$user->changePassword($newPassword);
		$user->write();
	}
	
	
	//---------------------------------------------------------------
	// PROJECT API
	//---------------------------------------------------------------
	
	/**
	 * Create/Update a Project
	 * @param ProjectModel $json
	 * @return string Id of written object
	 */
	public function project_update($object) {
		$project = new \models\ProjectModel();
		$id = $object['id'];
		$isNewProject = ($id == '');
		$oldDBName = '';
		if (!$isNewProject) {
			$project->read($id);
			// This is getting complex; it probably belongs in ProjectCommands. TODO: Rewrite it to put it there. RM 2013-08
			$oldDBName = $project->databaseName();
		}
		JsonDecoder::decode($project, $object);
		$newDBName = $project->databaseName();
		if (($oldDBName != '') && ($oldDBName != $newDBName)) {
			if (MongoStore::hasDB($newDBName)) {
				throw new \Exception("New project name " . $object->projectname . " already exists. Not renaming.");
			}
			MongoStore::renameDB($oldDBName, $newDBName);
		}
		$result = $project->write();
		if ($isNewProject) {
			//ActivityCommands::addProject($project); // TODO: Determine if any other params are needed. RM 2013-08
		}
		return $result;
	}

	/**
	 * Read a project from the given $id
	 * @param string $id
	 */
	public function project_read($id) {
		$project = new \models\ProjectModel($id);
		return JsonEncoder::encode($project);
	}
	
	/**
	 * Delete projects
	 * @param array<string> $projectIds
	 * @return int Count of deleted projects
	 */
 	public function project_delete($projectIds) {
 		return ProjectCommands::deleteProjects($projectIds);
 	}

	// TODO Pretty sure this is going to want some paging params
	public function project_list() {
		$list = new \models\ProjectListModel();
		$list->read();
		return $list;
	}
	
	public function project_list_dto() {
		// Eventually this will need to get the current user id and do:
		//return \models\dto\ProjectListDto::encode($userId);
		return \models\dto\ProjectListDto::encode();
	}
	
	public function project_readUser($projectId, $userId) {
		throw new \Exception("project_readUser NYI");
	}
	
	public function project_updateUser($projectId, $object) {
		$projectModel = new \models\ProjectModel($projectId);
		$command = new \models\commands\ProjectUserCommands($projectModel);
		return $command->updateUser($object);
	}
	
	public function project_deleteUsers($projectId, $userIds) {
		// This removes the user from the project.
		$projectModel = new \models\ProjectModel($projectId);
		$command = new \models\commands\ProjectUserCommands($projectModel);
		$command->removeUsers($userIds);
	}
	
	public function project_listUsers($projectId) {
		$result = ProjectSettingsDto::encode($projectId, $this->_userId);
		return $result;
	}
	
	//---------------------------------------------------------------
	// TEXT API
	//---------------------------------------------------------------
	
	public function text_update($projectId, $object) {
		$projectModel = new \models\ProjectModel($projectId);
		$textModel = new \models\TextModel($projectModel);
		$isNewText = ($object['id'] == '');
		if (!$isNewText) {
			$textModel->read($object['id']);
		}
		JsonDecoder::decode($textModel, $object);
		$textId = $textModel->write();
		if ($isNewText) {
			ActivityCommands::addText($projectModel, $textId, $textModel);
		}
		return $textId;
	}
	
	public function text_read($projectId, $textId) {
		$projectModel = new \models\ProjectModel($projectId);
		$textModel = new \models\TextModel($projectModel, $textId);
		return JsonEncoder::encode($textModel);
	}
	
	public function text_delete($projectId, $textIds) {
		return TextCommands::deleteTexts($projectId, $textIds);
	}
	
	public function text_list($projectId) {
		$projectModel = new \models\ProjectModel($projectId);
		$textListModel = new \models\TextListModel($projectModel);
		$textListModel->read();
		return $textListModel;
	}
	
	public function text_list_dto($projectId) {
		return \models\dto\TextListDto::encode($projectId, $this->_userId);
	}

	public function text_settings_dto($projectId, $textId) {
		return \models\dto\TextSettingsDto::encode($projectId, $textId, $this->_userId);
	}
	
	//---------------------------------------------------------------
	// Question / Answer / Comment API
	//---------------------------------------------------------------
	
	public function question_update($projectId, $object) {
		$projectModel = new \models\ProjectModel($projectId);
		$questionModel = new \models\QuestionModel($projectModel);
		$isNewQuestion = ($object['id'] == '');
		if (!$isNewQuestion) {
			$questionModel->read($object['id']);
		}
		JsonDecoder::decode($questionModel, $object);
		$questionId = $questionModel->write();
		if ($isNewQuestion) {
			ActivityCommands::addQuestion($projectModel, $questionId, $questionModel);
		}
		return $questionId;
	}
	
	public function question_read($projectId, $questionId) {
		$projectModel = new \models\ProjectModel($projectId);
		$questionModel = new \models\QuestionModel($projectModel, $questionId);
		return JsonEncoder::encode($questionModel);
	}
	
	public function question_delete($projectId, $questionIds) {
		return QuestionCommands::deleteQuestions($projectId, $questionIds);
	}
	
	public function question_list($projectId, $textId) {
		$projectModel = new \models\ProjectModel($projectId);
		$questionListModel = new \models\QuestionListModel($projectModel, $textId);
		$questionListModel->read();
		return $questionListModel;
	}
	
	public function question_update_answer($projectId, $questionId, $answer) {
		return QuestionCommands::updateAnswer($projectId, $questionId, $answer, $this->_userId);
	}
	
	public function question_update_answer_score($projectId, $questionId, $answerId, $score) {
		$projectModel = new \models\ProjectModel($projectId);
		$questionModel = new QuestionModel($projectModel, $questionId);
		$answerModel = $questionModel->readAnswer($answerId);
		$lastScore = $answerModel->score;
		$currentScore = intval($score);
		$answerModel->score = $currentScore;
		$questionModel->writeAnswer($answerModel);
		if ($currentScore > $lastScore) {
			ActivityCommands::updateScore($projectModel, $questionId, $answerId, $this->_userId, 'increase');
		} else {
			ActivityCommands::updateScore($projectModel, $questionId, $answerId, $this->_userId, 'decrease');
		}
	}
	
	public function question_remove_answer($projectId, $questionId, $answerId) {
		$projectModel = new \models\ProjectModel($projectId);
		return QuestionModel::removeAnswer($projectModel->databaseName(), $questionId, $answerId);
	}
	
	public function question_update_comment($projectId, $questionId, $answerId, $comment) {
		return QuestionCommands::updateComment($projectId, $questionId, $answerId, $comment, $this->_userId);
	}
	
	public function question_remove_comment($projectId, $questionId, $answerId, $commentId) {
		$projectModel = new \models\ProjectModel($projectId);
		return QuestionModel::removeComment($projectModel->databaseName(), $questionId, $answerId, $commentId);
	}
	
	public function question_comment_dto($projectId, $questionId) {
		return \models\dto\QuestionCommentDto::encode($projectId, $questionId, $this->_userId);
	}
	
	public function question_list_dto($projectId, $textId) {
		return \models\dto\QuestionListDto::encode($projectId, $textId, $this->_userId);
	}
	
	// ---------------- Activity Feed -----------------
	public function activity_list_dto() {
		return \models\dto\ActivityListDto::getActivityForUser($this->_userId);
	}
	
}

?>
