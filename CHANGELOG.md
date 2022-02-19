# Changelog

## [Unreleased]

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
