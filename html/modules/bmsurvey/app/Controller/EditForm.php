<?php
/**
 * Created by JetBrains PhpStorm.
 * Copyright (c) : Y.Sakai ( @bluemooninc )
 * Licence : GPL V3
 * Date: 2013/03/17
 * Time: 12:53
 * To change this template use File | Settings | File Templates.
 */
require_once 'bmsurvey.php';
require_once _MY_MODULE_PATH . 'app/View/AbstractView.class.php';
require_once _MY_MODULE_PATH . 'app/View/General.class.php';
require_once _MY_MODULE_PATH . 'app/View/Question.class.php';
require_once _MY_MODULE_PATH . 'app/Model/General.class.php';
require_once _MY_MODULE_PATH . 'app/Model/Question.class.php';
require_once _MY_MODULE_PATH . 'app/Model/Form.class.php';
include_once _MY_MODULE_PATH . 'app/View/HtmlRender.class.php';

class Controller_EditForm extends Controller_Bmsurvey
{
	protected $manageObjects;
	protected $tpl_vars;
	protected $tab;
	protected $contents;
	protected $form_id;
	protected $question_id;

	public function __construct()
	{
		parent::__construct();
		if (!$this->manageOn()) {
			redirect_header(XOOPS_URL . '/modules/bmsurvey/', 2, _MD_BMSURVEY_CAN_WRITE_USER_ONLY);
			exit();
		}
		$this->htmlRender = new bmsurveyHtmlRender();
	}

	private function _setTab_General()
	{
		// Model
		$Model_General = Model_General::forge();
		$Model_General->setPost();
		if ($this->form_id) {
			$formObject = $Model_General->get($this->form_id);
		}
		// View
		$View_General = View_General::forge();
		$this->contents = $View_General->setGeneral($this->htmlRender, $formObject);
	}

	private function _setTab_Question($option)
	{
		// Model
		$Model_Question = Model_Question::forge();
		$Model_Question->setPost();
		if ($this->question_id > 0) {
			$currentObject = $Model_Question->get($this->question_id);
			$this->form_id = $currentObject->getVar('form_id');
		}
		$questionObjects = $Model_Question->getObjectsOnForm($this->form_id);
		if (!$this->question_id && $questionObjects) {
			$this->question_id = $questionObjects[0]->getVar('id');
			$currentObject = $Model_Question->get($this->question_id);
		}
		if ($option && $currentObject) $Model_Question->$option($this->question_id);
		// View
		$View_Question = View_Question::forge();
		$this->contents = $View_Question->setQuestions(
			$this->htmlRender,
			$questionObjects,
			$currentObject,
			$Model_Question->getChoice($this->question_id)
		);
	}

	private function _setTab($postKeys, $option = "")
	{
		if (in_array('general', $postKeys) || in_array('saveGeneral', $postKeys)) {
			$this->_setTab_General();
			$this->tab = array('title' => _MB_General, 'name' => 'general', 'description' => _MB_The_information_on_this_tab_applies_to_the_whole_form);
		} elseif (in_array('questions', $postKeys) || in_array('saveQuestion', $postKeys)) {
			$this->_setTab_Question($option);
			$this->tab = array('title' => _MB_Questions, 'name' => 'questions', 'description' => "");
		} elseif (in_array('addChoice', $postKeys)) {
			$this->_setTab_Question('addChoice');
			$this->tab = array('title' => _MB_Questions, 'name' => 'questions', 'description' => "");
		} elseif (in_array('finish', $postKeys)) {
			$this->tab = array('title' => _MB_Finish, 'name' => 'finish', 'description' => "");
		}
	}

	private function _setQuestionId()
	{
		$this->question_id = isset($this->mParams[0]) ? intval($this->mParams[0]) : NULL;
		if (!$this->question_id && isset($_POST['id'])) {
			$this->question_id = $this->root->mContext->mRequest->getRequest('id');
		}
	}

	private function _setFormId()
	{
		$this->form_id = isset($this->mParams[0]) ? intval($this->mParams[0]) : NULL;
		if (!$this->form_id && isset($_POST['form_id'])) {
			$this->form_id = $this->root->mContext->mRequest->getRequest('form_id');
		}
	}

	public function action_new()
	{
		$this->template = 'bmsurvey_editform.html';
		$Model_General = Model_General::forge();
		$this->form_id = $Model_General->addNew();
		$this->_setTab(array('general'));
	}

	public function action_question()
	{
		$this->template = 'bmsurvey_editform.html';
		$this->_setQuestionId();
		$this->_setTab(array('questions'));
	}

	public function action_UpQuestion()
	{
		$this->template = 'bmsurvey_editform.html';
		$Model_Question = Model_Question::forge();
		$this->question_id = $Model_Question->position(intval($this->mParams[0]), -1);
		$this->_setTab(array('questions'));
	}

	public function action_DownQuestion()
	{
		$this->template = 'bmsurvey_editform.html';
		$Model_Question = Model_Question::forge();
		$this->form_id = $Model_Question->getFormId();
		$this->_setTab(array('questions'));
	}

	public function action_deleteQuestion()
	{
		$this->template = 'bmsurvey_editform.html';
		$Model_Question = Model_Question::forge();
		$this->form_id = $Model_Question->delete(intval($this->mParams[0]));
		$this->_setTab(array('questions'));
	}

	public function action_addQuestion()
	{
		$this->template = 'bmsurvey_editform.html';
		$Model_Question = Model_Question::forge();
		$this->question_id = $Model_Question->add(intval($this->mParams[0]));
		$this->_setTab(array('questions'));
	}

	public function action_deleteChoice()
	{
		$this->template = 'bmsurvey_editform.html';
		$Model_Question = Model_Question::forge();
		$this->question_id = $Model_Question->deleteChoice(intval($this->mParams[0]));
		$this->_setTab(array('questions'));
	}

	public function action_switchTab()
	{
		$this->template = 'bmsurvey_editform.html';
		$this->_setFormId();
		$this->_setQuestionId();
		$this->_setTab(array_keys($_POST));
	}

	public function action_general()
	{
		$this->template = 'bmsurvey_editform.html';
		$this->_setFormId();
		$this->_setTab(array('general'));
	}

	public function action_view()
	{
		$view = new View($this->root);
		$view->setTemplate($this->template);
		$view->set('is_manager', $this->manageObjects['is_manager']);
		$view->set('tab', $this->tab);
		$view->set('tpl_vars', $this->contents);
		$view->set('form_id', $this->form_id);
	}
}