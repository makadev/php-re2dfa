<?php

shell_exec('sudo /usr/local/bin/opcache-enable');
passthru("vendor/bin/psalm -c psalm.xml --output-format=compact --report=logs/psalm.pylint");
