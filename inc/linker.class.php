<?php
/**
 * ---------------------------------------------------------------------
 * Formcreator is a plugin which allows creation of custom forms of
 * easy access.
 * ---------------------------------------------------------------------
 * LICENSE
 *
 * This file is part of Formcreator.
 *
 * Formcreator is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Formcreator is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Formcreator. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 * @copyright Copyright © 2011 - 2020 Teclib'
 * @license   http://www.gnu.org/licenses/gpl.txt GPLv3+
 * @link      https://github.com/pluginsGLPI/formcreator/
 * @link      https://pluginsglpi.github.io/formcreator/
 * @link      http://plugins.glpi-project.org/#/plugin/formcreator
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginFormcreatorLinker
{
   private $imported = [];

   private $postponed = [];

   /**
    * Store an object added in the DB
    *
    * @param string|integer $originalId
    * @param PluginFormcreatorExportableInterface $object
    * @return void
    */
   public function addObject($originalId, PluginFormcreatorExportableInterface $object) {
      if (!isset($this->imported[$object->getType()])) {
         $this->imported[$object->getType()] = [];
      }
      $this->imported[$object->getType()][$originalId] = $object;
   }

   /**
    * Get a previously imported object
    *
    * @param integer $originalId
    * @param string $itemtype
    * @return PluginFormcreatorExportableInterface
    */
   public function getObject($originalId, $itemtype) {
      if (!isset($this->imported[$itemtype][$originalId])) {
         return false;
      }
      return $this->imported[$itemtype][$originalId];
   }

   public function getObjectsByType($itemtype) {
      if (!isset($this->imported[$itemtype])) {
         return false;
      }
      return $this->imported[$itemtype];
   }

   /**
    * Store input data of an object to add it later
    *
    * @param string|integer $originalId
    * @param string $itemtype
    * @param array $input
    * @return void
    */
   public function postpone($originalId, $itemtype, array $input, $relationId) {
      if (!isset($this->postponed[$itemtype])) {
         $this->postponed[$itemtype] = [];
      }
      $this->postponed[$itemtype][$originalId] = ['input' => $input, 'relationId' => $relationId];
   }

   /**
    * Add in DB all postponed objects
    *
    * @return boolean true on success, false otherwise
    */
   public function linkPostponed() {
      do {
         $postponedCount = 0;
         $postponedAgainCount = 0;
         foreach ($this->postponed as $itemtype => $postponedItemtypeList) {
            $postponedCount += count($postponedItemtypeList);
            $newList = $postponedItemtypeList;
            foreach ($postponedItemtypeList as $originalId => $postponedItem) {
               if ($itemtype::import($this, $postponedItem['input'], $postponedItem['relationId']) === false) {
                  $newList[$originalId] = $postponedItem;
                  $postponedAgainCount++;
               }
            }
         }

         // If no item was successfully imported,  then the import is in a deadlock and fails
         if ($postponedAgainCount > 0 && $postponedCount == $postponedAgainCount) {
            return false;
         }
      } while ($postponedCount > 0);

      return true;
   }
}
