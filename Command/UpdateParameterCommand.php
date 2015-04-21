<?php

/*
 * (c) Philipp Wahala <philipp.wahala@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace hacfi\ParameterUpdateBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;


class UpdateParameterCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('hacfi:update_parameter')
            ->setDescription('Update parameters')
            ->addArgument('parameter', InputArgument::OPTIONAL, 'Parameter to update')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->getContainer()->getParameter('hacfi_parameter_update.config');

        $yamlParser = new Parser();
        $accessor = PropertyAccess::createPropertyAccessor();

        if (null !== ($parameter = $input->getArgument('parameter'))) {
            $parameters = [$parameter];
        } else {
            $parameters = array_keys($config['values']);
        }

        foreach ($parameters as $parameter) {
            if (!isset($config['values'][$parameter])) {
                $output->writeln(sprintf('<error>Parameter "%s" is not configured under hacfi_parameter_update values</error>', $parameter));
                if (count($config['values']) > 0) {
                    $output->writeln(sprintf('<info>Valid parameters are:</info> %s', implode(', ', array_keys($config['values']))));
                } else {
                    $output->writeln('<info>hacfi_parameter_update values is not configured yet.</info>');
                }

                return 1;
            }

            $parameterConfig = $config['values'][$parameter];

            foreach (['parameters_file', 'parameters_key'] as $configKey) {
                if (!isset($parameterConfig[$configKey])) {
                    $parameterConfig[$configKey] = $config[$configKey];
                }
            }

            if (strpos($parameterConfig['parameters_key'], '[') === false) {
                $parameterConfig['parameters_key'] = '['.$parameterConfig['parameters_key'].']';
            }

            $exists = is_file($parameterConfig['parameters_file']);

            $configValues = [];

            $accessor->setValue($configValues, $parameterConfig['parameters_key'], []);

            if ($exists) {
                $existingValues = $yamlParser->parse(file_get_contents($parameterConfig['parameters_file']));
                if ($existingValues === null) {
                    $existingValues = [];
                }
                if (!is_array($existingValues)) {
                    $output->writeln(sprintf('<error>Parameters file "%s" does not contain an array</error>', $parameterConfig['parameters_file']));

                    return 1;
                }
                $configValues = array_merge($configValues, $existingValues);
            }

            $service = $parameterConfig['service'];
            $arguments = [];

            if (is_string($service)) {
                $count = substr_count($service, ':');
                if (1 == $count) {
                    list($service, $method) = explode(':', $service);
                } else {
                    $method = '__invoke';
                }

                $callable = [$this->getContainer()->get($service), $method];
            } elseif (is_array($service) && count($service) === 2) {

                if (is_string($service[0]) && is_string($service[1])) {
                    $serviceName = $service[0];
                    $method = $service[1];
                } elseif (is_array($service[0]) && count($service[0]) == 2) {
                    $serviceName = $service[0][0];
                    $method = $service[0][1];
                    $arguments = is_array($service[1]) ? $service[1] : [$service[1]];
                } else {
                    $output->writeln(sprintf('<error>Invalid service value "%s".</error>', var_export($service, true)));

                    return 1;
                }

                $callable = [$this->getContainer()->get($serviceName), $method];
            } else {
                $output->writeln(sprintf('<error>Invalid service value type %s, expected string or array with two elements</error>', gettype($service)));

                return 1;
            }

            $value = call_user_func_array($callable, $arguments);

            $propertyPath = isset($parameterConfig['property_path']) ? $parameterConfig['property_path'] : $parameter;

            if (strpos($propertyPath, '[') === false) {
                $propertyPath = '['.$propertyPath.']';
            }

            $accessor->setValue($configValues, $parameterConfig['parameters_key'].$propertyPath, $value);

            if (!is_dir($dir = dirname($parameterConfig['parameters_file']))) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($parameterConfig['parameters_file'], Yaml::dump($configValues, 99));
        }

        return 0;
    }
}
