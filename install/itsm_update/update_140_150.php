<?php
/**
 * ---------------------------------------------------------------------
 * ITSM-NG
 * Copyright (C) 2022 ITSM-NG and contributors.
 *
 * https://www.itsm-ng.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of ITSM-NG.
 *
 * ITSM-NG is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * ITSM-NG is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with ITSM-NG. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

/**
 * Update ITSM-NG from 1.4.0 to 1.5.0
 *
 * @return bool for success (will die for most error)
 **/
function update140to150() {
    /** @global Migration $migration */
    global $DB, $migration;

    $current_config   = Config::getConfigurationValues('core');
    $updateresult     = true;
    $ADDTODISPLAYPREF = [];

    $migration->displayTitle(sprintf(__('Update to %s'), '1.5.0'));

    // Update OIDC config table
    if(!$DB->fieldExists('glpi_oidc_config', 'use_ssl')) {
        $config = "ALTER TABLE `glpi_oidc_config` ADD COLUMN `use_ssl` TINYINT(1) NOT NULL DEFAULT 0";
        $DB->queryOrDie($config, "erreur lors de la mise a jour de la table de configuration oidc".$DB->error());
    }

    if(!$DB->fieldExists('glpi_oidc_config', 'certificate')) {
        $config = "ALTER TABLE `glpi_oidc_config` ADD COLUMN `certificate` varchar(255) DEFAULT NULL";
        $DB->queryOrDie($config, "erreur lors de la mise a jour de la table de configuration oidc".$DB->error());
    }

    if(!$DB->fieldExists('glpi_oidc_config', 'proxy')) {
        $config = "ALTER TABLE `glpi_oidc_config` ADD COLUMN `proxy` varchar(255) DEFAULT NULL";
        $DB->queryOrDie($config, "erreur lors de la mise a jour de la table de configuration oidc".$DB->error());
    }

    // ************ Keep it at the end **************
    $migration->executeMigration();
    return $updateresult;
}
