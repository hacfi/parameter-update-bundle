# Symfony Parameter Update Bundle


## Install


``` sh
composer require "hacfi/parameter-update-bundle":"dev-master"
```

## Configuration


``` yml
hacfi_parameter_update:
    parameters_file: "%kernel.root_dir%/config/parameters.yml"
    values:
        some_parameter1:
            service: some_service_name:method1
            parameters_file: "%kernel.root_dir%/config/local.yml"
            parameters_key: parameters
        some_parameter2:
            service: [other_service_name, method2]
            property_path: "[nested][some_bucketname2]"
        some_parameter3:
            service: [[some_service_name, test], ["argument 1", "argument 2"]]
```


## Usage

Configure the values which should be generated via a service. Process them via

``` sh
app/console hacfi:update_parameter
```

or individually


``` sh
app/console hacfi:update_parameter some_parameter1
```
