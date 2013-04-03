<?php
// $Id: FormTable.class.php,v0.83 2008/01/08 18:38:03 yoshis Exp $
//  ------------------------------------------------------------------------ //
//                      bmsurvey - Bluemoon Multi-Form                     //
//                   Copyright (c) 2005 - 2007 Bluemoon inc.                 //
//                       <http://www.bluemooninc.biz/>                       //
//              Original source by : phpESP V1.6.1 James Flemer              //
//  ------------------------------------------------------------------------ //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //
include_once('AbstractModel.class.php');

class Model_Form extends AbstractModel
{
	protected $myDirName = "bmsurvey";
	protected $publicShowToList = 0;
	protected $lastFormId = 0;
	protected $total = 0;
	protected $start = 0;
	protected $perpage = 10;
	protected $sortname = '';
	protected $sortorder = '';
	protected $sortorderStr = array('ASC' => 'DESC', 'DESC' => 'ASC');
	protected $status = '1';
	protected $stat_flag;
	protected $stat_desc;
	protected $userGroups;
	protected $ownerGroup;
	protected $ownerUid;
	protected $is_admin;
	protected $copybyGroup = FALSE;
	protected $editbyGroup = FALSE;
	protected $viewbyGroup = FALSE;
	protected $formInfo;
	protected $message;
	protected $root;
	protected static $groups;
	protected $FormList=array();        // for form id,title stack

	/**
	 * get Instance
	 * @param none
	 * @return object Instance
	 */
	public function &forge()
	{
		static $instance;
		if (!isset($instance)) {
			$instance = new Model_Form();
		}
		return $instance;
	}

	/**
	 * constructor
	 */
	function setForm($sid = 0, $formName = '')
	{
		global $xoopsUser, $xoopsModule, $xoopsModuleConfig;
		$this->publicShowToList = $xoopsModuleConfig['SHOW_PUBLIC_TO_OTHERGROUP'];

		$this->is_admin = FALSE;
		$this->userGroups = array();
		if ($xoopsUser) {
			$this->userGroups = $xoopsUser->getGroups();
			if (is_object($xoopsModule)) {
				$this->is_admin = $xoopsUser->isAdmin($xoopsModule->mid());
			}
		}
		if ($sid) {
			$this->formInfo = $this->get_formInfoById($sid);
			$this->ownerGroup = $this->formInfo['realm'];
		} elseif ($formName) {
			$this->formInfo = $this->get_formInfoByName($formName);
		}
	}

	public function &getFormInfo($param = NULL)
	{
		if ($param)
			return $this->formInfo[$param];
		else
			return $this->formInfo;
	}

	function editbyGroup()
	{
		return $this->editbyGroup;
	}

	function copybyGroup($uid)
	{
		global $xoopsUser, $xoopsModuleConfig;

		$this->copybyGroup = FALSE;
		if ($xoopsUser->isadmin()) {
			$this->copybyGroup = TRUE;
		} else {
			$grobal_group = explode("|", $xoopsModuleConfig['GROBAL_GROUP']);
			$User = new xoopsUser($uid);
			$formOwnerGroups = $User->getGroups();
			$yourGroups = $xoopsUser->getGroups();
			$this->copybyGroup = FALSE;
			foreach ($yourGroups as $you) {
				/* except grobal group */
				if (!in_array($you, $grobal_group)) {
					if (in_array($you, $formOwnerGroups)) {
						$this->copybyGroup = TRUE;
						break;
					}
				}
			}
		}
		return $this->copybyGroup;
	}

	function viewbyGroup()
	{
		return $this->viewbyGroup;
	}

	function uid()
	{
		return $this->formInfo['owner'];
	}

	function isOwnerGroup()
	{
		global $xoopsUser;
		$groups = $xoopsUser->getGroups();
		return in_array($this->ownerGroup, $groups);
	}

