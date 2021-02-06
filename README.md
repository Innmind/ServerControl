# ServerControl

[![Build Status](https://github.com/innmind/servercontrol/workflows/CI/badge.svg?branch=master)](https://github.com/innmind/servercontrol/actions?query=workflow%3ACI)
[![codecov](https://codecov.io/gh/innmind/servercontrol/branch/develop/graph/badge.svg)](https://codecov.io/gh/innmind/servercontrol)
[![Type Coverage](https://shepherd.dev/github/innmind/servercontrol/coverage.svg)](https://shepherd.dev/github/innmind/servercontrol)

Give access to giev instructions to the server.

## Installation

```sh
composer require innmind/server-control
```

## Usage

```php
use Innmind\Server\Control\{
    ServerFactory,
    Server\Command,
    Server\Process\Output\Type,
    Server\Process\Pid,
    Server\Signal,
    Server\Volumes\Name,
};
use Innmind\Url\Path;
use Innmind\Immutable\Str;

$server = ServerFactory::build();
$server
    ->processes()
    ->execute(
        Command::foreground('bin/console')
            ->withArgument('debug:router')
    )
    ->output()
    ->foreach(static function(Str $data, Type $type): void {
        $type = $type === Type::error() ? 'ERR : ' : 'OUT : ';

        echo $type.$data->toString();
    });
$server
    ->processes()
    ->kill(
        new Pid(42),
        Signal::kill()
    );
$server->volumes()->mount(new Name('/dev/disk2s1'), Path::of('/somewhere')); // the path is only interpreted for linux
$server->volumes()->unmount(new Name('/dev/disk2s1'));
$server->reboot();
$server->shutdown();
```

### Scripts

Sometimes you may want to run a set of commands on your server. You can easily do so like this:

```php
use Innmind\Server\Control\Server\Script;

$script = Script::of(
    'apt-get install php-fpm -y',
    'service nginx start',
);
$script($server);
```

If any command fails, it will stop the script and raise an exception.

### Remote server control

```php
use Innmind\Server\Control\Servers\Remote;
use Innmind\Url\Authority\{
    Host,
    Port,
    UserInformation\User,
};

$server = new Remote(
    $server,
    new User('john'),
    new Host('example.com'),
    new Port(42),
);
$server->processes()->execute(new Command('ls'));
```

This will run `ssh -p 42 john@example.com ls`.

**Important**: specifying environment variables or an input stream will not be taken into account on the remote server.

### Logging

```php
use Innmind\Server\Control\Servers\Logger;
use Psr\Log\LoggerInterface;

$server = new Logger($server, /** an instance of LoggerInterface */);
```
