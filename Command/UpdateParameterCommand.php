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

            $this->getContainer()->get('hacfi_parameter_update.yaml_updater')->updateValue($parameterConfig['parameters_file'], $parameterConfig['parameters_key'].$propertyPath, $value);
            try {
            } catch (\Exception $e) {
                $output->writeln(sprintf('<error>Could not write value %s at property path <comment>%s</comment> in file %s</error>', $value, $parameterConfig['parameters_key'].$propertyPath, $parameterConfig['parameters_file']));
                $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

                throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
            }
        }

        return 0;
    }
}