	function get_formInfo($sql)
	{
		global $xoopsDB;
		$result = $xoopsDB->query($sql);
		$row = $xoopsDB->fetchArray($result);
		$row['uid'] = $row['owner'];
		if (isset($row['last_update'])) {
			$row['last_update_s'] = formatTimestamp($row['last_update'], 's');
			$row['last_update_m'] = formatTimestamp($row['last_update'], 'm');
			$row['last_update_l'] = formatTimestamp($row['last_update'], 'l');
		}
		if (isset($row['published'])) {
			$row['published_s'] = formatTimestamp($row['published'], 's');
			$row['published_m'] = formatTimestamp($row['published'], 'm');
			$row['published_l'] = formatTimestamp($row['published'], 'l');
		}
		if (isset($row['expired'])) {
			$row['expired_s'] = formatTimestamp($row['expired'], 's');
			$row['expired_m'] = formatTimestamp($row['expired'], 'm');
			$row['expired_l'] = formatTimestamp($row['expired'], 'l');
		}
		if (isset($row['status'])) $this->get_status($row['status']);
		$row['status'] = $this->stat_flag;
		$row['status_desc'] = $this->stat_desc;
		if (isset($row['realm']) && isset($row['owner'])) {
			$this->ownerGroup = $row['realm'];
			$this->ownerUid = $row['owner'];
			$this->set_manageFlag($row['owner'], $row['realm']);
		}
		$row['editbyGroup'] = $this->editbyGroup;
		$row['viewbyGroup'] = $this->viewbyGroup;
		return $row;
	}

	function get_formInfoById($sid)
	{
		global $xoopsDB;
		$sql = "SELECT *, UNIX_TIMESTAMP(changed) AS last_update FROM " . TABLE_FORM . " WHERE id='" . $sid . "'";
		$row = $this->get_formInfo($sql);
		// Extra info for edit
		$userHander = new XoopsUserHandler($xoopsDB);
		$row['uname'] = ($tUser = $userHander->get($row['owner'])) ? $tUser->uname() : '';
		$row['resp'] = $this->get_responseCount($row['id']);
		return $row;
	}

	function get_formInfoByName($formName)
	{
		$sql = "SELECT *, UNIX_TIMESTAMP(changed) AS last_update FROM " . TABLE_FORM . " WHERE name='" . $formName . "'";
		$row = $this->get_formInfo($sql);
		return $row;
	}

	function set_response_id($rid, $sid)
	{
		global $xoopsDB;
		$sql = "UPDATE " . TABLE_FORM . " SET response_id = '${rid}' WHERE id='${sid}'";
		$result = $xoopsDB->query($sql);
		if ($result) $this->message = _MD_DEFAULTRESULTDONE;
	}

	function get_responseCount($sid)
	{
		global $xoopsDB;
		$sql = "SELECT count(*) FROM " . TABLE_RESPONSE . " WHERE complete='Y' AND form_id='" . $sid . "'";
		$result = $xoopsDB->query($sql);
		list($cnt) = $xoopsDB->fetchrow($result);
		return $cnt;
	}

	private function set_manageFlag($owner)
	{
		if ( $this->root->mContext->mXoopsUser->isadmin() ) return TRUE;
		if ( $owner==$this->root->mContext->mXoopsUser->uid() ) return TRUE;
	}

	function auth_is_owner($sid, $user)
	{
		global $xoopsDB;
		$val = FALSE;
		$sql = "SELECT s.owner = '$user' FROM " . TABLE_FORM . " s WHERE s.id='$sid'";
		$result = $xoopsDB->query($sql);
		if (!(list($val) = $xoopsDB->fetchRow($result)))
			$val = FALSE;

		return $val;
	}

	function auth_get_form_realm($sid)
	{
		global $xoopsDB;

		$sql = "SELECT s.realm FROM " . TABLE_FORM . " s WHERE s.id='$sid'";
		$result = $xoopsDB->query($sql);
		list($val) = $xoopsDB->fetchRow($result);

		return $val;
	}

