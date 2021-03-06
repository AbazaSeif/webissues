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

if ( !defined( 'WI_VERSION' ) ) die( -1 );

/**
* Helper class for loading JavaScript and CSS assets.
*/
class System_Web_Assets extends System_Web_Base
{
    const ProductionOnly = 1;

    private $devMode;
    private $devUrl;

    private $assets = array();

    private $stylesheets = array();
    private $scripts = array();

    public function __construct()
    {
        parent::__construct();

        $site = System_Core_Application::getInstance()->getSite();
        $this->devMode = $site->getConfig( 'dev_mode' );
        $this->devUrl = $site->getConfig( 'dev_url' );

        if ( !$this->devMode ) {
            $assetsPath = WI_ROOT_DIR . '/assets/assets.json';

            if ( file_exists( $assetsPath ) )
                $this->assets = json_decode( file_get_contents( $assetsPath ), true );
        }
    }

    public function add( $name, $flags = 0 )
    {
        if ( !$this->devMode ) {
            if ( isset( $this->assets[ $name ][ 'js' ] ) )
                $this->scripts[] = '/assets/' . $this->assets[ $name ][ 'js' ];

            if ( isset( $this->assets[ $name ][ 'css' ] ) )
                $this->stylesheets[] = '/assets/' . $this->assets[ $name ][ 'css' ];
        } else if ( ( $flags & self::ProductionOnly ) == 0 ) {
            $this->scripts[] = $this->devUrl . 'js/' . $name . '.js';
        }
    }

    public function render()
    {
        foreach ( $this->stylesheets as $url )
            echo "  <link rel=\"stylesheet\" href=\"" . $this->url( $url )  . "\" type=\"text/css\">\n";
        foreach ( $this->scripts as $url )
            echo "  <script type=\"text/javascript\" src=\"" . $this->url( $url ) . "\"></script>\n";
    }
}
