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
	protected $responseHandler;
	protected $responseBoolHandler;
	protected $responseSingleHandler;
	protected $responseMultipleHandler;
	protected $responseTextHandler;
	protected $responseDateHandler;
	protected $responseRankHandler;
	protected $responseOtherHandler;

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

	private function &_getObject(&$handler,$response_id,$question_id,$choice_id,$hasMany=false){
		$result = array();
		$criteria = new CriteriaCompo();
		$criteria->add(new Criteria('question_id',$question_id));
		if (!is_null($response_id)){
			$criteria->add(new Criteria('response_id',$response_id));
		}
		if (!is_null($choice_id)){
			$criteria->add(new Criteria('choice_id',$choice_id));
		}
		$responseObjects = $handler->getObjects($criteria);
		if ($responseObjects){
			if ($hasMany){
				$result = $responseObjects;
			}else{
				$result = $responseObjects[0];
			}
		}
		return $result;
	}

	private function &_response_choice(&$handler,$response_id,$question_id,$hasMany=false){
		$object = $this->_getObject($handler,$response_id,$question_id,null,$hasMany);
		if ($object){
			if($hasMany==false){
				$choice_id = $object->getVar('choice_id');
				return $choice_id;
			}
		}
		return $object;
	}

	private function &_response_text(&$handler,$response_id,$question_id,$hasMany=false){
		$object = $this->_getObject($handler,$response_id,$question_id,null,$hasMany);
		if ($object){
			if($hasMany==false){
				return $object->getVar('response');
			}
		}
		return $object;
	}

	private function &_response_other(&$handler,$response_id,$question_id,$choice_id,$hasMany=false){
		$object = $this->_getObject($handler,$response_id,$question_id,$choice_id,$hasMany);
		if ($object){
			if($hasMany==false){
				$otherValue = array(
					'choice_id'=>$choice_id,
					'value'=>$object->getVar('response')
				);
				return $otherValue;
			}
		}
		return $object;
	}

	private function _getResponse(&$questionObjects,$response_id = null){
		$hasMany = is_null($response_id) ? true : false;
		$otherValue = null;
		foreach($questionObjects as $questionObject){
			$question_id = $questionObject->getVar('id');
			$choice_id = null;
			switch ( $questionObject->getVar('type_id') ) {
				case 1: // Yes/No
					$value = $this->_response_choice($this->responseBoolHandler,$response_id,$question_id,$hasMany);
					break;
				case 2: // Text
					$value = $this->_response_text($this->responseTextHandler,$response_id,$question_id,$hasMany);
					break;
				case 3: // Text Area
					$value = $this->_response_text($this->responseTextHandler,$response_id,$question_id,$hasMany);
					break;
				case 4: // Radio button
					$value = $this->_response_choice($this->responseSingleHandler,$response_id,$question_id,$hasMany);
					if (!is_array($value)){
						$choice_id = $value;
					}
					$otherValue = $this->_response_other($this->responseOtherHandler,$response_id,$question_id,$choice_id,$hasMany);
					break;
				case 5: // Check box
					$choiceObjects = $this->_response_choice($this->responseMultipleHandler,$response_id,$question_id,true);
					$value = array();
					if($response_id){
						foreach($choiceObjects as $choiceObject){
							$choice_id = $value[] = $choiceObject->getVar('choice_id');
						}
					}else{
						$value = $choiceObjects;
					}
					$otherValue = $this->_response_other($this->responseOtherHandler,$response_id,$question_id,$choice_id,$hasMany);
					break;
				case 6: // Dropdown list
					$value = $this->_response_choice($this->responseSingleHandler,$response_id,$question_id,$hasMany);
					break;
				case 9: // Date
					$value = $this->_response_text($this->responseDateHandler,$response_id,$question_id,$hasMany);
					break;
				case 10: // Numeric
					$value = $this->_response_text($this->responseTextHandler,$response_id,$question_id,$hasMany);
					break;
			}
			$this->responseArray[$questionObject->getVar('id')]= array(
				'postValue' => $value,
				'otherValue' => $otherValue
			);
		}
	}

	public function load(&$questionObjects,$form_id, $uid=NULL){
		$this->responseHandler = xoops_getmodulehandler('response');
		$this->responseBoolHandler = xoops_getmodulehandler('response_bool');
		$this->responseSingleHandler = xoops_getmodulehandler('response_single');
		$this->responseMultipleHandler = xoops_getmodulehandler('response_multiple');
		$this->responseTextHandler = xoops_getmodulehandler('response_text');
		$this->responseDateHandler = xoops_getmodulehandler('response_date');
		$this->responseOtherHandler = xoops_getmodulehandler('response_other');
		$response_id = null;
		if ($uid){
			$responseObject = $this->responseHandler->getResponse($form_id,$uid);
			if ($responseObject){
				$response_id = $responseObject->getVar('id');
				$this->_getResponse($questionObjects,$response_id);
			}
		}else{
			$this->_getResponse($questionObjects);
		}
		return $this->responseArray;
	}

	private function _deleteResponse(&$questionObjects,$form_id){
		$otherValue = null;
		foreach($questionObjects as $questionObject){
			$criteria = new Criteria('question_id', $questionObject->getVar('id'));
			$this->responseBoolHandler->deleteAll($criteria,true);
			$this->responseSingleHandler->deleteAll($criteria,true);
			$this->responseMultipleHandler->deleteAll($criteria,true);
			$this->responseTextHandler->deleteAll($criteria,true);
			$this->responseDateHandler->deleteAll($criteria,true);
			$this->responseOtherHandler->deleteAll($criteria,true);
		}
		$criteria = new Criteria('form_id', $form_id);
		$this->responseHandler->deleteAll($criteria,true);
	}
	public function delete(&$questionObjects,$form_id){
		$this->responseHandler = xoops_getmodulehandler('response');
		$this->responseBoolHandler = xoops_getmodulehandler('response_bool');
		$this->responseSingleHandler = xoops_getmodulehandler('response_single');
		$this->responseMultipleHandler = xoops_getmodulehandler('response_multiple');
		$this->responseTextHandler = xoops_getmodulehandler('response_text');
		$this->responseDateHandler = xoops_getmodulehandler('response_date');
		$this->responseOtherHandler = xoops_getmodulehandler('response_other');
		$this->_deleteResponse($questionObjects,$form_id);
		return $this->responseArray;
	}
}
