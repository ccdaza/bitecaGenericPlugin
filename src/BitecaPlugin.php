<?php
namespace BitecaGenericPlugin;

use GuzzleHttp\Client;

class BitecaPlugin {

    public $context;
    public $request;
    public $tpl;
    public $guzzle;
    public $token;
    public $error;
    public $pluginName;
    public $_hooks;

    public function __construct(){
        
    }
	
	public function vamos(){
		return "vamos";
	}

    public function getDisplayName(){
        return __('plugins.generic.'.$this->getName().'.displayName');
    }

    public function getDescription(){
        return __('plugins.generic.'.$this->getName().'.description');
    }

    public function register($category, $path, $mainContextId = null){
        $success = parent::register($category, $path, $mainContextId);
        if ($success && $this->getEnabled($mainContextId)){
            $this->getToken();
            if($this->token != ''){
                foreach ($this->_hooks as $hook){
                    HookRegistry::register($hook["hook"], $hook["method"]);
                }
            }
        }

        return $success;
    }

    public function getToken(){
        $clientID = $this->getSetting($this->context->getId(), 'client_id');
        $token = $this->getSetting($this->context->getId(), 'token');

        if($clientID != '' && $token != ''){
            try{
                $response = $this->guzzle->post('http://www.apibiteca.cloudbiteca.com/oauth/token', [
                    'form_params' => [
                        'grant_type' => 'client_credentials',
                        'client_id' => $clientID,
                        'client_secret' => $token,
                        'scope' => '',
                    ],
                ]);
                $this->token = json_decode((string) $response->getBody(), true)["access_token"];
                $this->getTpl();
            }catch(Exception $e){
                print("<pre style='display: none'>");
                print_r($e);
                print("</pre>");
                $this->error = "No fue posible cargar la información, intentelo mas tarde (1)";
            }
        }

    }

    public function getTpl(){

        try{
            $response = $this->guzzle->get('http://www.apibiteca.cloudbiteca.com/api/'.$this->tplRoute, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer '.$this->token,
                ],
            ]);

            $this->tpl = json_decode((string) $response->getBody(), true)["html"];
        }catch(Exception $e){
            print("<pre style='display: none'>");
            print_r($e);
            print("</pre>");
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
        $router = $request->getRouter();
        import('lib.pkp.classes.linkAction.request.AjaxModal');
        return array_merge($this->getEnabled() ? array(
            new LinkAction(
                'settings',
                new AjaxModal(
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

    function manage($args, $request) {
        switch ($request->getUserVar('verb')) {
            case 'settings':

                $context = $request->getContext();

                AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON,  LOCALE_COMPONENT_PKP_MANAGER);
                $templateMgr = TemplateManager::getManager($request);
                $templateMgr->register_function('plugin_url', array($this, 'smartyPluginUrl'));

                $this->import('biteca/BitecaPluginForm');
                $form = new inc($this, $context->getId());

                if ($request->getUserVar('save')) {
                    $form->readInputData();
                    if ($form->validate()) {
                        $form->execute();
                        return new JSONMessage(true);
                    }
                } else {
                    $form->initData();
                }

                return new JSONMessage(true, $form->fetch($request));
        }
        return parent::manage($args, $request);
    }

}