	function get_status($status)
	{
		if ($status & STATUS_DELETED) {
			$this->stat_flag = STATUS_DELETED;      //$this->stat_desc = _MB_Archived;
		} elseif ($status & STATUS_STOP) {
			$this->stat_flag = STATUS_STOP;         //$this->stat_desc = _MB_Ended;
		} elseif ($status & STATUS_ACTIVE) {
			$this->stat_flag = STATUS_ACTIVE;       //$this->stat_desc = _MB_Active;
		} elseif ($status & STATUS_TEST) {
			$this->stat_flag = STATUS_TEST;         //$this->stat_desc = _MB_Testing;
		} else {
			$this->stat_flag = 0;                   //$this->stat_desc = _MB_Editing;
		}
		return $this->stat_flag;
	}

	/**
	 * Page Navi
	 *
	 * @param $start
	 */
	function setPageStart($start)
	{
		$this->start = $start;
	}

	function sortNavi()
	{
		$endNumber = $this->perpage < $this->total ? $this->perpage : $this->total;
		return array(
			'sortname' => $this->sortname,
			'sortorder' => $this->sortorder,
			'status' => $this->status,
			'start' => $this->start + 1,
			'end' => $endNumber,
			'perpage' => $this->perpage,
			'total' => $this->total
		);
	}

	function pageNavi($offset)
	{
		include XOOPS_ROOT_PATH . '/class/pagenav.php';
		$optparam = $this->sortname ? 'sortby=' . $this->sortname : "";
		$optparam .= $this->sortorder ? '&order=' . $this->sortorder : "";
		$nav = new XoopsPageNav($this->total, $this->perpage, $this->start, "start", $optparam);
		return $nav->renderNav($offset);
	}

	private function _checkMyGroup(&$realm)
	{
		global $xoopsUser;
		$myGroups = $xoopsUser->getGroups();
		$sameGroups = array_intersect($realm, $myGroups);
		if ($sameGroups) return TRUE;
	}

	private function _isGroupsOfUser(&$gulHandler, $uid)
	{
		$criteria = new CriteriaCompo();
		$criteria->add(new Criteria('uid', $uid));
		$generalObjects =& $gulHandler->getObjects($criteria);
		return (count($generalObjects) > 0 && is_object($generalObjects[0]));
	}

