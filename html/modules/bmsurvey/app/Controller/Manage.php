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
require_once _MY_MODULE_PATH . 'app/Model/General.class.php';
require_once _MY_MODULE_PATH . 'app/Model/Question.class.php';
require_once _MY_MODULE_PATH . 'app/Model/Response.class.php';

class Controller_Manage extends Controller_Bmsurvey
{
	protected $tpl_vars;

	public function __construct()
	{
		parent::__construct();
		if (!$this->root->mContext->mXoopsUser) {
			redirect_header(XOOPS_URL . '/modules/bmsurvey/', 2, _MD_BMSURVEY_CAN_WRITE_USER_ONLY);
			exit();
		}
	}

	private function _setFormId()
	{
		$this->form_id = isset($this->mParams[0]) ? intval($this->mParams[0]) : NULL;
		if (!$this->form_id && isset($_POST['form_id'])) {
			$this->form_id = $this->root->mContext->mRequest->getRequest('form_id');
		}
	}

	private function _adminOrOwner()
	{
		if ($this->mModuleAdmin) return TRUE;
		$Model_General = Model_General::forge();
		$generalObject = $Model_General->get($this->form_id);
		if (!$generalObject) return false;
		if ($generalObject->get('owner') == $this->root->mContext->mXoopsUser->uid()) {
			return TRUE;
		}
		return FALSE;
	}

	public function action_stop()
	{
		$this->_setFormId();
		if ($this->_adminOrOwner()) {
			$Model_General = Model_General::forge();
			$this->form_id = $Model_General->setStatus(intval($this->mParams[0]), 'stop');
		}
		$this->action_index();
	}

	public function action_test()
	{
		$this->_setFormId();
		if ($this->_adminOrOwner()) {
			$Model_General = Model_General::forge();
			$this->form_id = $Model_General->setStatus(intval($this->mParams[0]), 'test');
		}
		$this->action_index();
	}

	public function action_active()
	{
		$this->_setFormId();
		if ($this->_adminOrOwner()) {
			$Model_General = Model_General::forge();
			$this->form_id = $Model_General->setStatus(intval($this->mParams[0]), 'active');
		}
		$this->action_index();
	}

	public function action_stock()
	{
		$this->_setFormId();
		if ($this->_adminOrOwner()) {
			$Model_General = Model_General::forge();
			$this->form_id = $Model_General->setStatus(intval($this->mParams[0]), 'stock');
		}
		$this->action_index();
	}

	private function _purgeResponse(&$responseObjects)
	{
		$responseBoolHandler = xoops_getmodulehandler('response_bool');
		$responseDateHandler = xoops_getmodulehandler('response_date');
		$responseMultipleHandler = xoops_getmodulehandler('response_multiple');
		$responseOtherHandler = xoops_getmodulehandler('response_other');
		$responseRankHandler = xoops_getmodulehandler('response_rank');
		$responseSingleHandler = xoops_getmodulehandler('response_single');
		$responseTextHandler = xoops_getmodulehandler('response_text');
		foreach ($responseObjects as $responseObject) {
			$response_id = $responseObject->getVar('id');
			$criteria = new Criteria('response_id', $response_id);
			$responseBoolHandler->deleteAll($criteria, TRUE);
			$responseDateHandler->deleteAll($criteria, TRUE);
			$responseMultipleHandler->deleteAll($criteria, TRUE);
			$responseOtherHandler->deleteAll($criteria, TRUE);
			$responseRankHandler->deleteAll($criteria, TRUE);
			$responseSingleHandler->deleteAll($criteria, TRUE);
			$responseTextHandler->deleteAll($criteria, TRUE);
		}
	}

	public function action_purge()
	{
		$this->_setFormId();
		if ($this->_adminOrOwner()) {
			$Model_Question = Model_Question::forge();
			$choiceHandler = xoops_getmodulehandler('question_choice');
			$responseHandler = xoops_getmodulehandler('response');
			$questionObjects = $Model_Question->getObjectsOnForm($this->form_id);
			$criteria = new Criteria('form_id', $this->form_id);
			$responseObjects = $responseHandler->getObjects($criteria);
			// Delete Respose data
			$this->_purgeResponse($responseObjects);
			// Delete Question Choice data
			foreach ($questionObjects as $questionObject) {
				$question_id = $questionObject->getVar('id');
				$criteria = new Criteria('question_id', $question_id);
				$choiceHandler->deleteAll($criteria, TRUE);
			}
			// Delete Question
			$Model_Question->deleteAll($this->form_id, TRUE);
			// Delete General
			$generalHandler = xoops_getmodulehandler('form');
			$generalObject = $generalHandler->get($this->form_id);
			$generalHandler->delete($generalObject, TRUE);
		}
		$this->action_index();
	}

}