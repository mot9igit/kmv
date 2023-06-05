<?php

class kmv
{
    /** @var modX $modx */
    public $modx;


    /**
     * @param modX $modx
     * @param array $config
     */
    function __construct(modX &$modx, array $config = [])
    {
        $this->modx =& $modx;
        $corePath = $this->modx->getOption('kmv_core_path', $config, $this->modx->getOption('core_path') . 'components/kmv/');
        $assetsUrl = $this->modx->getOption('kmv_assets_url', $config, $this->modx->getOption('assets_url') . 'components/kmv/');
        $assetsPath = $this->modx->getOption('kmv_assets_path', $config, $this->modx->getOption('base_path') . 'assets/components/kmv/');

        $this->config = array_merge([
            'corePath' => $corePath,
            'assetsPath' => $assetsPath,
            'modelPath' => $corePath . 'model/',
            'processorsPath' => $corePath . 'processors/',
            'apiUrl' => $this->modx->getOption("kmv_api_url"),

            'connectorUrl' => $assetsUrl . 'connector.php',
            'actionUrl' => $assetsUrl . 'action.php',
            'assetsUrl' => $assetsUrl,
            'cssUrl' => $assetsUrl . 'css/',
            'jsUrl' => $assetsUrl . 'js/',
        ], $config);

        $this->modx->addPackage('kmv', $this->config['modelPath']);
        $this->modx->lexicon->load('kmv:default');

        if ($this->pdoTools = $this->modx->getService('pdoFetch')) {
            $this->pdoTools->setConfig($this->config);
        }
    }

    /**
     * Initializes component into different contexts.
     *
     * @param string $ctx The context to load. Defaults to web.
     * @param array $scriptProperties Properties for initialization.
     *
     * @return bool
     */
    public function initialize($ctx = 'web', $scriptProperties = array())
    {
        if (isset($this->initialized[$ctx])) {
            return $this->initialized[$ctx];
        }
        $this->config = array_merge($this->config, $scriptProperties);
        $this->config['ctx'] = $ctx;
        $this->modx->lexicon->load('shoplogistic:default');

        if ($ctx != 'mgr' && (!defined('MODX_API_MODE') || !MODX_API_MODE) && !$this->config['json_response']) {
            $config = $this->pdoTools->makePlaceholders($this->config);

            // Register CSS
            $css = trim($this->modx->getOption('kmv_frontend_css'));
            if (!empty($css) && preg_match('/\.css/i', $css)) {
                if (preg_match('/\.css$/i', $css)) {
                    $css .= '?v=' . substr(md5($this->config['version']), 0, 10);
                }
                $this->modx->regClientCSS(str_replace($config['pl'], $config['vl'], $css));
            }

            // Register JS
            $js = trim($this->modx->getOption('kmv_frontend_js'));
            if (!empty($js) && preg_match('/\.js/i', $js)) {
                if (preg_match('/\.js$/i', $js)) {
                    $js .= '?v=' . substr(md5($this->config['version']), 0, 10);
                }
                $this->modx->regClientScript(str_replace($config['pl'], $config['vl'], $js));


                $js_setting = array(
                    'cssUrl' => $this->config['cssUrl'] . 'web/',
                    'jsUrl' => $this->config['jsUrl'] . 'web/',
                    'actionUrl' => $this->config['actionUrl'],
                    'ctx' => $ctx
                );

                $data = json_encode($js_setting, true);
                $this->modx->regClientStartupScript(
                    '<script>kmvConfig = ' . $data . ';</script>',
                    true
                );
            }
        }
        $this->initialized[$ctx] = true;
        return true;
    }

    /**
     * Handle frontend requests with actions
     *
     * @param $action
     * @param array $data
     *
     * @return array|bool|string
     */
    public function handleRequest($action, $data = array())
    {
        $ctx = !empty($data['ctx'])
            ? (string)$data['ctx']
            : 'web';
        if ($ctx != 'web') {
            $this->modx->switchContext($ctx);
        }
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
        $this->initialize($ctx, array('json_response' => $isAjax));
        switch ($action) {
            case 'profile/auth':
                if($data['username'] && $data['password']){
                    $response = $this->auth($data['username'], $data['password']);
                    if($response['success']){
                        $res['redirect'] = $this->modx->makeUrl($this->modx->getOption("site_start"));
                        return $this->success("Вы успешно авторизовались", $res);
                    }else{
                        return $this->error("", $response);
                    }
                }
                break;
            case 'profile/logout':
                $res = array();
                $res['redirect'] = $this->modx->makeUrl(1);
                $this->deleteSession();
                return $this->success("Вы успешно вышли", $res);
                break;
        }
    }

