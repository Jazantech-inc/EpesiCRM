<?php
/**
 * @author Paul Bukowski <pbukowski@telaxus.com>
 * @copyright Copyright &copy; 2006, Janusz Tylek
 * @version 1.0
 * @license MIT
 * @package epesi-firstrun
 * @subpackage
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class FirstRun extends Module {
	private $ini;

	public function body() {
        // init lang from install process
        $install_lang_code = & $_GET['install_lang'];
        if (isset($install_lang_code)) {
            // set anonymous setup to true at very first run to allow use admin tools.
            Variable::set('anonymous_setup', true);
            //
            Variable::set('default_lang', $install_lang_code);
            Epesi::redirect('index.php');
            return;
        }
        Base_LangCommon::load();
        
		$th = $this->init_module(Base_Theme::module_name());
		ob_start();
		print('<center>');
        $post_install = & $_SESSION['first-run_post-install'];
		if (!empty($post_install)) {
			foreach($post_install as $i=>$v) {
				$i = str_replace('/','_',$i);
				ModuleManager::include_install($i);
				$f = array($i.'Install','post_install');
				$fs = array($i.'Install','post_install_process');
				if(!is_callable($f) || !is_callable($fs)) {
					unset($post_install[$i]);
					continue;
				}
				$ret = call_user_func($f);
				$form = $this->init_module(Libs_QuickForm::module_name(),null,$i);
				$form->addElement('header',null,__('Post installation of %s', array(str_replace('_','/',$i))));
				$form->add_array($ret);
				$form->addElement('submit',null,'OK');
				if($form->validate()) {
					$form->process($fs);
					unset($post_install[$i]);
				} else {
					$form->display();
					break;
				}
			}
			if(ModuleManager::is_installed('Base')>=0 && empty($post_install)) {
				Variable::set('default_module','Base_Box');
				Epesi::redirect();
			}
		}
        if (empty($post_install) && ModuleManager::is_installed('Base') < 0) {

			$wizard = $this->init_module(Utils_Wizard::module_name());
			/////////////////////////////////////////////////////////////
			$this->ini = parse_ini_file('modules/FirstRun/distros.ini',true);
			if (count($this->ini)>1) {
				$f = & $wizard->begin_page();
				$f->addElement('header', null, __('Welcome to EPESI first run wizard'));
				$f->setDefaults(array('setup_type'=>key($this->ini)));
				foreach($this->ini as $name=>$pkgs) {
					switch ($name) {
						case 'CRM installation': $label = __('CRM installation'); break;
						case 'CRM and Sales Opportunity': $label = __('CRM and Sales Opportunity'); break;
						case 'CRM and Bug Tracker installation': $label = __('CRM and Bug Tracker installation'); break;
						default: $label = $name.' (* missing translation)'; break;
					}
					$f->addElement('radio', 'setup_type', '', $label, $name);
				}
				$f->addElement('html','<tr><td colspan=2><br /><strong>If you are not sure which package to choose select CRM Installation.<br>You can customize your installation later.</strong><br><br></td></tr>');
				$wizard->next_page();
			}

			/////////////////////////////////////////////////////////////////
			$f = $wizard->begin_page('simple_user');
			$f->addElement('header', null, __('Please enter administrator user login and password'));

			$f->addElement('text', 'login', __('Login'));
			$f->addRule('login', __('A username must be between 3 and 32 chars'), 'rangelength', array(3,32));
			$f->addRule('login', __('Field required'), 'required');

			$f->addElement('text', 'mail', __('E-mail'));
			$f->addRule('mail', __('Field required'), 'required');
			$f->addRule('mail', __('Invalid e-mail address'), 'email');

			$f->addElement('password', 'pass', __('Password'));
			$f->addElement('password', 'pass_c', __('Confirm Password'));
			$f->addRule('pass', __('Field required'), 'required');
			$f->addRule('pass_c', __('Field required'), 'required');
			$f->addRule(array('pass','pass_c'), __('Passwords don\'t match'), 'compare');
			$f->addRule('pass', __('Your password must be longer then 5 chars'), 'minlength', 5);

			$wizard->next_page();

			/////////////////////////////////////////////////////
			$f = $wizard->begin_page('simple_mail');

			$f->addElement('header',null, __('Mail settings'));
			$f->addElement('html','<tr><td colspan=2>'.__('If you are on a hosted server it probably should stay as it is now.').'</td></tr>');
			$f->addElement('select','mail_method', __('Choose method'), array('smtp'=>__('remote smtp server'), 'mail'=>__('local php.ini settings')));
			$f->setDefaults(array('mail_method'=>'mail'));

			$wizard->next_page(array($this,'choose_mail_method'));

			//////////////////////
			$f = $wizard->begin_page('simple_mail_smtp');

			$f->addElement('header',null, __('Mail settings'));
			$f->addElement('text','mail_host', __('SMTP host address'));
			$f->addRule('mail_host', __('Field required'),'required');

			$f->addElement('header',null, __('If your server needs authorization...'));
			$f->addElement('text','mail_user', __('Login'));
			$f->addElement('password','mail_password', __('Password'));

			$wizard->next_page();

			////////////////////////////////////////////////////////////
			$f = $wizard->begin_page('setup_warning');
			$f->addElement('header', null, __('Warning'));
			$f->addElement('html','<tr><td colspan=2><br />' . __('Setup will now check for available modules and will install them.') . '<br>' . __('This operation may take several minutes.') . '<br><br></td></tr>');
			$wizard->next_page();

			/////////////////////////////////////////
			$this->display_module($wizard, array(array($this,'done')));
		}
		print('</center>');
		$th->assign('wizard',ob_get_clean());
		$th->display();
	}

	public function choose_mail_method($d) {
		if($d['mail_method']=='mail') return 'setup_warning';
		return 'simple_mail_smtp';
	}

	public function done($d) {
		@set_time_limit(0);
		if (count($this->ini)==1) {
			$pkgs = reset($this->ini);
			$pkgs = $pkgs['package'];
		} else
			$pkgs = isset($this->ini[$d[0]['setup_type']]['package'])?$this->ini[$d[0]['setup_type']]['package']:array();
		
		$t = microtime(true);
		epesi_log(date('Y-m-d H:i:s').': installing "Base" ...'."\n",'firstrun.log');
		if(!ModuleManager::install('Base',null,false)) {
			print('Unable to install Base module pack.');
			return false;
		}
		epesi_log(date('Y-m-d H:i:s').': done ('.(microtime(true)-$t)."s).\n",'firstrun.log');

		$t = microtime(true);
		epesi_log(date('Y-m-d H:i:s').': creating admin user ...'."\n",'firstrun.log');
		if(!Base_UserCommon::add_user($d['simple_user']['login'])) {
		    	print('Unable to create user');
		    	return false;
		}

		$user_id = Base_UserCommon::get_user_id($d['simple_user']['login']);
		if($user_id===false) {
		    print('Unable to get admin user id');
		    return false;
		}

		if(!DB::Execute('INSERT INTO user_password(user_login_id,password,mail) VALUES(%d,%s, %s)', array($user_id, md5($d['simple_user']['pass']), $d['simple_user']['mail']))) {
		   	print('Unable to set user password');
		    	return false;
		}

		if(!Base_UserCommon::change_admin($user_id, 2)) {
			print('Unable to update admin account data (groups).');
			return false;
		}

		Acl::set_user($user_id, true);

		Variable::set('anonymous_setup',false);
		epesi_log(date('Y-m-d H:i:s').': done ('.(microtime(true)-$t)."s).\n",'firstrun.log');

		$t = microtime(true);
		epesi_log(date('Y-m-d H:i:s').': setting mail server ...'."\n",'firstrun.log');
		$method = $d['simple_mail']['mail_method'];
		Variable::set('mail_method', $method);
		Variable::set('mail_from_addr', $d['simple_user']['mail']);
		Variable::set('mail_from_name', $d['simple_user']['login']);
		if($method=='smtp') {
			Variable::set('mail_host', $d['simple_mail_smtp']['mail_host']);
			if($d['simple_mail_smtp']['mail_user']!=='' && $d['simple_mail_smtp']['mail_user']!=='')
				$auth = true;
			else
				$auth = false;
			Variable::set('mail_auth', $auth);
			if($auth) {
				Variable::set('mail_user', $d['simple_mail_smtp']['mail_user']);
				Variable::set('mail_password', $d['simple_mail_smtp']['mail_password']);
			}
		}
		epesi_log(date('Y-m-d H:i:s').': done ('.(microtime(true)-$t)."s).\n",'firstrun.log');

		$t = microtime(true);
		epesi_log(date('Y-m-d H:i:s').': Installing modules ...'."\n",'firstrun.log');
		foreach($pkgs as $p) {
		    if(!is_dir('modules/'.$p)) continue;
			$t2 = microtime(true);
			epesi_log(' * '.date('Y-m-d H:i:s').' - '.$p.' (','firstrun.log');
			if(!ModuleManager::install(str_replace('/','_',$p),null,false)) {
				print('<b>Unable to install '.str_replace('_','/',$p).' module.</b>');
			}
			epesi_log((microtime(true)-$t2)."s)\n",'firstrun.log');
		}
		epesi_log(date('Y-m-d H:i:s').': done ('.(microtime(true)-$t)."s).\n",'firstrun.log');


		$t = microtime(true);
		epesi_log(date('Y-m-d H:i:s').': Refreshing cache of modules ...'."\n",'firstrun.log');
		ModuleManager::create_load_priority_array();
		Base_SetupCommon::refresh_available_modules();
		epesi_log(date('Y-m-d H:i:s').': done ('.(microtime(true)-$t)."s).\n",'firstrun.log');

		$t = microtime(true);
		epesi_log(date('Y-m-d H:i:s').': Creating cache of template files ...'."\n",'firstrun.log');
		Base_ThemeCommon::create_cache();
		epesi_log(date('Y-m-d H:i:s').': done ('.(microtime(true)-$t)."s).\n",'firstrun.log');

		$t = microtime(true);
		epesi_log(date('Y-m-d H:i:s').': Updating translation files ...'."\n",'firstrun.log');
		Base_LangCommon::update_translations();
		epesi_log(date('Y-m-d H:i:s').': done ('.(microtime(true)-$t)."s).\n",'firstrun.log');

		$processed = ModuleManager::get_processed_modules();

        $_SESSION['first-run_post-install'] = $processed['install'];
		location();
	}

}

?>
