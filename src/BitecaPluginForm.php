<?php
namespace BitecaGenericPlugin;

import('lib.pkp.classes.form.Form');
class BitecaPluginForm extends \Form {
	
    public $_journalId;
    public $_plugin;
    public $data;
	
    function __construct($plugin, $journalId, $data) {
        $this->_journalId = $journalId;
        $this->_plugin = $plugin;
        $this->data = $data;
        parent::__construct($this->_plugin->getTemplateResource('adminForm.tpl'));
        $this->addCheck(new \FormValidator($this, 'token', 'required', 'plugins.generic.'.$this->_plugin->getName().'.manager.token.required'));
        $this->addCheck(new \FormValidator($this, 'client_id', 'required', 'plugins.generic.'.$this->_plugin->getName().'.manager.clientId.required'));
        $this->addCheck(new \FormValidatorPost($this));
        $this->addCheck(new \FormValidatorCSRF($this));
    }
    /**
     * Initialize form data.
     */
    function initData() {
        $this->_data = [
            'token' => $this->_plugin->getSetting($this->_journalId, 'token'),
            'client_id' => $this->_plugin->getSetting($this->_journalId, 'client_id')
        ];
      
				foreach($this->data as $key => $data){
					$this->_data[$key] = $this->_plugin->getSetting($this->_journalId, $key)
				}
			
    }
    /**
     * Assign form data to user-submitted data.
     */
    function readInputData() {
			$read = ['token', 'client_id'];
      
			foreach($this->data as $key => $data){
					$this->_data = $key;
				}
			
			$this->readUserVars($read);
    }
    /**
     * Fetch the form.
     * @copydoc Form::fetch()
     */
    function fetch($request, $template = null, $display = false) {
        $templateMgr = \TemplateManager::getManager($request);
        $templateMgr->assign('pluginName', $this->_plugin->getName());
        return parent::fetch($request);
    }
    /**
     * Save settings.
     */
    function execute() {
        $this->_plugin->updateSetting($this->_journalId, 'token', trim($this->getData('token'), "\"\';"), 'string');
        $this->_plugin->updateSetting($this->_journalId, 'client_id', trim($this->getData('client_id'), "\"\';"), 'string');
			
				foreach($this->data as $key => $data){
					
					$function = "clear".ucfirst($data["type"]);
					
					if(method_exists($this, $function)){
						$value = $this->$function($this->getData($key));
					}else{
						$value = $this->getData($key);
					}
					
					$this->updateSetting($key, $value, $data["type"]);
					
				}
    }
	
		function clearString($value){
			return trim($value, "\"\';");
		}
	
		function clearInt($value){
			return intval($value);
		}
	
		function clearBool($value){
			
	
		function clearJson($value){
			return json_encode($value);
		}
	
		function updateSetting($key, $value, $type){
			$this->_plugin->updateSetting($this->_journalId, $key, $value, $type);
		}
}