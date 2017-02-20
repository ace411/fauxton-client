# fauxton-client
A simple CouchDB interaction library.

## Requirements

- curl
- php 5.4+

## Installation

fauxton-client is available on Packagist. To install it, type the following in your preferred
command-line interface:

`composer require chemem/fauxton-client dev-master`

## NoSQL vs SQL

Structured Query Language (SQL) is a popular approach to handling back-end information and follows 
Atomicity Consistency Isolation and Durability (ACID) conventions. Simply put, SQL databases allow 
those who use them to store unique data in relational, table structures.
  
NoSQL, on the other hand, presents a different paradigm to handling data interactions: 
NoSQL standards are a manifestation of Basically Available Soft-state Eventually consistent (BASE) practices. 
Couch Database is a NoSQL database that follows a document-oriented, key-value pair format 
that is also convenient for manipulating data.

## Fauxton

Fauxton, formerly Futon, is the name of the Couch Database web client. Like its predecessor, Fauxton 
is a robust web interface designed to ease interactions with CouchDB.

## The client

Fauxton-client is a PHP library written for the sole purpose of performing CouchDB operations such as 
creating databases, creating indexes and writing Mango queries.

## Documentation

I advise that you read the [official CouchDB documentation](http://docs.couchdb.org/en/2.0.0/api/index.html) 
so as to better understand the fauxton-client. Also, reading the fauxton-client wiki is recommended 
and therefore, prudent.

## Running the unit tests

In order to run the unit tests, run the following command:

`vendor/bin/phpunit -c phpunit.xml`

## Examples

There are a few samples in the tests directory. Please consider using them to further your knowledge.

## Dealing with problems

Endeavor to create an issue on GitHub when the need arises or send an email to lochbm@gmail.com
