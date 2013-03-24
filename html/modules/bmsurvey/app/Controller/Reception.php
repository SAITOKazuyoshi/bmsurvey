<?php
/**
 * Created by JetBrains PhpStorm.
 * User: bluemooninc
 * Date: 2013/03/22
 * Time: 9:06
 * To change this template use File | Settings | File Templates.
 */

require_once _MY_MODULE_PATH . 'app/View/view.php';
require_once _MY_MODULE_PATH . 'app/View/General.class.php';
require_once _MY_MODULE_PATH . 'app/View/Question.class.php';
require_once _MY_MODULE_PATH . 'app/Model/Status.class.php';
require_once _MY_MODULE_PATH . 'app/Model/General.class.php';
require_once _MY_MODULE_PATH . 'app/Model/Question.class.php';
require_once _MY_MODULE_PATH . 'app/Model/Response.class.php';
require_once _MY_MODULE_PATH . 'app/Model/Post.class.php';
include_once _MY_MODULE_PATH . 'app/View/HtmlRender.class.php';


class Controller_Reception extends AbstractAction
{
	protected $form_id;
	protected $question_id;
	protected $generalObject;
	protected $questionObjects;
	protected $valueArray;

	public function __construct()
	{
		parent::__construct();
		$this->htmlRender = new bmsurveyHtmlRender();
	}
	private function _setFormId(){
		$this->form_id =isset($this->mParams[0]) ? intval($this->mParams[0]) : null;
		if (!$this->form_id && isset($_POST['form_id'])){
			$this->form_id = $this->root->mContext->mRequest->getRequest('form_id');
		}
	}
	public function action_Receive(){
		$this->_setFormId();
		$Model_General = Model_General::forge();
		$this->generalObject = $Model_General->getFormObject($this->form_id);
		$Model_Question = Model_Question::forge();
		$this->questionObjects = $Model_Question->getObjectsOnForm($this->form_id);
		$Model_Post = Model_Post::forge();
		$Model_Post->setGeneral($this->generalObject);
		$Model_Post->setQuestion($this->questionObjects);
		$Model_Post->getPost();
		if ($Model_Post->passed()){
			// save and thank you page
			$Model_Post->save($this->form_id);
			$this->template = 'bmsurvey_thankyou.html';
		}else{
			// go back to form page
			$this->template = 'bmsurvey_webform.html';
		}
		$this->valueArray = $Model_Post->getValue();
	}
	public function action_view(){
		$view = new View($this->root);
		$view->setTemplate($this->template);
		$view->set('form_id', $this->form_id);
		$View_Question = View_Question::forge();
		$this->contents = array(
			'general' => $this->generalObject,
			'form' => $View_Question->generateForm($this->htmlRender,$this->questionObjects,$this->valueArray)
		);
		$view->set('tpl_vars', $this->contents);
	}
}