<?php
/**************************************************************************
* This file is part of the WebIssues Server program
* Copyright (C) 2006 Michał Męciński
* Copyright (C) 2007-2017 WebIssues Team
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
**************************************************************************/

require_once( '../../system/bootstrap.inc.php' );

class Server_Api_Info
{
    public $access = 'public';

    public $params = array();

    public function run( $login, $password )
    {
        $serverManager = new System_Api_ServerManager();
        $server = $serverManager->getServer();

        $result[ 'serverName' ] = $server[ 'server_name' ];
        $result[ 'serverVersion' ] = $server[ 'server_version' ];

        $serverManager = new System_Api_ServerManager();

        $settings[ 'locale' ] = $serverManager->getSetting( 'language' );
        $settings[ 'anonymousAccess' ] = $serverManager->getSetting( 'anonymous_access' ) == 1;
        $settings[ 'selfRegister' ] = $serverManager->getSetting( 'self_register' ) == 1 && $serverManager->getSetting( 'email_engine' ) != null;
        $settings[ 'resetPassword' ] = $serverManager->getSetting( 'email_engine' ) != null;

        $result[ 'settings' ] = $settings;

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Info' );
