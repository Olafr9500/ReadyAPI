<?php
namespace ReadyAPI;

/**
 * Interface pour lié le systeme de données avec les objects
 */
interface IConn
{
    public function __get($property);
    public function __set($property, $value);
    public function create();
    public function read();
    public function readAll();
    public function readBy($value, $index, $condition);
    public function update();
    public function delete();
    public function isEmpty();
    public function isDataCorrect();
}
