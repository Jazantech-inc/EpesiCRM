<?php
/**
 * Uploads file
 * 
 * @author Paul Bukowski <pbukowski@telaxus.com>
 * @copyright Copyright &copy; 2006, Janusz Tylek
 * @version 1.0
 * @license MIT
 * @package epesi-utils
 * @subpackage file-uploader
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class Utils_FileUploadInstall extends ModuleInstall {

	public function install() {
		$this->create_data_dir();
		return true;
	}
	
	public function uninstall() {
		return true;
	}
	public function version() {
		return array("1.0");
	}
	
	public function requires($v) {
		return array(
			array('name'=>Libs_QuickFormInstall::module_name(),'version'=>0),
			array('name'=>Base_LangInstall::module_name(),'version'=>0));
	}
}

?>