<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

include('../../../inc/includes.php');

Session::checkLoginUser();
if (Session::haveRight('entity', READ))
{
  return;
}

   if(isset($_GET['ticket_i_assigned']))
   {
     Html::helpHeader("Я исполнитель", 'ticketfilteredpage', 'ticket_i_assigned');
   }
   if(isset($_GET['ticket_i_author']))
   {
     Html::helpHeader("Я автор", 'ticketfilteredpage', 'ticket_i_author');
   }


$refresh_callback = <<<JS
const container = $('div.ajax-container.search-display-data');
if (container.length > 0 && container.data('js_class') !== undefined) {
    container.data('js_class').getView().refreshResults();
} else {
    // Fallback when fluid search isn't initialized
    window.location.reload();
}
JS;

echo Html::manageRefreshPage(false, $refresh_callback);
$search = new PluginTicketfilteredpageSearch();
$search->show('Ticket');

Html::footer();
