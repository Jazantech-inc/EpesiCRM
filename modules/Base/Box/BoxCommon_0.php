<?php
/**
 * Box class.
 *
 * This class provides basic container for other modules, with smarty as template engine.
 * 
 * @author Paul Bukowski <pbukowski@telaxus.com>
 * @copyright Copyright &copy; 2008, Janusz Tylek
 * @license MIT
 * @version 1.0
 * @package epesi-base
 * @subpackage box
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class Base_BoxCommon extends ModuleCommon {
    public static $override_box_main = null;
	public static $ini_file = null;
	public static function get_ini_file() {
		if (Base_BoxCommon::$ini_file)
			$ini = Base_BoxCommon::$ini_file;
		elseif(file_exists($ini = Base_ThemeCommon::get_template_file('Base_Box','default.ini')))
			return $ini;
		else
			$ini = 'modules/Base/Box/default.ini';

		return file_exists($ini) ?$ini:null;
	}
	public static function get_main_module_name() {
		$ini = self::get_ini_file();
		if(!$ini) {
			print(__('Unable to read Base_Box.ini file! Please create one, or change theme.'));
			return;
		}
		$containers = parse_ini_file($ini,true);
		return $containers['main']['module'];
	}

	/*
	 * TODO: $parent_module seems to be unused - remove it
	 */
	public static function create_href_array($parent_module,$module,$function=null,array $arguments=null, array $constructor_args=null) {
		if(!isset($_SESSION['client']['base_box_hrefs']))
			$_SESSION['client']['base_box_hrefs'] = array();
		$hs = & $_SESSION['client']['base_box_hrefs'];

		$r=array('m'=>$module);
		if(isset($arguments))
			$r['a']=$arguments;
		if(isset($constructor_args))
			$r['c']=$constructor_args;
		if(isset($function))
			$r['f']=$function;
			
		$md = md5(serialize($r));
		$hs[$md] = $r;

		return array('box_main_href'=>$md);
	}
	
	public static function create_href($parent_module,$module,$function=null,array $arguments=null,array $constructor_args=null,array $other_href_args=array()) {
		return Module::create_href(array_merge($other_href_args,Base_BoxCommon::create_href_array($parent_module, $module, $function, $arguments, $constructor_args)));
	}

	public static function create_href_js($parent_module,$module,$function=null,array $arguments=null,array $constructor_args=null,array $other_href_args=array()) {
		return Module::create_href_js(array_merge($other_href_args,Base_BoxCommon::create_href_array($parent_module, $module, $function, $arguments, $constructor_args)));
	}
	
	public static function location($module,$function=null,array $arguments=null,array $constructor_args=null,array $other_href_args=array()) {
		return location(array_merge($other_href_args,Base_BoxCommon::create_href_array(null, $module, $function, $arguments, $constructor_args)));	
	}
	
	public static function push_module($module = null, $func = null, $args = null, $constr_args = null, $name = null) {
        self::_base_box_instance()->push_main($module, $func, $args, $constr_args, $name);
        return false;
    }
    
    public static function pop_main() {
        self::_base_box_instance()->pop_main();
    }
    
    public static function pop_main_href() {
        return self::main_module_instance()->create_callback_href(array('Base_BoxCommon', 'pop_main'));
    }
	
	public static function update_version_check_indicator($force=false) {
		$version_no = __('version %s',array(EPESI_VERSION));
		if (CHECK_VERSION && Base_EpesiStoreInstall::is_installed()) {
			load_js('modules/Base/Box/check_for_new_version.js');
			if ($force) eval_js('jq("#epesi_new_version").attr("done","0");');
			eval_js('check_for_new_version();');
			$version_no = '<span id="epesi_new_version">'.Utils_TooltipCommon::create($version_no, __('Checking if there are updates available...'), false).'</span>';
			if (isset($_REQUEST['go_to_epesi_store_for_updates'])) {
				Base_BoxCommon::push_module('Base_EpesiStore', 'admin');
				return;
			}
		}
		return $version_no;
	}

    /**
     * Get instance of module that is currently on top.
     * @return Module
     */
    public static function main_module_instance() {
        return self::_base_box_instance()->get_main_module();
    }

    private static function _base_box_instance() {
        $x = ModuleManager::get_instance('/Base_Box|0');
        if (!$x)
            throw new Exception('There is no base box module instance');
        return $x;
    }
}

Module::register_method("create_main_href",array("Base_BoxCommon","create_href"));
Module::register_method("create_main_href_js",array("Base_BoxCommon","create_href_js"));

?>
