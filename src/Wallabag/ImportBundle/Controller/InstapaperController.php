<?php

namespace Wallabag\ImportBundle\Controller;

use Craue\ConfigBundle\Util\Config;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use Wallabag\ImportBundle\Form\Type\UploadImportType;
use Wallabag\ImportBundle\Import\InstapaperImport;

class InstapaperController extends Controller
{
    /**
     * @Route("/instapaper", name="import_instapaper")
     */
    public function indexAction(Request $request)
    {
        $form = $this->createForm(UploadImportType::class);
        $form->handleRequest($request);

        $instapaper = $this->get(InstapaperImport::class);
        $instapaper->setUser($this->getUser());

        if ($this->get(Config::class)->get('import_with_rabbitmq')) {
            $instapaper->setProducer($this->get('old_sound_rabbit_mq.import_instapaper_producer'));
        } elseif ($this->get(Config::class)->get('import_with_redis')) {
            $instapaper->setProducer($this->get('wallabag_import.producer.redis.instapaper'));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            $markAsRead = $form->get('mark_as_read')->getData();
            $name = 'instapaper_' . $this->getUser()->getId() . '.csv';

            if (null !== $file && \in_array($file->getClientMimeType(), $this->getParameter('wallabag_import.allow_mimetypes'), true) && $file->move($this->getParameter('wallabag_import.resource_dir'), $name)) {
                $res = $instapaper
                    ->setFilepath($this->getParameter('wallabag_import.resource_dir') . '/' . $name)
                    ->setMarkAsRead($markAsRead)
                    ->import();

                $message = 'flashes.import.notice.failed';

                if (true === $res) {
                    $summary = $instapaper->getSummary();
                    $message = $this->get(TranslatorInterface::class)->trans('flashes.import.notice.summary', [
                        '%imported%' => $summary['imported'],
                        '%skipped%' => $summary['skipped'],
                    ]);

                    if (0 < $summary['queued']) {
                        $message = $this->get(TranslatorInterface::class)->trans('flashes.import.notice.summary_with_queue', [
                            '%queued%' => $summary['queued'],
                        ]);
                    }

                    unlink($this->getParameter('wallabag_import.resource_dir') . '/' . $name);
                }

                $this->get(SessionInterface::class)->getFlashBag()->add(
                    'notice',
                    $message
                );

                return $this->redirect($this->generateUrl('homepage'));
            }

            $this->get(SessionInterface::class)->getFlashBag()->add(
                'notice',
                'flashes.import.notice.failed_on_file'
            );
        }

        return $this->render('@WallabagImport/Instapaper/index.html.twig', [
            'form' => $form->createView(),
            'import' => $instapaper,
        ]);
    }
}
