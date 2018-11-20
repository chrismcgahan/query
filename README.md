# Query
A simple class to make interacting with MySQL databases easier

# Installation
Simply copy `Query.php` into your code base and require it

# Basic Usage
Create a new instance of the `Query` class with your database credentials

```
$db = new Query([
    'host'     => 'localhost',
    'port'     => '3306',
    'username' => 'root',
    'password' => 'pass',
    'database' => 'test'
]);
```

The Query class is designed to be stateful. Methods should be called on the newly created Query object. All methods that connect to a database or execute a query may throw an `Exception`.

```
$db->query("
    CREATE TABLE `testTable`(
        `id` INT NOT NULL AUTO_INCREMENT,
        `testField` VARCHAR(50),

        PRIMARY KEY(`id`)
    )
");
```

# Examples

The `query` method and other methods that except a `$query` argument, support placeholders. Placeholder values are escaped prior to query excecution. If a placeholder value is an array the array elements are escaped and joined with commas.

```
$db->getAll('SELECT * FROM `testTable` WHERE `id` = ?', [1]);

$db->getAll('SELECT * FROM `testTable` WHERE `id` IN (?)', [[1, 2]]);

$db->getRow('SELECT * FROM `testTable` WHERE `id` = ? AND `testField` = ?', [1, 'foo']);

$db->getOne('SELECT `testField` FROM `testTable` WHERE id = ?', [1]);

$db->getCol('SELECT `testField` FROM `testTable` WHERE id > ?', 0, [0]);
```

Here are some sample return values.

```
// getAll
[
    [
        'id' => '1',
        'testField' => 'foo'
    ],
    [
        'id' => '2',
        'testField' => 'bar'
    ]
]

// getRow
[
    'id' => '1',
    'testField' => 'foo'
]

// getOne
foo

// getCol
[
    'foo',
    'bar'
]
```

There are also methods for inserting and updating rows.

```
// INSERT INTO `testTable`(`testField`) VALUES('foo')
$insertId = $db->insert('testTable', ['testField' => 'foo']);

// UPDATE `testTable` SET `testField` = 'bar' WHERE `id` = '{$insertId}'
$db->update('testTable', ['testField' => 'bar'], ['id' => $insertId]);
```

A method for escaping strings is available, though it is unnecessary when using placeholders or the `insert` and `update` methods.

```
$db->esc("Mary's");
```

Other useful methods

```
// The value of the AUTO_INCREMENT field updated by the previous query
$insertId = $db->insertId();

// The number of rows affected by the last query
$affectedRows = $db->affectedRows();

// A string representing the last query executed after placeholders have been substituted
$lastQuery = $db->lastQuery(); 
```
