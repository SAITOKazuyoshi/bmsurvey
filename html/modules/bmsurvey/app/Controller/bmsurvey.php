<?php
/**
 * Created by JetBrains PhpStorm.
 * User: bluemooninc
 * Date: 2013/03/17
 * Time: 10:28
 * To change this template use File | Settings | File Templates.
 */

require_once 'AbstractAction.class.php';
require_once _MY_MODULE_PATH . 'app/View/AbstractView.class.php';
require_once _MY_MODULE_PATH . 'app/Model/Form.class.php';

class Controller_Bmsurvey extends AbstractAction {
	protected $formObjects;
	protected $manageObjects;

	function __construct(){
		parent::__construct();
		$this->_setControlParameter();
	}
	public function manageOn(){
		return $this->manageObjects['manage_on'];
	}

	private function _SetControlParameter(){
		$sortby = xoops_getrequest('sortby');
		$order = xoops_getrequest('order');
		$start = xoops_getrequest('start');
		$sid = xoops_getrequest('sid');
		// set from session or default
		$this->manageObjects['sortby']    = $sortby;
		$this->manageObjects['order']     = $order;
		$this->manageObjects['start']     = $start;
		$this->manageObjects['sid']       = $sid;
		// get from usesr
		$this->manageObjects['altorder'] = ($this->manageObjects['order']=='asc') ? 'desc' : 'asc';
		$this->manageObjects['manage_on'] = false;

		if (count(array_intersect($this->root->mContext->mModuleConfig['MANAGERS'], $this->root->mContext->mXoopsUser->getGroups())) > 0) {
			$this->manageObjects['mySurvey'] = true;
		}else{
			$this->manageObjects['mySurvey'] = false;
		}
		if($this->root->mContext->mXoopsUser){
			if ($this->root->mContext->mXoopsUser->isadmin($this->mModuleId)){
				$this->manageObjects['manage_on'] = true;
			}
			$this->manageObjects['status'] = "0,1,2,4,8";
		}else{
			// For Guest
			$this->manageObjects['status'] = 1;
		}
	}

	private function _getFormData($manage_on, $sid, $start, $sortby, $order, $status){
		$Model_Form = Model_Form::forge();
		$Model_Form->setForm($sid);
		$Model_Form->setPageStart($start);
		$this->formObjects = array();
		if (!$sid){
			$this->formObjects['content']['forms'] = $Model_Form->get_form_list($sid, FALSE, $sortby, $order, $status);
			$this->formObjects['content']['pagenavi'] = $Model_Form->pageNavi(10);
			$this->formObjects['content']['sortnavi'] = $Model_Form->sortNavi();
			$xoopsOption['template_main'] = 'bmsurvey_index.html';
		}else{
			$xoopsOption['template_main'] = 'bmsurvey_controlpanel.html';
		}
	}
	public function action_index(){
		$this->template = 'bmsurvey_index.html';
		$this->_getFormData(
			$this->manageObjects['manage_on'],
			$this->manageObjects['sid'],
			$this->manageObjects['start'],
			$this->manageObjects['sortby'],
			$this->manageObjects['order'],
			$this->manageObjects['status']
		);
	}
	public function action_view(){
		$view = new View($this->root);
		$view->setTemplate($this->template);
		$view->set('manage_on', $this->manageObjects['manage_on']);
		$view->set('mySurvey', $this->manageObjects['mySurvey']);
		$view->set('order', $this->manageObjects['altorder']);
		$view->set('bmsurvey', $this->formObjects);
	}
}