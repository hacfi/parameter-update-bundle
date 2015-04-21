<?php

/*
 * (c) Philipp Wahala <philipp.wahala@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace hacfi\ParameterUpdateBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use hacfi\ParameterUpdateBundle\DependencyInjection\hacfiParameterUpdateExtension;


class hacfiParameterUpdateBundle extends Bundle
{
    private $configurationAlias;

    public function __construct($alias = 'hacfi_parameter_update')
    {
        $this->configurationAlias = $alias;
    }

    public function getContainerExtension()
    {
        return new hacfiParameterUpdateExtension($this->configurationAlias);
    }
}
