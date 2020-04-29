<?php

/**
 * @package    3slab/VdmLibraryAmqpTransportBundle
 * @copyright  2020 Suez Smart Solutions 3S.lab
 * @license    https://github.com/3slab/VdmLibraryAmqpTransportBundle/blob/master/LICENSE
 */

namespace Vdm\Bundle\LibraryAmqpTransportBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
/**
 * Class VdmLibraryAmqpTransportExtension
 *
 * @package Vdm\Bundle\LibraryAmqpTransportBundle\DependencyInjection
 */
class VdmLibraryAmqpTransportExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {       
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yaml');
    }

    /**
     * {@inheritDoc}
     */
    public function getAlias()
    {
        return 'vdm_library_amqp_transport';
    }
}
