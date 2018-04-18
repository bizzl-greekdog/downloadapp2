<?php

namespace DownloadApp\Scanners\DeviantArtBundle\Controller;

use Benkle\Deviantart\Exceptions\UnauthorizedException;
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
    public function authorizeAction(Request $request)
    {
        if (!$request->query->has('code')) {
            throw new MissingMandatoryParametersException('Parameter "code" is missing');
        }
        $code = $request->query->get('code');
        $this->get('downloadapp.scanners.deviantart.api')->initialize($code);
        return $this->redirect('/');
    }

    /**
     * Request authorization.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function requestAuthorizationAction()
    {
        try {
            $this->get('downloadapp.scanners.deviantart.api')->initialize();
        } catch (UnauthorizedException $e) {
            return $this->redirect($e->getUrl());
        }
        return $this->redirect('/');
    }
}
