<?php
/** @var modX $modx */
switch ($modx->event->name) {
    case "OnLoadWebDocument":
        $resource = $modx->resource;
        $corePath = $modx->getOption('kmv_core_path', array(), $modx->getOption('core_path') . 'components/kmv/');
        $kmv = $modx->getService('kmv', 'kmv', $corePath . 'model/');
        if (!$kmv) {
            $modx->log(xPDO::LOG_LEVEL_ERROR, "kmv: Не могу инициализировать класс KMV");
        }else{
            $kmv->initialize($modx->context->key);
            if($resource->get("template") != 2){
                // проверяем авторизацию
                if(!$kmv->getSession()){
                    $url = $modx->makeUrl(1);
                    $modx->sendRedirect($url);
                }else{
                    $placeholders = $kmv->getPlaceholders();
                    $modx->toPlaceholders($placeholders,'kmv');
                }
            }else{
                if($kmv->getSession()){
                    $url = $modx->makeUrl($modx->getOption("site_start"));
                    $modx->sendRedirect($url);
                }
            }
        }
        break;
}