	/**
	 * get form list for index.php, manage.php
	 * @param null $formId
	 * @param bool $limit
	 * @param string $sortby
	 * @param string $order
	 * @param int $status
	 * @param int $uid
	 * @return array
	 */
	function get_form_list($formId = NULL, $limit = TRUE, $sortby = 'changed', $order = 'DESC', $status = 1, $uid = 0)
	{
		$this->perpage = $this->root->mContext->mModuleConfig['BLOCKLIST'];
		$this->sortname = in_array($sortby, array('changed', 'published', 'expired', 'owner', 'title', 'name', 'status')) ? $sortby : "changed";
		$this->sortorder = preg_match("/DESC|ASC/i", $order) ? $order : "ASC";
		$this->status = $status;

		/** @protected XoopsUser $xoopsUser */
		$userIds = $this->_getSameGroupUserIds($this->root->mContext->mXoopsUser);

		$criteria = new CriteriaCompo();
		if (strlen($status) > 1) {
			$criteria->add(new Criteria('status', $status, 'IN'));
		} else {
			$criteria->add(new Criteria('status', intval($status), '='));
		}
		if (is_object($this->root->mContext->mXoopsUser) === FALSE or !$this->root->mContext->mXoopsUser->isadmin()) {
			$criteria->add(new Criteria('owner', implode(',', $userIds), 'IN'));
		}
		//
		// Get Recordcount for page switching
		//
		$generalHandler = xoops_getmodulehandler('form',$this->myDirName);
		$this->total = $generalHandler->getCount($criteria);
		if (!empty($formId)) {
			if ($this->sortorder == "DESC") {
				$operator_for_position = '>';
			} else {
				$operator_for_position = '<';
			}
			$criteria->add(new Criteria('id', $formId, $operator_for_position));
			$position = $generalHandler->getCount($criteria);
			$this->start = intval($position / $this->perpage) * $this->perpage;
		}
		$criteria->addSort($this->sortname, $this->sortorder);
		$criteria->setStart($this->start);
		$criteria->setLimit($this->perpage);
		$generalObjects = $generalHandler->getObjects($criteria);
		$responseHandler = xoops_getmodulehandler('response',$this->myDirName);
		$GulHandler = xoops_getmodulehandler('groups_users_link', 'user');
		$tpl_vars = array();
		foreach ($generalObjects as $generalObject) {
			$responseObject = $responseHandler->getResponse($generalObject->getVar('id'), $this->root->mContext->mXoopsUser->uid());
			if ($responseObject) {
				$submitted = $responseObject->getVar('submitted');
			} else {
				$submitted = NULL;
			}
			$this->FormList[$generalObject->getVar('id')]=$generalObject->getVar('title');
			$row = array(
				'realm' => $this->_isGroupsOfUser($GulHandler, $generalObject->getVar('owner')),
				'manage_on' => $this->set_manageFlag( $generalObject->getVar('owner') ),
				'hidelist' => 0,
				'editbyGroup' => $this->editbyGroup,
				'viewbyGroup' => $this->viewbyGroup,
				'generalObject' => $generalObject,
				'uname' => XoopsUser::getUnameFromId($generalObject->getVar('owner')),
				'status_desc' => $this->get_status($generalObject->getVar('status')),
				'resp' => $this->get_responseCount($generalObject->getVar('id')),
				'submitted' => $submitted
			);
			$this->get_status($generalObject->getVar('status'));
			$tpl_vars[] = $row;
		}
		return $tpl_vars;
	}
	public function &FormList(){
		return $this->FormList;
	}
	/**
	 * 同じグループに所属するユーザIDをすべて返す
	 * @param $xoopsUser
	 * @return array
	 */
	protected function &_getSameGroupUserIds(&$xoopsUser)
	{
		$sameGroupUserIds = array($xoopsUser->uid());
		if (is_object($xoopsUser) === FALSE) {
			return $sameGroupUserIds;
		}
		$group_ids = $xoopsUser->getGroups();
		$gulHandler = xoops_getmodulehandler('groups_users_link', 'user');
		$criteria = new Criteria('groupid', implode(",", $group_ids), "IN");
		$gulObjects = $gulHandler->getObjects($criteria);
		foreach ($gulObjects as $gulOnject) {
			$sameGroupUserIds[] = $gulOnject->getVar('uid');
		}
		return $sameGroupUserIds;
	}

	/**
	 * @param int $userId
	 * @return bool|string
	 */
	protected function _getGeneralGroupByUserId($userId)
	{
		/** @protected XoopsMemberHandler $memberHandler */
		$memberHandler = xoops_gethandler('member');
		/** @protected XoopsGroup[] $xoopsGroups */
		$xoopsGroups = $memberHandler->getGroupsByUser($userId, TRUE);

		foreach ($xoopsGroups as $xoopsGroup) {
			if ($xoopsGroup->isGeneral()) {
				return $xoopsGroup->get('groupid');
			}
		}

		return FALSE;
	}

	function get_Respondentinfo($unm)
	{
		global $xoopsDB;
		$sql = "SELECT * FROM " . TABLE_RESPONDENT . " WHERE uid='" . $unm . "'";
		$result = $xoopsDB->query($sql);
		if ($xoopsDB->getRowsNum($result) != 1) return (FALSE);
		$ret = $xoopsDB->fetchArray($result);

		$ret['sid'] = $ret['form_id'];
		$ret['rid'] = $ret['response_id'];
		return $ret;
	}

	function delete_respondent($uid)
	{
		global $xoopsDB;
		$sql = "DELETE FROM " . TABLE_RESPONDENT . " WHERE uid='" . $uid . "'";
		$result = $xoopsDB->query($sql);
		if (!$xoopsDB->query($sql)) {
			/* unsucessfull -- abort */
			echo _MB_Cannot_delete_account . $uid . ' (' . $xoopsDB->error() . ')';
		}
	}

