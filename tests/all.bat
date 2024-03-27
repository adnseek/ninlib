@echo off
cd ..
call phpunit --bootstrap bootstrap.php tests\
cd tests
