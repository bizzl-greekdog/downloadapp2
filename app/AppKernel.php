<?php

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            // External Bundles
            new Csa\Bundle\GuzzleBundle\CsaGuzzleBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new Benkle\DoctrineAdoptionBundle\BenkleDoctrineAdoptionBundle(),
            new JMS\DiExtraBundle\JMSDiExtraBundle(),
            new JMS\AopBundle\JMSAopBundle(),
            new JMS\JobQueueBundle\JMSJobQueueBundle(),
            new FOS\UserBundle\FOSUserBundle(),
            new Benkle\NotificationBundle\BenkleNotificationBundle(),
            new \Knp\Bundle\MenuBundle\KnpMenuBundle(),
            // Internal Bundles,
            new DownloadApp\App\FrontendBundle\FrontendBundle(),
            new DownloadApp\App\UtilsBundle\UtilsBundle(),
            new DownloadApp\App\UserBundle\UserBundle(),
            new DownloadApp\App\DownloadBundle\DownloadBundle(),
            new DownloadApp\Scanners\CoreBundle\CoreBundle(),
            new DownloadApp\Scanners\DeviantArtBundle\DeviantArtBundle(),
            new DownloadApp\Scanners\FurAffinityBundle\FurAffinityBundle(),
            new DownloadApp\Scanners\WeasylBundle\WeasylBundle(),
        ];

        if (in_array($this->getEnvironment(), ['dev', 'test'], true)) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();

            if ('dev' === $this->getEnvironment()) {
                $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
                $bundles[] = new Symfony\Bundle\WebServerBundle\WebServerBundle();
            }
        }

        return $bundles;
    }

    public function getRootDir()
    {
        return __DIR__;
    }

    public function getCacheDir()
    {
        return dirname(__DIR__).'/var/cache/'.$this->getEnvironment();
    }

    public function getLogDir()
    {
        return dirname(__DIR__).'/var/logs';
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir().'/config/config_'.$this->getEnvironment().'.yml');
    }
}
