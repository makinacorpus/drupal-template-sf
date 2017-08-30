<?php

use MakinaCorpus\Drupal\Sf\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AppKernel extends Kernel
{
    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        $bundles = [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new \Symfony\Bundle\TwigBundle\TwigBundle(),
//             new \Symfony\Bundle\MonologBundle\MonologBundle(),
//             new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
        ];

        if (in_array($this->getEnvironment(), ['dev', 'test'], true)) {
            $bundles[] = new \Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new \Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
//             $bundles[] = new \Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle();
        }

        return $bundles;
    }

    /**
     * {@inheritdoc}
     */
    public function getRootDir()
    {
        return __DIR__;
    }

    /**
     * {@inheritdoc}
     */
    public function getProjectDir()
    {
        return dirname(__DIR__);
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        // Reproduce the config_ENV.yml file from Symfony, but keep it
        // optional instead of forcing its usage
        $customConfigFile = $this->rootDir.'/config/config_'.$this->getEnvironment().'.yml';
        if (!file_exists($customConfigFile)) {
            // Else attempt with a default one
            $customConfigFile = $this->rootDir.'/config/config.yml';
        }
        if (!file_exists($customConfigFile)) {
            // If no file is provided by the user, just use the default one
            // that provide sensible defaults for everything to work fine
            $customConfigFile = __DIR__.'/../Resources/config/config.yml';
        }

        $loader->load($customConfigFile);
    }

    /**
     * {@inheritdoc}
     */
    protected function build(ContainerBuilder $container)
    {
        $container->setParameter('kernel.project_dir', dirname(__DIR__));
    }
}
