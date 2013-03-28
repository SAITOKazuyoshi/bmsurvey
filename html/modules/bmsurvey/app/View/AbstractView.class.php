<?php
/**
 * Created by JetBrains PhpStorm.
 * User: bluemooninc
 * Date: 12/07/27
 * Time: 19:09
 * To change this template use File | Settings | File Templates.
 */

class View
{
	protected $render;
	protected $root;

	/**
	 * constructor
	 */
	public function __construct() {
		$this->root = XCube_Root::getSingleton();
		$this->render = $this->root->mContext->mModule->getRenderTarget();
	}

	public function setTemplate($viewTemplate)
	{
		$this->render->setTemplateName($viewTemplate);
	}

	public function set( $name, $object ){
		$this->render->setAttribute($name, $object);
	}

	public function setStylesheet( $name ){
		$headerScript = $this->root->mContext->getAttribute('headerScript');
		$headerScript->addStylesheet($name);
	}
}
