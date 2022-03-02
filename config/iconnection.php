<?php

namespace ReadyAPI;

/**
 * Interface to link the data system with the objects
 */
interface IConnection
{
    public function __get($property);
    public function __set($property, $value);
    /**
     * Add a new entry for the object in the database
     *
     * @return boolean Insertion validation status
     */
    public function create();
    /**
     * Retrieves data of the object in the database
     *
     * @return boolean Data recovery status
     */
    public function read();
    /**
     * Retrieves all of the object's entries in the database
     *
     * @return Object[] List of database entries or false
     */
    public function readAll();
    /**
     * Retrieves object's entries in the database with a condition
     *
     * @param Array $value
     * @param String[] $index
     * @param String[] $condition
     * @param String[] $separator
     * @return Object[]
     */
    public function readBy($value, $index, $condition, $separator);
    /**
     * Edit an entry for a database object
     *
     * @return boolean Status operation
     */
    public function update();
    /**
     * Delete an entry of a database object
     *
     * @return boolean Status operation
     */
    public function delete();
    /**
     * Check if the variables in the object is empty or not.
     *
     * @return boolean
     */
    public function isEmpty();
    /**
     * Check if the variables in the object is correct or not.
     *
     * @return boolean
     */
    public function isDataCorrect();
    /**
     * Make log
     *
     * @param string $action
     * @param int $user
     * @return boolean
     */
    public function logInfo($action, $user);
}
