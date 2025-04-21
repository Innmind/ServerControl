# ServerControl

[![Build Status](https://github.com/innmind/servercontrol/workflows/CI/badge.svg?branch=master)](https://github.com/innmind/servercontrol/actions?query=workflow%3ACI)
[![codecov](https://codecov.io/gh/innmind/servercontrol/branch/develop/graph/badge.svg)](https://codecov.io/gh/innmind/servercontrol)
[![Type Coverage](https://shepherd.dev/github/innmind/servercontrol/coverage.svg)](https://shepherd.dev/github/innmind/servercontrol)

Give access to giving instructions to the server.

> [!IMPORTANT]
> To correctly use this library you must validate your code with [`vimeo/psalm`](https://packagist.org/packages/vimeo/psalm)

## Installation

```sh
composer require innmind/server-control
```

## Usage

```php
use Innmind\Server\Control\{
    ServerFactory,
    Server\Command,
    Server\Process\Output\Chunk,
    Server\Process\Output\Type,
    Server\Process\Pid,
    Server\Signal,
    Server\Volumes\Name,
};
use Innmind\TimeContinuum\Clock;
use Innmind\TimeWarp\Halt\Usleep;
use Innmind\IO\IO;
use Innmind\Url\Path;
use Innmind\Immutable\Str;

$server = ServerFactory::build(
    Clock::live(),
    IO::fromAmbientAuthority(),
    Usleep::new(),
);
$server
    ->processes()
    ->execute(
        Command::foreground('bin/console')
            ->withArgument('debug:router')
    )
    ->unwrap()
    ->output()
    ->foreach(static function(Chunk $chunk): void {
        $type = match ($chunk->type()) {
            Type::error => 'ERR : ',
            Type::output => 'OUT : ',
        };

        echo $type.$chunk->data()->toString();
    });
$server
    ->processes()
    ->kill(
        new Pid(42),
        Signal::kill,
    )
    ->unwrap();
$server
    ->volumes()
    ->mount(new Name('/dev/disk2s1'), Path::of('/somewhere')) // the path is only interpreted for linux
    ->unwrap();
$server
    ->volumes()
    ->unmount(new Name('/dev/disk2s1'))
    ->unwrap();
$server->reboot()->unwrap();
$server->shutdown()->unwrap();
```

### Scripts

Sometimes you may want to run a set of commands on your server. You can easily do so like this:

```php
use Innmind\Server\Control\Server\{
    Script,
    Command,
};

$script = Script::of(
    Command::foreground('apt-get install php-fpm -y'),
    Command::foreground('service nginx start'),
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

$server = Remote::of(
    $server,
    User::of('john'),
    Host::of('example.com'),
    Port::of(42),
);
$server
    ->processes()
    ->execute(Command::foreground('ls'))
    ->unwrap();
```

This will run `ssh -p 42 john@example.com ls`.

> [!IMPORTANT]
> Specifying environment variables or an input stream will not be taken into account on the remote server.

### Logging

```php
use Innmind\Server\Control\Servers\Logger;
use Psr\Log\LoggerInterface;

$server = Logger::psr($server, /** an instance of LoggerInterface */);
```