	function update_respondent($respondent)
	{
		global $xoopsDB;
		$debug = 0;

		if ($debug) print_r($respondent);
		$disabled = ($respondent['disabled'] == 1) ? 'Y' : 'N';
		$sql = "SELECT * FROM " . TABLE_RESPONDENT . " WHERE uid='" . $respondent['uid'] . "'";
		$result = $xoopsDB->query($sql);
		if ($xoopsDB->getRowsNum($result) != 1) {
			$sql = sprintf("insert into %s
				(uid,password,fname,lname,email,disabled,form_id,response_id,changed,expiration)
				values('%s','%s','%s','%s','%s','%s',%u,%u,CURRENT_TIMESTAMP(),'%s')",
				TABLE_RESPONDENT,
				$respondent['uid'],
				$respondent['password'],
				$respondent['fname'],
				$respondent['lname'],
				$respondent['email'],
				$disabled,
				$respondent['sid'],
				$respondent['rid'],
				$respondent['expiration']);
		} else {
			$sql = "UPDATE " . TABLE_RESPONDENT . " SET "
				. "password='" . $respondent['password'] . "'"
				. ",fname='" . $respondent['fname'] . "'"
				. ",lname='" . $respondent['lname'] . "'"
				. ",email='" . $respondent['email'] . "'"
				. ",disabled='" . $disabled . "'"
				. ",form_id=" . $respondent['sid']
				. ",response_id=" . $respondent['rid']
				. ",changed='" . $respondent['changed'] . "'"
				. ",expiration='" . $respondent['expiration'] . "'"
				. " WHERE uid='" . $respondent['uid'] . "'";
		}
		if ($debug) echo "<p>" . $sql;
		$xoopsDB->queryF($sql);
	}

	function createUrl($uid)
	{
		return XOOPS_URL . "/modules/'.$this->myDirName.'/";
	}

	function checkYear($iyear)
	{
		$year = intval($iyear);
		if (($year > 1000) && ($year < 3000)) {
			return $iyear;
		}
		redirect_header(XOOPS_URL . '/', 1, _MD_POPNUPBLOG_INVALID_DATE . '(YEAR)' . $iyear);
		exit();
	}

	function checkMonth($imonth)
	{
		$month = intval($imonth);
		if (($month > 0) && ($month < 13)) {
			return $imonth;
		}
		redirect_header(XOOPS_URL . '/', 1, _MD_POPNUPBLOG_INVALID_DATE . '(MONTH)');
		exit();
	}

	function checkDate($year, $month, $date)
	{
		if (checkdate(intval($month), intval($date), intval($year))) {
			return $date;
		}
		redirect_header(XOOPS_URL . '/', 1, _MD_POPNUPBLOG_INVALID_DATE . '(ALL DATE) ' . intval($year) . "-" . intval($month) . "-" . intval($date));
		exit();
	}


	function isCompleteDate($d)
	{
		if (!empty($d['year'])) {
			if (checkdate(intval($d['month']), intval($d['date']), intval($d['year']))) {
				return TRUE;
			}
		}
		return FALSE;
	}

	function complementDate($d)
	{
		if (!checkdate(intval($d['month']), intval($d['date']), intval($d['year']))) {
			$time = time();
			$d['year'] = date('Y', $time);
			$d['month'] = sprintf('%02u', date('m', $time));
			$d['date'] = sprintf('%02u', date('d', $time));
			$d['hours'] = sprintf('%02u', date('H', $time));
			$d['minutes'] = sprintf('%02u', date('i', $time));
			$d['seconds'] = sprintf('%02u', date('s', $time));
		}
		//print($d['hours'].$d['minutes'].$d['seconds']);
		return $d;
	}


	function assign_message(&$tpl)
	{
		$all_constants_ = get_defined_constants();
		foreach ($all_constants_ as $key => $val) {
			if (preg_match("/^_(MB|MD|AM|MI)_BMSURVEY_(.)*$/", $key) || preg_match("/^BMSURVEY_(.)*$/", $key)) {
				if (is_array($tpl)) {
					$tpl[$key] = $val;
				} else if (is_object($tpl)) {
					$tpl->assign($key, $val);
				}
			}
		}
	}

}
?>
