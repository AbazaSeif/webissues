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

require_once( '../../../../system/bootstrap.inc.php' );

class Server_Api_Issues_Description_Edit
{
    public $access = '*';

    public $params = array(
        'issueId' => array( 'type' => 'int', 'required' => true ),
        'description' => array( 'type' => 'string', 'required' => true ),
        'descriptionFormat' => array( 'type' => 'int', 'default' => System_Const::PlainText )
    );

    public function run( $issueId, $descriptionText, $descriptionFormat )
    {
        $issueManager = new System_Api_IssueManager();
        $issue = $issueManager->getIssue( $issueId, System_Api_IssueManager::RequireAdministratorOrOwner );

        $description = $issueManager->getDescription( $issue );

        $validator = new System_Api_Validator();
        $serverManager = new System_Api_ServerManager();

        $validator->checkString( $descriptionText, $serverManager->getSetting( 'comment_max_length' ), System_Api_Validator::MultiLine );
        $validator->checkTextFormat( $descriptionFormat );

        $stampId = $issueManager->editDescription( $description, $descriptionText, $descriptionFormat );

        $result[ 'stampId' ] = $stampId;

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Issues_Description_Edit' );
