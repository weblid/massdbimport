# Mass DB Import Interface

## What is it?

Allows the easy upload of flat database files (currently only CSV) to be mapped to Eloquent models with simple relations. 

## How does it work?

```php
Massdbimport::model('\App\Model')->source('db.csv')->import();
```

This will read the .csv file from the server and use the first row of columns as model attributes. It then skips through the values below and imports them to the database through the model.

## Options

### Relations

You can define relations in the CSV header with the 'relation:column' syntax. It will look in your model for the relation, and check the column of the related db table for the csv value (usually slug or id). Once found, it will create the relation on import.

You can define ManyToMany relationships by seperating your values with '|'.

## Handling duplicates

You can define unique id columns (such as a slug) with the ->unique() interface like so:

```php
Massdbimport::model('\Weblid\Massdbimport\TestLocation')
->source('db.csv')
->unique('slug')
->ifDuplicate("SKIP")
->import();
```

You can also tell the importer what to do when it hits a duplicate with the ->ifDuplicate() interface. Options are:

UPDATE
SKIP
RENAME

## Helper functions

Some functions have been made available that can be used in cells in the CSV file to manipulate or create custom cell data.

### Slugify 
```php
slugify(column_name)
```
This will create a slug (no spaces, upper case) string from the given column name.


## Current Status

Very early development.