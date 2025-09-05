<?php

/**
 * -------------------------------------------------------------------------
 * TicketFilteredPage plugin for GLPI
 * Copyright (C) 2024 by the TicketFilteredPage Development Team.
 * -------------------------------------------------------------------------
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * --------------------------------------------------------------------------
 */

/**
 * Plugin install process
 *
 * @return boolean
 */
function plugin_ticketfilteredpage_install()
{
    return true;
}

/**
 * Plugin uninstall process
 *
 * @return boolean
 */
function plugin_ticketfilteredpage_uninstall()
{
    return true;
}

function plugin_ticketfilteredpage_redefine_menus($menu)
{
   if (empty($menu)) {
      return $menu;
   }
   $user_groups = Group_User::getUserGroups(Session::getLoginUserID());
   if(empty($user_groups)){
     return $menu;
   }

   $front_fields = Plugin::getPhpDir('ticketfilteredpage', false) . "/front";
   if (array_key_exists('ticketfilteredpage', $menu) === false) {
           $menu['ticketfilteredpage'] = [
               'default'   => "$front_fields/ticket.php",
               'title'     => 'Мои обращения',
               'types'   =>['ticket_i_assigned','ticket_i_author'],
               'content'   => [
                 'ticket_i_assigned'=>[
                 'title'=>'Я исполнитель',
                 'shortcut'=>'t',
                 'page'=>"$front_fields/ticket.php?ticket_i_assigned",
                 'lists_itemtype'=>'Ticket',
                 'icon'=>'',
                 'links'=>['lists'=>'']
               ],
               'ticket_i_author'=>[
                 'title'=>'Я автор',
                 'shortcut'=>'t',
                 'page'=>"$front_fields/ticket.php?ticket_i_author",
                 'lists_itemtype'=>'Ticket',
                 'icon'=>'',
                 'links'=>['lists'=>'']
               ]
             ],
               'icon'  =>"fa-fw ti ti-report",
           ];
       }

   return $menu;
}
