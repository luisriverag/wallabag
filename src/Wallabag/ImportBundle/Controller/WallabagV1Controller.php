<?php

namespace Wallabag\ImportBundle\Controller;

use Craue\ConfigBundle\Util\Config;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Wallabag\ImportBundle\Import\WallabagV1Import;

class WallabagV1Controller extends WallabagController
{
    /**
     * @Route("/wallabag-v1", name="import_wallabag_v1")
     */
    public function indexAction(Request $request)
    {
        return parent::indexAction($request);
    }

    /**
     * {@inheritdoc}
     */
    protected function getImportService()
    {
        $service = $this->get(WallabagV1Import::class);

        if ($this->get(Config::class)->get('import_with_rabbitmq')) {
            $service->setProducer($this->get('old_sound_rabbit_mq.import_wallabag_v1_producer'));
        } elseif ($this->get(Config::class)->get('import_with_redis')) {
            $service->setProducer($this->get('wallabag_import.producer.redis.wallabag_v1'));
        }

        return $service;
    }

    /**
     * {@inheritdoc}
     */
    protected function getImportTemplate()
    {
        return '@WallabagImport/WallabagV1/index.html.twig';
    }
}
