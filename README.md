# ServerControl

| `master` | `develop` |
|----------|-----------|
| [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Innmind/ServerControl/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Innmind/ServerControl/?branch=master) | [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Innmind/ServerControl/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/ServerControl/?branch=develop) |
| [![Code Coverage](https://scrutinizer-ci.com/g/Innmind/ServerControl/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Innmind/ServerControl/?branch=master) | [![Code Coverage](https://scrutinizer-ci.com/g/Innmind/ServerControl/badges/coverage.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/ServerControl/?branch=develop) |
| [![Build Status](https://scrutinizer-ci.com/g/Innmind/ServerControl/badges/build.png?b=master)](https://scrutinizer-ci.com/g/Innmind/ServerControl/build-status/master) | [![Build Status](https://scrutinizer-ci.com/g/Innmind/ServerControl/badges/build.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/ServerControl/build-status/develop) |

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
    Server\Signal
};
use Innmind\Immutable\Str;

$server = (new ServerFactory)->make();
$server
    ->processes()
    ->execute(
        (new Command('bin/console'))
            ->withArgument('debug:router')
    )
    ->output()
    ->foreach(static function(Str $data, Type $type): void {
        $type = $type === Type::error() ? 'ERR : ' : 'OUT : ';

        echo $type.$data;
    });
$server
    ->processes()
    ->kill(
        new Pid(42),
        Signal::kill()
    );
```

### Scripts

Sometimes you may want to run a set of commands on your server. You can easily do so like this:

```php
use Innmind\Server\Control\Server\Script;

$script = Script::of(
    'apt-get install php-fpm -y',
    'service nginx start'
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
    UserInformation\User
};

$server = new Remote(
    $server,
    new User('john'),
    new Host('example.com'),
    new Port(42)
);
$server->processes()->execute(new Command('ls'));
```

This will run `ssh -p 42 john@example.com ls`.

**Important**: specifying environment variables, a working directory or an input stream will not be taken into account on the remote server.
