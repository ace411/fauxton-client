# fauxton-client
A simple CouchDB interaction library.

## Requirements

- curl
- php 5.4+

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

I advise that you read the [http://docs.couchdb.org/en/2.0.0/api/index.html](official CouchDB documentation) 
so as to better understand the fauxton-client. Also, reading the fauxton-client wiki is recommended 
and therefore, prudent.
