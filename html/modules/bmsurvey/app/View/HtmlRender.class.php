<?php
# $Id: esphtml.forms.php,v 1.1.1.1 2005/08/10 12:14:03 yoshis Exp $
// First written by James Flemer For eGrad2000.com <jflemer@alum.rpi.edu>
// string	mkwarn(char *warning);
// string	mkerror(char *error);
// string	mkradio(char *name, char *value);
// string	mkcheckbox(char *name, char *value);
// string	mktext(char *name);
// string	mktextarea(char *name, int rows, int cols, char *wordwrap);
// string	mkselect(char *name, char *options[]);
// string	mkfile(char *name);
require_once _MY_MODULE_PATH . 'app/Model/Question.class.php';

class bmsurveyHtmlRender extends View
{
	var $htmlTag = array();
	var $has_required = FALSE;
	var $questions = array();
	var $sections = array();
	var $pages = 1;

	function __construct(){
		parent::__construct();
	}
	function getHtmlTag()
	{
		$ret = $this->htmlTag;
		$this->htmlTag = array();
		return $ret;
	}

	function mkerror($msg)
	{
		return ("<font color=\"" . $GLOBALS['FMXCONFIG']['error_color'] . "\" size=\"+1\">[ ${msg} ]</font>\n");
	}

	function mkwarn($msg)
	{
		return ("<font color=\"" . $GLOBALS['FMXCONFIG']['warn_color'] . "\" size=\"+1\">${msg}</font>\n");
	}

	function mkother($_name, $value)
	{
		return array(
			'type' => "text",
			'name' => htmlspecialchars($_name,ENT_QUOTES),
			'value' => htmlspecialchars($value,ENT_QUOTES),
			'onKeyPress' => "other_check(this.name)",
		);
	}

	function mkrankTitle($_name, $value)
	{
		return array(
			'type' => NULL,
			'name' => htmlspecialchars($_name,ENT_QUOTES),
			'value' => htmlspecialchars($value,ENT_QUOTES),
		);
	}

	function mkradio($_name, $value, $varr = NULL, $message = NULL)
	{
		$checked = FALSE;
		if (is_array($varr)) {
			if ((isset($varr[$_name])) && (in_array($value, $varr))) {
				$checked = TRUE;
			}
		} else {
			if (strcmp($value, $varr) == 0) $checked = TRUE;
		}
		return array(
			'type' => "radio",
			'name' => htmlspecialchars($_name,ENT_QUOTES),
			'value' => htmlspecialchars($value,ENT_QUOTES),
			'checked' => $checked,
			'message' => $message
		);
	}

	function mkradioCancel($_name, $value)
	{
		return array(
			'type' => "button",
			'name' => "Button",
			'value' => htmlspecialchars($value,ENT_QUOTES),
			'onclick' => 'uncheckRadio(\'' . $_name . '\')'
		);
	}

	function mkcheckbox($_name, $value, $valueArray = NULL, $message = NULL)
	{
		$checked = FALSE;
		if ((in_array($value, $valueArray))) {
			$checked = TRUE;
		}
		return array(
			'type' => "checkbox",
			'name' => htmlspecialchars($_name,ENT_QUOTES),
			'value' => htmlspecialchars($value,ENT_QUOTES),
			'checked' => $checked,
			'message' => $message
		);
	}

	function mktext($_name, $size = 20, $max = 0, $value = NULL, $class = NULL)
	{
		$size = intval($size);
		$max = intval($max);
		return array(
			'type' => "text",
			'name' => htmlspecialchars($_name,ENT_QUOTES),
			'value' => $value
		);
	}

	function mktextarea($_name, $rows, $cols, $wrap, $value = NULL)
	{
		return array(
			'type' => "textarea",
			'name' => htmlspecialchars($_name,ENT_QUOTES),
			'rows' => $rows,
			'cols' => $cols,
			'value' => $value,
			'wrap' => $wrap
		);
	}

	function &mkselect($_name, $options, $varr = NULL)
	{
		$opt = array();
		while (list($cid, $content) = each($options)) {
			$checked = '';
			if (is_array($varr)) {
				if (isset($varr[$_name])) {
					if (is_array($varr[$_name]))
						$nm = $varr[$_name][0];
					else
						$nm = $varr[$_name];
					if ($nm == $cid) $checked = ' selected';
				}
			} else {
				if (strcmp($cid, $varr) == 0) $checked = ' selected';
			}
			$opt[] = array(
				'value' => $cid,
				'checked' => $checked,
				'content' => $content
			);
		}
		return array(
			'type' => "select",
			'name' => htmlspecialchars($_name,ENT_QUOTES),
			'value' => $opt
		);
	}

