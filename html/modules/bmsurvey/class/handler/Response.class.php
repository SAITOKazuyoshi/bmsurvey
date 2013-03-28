<?php
/**
 * Created by JetBrains PhpStorm.
 * User: bluemooninc
 * Date: 2013/03/18
 * Time: 16:43
 * To change this template use File | Settings | File Templates.
 */

if (!defined('XOOPS_ROOT_PATH')) exit();
class Bmsurvey_ResponseObject extends XoopsSimpleObject
{
	public function __construct()
	{
		$this->initVar('id', XOBJ_DTYPE_INT, 0);
		$this->initVar('form_id', XOBJ_DTYPE_INT, 0);
		$this->initVar('submitted', XOBJ_DTYPE_INT, 0);
		$this->initVar('complete', XOBJ_DTYPE_STRING, 'N', true, 1);
		$this->initVar('uid', XOBJ_DTYPE_INT, 0);
	}
}
class Bmsurvey_ResponseHandler extends XoopsObjectGenericHandler
{
	public $mTable = 'bmsurvey_response';
	public $mPrimary = 'id';
	public $mClass = 'Bmsurvey_responseObject';
	public function __construct(&$db)
	{
		parent::XoopsObjectGenericHandler($db);
	}

	public function &getResponse($form_id,$uid){
		$criteria = new CriteriaCompo();
		$criteria->add(new Criteria('uid',$uid));
		$criteria->add(new Criteria('form_id',$form_id));
		$responseObjects = $this->getObjects($criteria);
		if ($responseObjects){
			return $responseObjects[0];
		}else{
			return $responseObjects;
		}
	}

}