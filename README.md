# Base for step-to-step [Telegram bot](https://core.telegram.org/bots)

Editing this files you will be able to create a full [Telegram](https://www.telegram.org) bot.

With those files you can create a bot that allow users to talk and interact with it sending one information per message.

## Installation
All you need to have are a server with LAMP and the token of a Telegram bot.
##### Install the base package

 To set up the server you just need to type in a terminal 

```sh
$ sudo apt-get install apache2 php5 php5-mysql php5-mcrypt mysql-server
```

##### Create a database

When the installation is finished you have to create a database (in my case I called it "telegram") and insert in it the tables specified in databases.sql.

##### Make Telegram's servers aware of the changes
In addition, you have to make a request to Telegram's servers in this format:
```html
https://api.telegram.org/bot$token/getUpdates?url=https://www.example.com/path-to-file/receive.php?pw=yourpassword
```

`yourpassword` is the password that you have set in line 24:

```php
if(filter_input(INPUT_GET, "pw") != "yourpassword")
```

##### Creating the db_connect file
Both `send.php` and `receive.php` requires a file called db_connect situated in the parent folder. If you want to change the name or the position of this file you can edit this line in the two files mentioned above
```php
require_once '../db_connect.php';
```
In this file I putted the function connect_telegram, that consist in a connector to mysqli and initialized the variable `$token`, that is the Telegram's API token:
```php
$token = "123456798:abcdefghijklmno";
function connect_telegram()
{
  return new mysqli("host","username","passwd", "dbname");
}
```

## Make the bot works
### Create commands 
Create a new function in the `telegram_receive` class. It must receive a `$valori` parameter. 

After that you should insert the correct values in the elseif in the scelta_operazione function. Following this format:
```php
elseif(!strcasecmp($funzione, "/commandName"))
            $this->functionName($valori);
```
### Know at what point of the command the user is
Inside the function of teh command you can check the valoue of the `$valori` variable. It will be:
- `NULL` if it is the first message of the command (e.g. the user has just sent the message `/weather` to start the `weather` command).
- `false` if it is the second message that the user send to the bot and you have not stored any data about the user.
- a **number** checked with `count($valori)` that represent the number of values that you stored about the user.

You can check the function `riceviNotifica` to understand how it works.

### Store data 
To save that the user have selected a particular command, use the function
```php
set_user_status($this->sender, $azione);
```
where `$azione` is the command that the user choose.

If you want to save values about the user you should add two parameters to the function:
```php
set_user_status($this->sender, $azione, $valori, true);
```
where `$valori` is an associative array of values

### Get stored data
To get the data that you previously stored about a user you can simply use the value contained in `$valori` (e.g. `$valori["age"]`).
