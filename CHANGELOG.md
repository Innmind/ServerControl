# Changelog

## [Unreleased]

### Fixed

- Output generated while writing the input is now directly available (previously it was when the whole input was written)

## 5.2.1 - 2023-11-11

### Fixed

- Using too much CPU when waiting for processes (due to polling)

## 5.2.0 - 2023-10-22

### Changed

- Requires `innmind/filesystem:~7.0`

## 5.1.0 - 2023-09-16

### Added

- Support for `innmind/immutable:~5.0`

### Removed

- Support for PHP `8.1`

## 5.0.0 - 2023-01-29

### Added

- `Innmind\Server\Control\Server\Process\Success`
- `Innmind\Server\Control\Server\Process\Failed::output()`
- `Innmind\Server\Control\Server\Process\TimedOut::output()`
- `Innmind\Server\Control\Server\Process\Signaled::output()`

### Changed

- `Innmind\Server\Control\ServerFactory::build()` second argument now expect `Innmind\Stream\Capabilities`
- `Innmind\Server\Control\Server\Process::wait()` right side of the returned `Either` is now `Innmind\Server\Control\Server\Process\Success` instead of `Innmind\Immutable\SideEffect`

## 4.3.0 - 2022-12-18

### Added

- Support for `innmind/filesystem:~6.0`

## 4.2.0 - 2022-07-15

### Added

- `Command::withEnvironments` to define multiple variables at once

## 4.1.1 - 2022-05-01

### Fixed

- `Command::foreground` and `Command::background` are now declared pure

## 4.1.0 - 2022-05-01

### Changed

- It is now allowed to call `output()` and `wait()` on the same foreground process

## 4.0.1 - 2022-02-26

### Fixed

- Some processes hanging forever once killed

## 4.0.0 - 2022-02-19

### Added

- `Innmind\Server\Control\ScriptFailed`
- `Innmind\Server\Control\Server\Process\Failed`
- `Innmind\Server\Control\Server\Process\Signaled`
- `Innmind\Server\Control\Server\Process\TimedOut`
- `Innmind\Server\Control\Server\Process\Output::chunks()`

### Changed

- `Innmind\Server\Control\Server::reboot()` now returns `Innmind\Immutable\Either<Innmind\Server\Control\ScriptFailed, Innmind\Immutable\SideEffect>` instead of throwing an exception
- `Innmind\Server\Control\Server::shutdown()` now returns `Innmind\Immutable\Either<Innmind\Server\Control\ScriptFailed, Innmind\Immutable\SideEffect>` instead of throwing an exception
- `Innmind\Server\Control\Server\Command::withInput()` now expect a `Innmind\Filesystem\File\Content`
- `Innmind\Server\Control\Server\Process::pid()` now returns a `Innmind\Immutable\Maybe<Innmind\Server\Control\Server\Process\Pid>` instead of throwing an exception
- `Innmind\Server\Control\Server\Process::wait()` now returns a `Innmind\Immutable\Either<Innmind\Server\Control\Server\Process\Failed|Innmind\Server\Control\Server\Process\Signaled|Innmind\Server\Control\Server\Process\TimedOut, Innmind\Immutable\SideEffect>` instead of throwing an exception
- Calling `Innmind\Server\Control\Server\Process::output()` twice when streaming the output will throw an exception
- `Innmind\Server\Control\Server\Process\BackgroundProcess` has been renamed to `Background`
- `Innmind\Server\Control\Server\Process\ForegroundProcess` has been renamed to `Foreground`
- `Innmind\Server\Control\Server\Process\LoggerProcess` has been renamed to `Logger`
- `Innmind\Server\Control\Server\Process\Logger` constructor is now private, use `::psr()` named constructor instead
- `Innmind\Server\Control\Server\Process\Output::foreach()` now returns `Innmind\Immutable\SideEffect`
- `Innmind\Server\Control\Server\Process\Output\Logger` constructor is now private, use `::psr()` named constructor instead
- `Innmind\Server\Control\Server\Process\Output\Type` is now an enum
- `Innmind\Server\Control\Server\Processes\LoggerProcesses` has been renamed to `Logger`
- `Innmind\Server\Control\Server\Processes\Logger` constructor is now private, use `::psr()` named constructor instead
- `Innmind\Server\Control\Server\Processes\RemoteProcesses` has been renamed to `Remote`
- `Innmind\Server\Control\Server\Processes\UnixProcesses` has been renamed to `Unix`
- `Innmind\Server\Control\Server\Processes\Unix` constructor is now private, use `::of()` named constructor instead
- `Innmind\Server\Control\Server\Script::__invoke()` now returns `Innmind\Immutable\Either<Innmind\Server\Control\ScriptFailed, Innmind\Immutable\SideEffect>` instead of throwing exceptions
- `Innmind\Server\Control\Server\Signal` is now an enum
- `Innmind\Server\Control\Server\Volumes::mount()` now returns `Innmind\Immutable\Either<Innmind\Server\Control\ScriptFailed, Innmind\Immutable\SideEffect>` instead of throwing exceptions
- `Innmind\Server\Control\Server\Volumes::unmount()` now returns `Innmind\Immutable\Either<Innmind\Server\Control\ScriptFailed, Innmind\Immutable\SideEffect>` instead of throwing exceptions
- `Innmind\Server\Control\Servers\Logger` constructor is now private, use `::psr()` named constructor instead
- `Innmind\Server\Control\Servers\Unix` constructor is now private, use `::of()` named constructor instead

### Removed

- Support for php `7.4` and `8.0`
- `Innmind\Server\Control\Exception\BackgroundProcessInformationNotAvailable`
- `Innmind\Server\Control\Exception\CannotGroupEmptyOutput`
- `Innmind\Server\Control\Exception\DomainException`
- `Innmind\Server\Control\Exception\EmptyEnvironmentKeyNotAllowed`
- `Innmind\Server\Control\Exception\EmptyExecutableNotAllowed`
- `Innmind\Server\Control\Exception\EmptyOptionNotAllowed`
- `Innmind\Server\Control\Exception\LogicException`
- `Innmind\Server\Control\Exception\LowestPidPossibleIsTwo`
- `Innmind\Server\Control\Exception\OutOfRangeException`
- `Innmind\Server\Control\Exception\OutOfRangeExitCode`
- `Innmind\Server\Control\Exception\ProcessStillRunning`
- `Innmind\Server\Control\Exception\ProcessTimedOut`
- `Innmind\Server\Control\Exception\ScriptFailed`
- `Innmind\Server\Control\Server\Process::exitCode()` has been removed, use `::wait()` instead
- `Innmind\Server\Control\Server\Process::isRunning()`
- `Innmind\Server\Control\Server\Process\ExitCode::isSuccessful()`
- `Innmind\Server\Control\Server\Process\Input\Bridge`
