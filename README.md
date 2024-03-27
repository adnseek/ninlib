# nadlib

started as a collection of utility PHP classes which were used in multiple project since 2001. There were no decent PHP
frameworks at that time. After years nadlib has evolved into a framework of a sort. The main power of nadlib is quick
prototyping. Even at the price of readability, backward compatibility, cleanness or complexity of the code.

## current status

Actively improved.

## todo

* create unit tests
* refactor HTMLFormTable to use an array of objects which can be manipulated even after the whole form is built.
	Currently, every form element is rendered and concatenated as string making post modification difficult.
* refactor dbLayer->queryLog into a separate class which can be used by multiple dbLayer implementations.
* continue crating a standard dbLayer interface for all db classes.
* write documentation and a demo page showing all features of nadlib.
