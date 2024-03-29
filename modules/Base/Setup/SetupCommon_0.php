<?php
/**
 * @author Paul Bukowski <pbukowski@telaxus.com>
 * @copyright Copyright &copy; 2008, Janusz Tylek
 * @license MIT
 * @version 1.0
 * @package epesi-base
 * @subpackage setup
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class Base_SetupCommon extends ModuleCommon {
	public static function body_access() {
		return self::admin_access();
	}

	public static function admin_access() {
	    if(DEMO_MODE) return false;
		if (Variable::get('anonymous_setup')) return true;
		return Base_AclCommon::i_am_admin();
	}

	public static function admin_access_levels() {
		return false;
	}

	public static function admin_caption() {
		if (ModuleManager::is_installed('Base_EpesiStore')>=0
                && Base_EpesiStoreCommon::admin_access()) return null;
		return array('label'=>__('Modules Administration'), 'section'=>__('Server Configuration'));
	}
    
    public static function is_simple_setup() {
        return Variable::get('simple_setup');
    }
    
    public static function set_simple_setup($value) {
        Variable::set('simple_setup', $value);
    }
	
	public static function refresh_available_modules() {
		$module_dirs = ModuleManager::list_modules();
		DB::Execute('TRUNCATE TABLE available_modules');
		foreach($module_dirs as $name => $v)
			foreach($v as $ver => $u) 
				DB::Execute('INSERT INTO available_modules VALUES(%s, %d, %s)',array($name,$ver,$u));
		return $module_dirs;
	}
    
    public static function is_store_visible() {
        $ret = Variable::get('base_setup_store_enabled', false);
        return ($ret === '') ? true : ($ret ? true : false);
    }
    
    public static function set_store_visibility($enabled) {
        Variable::set('base_setup_store_enabled', $enabled ? true : false);
    }
}
?>