    public function getPlaceholders(){
        $pls = array();
        $pls["name"] = $_SESSION['kmv']['auth']['abonent']['VC_NAME'];
        $pls["login"] = $_SESSION['kmv']['auth']['abonent']['VC_CODE'];
        $pls["balance"] = 0;
        $pls["payment_date"] = 0;
        foreach($_SESSION['kmv']['auth']['accounts'] as $account){
            $pls["balance"] += floatval(str_replace(",", ".", $account['N_SUM_BAL']));
            $date = strtotime($account['D_ACCOUNTING_END']);
            if($pls["payment_date"] > $date || $pls["payment_date"] == 0){
                $pls["payment_date"] = $date;
            }
            $pls["account_id"] = $account['N_ACCOUNT_ID'];
        }
        $pls["balance"] = number_format($pls["balance"], 2, '.', ' ');
        return $pls;
    }

    public function getUserFiles($type){
        // type: contract, act, invoice
        $files = array();
        foreach($_SESSION['kmv']['auth']['accounts'] as $account){
            $account_id = $account['N_ACCOUNT_ID'];
            // scan folder
            $path = $this->modx->getOption("base_path")."assets/files/user_docs/{$account_id}/";
            if(!file_exists($path)){
                mkdir($path, 0755);
                return array();
            }else{
                $out = array();
                $files = scandir($path, SCANDIR_SORT_DESCENDING);
                foreach($files as $file){
                    $types = explode(",", $type);
                    foreach($types as $t){
                        if($t == 'contract'){
                            $prefix = "Договор";
                        }
                        if($t == 'act'){
                            $prefix = "Акт";
                        }
                        if($t == 'invoice'){
                            $prefix = "Счет";
                        }
                        $pos = strpos($file, $t.'_');
                        if ($pos === 0) {
                            $file_name = explode("_", $file);
                            $name = $prefix." №{$file_name[1]} от {$file_name[2]}";
                            $out[] = array(
                                "name" => $name,
                                "file" => "/assets/files/user_docs/{$account_id}/".$file
                            );
                        }
                    }
                }
                return $out;
            }
        }
    }

    public function auth($login, $pass){
        $provider = $this->getProvider();
        $data = array(
            'login' => $login,
            'pass' => $pass,
            'provider' => $provider
        );
        $response = $this->request("apilk/auth", $data);
        if($response['result']['bearer']){
            $this->setSession($response['result']['bearer']);
            unset($response['result']['bearer']);
            $this->saveData($response['result']);
        }
        return $response;
    }

    public function getProvider(){
        // определять будем на основе домена
        $this->modx->log(1, $this->modx->getOption("site_url"));
        return 'post';
    }

    public function logout(){
        $this->deleteSession();
    }

    public function setSession($bearer){
        $_SESSION['kmv']['bearer'] = $bearer;
    }

    public function getSession(){
        return $_SESSION['kmv']['bearer'];
    }

    public function deleteSession(){
        unset($_SESSION['kmv']);
    }

    public function saveData($data, $key = 'auth'){
        $_SESSION['kmv'][$key] = $data;
    }

    public function getData(){
        if($bearer = $this->getSession()){
            $response = $this->request("apilk/getdata", array("bearer" => $bearer));
            if($response['code'] == 99998){
                $this->deleteSession();
                $this->error("Ошибка авторизации", array("code" => 403));
            }else{
                $this->success("", $response);
            }
        }else{
            return $this->error("Ошибка авторизации", array("code" => 403));
        }
    }

    public function getAbonent(){
        if($bearer = $this->getSession()){
            $response = $this->request("apilk/getabonent", array("bearer" => $bearer));
            if($response['code'] == 99998){
                $this->deleteSession();
                $this->error("Ошибка авторизации", array("code" => 403));
            }else{
                $this->success("", $response);
            }
        }else{
            return $this->error("Ошибка авторизации", array("code" => 403));
        }
    }

    public function getPersons(){
        if($bearer = $this->getSession()){
            $response = $this->request("apilk/getpersons", array("bearer" => $bearer));
            if($response['code'] == 99998){
                $this->deleteSession();
                $this->error("Ошибка авторизации", array("code" => 403));
            }else{
                $this->success("", $response);
            }
        }else{
            return $this->error("Ошибка авторизации", array("code" => 403));
        }
    }

    public function getCompanies(){
        if($bearer = $this->getSession()){
            $response = $this->request("apilk/getcompanies", array("bearer" => $bearer));
            if($response['code'] == 99998){
                $this->deleteSession();
                $this->error("Ошибка авторизации", array("code" => 403));
            }else{
                $this->success("", $response);
            }
        }else{
            return $this->error("Ошибка авторизации", array("code" => 403));
        }
    }