	function other_text($content)
	{
		return preg_replace(array("/^!other=/", "/^!other/"), array('', _MD_QUESTION_OTHER), $content);
	}

	public function generateHtml(&$questionObject, &$choiceObjects = null, &$valueArray = null)
	{
		$cancelButton = $this->root->mContext->mModuleConfig['RESET_RB'];
		$question_id = $questionObject->getVar('id');
		$defaultValue = isset($valueArray[$question_id]['postValue']) ? $valueArray[$question_id]['postValue'] : null;
		$otherValue = isset($valueArray[$question_id]['otherValue']) ? $valueArray[$question_id]['otherValue'] : null;
		$mixinMessage = isset($valueArray[$question_id]['guideMessage'] ) ? $valueArray[$question_id]['guideMessage'] : null;
		$qname = "Q" . $questionObject->getVar('id');
		$htmlTag = array();
		switch ($questionObject->getVar('type_id')) {
			case '1': // Yes/No
				$htmlTag[] = $this->mkradio($qname, 'Y', $defaultValue, _MB_Yes);
				$htmlTag[] = $this->mkradio($qname, 'N', $defaultValue, _MB_No);
				if ($cancelButton) $htmlTag[] = $this->mkradioCancel($qname, _MD_BMSURVEY_CHECKRESET);
				break;
			case '2': // single text line
				$htmlTag[] = $this->mktext($qname, $questionObject->getVar('length'), $questionObject->getVar('precise'), $defaultValue);
				break;
			case '3': // essay
				$htmlTag[] = $this->mktextarea($qname, $questionObject->getVar('precise'), $questionObject->getVar('length'), 'VIRTUAL', $defaultValue);
				break;
			case '4': // radio
				foreach ($choiceObjects as $choiceObject) {
					$choice_id = $choiceObject->getVar('id');
					$content = $choiceObject->getVar('value');
					if (preg_match("/^!other$/", $content)) {
						$htmlTag[] = $this->mkradio( $qname, $choice_id, $defaultValue, $this->other_text($content));
						$htmlTag[] = $this->mkother( $qname . "_" . $choice_id, $otherValue['value']);
					} else {
						$htmlTag[] = $this->mkradio( $qname, $choice_id, $defaultValue, $content);
					}
				}
				if ($cancelButton) $htmlTag[] = $this->mkradioCancel($qname, _MD_BMSURVEY_CHECKRESET);
				break;
			case '5': // check boxes
				foreach ($choiceObjects as $choiceObject) {
					$choice_id = $choiceObject->getVar('id');
					$content = $choiceObject->getVar('value');
					if (preg_match("/!other/", $content)) {
						$htmlTag[] = $this->mkcheckbox($qname, "other_".$choice_id, $defaultValue, $this->other_text($content));
						$htmlTag[] = $this->mkother($qname . "_" . $choice_id, $otherValue);
					} else {
						$htmlTag[] = $this->mkcheckbox($qname, $choice_id, $defaultValue, $content);
					}
				}
				break;
			case '6': // dropdown box
				$options = array();
				foreach ($choiceObjects as $choiceObject) {
					$choice_id = $choiceObject->getVar('id');
					$content = $choiceObject->getVar('value');
					$options[$choice_id] = $content;
				}
				$htmlTag[] = $this->mkselect($qname, $options, $defaultValue);
				break;
			case '9': // date
				$varr[$qname] = date(_SHORTDATESTRING, time());
				$htmlTag[] = $this->mktext($qname, 10, 10, $defaultValue);
				break;
			case '99': // Page Break
				$this->pages++;
				$question['content'] = NULL;
				break;
			case '100': // Section Text
				//if ($section_id=="") $question['section_top'] = 1;
				//$section_id = 'tab-'.$question['id'];
				$question['section_id'] = 'tab-' . $questionObject->getVar('id');
				break;
		}
		return array(
			'id' => $questionObject->getVar('id'),
			'name' => $questionObject->getVar('name'),
			'content' => $questionObject->getVar('content'),
			'tag' => $htmlTag,
			'required'=> $questionObject->getVar('required'),
			'message' => $mixinMessage
		);
	}
	private function &_makeChoiceTitle(&$choiceObjects){
		$ret = array();
		foreach($choiceObjects as $choiceObject){
			if (preg_match('/^!other$/',$choiceObject->getVar('value'))){
				$ret[$choiceObject->getVar('id')] = array(
					'title' => _MD_QUESTION_OTHER,
					'hasOther' => true
				);
			}else{
				$ret[$choiceObject->getVar('id')] = array(
					'title' => $choiceObject->getVar('value'),
					'hasOther' => false
				);
			}
		}
		return $ret;
	}
	private function _makePercentageBar(&$choiceArray,$total,$barColor){
		$i=0;
		foreach($choiceArray as $key=>$choices){
			$choiceArray[$key]['percentage'] = $choices['value']/$total*100;
			$choiceArray[$key]['bar_color'] = $barColor[ $i % 3 ];
			$i++;
		}
	}
	private function _getBarColor(){
		return array(
			"bar-info",
			"bar-warning",
			"bar-success",
			"bar-danger"
		);
	}
	public function mixinReport(&$questionObject, &$choiceObjects = null, &$valueArray = null)
	{
		$question_id = $questionObject->getVar('id');
		$responseObjects = isset($valueArray[$question_id]['postValue']) ? $valueArray[$question_id]['postValue'] : null;
		$otherObjects = isset($valueArray[$question_id]['otherValue']) ? $valueArray[$question_id]['otherValue'] : null;
		$htmlTag = array();
		$barColor = $this->_getBarColor();
		$choiceTitle = $this->_makeChoiceTitle($choiceObjects);
		$order = "asc";
		switch ($questionObject->getVar('type_id')) {
			case '1': // Yes/No
			case '4': // radio
			case '5': // check boxes
			case '6': // dropdown box
				foreach($responseObjects as $responseObject){
					$choice_id = $responseObject->getVar('choice_id');
					if (!isset($htmlTag[$choice_id]['value'])) $htmlTag[$choice_id]['value']=0;
					$htmlTag[$choice_id]['value']++;
					if (isset($choiceTitle[$responseObject->getVar('choice_id')])){
						$htmlTag[$choice_id]['content'] = $choiceTitle[$responseObject->getVar('choice_id')]['title'];
						$htmlTag[$choice_id]['hasOther'] = $choiceTitle[$responseObject->getVar('choice_id')]['hasOther'];
						if ($choiceTitle[$responseObject->getVar('choice_id')]['hasOther']){
							foreach($otherObjects as $otherObject){
								$htmlTag[$choice_id]['otherValue']=array(
									'value' => $otherObject->getVar('response')
								);
							}
						}
					}else{
						// for Y or N
						$htmlTag[$choice_id]['content'] = $choice_id;
						$order = "desc";
					}
				}
				$hasCount = true;
				$this->_makePercentageBar($htmlTag,count($responseObjects),$barColor);
				break;
			case '2': // single text line
			case '3': // essay
			case '9': // date
				foreach($responseObjects as $responseObject){
					$htmlTag[]=array(
						'value' => $responseObject->getVar('response')
					);
				}
				$hasCount = false;
				break;
		}
		if ($order=="asc"){
			ksort($htmlTag);
		}else{
			krsort($htmlTag);
		}
		return array(
			'id' => $questionObject->getVar('id'),
			'name' => $questionObject->getVar('name'),
			'content' => $questionObject->getVar('content'),
			'tag' => $htmlTag,
			'required'=> $questionObject->getVar('required'),
			'hasCount' => $hasCount
		);
	}
	function formRender_smarty(&$questionObjects, $pageNumber = 1)
	{
		global $_POST, $xoopsModuleConfig;

		$has_choices = $this->esp_type_has_choices();
		$qnum = 1;
		$formRender_smarty = array();
		$section_id = "";
		$section = array();
		$question = array();
		$questionHandler = Model_Question::forge();
		foreach ($questionObjects as $questionObject) {
			$question['section_top'] = 0;
			$question['type_id'] = $questionObject->getVar('type_id');
			$question['id'] = $questionObject->getVar('id');
			$question['required'] = $questionObject->getVar('required');
			$question['length'] = $questionObject->getVar('length');
			$question['precise'] = $questionObject->getVar('precise');
			if ($question['required'] == 'Y') {
				$this->has_required = TRUE;
			}
			if ($pageNumber > $this->pages) {
				// Page Break
				if ($question['type_id'] < 99) $qnum++;
				if ($question['type_id'] == '99') $this->pages++;
				continue; // Skip to pageNumber
			}
			if ($pageNumber < $this->pages) break; // Stop over the pageNumber
			// process each question
			$choiceObjects = NULL;
			if ($has_choices[$question['type_id']]) {
				$choiceObjects = $questionHandler->getChoice($question['id']);
			}
			$this->generateHtml(
				$question['id'],
				$question['type_id'],
				$xoopsModuleConfig['RESET_RB'],
				$question['precise'],
				$question['length'],
				$choiceObjects
			);
			$question['qnum'] = $qnum;
			$question['section_id'] = $section_id;
			$formRender_smarty[$question['id']] = $question;
			$formRender_smarty[$question['id']]['htmlTag'] = $this->getHtmlTag();
			if ($question['type_id'] < 99) $qnum++;
			if ($question['type_id'] == 100) $section[$question['id']] = $question;
		}
		$this->sections = $section;
		return $formRender_smarty;
	}
}
