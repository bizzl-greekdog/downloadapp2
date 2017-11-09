<?php

namespace DownloadApp\Scanners\DeviantArtBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;

/**
 * Class DeviantArtController
 * @package DownloadApp\Scanners\DeviantArtBundle\Controller
 */
class DeviantArtController extends Controller
{
    /**
     * Handle authorizations.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function indexAction(Request $request)
    {
        if (!$request->query->has('code')) {
            throw new MissingMandatoryParametersException('Parameter "code" is missing');
        }
        $code = $request->query->get('code');
        $this->get('downloadapp.scanners.deviantart.api')->initialize($code);
        return $this->redirect('/');
    }
}
