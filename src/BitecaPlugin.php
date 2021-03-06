<?php
namespace BitecaGenericPlugin;

import('lib.pkp.classes.plugins.GenericPlugin');

use GuzzleHttp\Client;

class BitecaPlugin extends \GenericPlugin {

    public $context;
    public $request;
    public $tpl;
    public $tplRoute;
    public $guzzle;
    public $token;
    public $error;
    public $pluginName;
    public $_hooks;
    public $data;

    public function __construct($hooks, $tplRoute, $data){
        $this->request = $this->getRequest();
        $this->context = $this->request->getContext();
        $this->guzzle = new Client();
        $this->_hooks = $hooks;
        $this->tplRoute = $tplRoute;
        $this->data = $data;
    }

    public function getDisplayName(){
        return __('plugins.generic.'.$this->getName().'.displayName');
    }

    public function getDescription(){
        return __('plugins.generic.'.$this->getName().'.description');
    }

    public function register($category, $path, $mainContextId = null){
				if(!is_null($this->context)){
					
					$success = parent::register($category, $path, $mainContextId);
					if ($success && $this->getEnabled($this->context->getId()) && !is_null($this->context)){
							$this->getToken();
							if($this->token != ''){
									foreach ($this->_hooks as $hook){
											\HookRegistry::register($hook["hook"], $hook["method"]);
									}
							}
					}
				}

        return $success;
    }

    public function getToken(){

        $clientID = $this->getSetting($this->context->getId(), 'client_id');
        $token = $this->getSetting($this->context->getId(), 'token');

        if($clientID != '' && $token != ''){
            if(isset($_SESSION[$this->getName()]["token"]) && $_SESSION[$this->getName()]["client_id"] == $clientID && $_SESSION[$this->getName()]["hash"] == $token){
                $this->token =  $_SESSION[$this->getName()]["token"];
                $this->getTpl();
                return true;
            }else{

                $_SESSION[$this->getName()]["client_id"] = $clientID;
                $_SESSION[$this->getName()]["hash"] = $token;
                try{
                    $response = $this->guzzle->post('http://apibiteca.cloudbiteca.com/oauth/token', [
                        'form_params' => [
                            'grant_type' => 'client_credentials',
                            'client_id' => $clientID,
                            'client_secret' => $token,
                            'scope' => '',
                        ],
                    ]);
                    $this->token = json_decode((string) $response->getBody(), true)["access_token"];
                    $_SESSION[$this->getName()]["token"] = $this->token;
                    $this->getTpl();
                }catch(\Exception $e){
                    unset($_SESSION[$this->getName()]["token"]);
                    $this->error = "No fue posible cargar la información, intentelo mas tarde (1)";
                }
            }
        }

    }

    public function getTpl(){

        try{
            $response = $this->guzzle->get('http://apibiteca.cloudbiteca.com/api/'.$this->tplRoute, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer '.$this->token,
                ],
            ]);

            $this->tpl = json_decode((string) $response->getBody(), true)["html"];
        }catch(\Exception $e){
            $this->error = "No fue posible cargar la información, intentelo mas tarde (2)";
        }
    }

    function printError($hookName, $params){
        $smarty =& $params[1];
        $output =& $params[2];

        $output .= "<p>$this->error</p>";
        return false;
    }

    function getActions($request, $verb){
				if(!is_null($this->context)){
					$router = $request->getRouter();
					import('lib.pkp.classes.linkAction.request.AjaxModal');
					return array_merge($this->getEnabled() ? array(
							new \LinkAction(
									'settings',
									new \AjaxModal(
											$router->url($request, null, null, 'manage', null, array('verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic')),
											$this->getDisplayName()
									),
									__('manager.plugins.settings'),
									null
							),
					) : array(),
							parent::getActions($request, $verb)
					);
				}
    }

    function manage($args, $request) {
        switch ($request->getUserVar('verb')) {
            case 'settings':

                $context = $request->getContext();

                \AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON,  LOCALE_COMPONENT_PKP_MANAGER);
                $templateMgr = \TemplateManager::getManager($request);
                $templateMgr->register_function('plugin_url', array($this, 'smartyPluginUrl'));

                $form = new BitecaPluginForm($this, $context->getId(), $this->data);

                if ($request->getUserVar('save')) {
                    $form->readInputData();
                    if ($form->validate()) {
                        $form->execute();
                        return new \JSONMessage(true);
                    }
                } else {
                    $form->initData();
                }

                return new \JSONMessage(true, $form->fetch($request));
        }
        return parent::manage($args, $request);
    }

}