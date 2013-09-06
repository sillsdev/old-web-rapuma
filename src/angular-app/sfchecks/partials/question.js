'use strict';

angular.module(
		'sfchecks.question',
		[ 'sf.services', 'palaso.ui.listview', 'palaso.ui.typeahead', 'palaso.ui.jqte', 'ui.bootstrap' ]
	)
	.controller('QuestionCtrl', ['$scope', '$routeParams', 'questionService', 'sessionService', 'breadcrumbService',
	                             function($scope, $routeParams, questionService, ss, bcs) {
		$scope.jqteOptions = {
			'placeholder': 'Say what you think...',
			'u': false,
			'indent': false,
			'outdent': false,
			'left': false,
			'center': false,
			'right': false,
			'rule': false,
			'source': false,
			'link': false,
			'unlink': false,
			'fsize': false,
			'formats': [
				['p', 'Normal'],
				['h4', 'Large']
			]
		};

		var projectId = $routeParams.projectId;
		var questionId = $routeParams.questionId;
		questionService.read(projectId, questionId, function(result) {
			console.log('questionService.read(', projectId, questionId, ')');
			if (result.ok) {
				$scope.text = result.data.text;
				$scope.question = result.data.question;
				$scope.project = result.data.project;
				bcs.updateMap('project', $scope.project.id, $scope.project.projectname);
				bcs.updateMap('text', $scope.text.id, $scope.text.title);
				bcs.updateMap('question', $scope.question.id, $scope.question.title);
				// Keep track of answer count so we can show or hide "There are no answers" as appropriate
				$scope.question.answerCount = Object.keys($scope.question.answers).length;
				$scope.rights = result.data.rights;
			} else {
				// error condition
			}
		});
		
		$scope.rightsEditOwn = function(userId) {
			var right = (userId == ss.currentUserId()) && ss.hasRight($scope.rights, ss.domain.ANSWERS, ss.operation.EDIT_OWN);
			return right;
		};

		$scope.rightsDeleteOwn = function(userId) {
			var right = (userId == ss.currentUserId()) && ss.hasRight($scope.rights, ss.domain.ANSWERS, ss.operation.DELETE_OWN);
			return right;
		};

		$scope.editQuestionCollapsed = true;
		$scope.showQuestionEditor = function() {
			$scope.editQuestionCollapsed = false;
		};
		$scope.hideQuestionEditor = function() {
			$scope.editQuestionCollapsed = true;
		};
		$scope.toggleQuestionEditor = function() {
			$scope.editQuestionCollapsed = !$scope.editQuestionCollapsed;
		};
		$scope.$watch('editQuestionCollapsed', function(newval) {
			if (newval) { return; }
			// Question editor not collapsed? Then set up initial values
			$scope.editedQuestion = {
				id: $scope.question.id,
				title: $scope.question.title,
				description: $scope.question.description,
				// Do we need to copy the other values? Let's check:
				//dateCreated: $scope.question.dateCreated,
				//textRef: $scope.question.textRef,
				//answers: $scope.question.answers,
				//answerCount: $scope.question.answerCount,
			}
		});
		$scope.updateQuestion = function(newQuestion) {
			questionService.update(projectId, newQuestion, function(result) {
				if (result.ok) {
					questionService.read(projectId, newQuestion.id, function(result) {
						if (result.ok) {
							$scope.question = result.data.question;
							// Recalculate answer count since the DB doesn't store it
							$scope.question.answerCount = Object.keys($scope.question.answers).length;
						} else {
							// error condition
							console.log('update_question failed to read DB after update');
							console.log(result);
						}
					});
				} else {
					console.log('update_question ERROR');
					console.log(result);
				}
			});
		};

		$scope.openEditors = {
			answerId: null,
			commentId: null,
		};

		$scope.showAnswerEditor = function(answerId) {
			$scope.openEditors.answerId = answerId;
		};

		$scope.hideAnswerEditor = function() {
			$scope.openEditors.answerId = null;
		};

		$scope.$watch('openEditors.answerId', function(newval, oldval) {
			if (newval === null || newval === undefined) {
				// Skip; we're being called during initialization
				return;
			}

			// Set up the values needed by the new editor
			var answer = $scope.question.answers[newval];
			if (angular.isUndefined(answer)) {
				//console.log('Failed to find', newval, 'in', $scope.question.answers);
				return;
			}
			$scope.editedAnswer = {
				id: newval,
				comments: {},
				content: answer.content,
				//dateEdited: Date.now(), // Commented out for now because the model wasn't happy with a Javascript date. TODO: Figure out what format I should be passing this in. RM 2013-08
				score: answer.score,
				textHighlight: answer.textHighlight,
				userRef: answer.userRef,
			};
			for (var id in answer.comments) {
				var strippedComment = {};
				var comment = answer.comments[id];
				strippedComment.id = comment.id;
				strippedComment.content = comment.content;
				strippedComment.dateCreated = comment.dateCreated;
				strippedComment.dateEdited = comment.dateEdited;
				strippedComment.userRef = comment.userRef.userid;
				$scope.editedAnswer.comments[id] = strippedComment;
			}
		});

		$scope.answerEditorVisible = function(answerId) {
			return (answerId == $scope.openEditors.answerId);
		};

		$scope.showCommentEditor = function(commentId) {
			$scope.openEditors.commentId = commentId;
		};
		$scope.hideCommentEditor = function() {
			$scope.openEditors.commentId = null;
		};
		$scope.$watch('openEditors.commentId', function(newval, oldval) {
			if (newval === null || newval === undefined) {
				// Skip; we're being called during initialization
				return;
			}

			// We're in the question-level scope, and we need to find a
			// specific commentId without knowing which answer it belongs
			// to, because all we have to work with is the new value of
			// the commentId (the old value won't help us).
			var comment;
			search_loop:
			for (var aid in $scope.question.answers) {
				var answer = $scope.question.answers[aid];
				for (var cid in answer.comments) {
					if (cid == newval) {
						comment = answer.comments[cid];
						break search_loop;
					}
				}
			}
			// Set up the values needed by the new editor
			if (angular.isUndefined(comment)) {
				//console.log('Failed to find', newval, 'in', $scope.question.comments);
				return;
			}
			$scope.editedComment = {
				id: newval,
				content: comment.content,
				//dateEdited: Date.now(), // Commented out for now because the model wasn't happy with a Javascript date. TODO: Figure out what format I should be passing this in. RM 2013-08
				userRef: comment.userRef, // Do we really need to copy this over? Or will the PHP model code take care of that for us?
			};
		});

		$scope.commentEditorVisible = function(commentId) {
			return (commentId == $scope.openEditors.commentId);
		};
		
		$scope.newComment = {
			'content': ''
		};
		
		$scope.newAnswer = {
			content: ''
		};
		
		$scope.updateComment = function(answerId, answer, newComment) {
			questionService.update_comment(projectId, questionId, answerId, newComment, function(result) {
				if (result.ok) {
					for (var id in result.data) {
						newComment = result.data[id]; // There should be one, and only one, record in result.data
					}
					$scope.question.answers[answerId].comments[newComment.id] = newComment;
				} else {
					console.log('update_comment ERROR');
					console.log(result);
				}
			});
		};
		
		$scope.submitComment = function(answerId, answer) {
			var newComment = {
				id: '',
				content: $scope.newComment.content,
			};
			$scope.updateComment(answerId, answer, newComment);
			$scope.newComment.content = '';
		}
		
		$scope.editComment = function(answerId, answer, comment) {
			if ($scope.rightsEditOwn(comment.userRef.userid)) {
				$scope.updateComment(answerId, answer, comment);
			}
			$scope.hideCommentEditor();
		}
		
		$scope.commentDelete = function(answer, commentId) {
			console.log('delete ', commentId);
			questionService.remove_comment(projectId, questionId, answer.id, commentId, function(result) {
				if (result.ok) {
					console.log('remove_comment ok');
					// Delete locally
					delete answer.comments[commentId];
				}
			});
		};
		
		$scope.updateAnswer = function(projectId, questionId, answer) {
			questionService.update_answer(projectId, questionId, answer, function(result) {
				if (result.ok) {
					console.log('update_answer ok');
					for (var id in result.data) {
						$scope.question.answers[id] = result.data[id];
					}
					// Recalculate answer count as it might have changed
					$scope.question.answerCount = Object.keys($scope.question.answers).length;
					// TODO error condition (well, that should be handled globally by the service CP 2013-08)
				}
			});
		};

		$scope.submitAnswer = function() {
			var answer = {
				'id':'',
				'content': $scope.newAnswer.content
			};
			$scope.updateAnswer(projectId, questionId, answer);
			$scope.newAnswer.content = '';
		};
		
		$scope.editAnswer = function(answer) {
			if ($scope.rightsEditOwn(answer.userRef.userid)) {
				$scope.updateAnswer(projectId, questionId, answer);
			}
			$scope.hideAnswerEditor();
		};
		
		$scope.answerDelete = function(answerId) {
			console.log('delete ', answerId);
			questionService.remove_answer(projectId, questionId, answerId, function(result) {
				if (result.ok) {
					console.log('remove_answer ok');
					// Delete locally
					delete $scope.question.answers[answerId];
					// Recalculate answer count as it just changed
					$scope.question.answerCount = Object.keys($scope.question.answers).length;
				}
			});
		};
		
	}])
	;
