<?php

class kmvOfficeItemCreateProcessor extends modObjectCreateProcessor
{
    public $objectType = 'kmvItem';
    public $classKey = 'kmvItem';
    public $languageTopics = ['kmv'];
    //public $permission = 'create';


    /**
     * @return bool
     */
    public function beforeSet()
    {
        $name = trim($this->getProperty('name'));
        if (empty($name)) {
            $this->modx->error->addField('name', $this->modx->lexicon('kmv_item_err_name'));
        } elseif ($this->modx->getCount($this->classKey, ['name' => $name])) {
            $this->modx->error->addField('name', $this->modx->lexicon('kmv_item_err_ae'));
        }

        return parent::beforeSet();
    }

}

return 'kmvOfficeItemCreateProcessor';