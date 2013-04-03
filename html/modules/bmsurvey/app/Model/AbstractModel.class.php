<?php
/**
 * Created by JetBrains PhpStorm.
 * Copyright(c): bluemooninc
 * Date: 2013/01/08
 * Time: 15:55
 * To change this template use File | Settings | File Templates.
 */

define('STATUS_EDIT',    0x00);
define('STATUS_ACTIVE',  0x01);
define('STATUS_STOP',    0x02);
define('STATUS_DELETED', 0x04);
define('STATUS_TEST',    0x08);

abstract class AbstractModel {
	// object
	protected $root = NULL;

	/**
	 * constructor
	 */
	public function __construct() {
		$this->root = XCube_Root::getSingleton();
		define('TABLE_REALM', $this->root->mController->mDB->prefix("bmsurvey_realm"));
		define('TABLE_RESPONDENT', $this->root->mController->mDB->prefix("bmsurvey_respondent"));
		define('TABLE_editFormER', $this->root->mController->mDB->prefix("bmsurvey_editFormer"));
		define('TABLE_FORM', $this->root->mController->mDB->prefix("bmsurvey_form" ));
		define('TABLE_QUESTION_TYPE', $this->root->mController->mDB->prefix("bmsurvey_question_type" ));
		define('TABLE_QUESTION', $this->root->mController->mDB->prefix("bmsurvey_question" ));
		define('TABLE_QUESTION_CHOICE', $this->root->mController->mDB->prefix("bmsurvey_question_choice" ));
		define('TABLE_ACCESS', $this->root->mController->mDB->prefix("bmsurvey_access" ));
		define('TABLE_RESPONSE', $this->root->mController->mDB->prefix("bmsurvey_response" ));
		define('TABLE_RESPONSE_BOOL', $this->root->mController->mDB->prefix("bmsurvey_response_bool" ));
		define('TABLE_RESPONSE_SINGLE', $this->root->mController->mDB->prefix("bmsurvey_response_single" ));
		define('TABLE_RESPONSE_MULTIPLE', $this->root->mController->mDB->prefix("bmsurvey_response_multiple" ));
		define('TABLE_RESPONSE_RANK', $this->root->mController->mDB->prefix("bmsurvey_response_rank" ));
		define('TABLE_RESPONSE_TEXT', $this->root->mController->mDB->prefix("bmsurvey_response_text" ));
		define('TABLE_RESPONSE_OTHER', $this->root->mController->mDB->prefix("bmsurvey_response_other" ));
		define('TABLE_RESPONSE_DATE', $this->root->mController->mDB->prefix("bmsurvey_response_date" ));
		define('TABLE_', $this->root->mController->mDB->prefix("bmsurvey_" ));
	}
	protected function getModuleNames($isactive = FALSE)
	{
		$criteria = new CriteriaCompo();
		if ($isactive) {
			$criteria->add(new Criteria('isactive', '1', '='));
		}
		$module_handler =& xoops_gethandler('module');
		$objs = $module_handler->getObjects($criteria);
		$ret = array();
		foreach ($objs as $obj) {
			$ret[$obj->getVar('mid')] = $obj->getVar('name');
		}
		return $ret;
	}

	/**
	 * @param $array
	 * @return array
	 */
	function array_flatten($array){
		$result = array();
		array_walk_recursive($array, function($v) use (&$result){
			$result[] = $v;
		});
		return $result;
	}

	/**
	 * @param $key
	 * @return mixed
	 */
	public function getRequest( $key ) {
		return $this->root->mContext->mRequest->getRequest( $key );
	}
}