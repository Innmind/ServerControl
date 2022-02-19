<?php
declare(strict_types = 1);

foreach (\range(0, 5) as $int) {
    \sleep(1);
    \fwrite(
        $int % 2 === 0 ? \STDOUT : \STDERR,
        $int.\PHP_EOL,
    );
}