    public function getAddresses(){
        if($bearer = $this->getSession()){
            $response = $this->request("apilk/getbasesubjectaddresses", array("bearer" => $bearer));
            if($response['code'] == 99998){
                $this->deleteSession();
                $this->error("Ошибка авторизации", array("code" => 403));
            }else{
                $this->success("", $response);
            }
        }else{
            return $this->error("Ошибка авторизации", array("code" => 403));
        }
    }

    public function getAccounts(){
        if($bearer = $this->getSession()){
            $response = $this->request("apilk/getaccounts", array("bearer" => $bearer));
            if($response['code'] == 99998){
                $this->deleteSession();
                $this->error("Ошибка авторизации", array("code" => 403));
            }else{
                $this->success("", $response);
            }
        }else{
            return $this->error("Ошибка авторизации", array("code" => 403));
        }
    }

    public function getAccount($account_id){
        if($bearer = $this->getSession()){
            $response = $this->request("apilk/getaccounts", array("n_account_id" => $account_id, "bearer" => $bearer));
            if($response['code'] == 99998){
                $this->deleteSession();
                $this->error("Ошибка авторизации", array("code" => 403));
            }else{
                $this->success("", $response);
            }
        }else{
            return $this->error("Ошибка авторизации", array("code" => 403));
        }
    }

    public function getGoods(){
        if($bearer = $this->getSession()){
            $response = $this->request("apilk/getgoods", array("bearer" => $bearer));
            if($response['code'] == 99998){
                $this->deleteSession();
                $this->error("Ошибка авторизации", array("code" => 403));
            }else{
                $this->success("", $response);
            }
        }else{
            return $this->error("Ошибка авторизации", array("code" => 403));
        }
    }

    public function getLastPays($account_id){
        if($bearer = $this->getSession()){
            $response = $this->request("apilk/getlastpays", array("n_account_id" => $account_id, "bearer" => $bearer));
            if($response['code'] == 99998){
                $this->deleteSession();
                $this->error("Ошибка авторизации", array("code" => 403));
            }else{
                $this->success("", $response);
            }
        }else{
            return $this->error("Ошибка авторизации", array("code" => 403));
        }
    }

    public function getOPay($account_id){
        if($bearer = $this->getSession()){
            $response = $this->request("apilk/getopay", array("n_account_id" => $account_id, "bearer" => $bearer));
            if($response['code'] == 99998){
                $this->deleteSession();
                $this->error("Ошибка авторизации", array("code" => 403));
            }else{
                $this->success("", $response);
            }
        }else{
            return $this->error("Ошибка авторизации", array("code" => 403));
        }
    }

    public function setOPay($account_id){
        if($bearer = $this->getSession()){
            $response = $this->request("apilk/setopay", array("n_account_id" => $account_id, "bearer" => $bearer));
            if($response['code'] == 99998){
                $this->deleteSession();
                $this->error("Ошибка авторизации", array("code" => 403));
            }else{
                $this->success("", $response);
            }
        }else{
            return $this->error("Ошибка авторизации", array("code" => 403));
        }
    }

    public function request($method, $data){
        $curl = curl_init();
        $url = $this->config['apiUrl'].$method.'?'.http_build_query($data);
        if($data['bearer']){
            $authorization = "Authorization: Bearer ".$data['bearer'];
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization ));
            unset($data['bearer']);
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        $result = curl_exec($curl);
        if ($errno = curl_errno($curl)) {
            $message = curl_strerror($errno);
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, "kmv: cURL error ({$errno}):\n {$message}");
        }
        curl_close($curl);
        return json_decode($result,1);
    }

    /**
     * This method returns an error of the request
     *
     * @param string $message A lexicon key for error message
     * @param array $data .Additional data, for example cart status
     * @param array $placeholders Array with placeholders for lexicon entry
     *
     * @return array|string $response
     */
    public function error($message = '', $data = array(), $placeholders = array())
    {
        $response = array(
            'success' => false,
            'message' => $message,
            'data' => $data,
        );

        return $this->config['json_response']
            ? json_encode($response)
            : $response;
    }


    /**
     * This method returns an success of the request
     *
     * @param string $message A lexicon key for success message
     * @param array $data .Additional data, for example cart status
     * @param array $placeholders Array with placeholders for lexicon entry
     *
     * @return array|string $response
     */
    public function success($message = '', $data = array(), $placeholders = array())
    {
        $response = array(
            'success' => true,
            'message' => $message,
            'data' => $data,
        );

        return $this->config['json_response']
            ? json_encode($response)
            : $response;
    }
}