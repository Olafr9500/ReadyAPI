<?php

namespace ReadyAPI;

/**
 * Interface to link the data system with the objects
 */
interface IConnection
{
    public function __get($property);
    public function __set($property, $value);
    public function create();
    public function read();
    public function readAll();
    public function readBy($value, $index, $condition, $separator);
    public function update();
    public function delete();
    public function isEmpty();
    public function isDataCorrect();
    public function logInfo($action, $user);
}