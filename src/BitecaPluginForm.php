<?php
import('lib.pkp.classes.form.Form');
class BitecaPluginForm extends \Form {
	
    public $_journalId;
    public $_plugin;
    public $data = [];
	
    function __construct($plugin, $journalId) {
        $this->_journalId = $journalId;
        $this->_plugin = $plugin;
        parent::__construct($this->_plugin->getTemplateResource('adminForm.tpl'));
        $this->addCheck(new FormValidator($this, 'token', 'required', 'plugins.generic.'.$this->_plugin->getName().'.manager.token.required'));
        $this->addCheck(new FormValidator($this, 'client_id', 'required', 'plugins.generic.'.$this->_plugin->getName().'.manager.clientId.required'));
        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }
    /**
     * Initialize form data.
     */
    function initData() {
        $required = [
            'token' => $this->_plugin->getSetting($this->_journalId, 'token'),
            'client_id' => $this->_plugin->getSetting($this->_journalId, 'client_id')
        ];
        $this->_data = array_merge($required, $this->data);
    }
    /**
     * Assign form data to user-submitted data.
     */
    function readInputData() {
        $this->readUserVars(['token', 'client_id']);
    }
    /**
     * Fetch the form.
     * @copydoc Form::fetch()
     */
    function fetch($request, $template = null, $display = false) {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('pluginName', $this->_plugin->getName());
        return parent::fetch($request);
    }
    /**
     * Save settings.
     */
    function execute() {
        $this->_plugin->updateSetting($this->_journalId, 'token', trim($this->getData('token'), "\"\';"), 'string');
        $this->_plugin->updateSetting($this->_journalId, 'client_id', trim($this->getData('client_id'), "\"\';"), 'string');
    }
}