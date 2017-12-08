<?php

namespace DownloadApp\App\FrontendBundle\Controller;

use DownloadApp\App\DownloadBundle\Service\Downloads;
use JMS\JobQueueBundle\Entity\Job;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DefaultController
 * @package DownloadApp\App\FrontendBundle\Controller
 */
class DefaultController extends Controller
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \DownloadApp\App\UserBundle\Exception\NoLoggedInUserException
     */
    public function indexAction(Request $request)
    {

        $user = $this->get('downloadapp.user.current')->get();
        $jobs = $this->get('downloadapp.utils.jobs');
        $downloads = $this->get('downloadapp.download');

        $variables = [
            'user'      => $user,
            'downloads' => [
                'failed' => $downloads->findByUser($user, Downloads::FAILED_REQUIRED, Downloads::DOWNLOADED_EXCLUDED),
            ],
            'stats'     => [
                'open'   => [
                    'scans'     => $jobs->countJobsForUser(
                        $user, [
                                 Job::STATE_INCOMPLETE,
                                 Job::STATE_NEW,
                                 Job::STATE_PENDING,
                             ]
                    ),
                    'downloads' => $downloads->countByUser($user, Downloads::FAILED_ALLOWED, Downloads::DOWNLOADED_EXCLUDED),
                ],
                'failed' => [
                    'downloads' => $downloads->countByUser($user, Downloads::FAILED_REQUIRED, Downloads::DOWNLOADED_EXCLUDED),
                ],
            ],
        ];

        return $request->isXmlHttpRequest()
            ? $this->json($variables)
            : $this->render('FrontendBundle:Default:index.html.twig', $variables);
    }
}
