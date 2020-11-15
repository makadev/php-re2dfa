<?php

shell_exec('sudo /usr/local/bin/opcache-enable');
passthru("vendor/bin/phpstan analyse -c phpstan.neon | tee logs/phpstan.log");
