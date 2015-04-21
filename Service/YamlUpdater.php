<?php

/*
 * (c) Philipp Wahala <philipp.wahala@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace hacfi\ParameterUpdateBundle\Service;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;


class YamlUpdater
{
    /**
     * @var \Symfony\Component\PropertyAccess\PropertyAccessor
     */
    protected $accessor;


    /**
     * @var Parser
     */
    protected $yamlParser;

    public function __construct()
    {
        $this->accessor = PropertyAccess::createPropertyAccessor();
        $this->yamlParser = new Parser();
    }

    public function updateValue($file, $propertyPath, $value)
    {
        $configValues = [];

        $exists = is_file($file);

        if ($exists) {
            $existingValues = $this->yamlParser->parse(file_get_contents($file));
            if ($existingValues === null) {
                $existingValues = [];
            }

            if (!is_array($existingValues)) {
                throw new \InvalidArgumentException(sprintf('<error>Parameters file "%s" does not contain an array</error>', $file));
            }

            $configValues = array_merge($configValues, $existingValues);
        }

        $this->accessor->setValue($configValues, $propertyPath, $value);

        if (!is_dir($dir = dirname($file))) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($file, Yaml::dump($configValues, 99));
    }
}
