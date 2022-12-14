<?php
/*
 * This file is part of MODX Revolution.
 *
 * Copyright (c) MODX, LLC. All Rights Reserved.
 *
 * For complete copyright and license information, see the COPYRIGHT and LICENSE
 * files found in the top-level directory of this distribution.
 */

namespace MODX\Revolution\Processors\Resource;


use MODX\Revolution\Processors\Processor;
use MODX\Revolution\modTemplateVar;
use MODX\Revolution\Registry\modRegister;

/**
 * save resource form data for reload
 */
class Reload extends Processor
{
    public function checkPermissions()
    {
        return $this->modx->hasPermission('save_document');
    }

    /** @var modRegister registry */
    private $reg;

    /**
     * initialization tasks before processing
     *
     * @return bool|string true to continue with processing, otherwise err msg
     */
    public function initialize()
    {
        $return = true;
        $modx =& $this->modx;
        if (!isset($modx->registry)) {
            if (!$modx->getService('registry', 'registry.modRegistry')) {
                $return = 'Could not instantiate registry service.';
            }
        }
        $modx->registry->addRegister('resource_reload', 'registry.modDbRegister', ['directory' => 'resource_reload']);
        $this->reg = $modx->registry->resource_reload;
        if (!$this->reg->connect()) {
            $return = 'Could not connect to resource_reload queue.';
        }
        return $return;
    }

    public function process()
    {
        $result = '';
        $scriptProperties = $this->getProperties();
        $modx = $this->modx;

        foreach ($scriptProperties as $key => &$value) {
            $matched = preg_match("/^tv(\d+)$/i", $key, $matches);
            if ($matched && !empty($matches[1])) {
                $tv = $this->modx->getObject(modTemplateVar::class, $matches[1]);
                if ($tv) {
                    /* validation for different types */
                    switch ($tv->get('type')) {
                        case 'url':
                            $prefix = $this->getProperty($key . '_prefix', '');
                            if ($prefix != '--') {
                                $value = str_replace(['ftp://', 'http://'], '', $value);
                                $value = $prefix . $value;
                            }
                            break;
                        case 'date':
                            $value = empty($value) ? '' : date('Y-m-d H:i:s', strtotime($value));
                            break;
                        /* ensure tag types trim whitespace from tags */
                        case 'tag':
                        case 'autotag':
                            $tags = explode(',', $value);
                            $newTags = [];
                            foreach ($tags as $tag) {
                                $newTags[] = trim($tag);
                            }
                            $value = implode(',', $newTags);
                            break;
                        default:
                            /* handles checkboxes & multiple selects elements */
                            if (is_array($value)) {
                                $featureInsert = [];
                                foreach ($value as $featureValue => $featureItem) {
                                    if (empty($featureItem)) {
                                        continue;
                                    }
                                    $featureInsert[count($featureInsert)] = $featureItem;
                                }
                                $value = implode('||', $featureInsert);
                            }
                            break;
                    }
                }
            }
        }

        if (array_key_exists('create-resource-token', $scriptProperties) && !empty($scriptProperties['create-resource-token'])) {
            $topic = '/resourcereload/';
            $this->reg->subscribe($topic);

            $result = [
                'reload' => $scriptProperties['create-resource-token'],
                'action' => 'resource/create',
                'class_key' => $scriptProperties['class_key'],
                'context_key' => $scriptProperties['context_key'],
            ];

            if (array_key_exists('id', $scriptProperties) && is_numeric($scriptProperties['id']) && intval($scriptProperties['id']) > 0) {
                $result['id'] = $scriptProperties['id'];
                $result['action'] = 'resource/update';
            }
            $this->reg->send($topic, [$scriptProperties['create-resource-token'] => $scriptProperties], ['ttl' => 300, 'delay' => -time()]);
            $this->reg->unsubscribe($topic);
        } else {
            return $modx->error->failure($modx->lexicon('resource_err_save'));
        }

        return $modx->error->success('', $result);
    }
}
