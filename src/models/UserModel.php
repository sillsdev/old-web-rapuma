<?php

namespace models;

use models\rights\Roles;

use models\mapper\IdReference;

use models\mapper\MongoMapper;

use models\mapper\Id;
use models\mapper\ReferenceList;

require_once(APPPATH . '/models/ProjectModel.php');

class UserModelMongoMapper extends \models\mapper\MongoMapper
{
	public static function instance() {
		static $instance = null;
		if (null === $instance) {
			$instance = new UserModelMongoMapper(RAPUMA_DATABASE, 'users');
		}
		return $instance;
	}
	
}

class UserModel extends \models\mapper\MapperModel
{
	public function __construct($id = '') {
		$this->id = new Id();
		$this->projects = new ReferenceList();
		parent::__construct(UserModelMongoMapper::instance(), $id);
	}
	
	/**
	 *	Removes a user from the collection
	 *  Project references to this user are also removed
	 */
	public function remove() {
		UserModelMongoMapper::instance()->remove($this->id->asString());
	}

	public function read($id) {
		parent::read($id);
		if (!$this->avatar_ref) {
			$default_avatar = "/images/avatar/anonymoose.png";
			$this->avatar_ref = $default_avatar;
		}
	}
	
	/**
	 *	Adds the user as a member of $projectId
	 *  You do must call write() as both the user model and the project model!!!
	 * @param string $projectId
	 */
	public function addProject($projectId) {
		//$projectModel = new ProjectModel($projectId);
		$this->projects->_addRef($projectId);
		//$projectModel->users->_addRef($this->id);
	}
	
	/**
	 *	Removes the user as a member of $projectId
	 *  You must call write() on both the user model and the project model!!!
	 * @param string $projectId
	 */
	public function removeProject($projectId) {
		//$projectModel = new ProjectModel($projectId);
		$this->projects->_removeRef($projectId);
		//$projectModel->users->_removeRef($this->id);
	}
	
	public function listProjects() {
		$projectList = new ProjectList_UserModel($this->id->asString());
		$projectList->read();
		return $projectList;
	}
	
	/**
	 * @var IdReference
	 */
	public $id;
	
	/**
	 * @var string
	 */
	public $name;
	
	/**
	 * @var string
	 */
	public $username;
	
	/**
	 * @var string
	 */
	public $email;
	
	/**
	 * @var string
	 * @see Roles
	 */
	public $role;
	
	//public $groups;
	
	/**
	 * @var string
	 */
	public $avatar_shape;
	
	/**
	 * @var string
	 */
	public $avatar_color;
	
	public $avatar_ref;

	/**
	 * @var bool
	 */
	public $active;
	
	/**
	 * @var int
	 */
	public $created_on;	
	
	public $last_login; // read only field
	
	/**
	 * @var ReferenceList
	 */
	public $projects;
	
	/**
	 * @var string
	 */
	public $mobile_phone;
	/**
	 * @var string - possible values are "email", "sms" or "both"
	 */
	public $communicate_via;
	/**
	 * @var string
	 */
	public $age;
	/**
	 * @var string
	 */
	public $gender;
	/**
	 * @var string
	 */
	public $city;
	/**
	 * @var string
	 */
	public $preferred_bible_version;
	/**
	 * @var string
	 */
	public $religious_affiliation;
	/**
	 * @var string
	 */
	public $study_group;
	/**
	 * @var string
	 */
	public $feedback_group;
}

class UserListModel extends \models\mapper\MapperListModel
{

	public function __construct()
	{
		parent::__construct(
			UserModelMongoMapper::instance(),
			array('name' => array('$regex' => '')),
			array('username', 'email', 'name', 'avatar_ref', 'role')
		);
	}
	
}

class UserTypeaheadModel extends \models\mapper\MapperListModel
{
	public function __construct($term)
	{
		parent::__construct(
				UserModelMongoMapper::instance(),
				array('name' => array('$regex' => $term, '$options' => '-i')),
				array('username', 'email', 'name', 'avatarRef')
		);
	}	
	
}

class UserList_ProjectModel extends \models\mapper\MapperListModel
{

	public function __construct($projectId)
	{
		parent::__construct(
				UserModelMongoMapper::instance(),
				array('projects' => array('$in' => array(MongoMapper::mongoID($projectId)))),
				array('username', 'email', 'name')
		);
	}

}



?>
