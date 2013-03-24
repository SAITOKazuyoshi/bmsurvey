<?php
include_once('AbstractModel.class.php');

class Model_Response extends AbstractModel{
	var $responseId = 0;
	var $responseValues = array();
	var $load_bool = FALSE;
	var $load_single = FALSE;
	var $load_multiple = FALSE;
	var $load_other = FALSE;
	var $load_rank = FALSE;
	var $load_text = FALSE;
	var $load_date = FALSE;
	protected $responseArray;

	function __construct()
	{
		$this->root = XCube_Root::getSingleton();
		$this->mHandler = xoops_getmodulehandler('response');
	}

	/**
	 * get Instance
	 * @param none
	 * @return object Instance
	 */
	public function &forge()
	{
		static $instance;
		if (!isset($instance)) {
			$instance = new Model_Response();
		}
		return $instance;
	}

	private function &_getObject(&$handler,$response_id,$question_id){
		$object = array();
		$criteria = new CriteriaCompo();
		$criteria->add(new Criteria('response_id',$response_id));
		$criteria->add(new Criteria('question_id',$question_id));
		$responseObjects =  $handler->getObjects($criteria);
		if ($responseObjects){
			$object = $responseObjects[0];
		}
		return $object;
	}

	private function &_response_bool(&$handler,$response_id,$question_id){
		$object = $this->_getObject($handler,$response_id,$question_id);
		return $object->getVar('choice_id');
	}
	private function &_response_text(&$handler,$response_id,$question_id){
		$object = $this->_getObject($handler,$response_id,$question_id);
		return $object->getVar('response');
	}
	private function &_response_multi(&$handler,$response_id,$question_id){
		$object = $this->_getObject($handler,$response_id,$question_id);
		return $object->getVar('choice_id');
	}

	// --------------------- response_other ---------------------
	function response_other($responseId){
		global $xoopsDB;
		$sql = "SELECT question_id, choice_id, response FROM ".TABLE_RESPONSE_OTHER." WHERE response_id='${responseId}'";
		$result = $xoopsDB->query($sql);
		while($row = $xoopsDB->fetchArray($result)) {
			$this->responseValues[$row['question_id']]["Q${row['question_id']}_${row['choice_id']}"] = $row['response'];
		}
	}	
	// --------------------- response_text ---------------------
	function response_text($responseId=0){
		global $xoopsDB;
		$sql = "SELECT question_id,response FROM ".TABLE_RESPONSE_TEXT
			." WHERE response_id='$responseId'";
		$result = $xoopsDB->query($sql);
		while($row = $xoopsDB->fetchArray($result)) {
			$this->responseValues[$row['question_id']] = $row['response'];
		}
	}

	// --------------------- response_date ---------------------
	function response_date($responseId){
		global $xoopsDB;
		$sql = "SELECT * FROM ".TABLE_RESPONSE_DATE." WHERE response_id='${responseId}'";
		$result = $xoopsDB->query($sql);
		while($row = $xoopsDB->fetchArray($result)) {
			$this->responseValues[$row['question_id']] = $row['response'];
		}
	}	

	private function &_response(&$handler,$form_id){
		$criteria = new CriteriaCompo();
		$criteria->add(new Criteria('uid',$this->root->mContext->mXoopsUser->uid()));
		$criteria->add(new Criteria('form_id',$form_id));
		$responseObjects =  $handler->getObjects($criteria);
		if ($responseObjects){
			return $responseObjects[0]->getVar('id');
		}else{
			return null;
		}
	}

	public function load(&$questionObjects,$form_id){
		$responseHandler = xoops_getmodulehandler('response');
		$responseBoolHandler = xoops_getmodulehandler('response_bool');
		$responseSingleHandler = xoops_getmodulehandler('response_single');
		$responseMultipleHandler = xoops_getmodulehandler('response_multiple');
//		$responseRankHandler = xoops_getmodulehandler('response_rank');
		$responseTextHandler = xoops_getmodulehandler('response_text');
//		$responseOtherHandler = xoops_getmodulehandler('response_other');
		$responseDateHandler = xoops_getmodulehandler('response_date');
		$response_id = $this->_response($responseHandler,$form_id);
		if(empty($response_id)) return null;
		foreach($questionObjects as $questionObject){
			$question_id = $questionObject->getVar('id');
			switch ( $questionObject->getVar('type_id') ) {
				case 1: // Yes/No
					$value = $this->_response_bool($responseBoolHandler,$response_id,$question_id);
					break;
				case 2: // Text
					$value = $this->_response_text($responseTextHandler,$response_id,$question_id);
					break;
				case 3: // Text Area
					$value = $this->_response_text($responseTextHandler,$response_id,$question_id);
					break;
				case 4: // Radio button
					$value = $this->_response_bool($responseSingleHandler,$response_id,$question_id);
					break;
				case 5: // Check box
					$value = $this->_response_multi($responseMultipleHandler,$response_id,$question_id);
					break;
				case 6: // Dropdown list
					$value = $this->_response_bool($responseSingleHandler,$response_id,$question_id);
					break;
				case 9: // Date
					$value = $this->_response_text($responseDateHandler,$response_id,$question_id);
					break;
				case 10: // Numeric
					$value = $this->_response_text($responseTextHandler,$response_id,$question_id);
					break;
			}
			$this->responseArray[$questionObject->getVar('id')]['postValue'] = $value;
		}
		return $this->responseArray;
	}
}
