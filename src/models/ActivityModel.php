<?php

namespace models;

use models\mapper\ArrayOf;

use models\mapper\MongoMapper;

use models\mapper\IdReference;

use models\mapper\Id;
use models\mapper\MapOf;

class ActivityModelMongoMapper extends \models\mapper\MongoMapper
{
	/**
	 * @var ActivityModelMongoMapper[]
	 */
	private static $_pool = array();
	
	/**
	 * @param string $databaseName
	 * @return ActivityModelMongoMapper
	 */
	public static function connect($databaseName) {
		if (!isset(static::$_pool[$databaseName])) {
			static::$_pool[$databaseName] = new ActivityModelMongoMapper($databaseName, 'activity');
		}
		return static::$_pool[$databaseName];
	}
}

class ActivityModel extends \models\mapper\MapperModel
{
	// constants describing Actions
	const ADD_COMMENT = 'add_comment';
	const UPDATE_COMMENT = 'update_comment';
	const ADD_ANSWER = 'add_answer';
	const UPDATE_ANSWER = 'update_answer';
	const ADD_TEXT = 'add_text';
	const ADD_QUESTION = 'add_question';
	const CHANGE_STATE_OF_QUESTION = 'change_state_of_question';
	const INCREASE_SCORE = 'increase_score';
	const DECREASE_SCORE = 'decrease_score';
	const ADD_USER_TO_PROJECT = 'add_user_to_project';
	const UNKNOWN = 'unknown';
	
	// content types for use with the addContent method
	const PROJECT = 'project';
	const TEXT = 'text';
	const QUESTION = 'question';
	const ANSWER = 'answer';
	const COMMENT = 'comment';
	const USER = 'user';
	const USER2 = 'user2';
	
	/**
	 * 
	 * @param ProjectModel $projectModel
	 * @param string $id
	 */
	public function __construct($projectModel, $id = '') {
		$this->id = new Id();
		$this->projectRef = new IdReference($projectModel->id->asString());
		$this->textRef = new IdReference();
		$this->questionRef = new IdReference();
		$this->userRef = new IdReference();
		$this->userRef2 = new IdReference();
		$this->action = $this::UNKNOWN;
		$this->date = new \DateTime(); // set the timestamp to now
		$this->actionContent = new MapOf(); // strings
		$this->addContent($this::PROJECT, $projectModel->projectname);
		$databaseName = $projectModel->databaseName();
		parent::__construct(ActivityModelMongoMapper::connect($databaseName), $id);
	}	
	
	/**
	 * 
	 * @param string $type - this is one of 
	 * @param string $content
	 */
	public function addContent($type, $content) {
		$this->actionContent->data[$type] = $content;
	}
	
	// TODO add a userFilter ArrayOf type that we can use to query Mongo for activities that only apply to specific users
	
	/**
	 * @var Id
	 */
	public $id;
	
	/**
	 * 
	 * @var IdReference
	 */
	public $projectRef;
	
	/**
	 * 
	 * @var IdReference
	 */
	public $textRef;
	
	/**
	 * 
	 * @var IdReference
	 */
	public $questionRef;
	
	/**
	 * 
	 * @var IdReference
	 */
	public $userRef;
	
	/**
	 * 
	 * @var IdReference
	 */
	public $userRef2;
	
	/**
	 * 
	 * @var string 
	 */
	// TODO add broadcast_message as an action on a GlobalActivityModel class cjh 2013-08
	public $action;
	
	/**
	 * 
	 * @var ArrayOf
	 * ArrayOf<string>
	 */
	public $actionContent;
	
	
	/**
	 * @var \DateTime
	 */
	public $date;
	
}

class ActivityListModel extends \models\mapper\MapperListModel
{

	public function __construct($projectModel)
	{
		$this->entries = new MapOf(function($data) use ($projectModel) { return new ActivityModel($projectModel); });
		parent::__construct(
			ActivityModelMongoMapper::connect($projectModel->databaseName()),
			array('action' => array('$regex' => ''))
		);
	}
	
}



?>