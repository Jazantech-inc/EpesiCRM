<?php
/**
 * LoginInstall class.
 *
 * This class provides initialization data for Login module.
 *
 * @author Paul Bukowski <pbukowski@telaxus.com>
 * @copyright Copyright &copy; 2008, Janusz Tylek
 * @license MIT
 * @version 1.0
 * @package epesi-base
 * @subpackage user-login
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class Base_User_LoginInstall extends ModuleInstall {
	public function install() {
		$ret = DB::CreateTable('user_password',"user_login_id I KEY, password C(256) NOTNULL, mail C(255) NOTNULL",array('constraints' => ', FOREIGN KEY (user_login_id) REFERENCES user_login(id)'));
		if($ret===false) {
			print('Invalid SQL query - user_password table install');
			return false;
		}
		$ret = DB::CreateTable('user_autologin',"user_login_id I NOTNULL, autologin_id C(32) NOTNULL, last_log T, description C(64)",array('constraints' => ', FOREIGN KEY (user_login_id) REFERENCES user_login(id)'));
		if($ret===false) {
			print('Invalid SQL query - user_autologin table install');
			return false;
		}
		$ret = DB::CreateTable('user_login_ban',"failed_on I4, from_addr C(32)");
		if($ret===false) {
			print('Invalid SQL query - user_login_ban table install');
			return false;
		}
		$ret = DB::CreateTable('user_reset_pass',"user_login_id I NOTNULL, hash_id C(32) NOTNULL, created_on T DEFTIMESTAMP",array('constraints' => ', FOREIGN KEY (user_login_id) REFERENCES user_login(id)'));
		if($ret===false) {
			print('Invalid SQL query - user_autologin table install');
			return false;
		}
		Variable::set('host_ban_time',300);
        Variable::set('host_ban_nr_of_tries', 3);
        Variable::set('host_ban_by_login', 0);
		Base_ThemeCommon::install_default_theme(Base_User_LoginInstall::module_name());
		return true;
	}

	public function uninstall() {
		Base_ThemeCommon::uninstall_default_theme(Base_User_LoginInstall::module_name());
		Variable::delete('host_ban_time');
        Variable::delete('host_ban_nr_of_tries');
        Variable::delete('host_ban_by_login');
		return DB::DropTable('user_password') && DB::DropTable('user_login_ban') && DB::DropTable('user_autologin') && DB::DropTable('user_reset_pass');
	}

	public function version() {
		return array('1.0.0');
	}
	public function requires($v) {
		return array(
			array('name'=>Libs_QuickFormInstall::module_name(),'version'=>0),
			array('name'=>Base_UserInstall::module_name(),'version'=>0),
			array('name'=>Base_ThemeInstall::module_name(),'version'=>0),
			array('name'=>Base_LangInstall::module_name(),'version'=>0),
			array('name'=>Base_ThemeInstall::module_name(),'version'=>0),
			array('name'=>Base_MailInstall::module_name(), 'version'=>0));
	}

	public static function simple_setup() {
		return __('EPESI Core');
	}
}

?>
