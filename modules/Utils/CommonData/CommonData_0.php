<?php
/**
 * @author Arkadiusz Bisaga <abisaga@telaxus.com>
 * @copyright Copyright &copy; 2006, Janusz Tylek
 * @version 1.0
 * @license MIT
 * @package epesi-utils
 * @subpackage CommonData
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class Utils_CommonData extends Module {
	/**
	 * For internal use only.
	 */
	public function admin() {
		if($this->is_back()) {
			if($this->parent->get_type()=='Base_Admin')
				$this->parent->reset();
			else
				location(array());
			return;
		}
		Base_ActionBarCommon::add('back',__('Back'),$this->create_back_href());

		$this->browse();
	}

	public function admin_array($name) {
		$this->browse($name);
	}

	/**
	 * For internal use only.
	 */
	public function edit($parent,$key=null){
		if ($this->is_back()) return false;

		$id = Utils_CommonDataCommon::get_id($parent);
		if (!$id) {
			print(__('No such array'));
			return false;
		}

		$f = $this->init_module(Libs_QuickForm::module_name(),null,'edit');
		$f->addElement('header', null, ($key===null)?__('New node'):__('Edit node'));
		$f->add_table('utils_commondata_tree',array(
						array(
                            'name'=>'akey',
                            'label'=>__('Key'),
							'rule'=>array(
                                array('type'=>'callback','param'=>array($parent,$key),
									'func'=>array($this,'check_key'),
									'message'=>__('Specified key already exists')),
							    array('type'=>'callback','param'=>array($parent,$key),
									'func'=>array($this,'check_key2'),
									'message'=>__('Specified contains invalid character "/"'))
                            )
						),
						array('name'=>'value','label'=>__('Value'))
						));
		if($key!==null) {
			$value=Utils_CommonDataCommon::get_value($parent.'/'.$key);
			$f->setDefaults(array('akey'=>$key,'value'=>$value));
		}

		if ($f->validate()) {
			$submited = $f->exportValues();
			if($key!==null)
				Utils_CommonDataCommon::rename_key($parent,$key,$submited['akey']);
			Utils_CommonDataCommon::set_value($parent.'/'.$submited['akey'],$submited['value']);
			return false;
		}
		Utils_ShortcutCommon::add(array('Ctrl','S'), 'function(){'.$f->get_submit_form_js().'}');
		Base_ActionBarCommon::add('save',__('Save'),$f->get_submit_form_href());
		Base_ActionBarCommon::add('back',__('Cancel'),$this->create_back_href());
		$f->display();
		return true;
	}

	public function check_key($new_key,$arr) {
		if($arr[1]==$new_key) return true;
		return Utils_CommonDataCommon::get_id($arr[0].'/'.$new_key)===false;
	}

	public function check_key2($new_key,$arr) {
	    return strpos($new_key,'/')===false;
	}

	/**
	 * For internal use only.
	 */
	public function browse($name='',$root=true){
		if($this->is_back()) return false;
		
		if (isset($_REQUEST['node_position'])) {
			list($node_id, $position) = $_REQUEST['node_position'];

			Utils_CommonDataCommon::change_node_position($node_id, $position);
		}

		$gb = $this->init_module(Utils_GenericBrowser::module_name(),null,'browse'.md5($name));

		$gb->set_table_columns(array(
						array('name'=>__('Position'),'width'=>5, 'order'=>'position'),
						array('name'=>__('Key'),'width'=>20, 'order'=>'akey','search'=>1,'quickjump'=>'akey'),
						array('name'=>__('Value'),'width'=>20, 'order'=>'value','search'=>1)
					));

		print('<h2>'.$name.'</h2><br>');
		$ret = Utils_CommonDataCommon::get_translated_array($name,'position',true);
		foreach($ret as $k=>$v) {
			$gb_row = $gb->get_new_row();
			$gb_row->add_data($v['position'],$k,$v['value']); // ****** CommonData value translation
			$gb_row->add_action($this->create_callback_href(array($this,'browse'),array($name.'/'.$k,false)),'View');
			if(!$v['readonly']) {
				$gb_row->add_action($this->create_callback_href(array($this,'edit'),array($name,$k)),'Edit');
				$gb_row->add_action($this->create_confirm_callback_href(__('Delete array').' \''.Epesi::escapeJS($name.'/'.$k,false).'\'?',array('Utils_CommonData','remove_array'), array($name.'/'.$k)),'Delete');
			}
			$node_id = $v['id'];
			$gb_row->add_action('class="move-handle"','Move', __('Drag to change node order'), 'move-up-down');
			$gb_row->set_attrs("node=\"$node_id\" class=\"sortable\"");
		}
		
		$gb->set_default_order(array(__('Position') => 'ASC'));
		//$this->display_module($gb);
		$this->display_module($gb,array(true),'automatic_display');

		// sorting
		load_js($this->get_module_dir() . 'sort_nodes.js');
		$table_md5 = md5($gb->get_path());
		eval_js("utils_commondata_sort_nodes_init(\"$table_md5\")");
		
		Base_ActionBarCommon::add('settings',__('Reset Order By Key'),$this->create_callback_href(array('Utils_CommonDataCommon','reset_array_positions'),$name));
		Base_ActionBarCommon::add('add',__('Add array'),$this->create_callback_href(array($this,'edit'),$name));
		if(!$root)
			Base_ActionBarCommon::add('back',__('Back'),$this->create_back_href());
		return true;
	}

	/**
	 * For internal use only.
	 */
	public static function remove_array($name){
		Utils_CommonDataCommon::remove($name);
	}

}

?>
