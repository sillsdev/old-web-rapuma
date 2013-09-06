<?php

namespace models;

use models\mapper\IdReference;

use models\mapper\Id;

class CommentModel
{

	public function __construct() {
		$this->id = new Id();
		$this->dateCreated = new \DateTime();
		$this->dateEdited = new \DateTime();
		$this->userRef = new IdReference();
	}

	/**
	 * @var Id
	 */
	public $id;
	
	/**
	 * @var string
	 */
	public $content;

	/**
	 * @var \DateTime
	 */
	public $dateCreated;
	
	/**
	 * @var \DateTime
	 */
	public $dateEdited;
	
	/**
	 * @var IdReference
	 */
	public $userRef;
			
}

?>