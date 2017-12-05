<?php
/**
 * User: Hannes
 * Date: 20.11.2017
 * Time: 23:16
 */

namespace App\SparkPlug\Database;

use PDO;

interface DBAccessInterface
{
    /**
     * @return \PDO The created DB Object
     */
    public function getDB(): PDO;
}