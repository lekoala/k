K SQL package
===

Introduction
---
`k\sql\Pdo` class is the base class of this package. This class extends transparently the native PDO class, and you can use `k\sql\Pdo` as a drop-in replacement of the class, since it extends the native PDO class and keeps the same syntax.

On top of that, you can enjoy the following features:

- **Logging**: you can attach a psr-3 logger to the pdo instance. All queries are logged, even the prepared ones with their placeholders properly replaced. All queries are also logged directly inside the object instance, so that you can access them even without a logger attached.
- **Lazy connection**: when you create an object instance, it doesn't initiate a db connection. Instead, the connection is made when you are effectively doing queries on your database.
- **Better defaults**: tired of always setting your pdo class to throw exceptions? me too! Tired of stupid exception saying you have a syntax error without giving you the full sql statement? me too!
- **Flexible constructor**: pass a dsn string or an array of parameters as the first arguments allows you to easily instantiate the class from a config array for instance.
- **Connection manager**: each instance of your pdo class will automatically register itself into a registry where each connection is kept for multi db usage.
- **Sql helpers**: friendly insert, update and delete statement. No select? True, it's better to use `k\sql\Query` for that. Also enjoy syntax highlighting and formatting, + a few extras.
- **Factories**: easily instantiate Query and Table objects if you need them.

`k\sql\Query` comes next. This class is a chainable query builder to select data. It supports complex queries building and all kind of data fetching.

`k\sql\Table` represent a table in your database. It also acts as friendly wrapper for pdo, allowing you to execute insert/update/delete statement without having to specify the first argument : the table.

Orm
---
The Orm use static definitions to define fields. Each orm object is jsonserializable by default. The orm supports virtual getters/setters and provide built in validation.

**Static properties**

**Persistence**

**Hooks**

**Relations**

**Traits**
The orm is shipped with a series of built in traits to provide common orm functionnalities across models, for instance : geolocalisation, created/updated timestamps…
To use these traits, simply… use them in your models.

Creating traits is a bit more difficult. 

TODO







