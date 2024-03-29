<?php
/**
 * Fax abstraction layer module
 * @author pbukowski@telaxus.com
 * @copyright Janusz Tylek
 * @license MIT
 * @version 0.1
 * @package epesi-CRM
 * @subpackage Fax
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class CRM_FaxCommon extends ModuleCommon {
	public static function attachment_getters() {
		if(Base_AclCommon::check_permission('Fax - Send'))
			return array(_M('Fax')=>array('func'=>'fax_file','icon'=>null));
	}
	
	public static function fax_file($f,$oryg) {
		$tmp = self::Instance()->get_data_dir().$oryg;
		copy($f,$tmp);

		Base_BoxCommon::push_module(CRM_Fax::module_name(),'send',$tmp);
	}
	
	public static function rpicker_contact_format($e) {
		return CRM_ContactsCommon::contact_format_default($e,true);
	}

	public static function rpicker_company_format($e) {
		return $e['company_name'];
	}

	public static function menu() {
		if(!Acl::is_user() || !Base_AclCommon::check_permission('Fax - Browse')) return array();
		return array(_M('CRM')=>array('__submenu__'=>1,_M('Fax')=>array()));
	}
	

}

?>