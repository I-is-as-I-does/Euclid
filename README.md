# Euclid

Simple PHP tool to map your classes and methods, so you can execute them from the comfort of your command line interface.

### Includes:
- the core Euclid class that will handle your commands;
- something resembling a CRUD class to configure your **commands map**: set hooks to **filter** through methods; operate permanent or per-instance edits; redirect to another config file; etc -- the list of available commands will updated itself;
- a nano bin script, allowing you to **edit CLI arguments**  *before* passing them to Euclid, if you do so wish; 
- a super nano Tools static class for echoing feedbacks (with colors!), or passing arrays as arguments.


## Getting Started

### Prerequisites

- Very basic knowledge of CLI ([CLI is awesome](https://www.w3schools.com/whatis/whatis_cli.asp) – like a lot of things made in the sixties)
- PHP 8.0.3 (probably works with earlier versions; but untested)
- Composer

### Install 

```bash
$ composer require exoproject/euclid
```
 
By default, path to composer autoload is set to `./composer/autoload.php`  
If need be, you can edit it in the `./bin/euclid` file.

## CLI

A basic command will look like this:
```bash
$ php ./bin/euclid classkey ->method
```
if your class has parameters:
```bash
$ php ./bin/euclid classkey arg1 arg2 ->method
```
and if your method has parameters:
```bash
$ php ./bin/euclid classkey arg1 arg2 ->method arg1 arg2
```
  
To get your **commands list** you can do:
```bash
$ php ./bin/euclid help
```
For a nano Readme:
```bash
$ php ./bin/euclid readme
```

## Config


For each of your classes you wish to call from CLI, you will need to set:
* the class "key", that will constitute the first part of your CLI command;
*Example:* for a class named `DemoDoer`: its key can be `demo` (or... whatever)

* the class full name, preceded by any applicable namespace;
*Example:*  `ExoProject\Euclid\Test\DemoDoer`

* optionally, a "method hook", aka a method name, or a prefix / sufix found in the names of methods you wish to target – because not *all* methods are meant for CLI.
	- If it's a sufix, prepend your hook with an `*` : `*yourHook`; if it's a prefix: `yourHook*`.
	- In CLI, **omit the hook** in the method's name.
	*Example:* 
	aforementioned `DemoDoer` class has 3 methods:
`buildDirTree`, `buildFile`, `setBobName`;
method hook can be set to `build*`; 
`setBobName` will be not be listed in commands list;
and in CLI: `php ./bin/euclid demo>DirTree` or `demo>File`

`EuclidMap` class will automatically construct a list of commands from there.

Please note that **protected and private function**  *cannot* be listed or called from CLI.

***

If you have a **set list of classes to call**, a dedicated **JSON  file** will be used to store their keys/names/hooks (and their auto-built list of commands).

```json
{
	"maps": {
		"demo": {
			"className": "ExoProject\\EuclidTest\\DemoDoer",
			"cmdList": {
				"DirTree": ["parentdir", "subdirs|opt"],
				"File": []
			},
			"methodHook": "build*"
		}
	}
}
```

Default JSON file path : `config/euclid_config.json`

A redirect to another file will look like this:
```json
{
"REDIRECT":"path/to/custom/config.json"
}
```
***
You can edit your JSON by hand if you do so wish, but otherwise:
 `EuclidMap` class is at your service.
 ```php
$cmdMapClass = new EuclidMap();
```

By default, `Euclid` main class constructor will do:

```php
$cmdMapClass = new EuclidMap();
$cmdMapClass->setMapFromConfig();
$cmdMap = $cmdMapClass->getMap();
```

But you can also take control:

```php
$cmdMapClass = new EuclidMap();
$cmdMapClass->setCustomConfigPath($path, $permanent = false);
$cmdMapClass->unsetPermanentCustomConfigPath();
$cmdMapClass->setMapFromConfig($path = null);
$cmdMapClass->addToMap($key, $className, $methodHook = null);
$cmdMapClass->remvFromMap($key);
$cmdMapClass->updateMap($key, $value, $jsonkey); //@doc: $jsonkey is either 'className' or 'methodHook';
$cmdMapClass->saveEdits($path = null, $content = null);
$cmdMapClass->buildCmdList($key, $classdata = null);
``` 

And in `bin/euclid`:

* edit [$argv](https://www.php.net/manual/en/reserved.variables.argv.php), if you do so wish;

* then call Euclid with $cmdMap parameter:

```php
$cmdMap = $cmdMapClass->getMap();
$Euclid = new Euclid($argv,$cmdMap);

```

***

Another option is to **extend**  `EuclidMap` class, making its protected properties and methods  accessible.

## Purposes

Well, I personally use that lib to automate my "build jobs" : compiling, editing, minifying, moving assets around, and sending me a message if something went wrong.

Check out `test/DemoDoer.php` for some nano demo.

## Contributing
  
Yes please! You can take a loot at [CONTRIBUTING](CONTRIBUTING.md).  
This repo also features a [Discussions](https://github.com/I-is-as-I-does/Euclid/discussions) tab.
  
## License

This project is under the MIT License; cf. [LICENSE](LICENSE) for details.