
# Euclid

Simple PHP tool to map your classes and methods, so you can execute them from the comfort of your command line interface.

### Includes:

- the core Euclid class that will handle your commands, offering a **direct** mode and a **guided** one;

- a Parser, mainly to decipher direct commands;

- something resembling a CRUD class to configure your **commands map**: set hooks to **filter** through methods; operate permanent or per-instance edits; save edits, possibly to another config file; etc -- the list of available commands will updated itself;

- a companion class that will handle input, output and formatting, and can be used **independently** of Euclid;

- a sample nano bin script, allowing you to **edit CLI arguments**  *before* passing them to Euclid, if you do so wish.


## Getting Started


### Prerequisites

- Very basic knowledge of CLI ([CLI is awesome](https://www.w3schools.com/whatis/whatis_cli.asp) – like a lot of things made in the sixties)
- PHP 8.0.3 (probably works with earlier versions; but untested)
- Composer

### Install

```bash
$ composer require ssitu/euclid
```
Set up a `bin/euclid file`, and a `euclid-config.json` to suit your needs.
Sample files are available in `samples/`.

### Setup

- `bin/euclid` file:

```php
#!/usr/bin/php
<?php
use SSITU\Euclid\EuclidCore;
if (php_sapi_name() !== 'cli') {
exit;
}
require_once  dirname(__DIR__).'/vendor/autoload.php';

$Euclid = new  EuclidCore();
// or
$Euclid = new  EuclidCore($argv);
// or
$configPath = dirname(__DIR__).'/config/euclid.json';
$Euclid = new  EuclidCore($argv, $configPath);
// or even
$EuclidMap = new  EuclidMap();
$EuclidMap->initMap($configPath);
$EuclidMap->rmvFromMap('unsetme');
$EuclidMap->updtMap('demo', 'build*', 'methodHook');
$EuclidMap->saveMap();
$argv[4] = 'something else';
$Euclid = new  EuclidCore($argv, $EuclidMap);
```

- CLI:

```bash
$ php bin/euclid

# or to run Euclid and a command in one go (direct mode):
$ php bin/euclid classkey constrArg1 constrArg2 ->method methodArg1 methodArg2
```
Second case requires that EuclidCore was called with either a valid path to your config file, or an instance of EuclidMap.

## CLI

Exit anytime by entering `$`.

### Guided Mode

Easily navigate through classes and methods, and then enter arguments if any.
Just follow the prompts!

```
1 medicis
2 igor
Pick a class > 2

1  compileCreature
Pick a method > 1

[param1] $creatureFace
Enter argument > SomeFace

[param2] $creatureBrain|opt
Enter argument >
```

### Direct Mode

Commands will look like this:

```bash
classkey ->method
# if your class constructor has parameters:
classkey constrArg1 constrArg2 ->method
# and if your method has parameters:
classkey constrArg1 constrArg2 ->method methodArg1 methodArg2
```

Your list of commands is available by entering `#`;
a nano readme by entering `?`;
and you can switch to guided mode with `%`.

### Result

After passing a command: result will be displayed, and you can pick what to do next.

```bash
[class] igor
[method] compileCreature
[return] 'Creature compiled without brain.'

$ exit
* edit map
1 reset | direct mode
2 reset | guided mode
3 re-run same cmd
4 call another method of class "igor"
5 call method "compileCreature" with new arguments
```

### Escaping Arguments

#### Strings

To escape strings: you can wrap them in *double* quotes,
or if you're automating Euclid jobs you may use `urlencode()`.

*Examples:*
- If you need to pass `$` as an argument *without Euclid exiting*, use quotes: `"$"`. 
- In direct mode: 
   ```bash
   myclass ->mymethod "some string with spaces"
   ```
   Quotes here will prevent `"some string with spaces"` to be considered as four different arguments.

#### Arrays

You can use method `parseArrayArgm($array)` available in EuclidCompanion,
or pass them as follow:

```bash
a[]=bob&a[]=0.5&a[b]="some other bob"
```
It will be parsed into:
```php
[0 =>'bob',1 => 0.5,'b' => 'some other bob']
``` 

## Config

### Concept

For each of your classes you wish to call from Euclid, you will need to set:
* the class "key name", that will constitute the first part of your CLI command;
*Example:* for a class named `DemoDoer`: its key name can be `demo` (or... whatever)

* the class full name, preceded by any applicable namespace;
*Example:*  `SSITU\Euclid\Demo\DemoDoer`

* optionally, a "method hook", aka a method name, or a prefix / suffix found in the names of methods you wish to target – because not *all* methods are meant for CLI.
  - If it's a sufix, prepend your hook with an `*` : `*yourHook`;
  - if it's a prefix: `yourHook*`.  
  - *Example:*
    aforementioned `DemoDoer` class has 3 methods:
`buildDirTree`, `buildFile`, `setBobName`;
method hook can be set to `build*`;
`setBobName` will therefore not be listed in commands list.

Please note that **protected and private function**  *cannot* be listed or called from CLI.

### JSON File

If you have a **set list of classes to call**, a dedicated **JSON file** will be used to store their keys/names/hooks (and their auto-built list of commands).

```json
{
"maps": {
	"demo": {
		"className": "SSITU\\Demo\\DemoDoer",
		"methodHook": "build*"
		}
	}
}
```
### Handling

You can set up your config:
- by manually editing the JSON file if you do so wish;
- in CLI with Euclid (menu 'edit', key *);
- or by using `EuclidMap` class and its CRUD-like methods.

## Purposes

Well, I personally use that lib to automate my "build jobs" : compiling, editing, minifying, moving assets around, and sending me a message if something went wrong.

## Contributing

Yes please! You can take a loot at [CONTRIBUTING](CONTRIBUTING.md).
This repo also features a [Discussions](https://github.com/I-is-as-I-does/Euclid/discussions) tab.

## License

This project is under the MIT License; cf. [LICENSE](LICENSE) for details.