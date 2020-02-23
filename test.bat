@ECHO OFF
vendor\bin\phpcbf --ignore=./tests/_support/* ./src/ ./tests/ && vendor\bin\phpcs --ignore=./tests/_support/* ./src/ ./tests/ && vendor\bin\phpstan analyze && vendor\bin\psalm && codecept run unit --coverage-txt && vendor\bin\infection
