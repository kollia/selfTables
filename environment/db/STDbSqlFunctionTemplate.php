<?php

interface STDbSqlFunctionTemplate
{
    function IN(string $column, string|array $content) : string;
}
