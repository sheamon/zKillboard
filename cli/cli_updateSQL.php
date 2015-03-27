<?php
/* zKillboard
 * Copyright (C) 2012-2015 EVE-KILL Team and EVSCO.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class cli_updateSQL implements cliCommand
{
        public function getDescription()
        {
                return "";
        }

        public function getAvailMethods()
        {
                return ""; // Space seperated list
        }

        public function execute($parameters, $db)
        {
                global $baseDir;

                // Install .sql files are located at:
                $installSQLFiles = $baseDir . "install/sql/";

                $tables = $db->query("SHOW TABLES");
                foreach($tables as $table)
                {
                        $definitionTable = $table["Tables_in_zkillboard"];
                        $create = $db->queryRow("SHOW CREATE TABLE {$definitionTable}");

                        $createTable = $create["Table"];
                        $createStatement = $create["Create Table"];

                        $createStatement = "DROP TABLE IF EXISTS `{$createTable}`;\n" . $create["Create Table"] . ";";
                        $file = $installSQLFiles . $createTable . ".sql";

                        if(file_exists($file))
                        {
                                CLI::out("Unlinking {$file}");
                                unlink($file);
                        }

                        CLI::out("Writing {$createTable} to {$file}");
                        file_put_contents($file, $createStatement);
                }

        }
}