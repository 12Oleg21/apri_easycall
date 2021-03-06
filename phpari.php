<?php
namespace phpari;
use Yii;
    /**
     * phpari - A PHP Class Library for interfacing with Asterisk(R) ARI
     * Copyright (C) 2014  Nir Simionovich
     *
     * This library is free software; you can redistribute it and/or
     * modify it under the terms of the GNU Lesser General Public
     * License as published by the Free Software Foundation; either
     * version 2.1 of the License, or (at your option) any later version.
     *
     * This library is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
     * Lesser General Public License for more details.
     *
     * You should have received a copy of the GNU Lesser General Public
     * License along with this library; if not, write to the Free Software
     * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
     * Also add information on how to contact you by electronic and paper mail.
     *
     * Greenfield Technologies Ltd., hereby disclaims all copyright interest in
     * the library `phpari' (a library for creating smart telephony applications)
     * written by Nir Simionovich and its respective list of contributors.
     */
    class phpari
    {
        /** @var applications */
        private $applications;
        /** @var asterisk */
        private $asterisk;
        /** @var bridges */
        private $bridges;
        /** @var channels */
        private $channels;
        /** @var devicestates */
        private $devicestates;
        /** @var endpoints */
        private $endpoints;
        /** @var events */
        private $events;
        /** @var mailboxes */
        private $mailboxes;
        /** @var recordings */
        private $recordings;
        /** @var sounds */
        private $sounds;
        /** @var playbacks */
        private $playbacks;
        private $configuration;

        /** @var \Evenement\EventEmitter */
        public $stasisEvents;
        /** @var Yii\Logger */
        public $stasisLogger;
        /** @var React\EventLoop\LoopInterface */
        public $stasisLoop;
        /** @var Devristo\Phpws\Client\WebSocket */
        public $stasisClient;
        public $debug;
        public $logfile;
        public $lasterror;
        public $lasttrace;
        public $lasterrorinfo;

        /**
         * @param null   $stasisApplication
         * @param string $configFile
         *
         * Returns an array containing 5 objects: WebSocket, Pest, EventLoopFactory, Logger, StasisEventHandler
         *
         */
        public function __construct($stasisApplication = NULL, $configFile = NULL)
        {
            if($configFile == NULL)
            {
                $configFile = Yii::$app->params['phpariConfig'];
            }
            try {
                /* Get our configuration */
                $this->configuration = (object)parse_ini_file($configFile, TRUE);

                /* Some general information */
                $this->debug   = $this->configuration->general['debug'];
                $this->logfile = $this->configuration->general['logfile'];

                /* Connect to ARI server */
                $result = $this->connect($this->configuration->asterisk_ari['username'],
                    $this->configuration->asterisk_ari['password'],
                    $stasisApplication,
                    $this->configuration->asterisk_ari['host'],
                    $this->configuration->asterisk_ari['port'],
                    $this->configuration->asterisk_ari['endpoint'],
                    $this->configuration->asterisk_ari['transport']);

                return $result;


            } catch (Exception $e) {
                die("Exception raised: " . $e->getMessage() . "\nFile: " . $e->getFile() . "\nLine: " . $e->getLine());
            }

        }

        /**
         * This function is connecting and returning a phpari client object which
         * transferred to any of the interfaces will assist with the connection process
         * to the Asterisk Stasis or to the Asterisk 12 web server (Channels list , End Points list)
         * etc.
         *
         * @param $ariUsername
         * @param $ariPassword
         * @param $stasisApplication
         * @param $ariServer
         * @param $ariPort
         * @param $ariEndpoint
         * @param $ariTransport
         *
         * @return array
         */
        private function connect($ariUsername, $ariPassword, $stasisApplication, $ariServer, $ariPort, $ariEndpoint, $ariTransport)
        {

            try {
                $this->ariEndpoint = new \PestJSON("http://" . $ariServer . ":" . $ariPort . $ariEndpoint);
                $this->ariEndpoint->setupAuth($ariUsername, $ariPassword, "basic");
                
                $this->stasisLoop = \React\EventLoop\Factory::create();

                $this->stasisLogger = new \yii\log\Logger();


                if ($this->debug) $this->stasisLogger->log("Initializing WebSocket Information", \yii\log\Logger::LEVEL_INFO);

                $this->stasisClient = new \Devristo\Phpws\Client\WebSocket($ariTransport . "://" . $ariServer . ":" . $ariPort . $ariEndpoint . "/events?api_key=" . $ariUsername . ":" . $ariPassword . "&app=" . $stasisApplication, $this->stasisLoop, $this->stasisLogger);

                if ($this->debug) $this->stasisLogger->log("Initializing Stasis Event Emitter", \yii\log\Logger::LEVEL_INFO);

                $this->stasisEvents = new \Evenement\EventEmitter();


                return TRUE;
                //return array("stasisClient" => $this->stasisClient, "stasisLoop" => $this->stasisLoop, "stasisLogger" => $this->stasisLogger, "ariEndpoint" => $this->ariEndpoint);

            } catch (Exception $e) {
                die("Exception raised: " . $e->getMessage() . "\nFile: " . $e->getFile() . "\nLine: " . $e->getLine());
            }
        }

        /**
         * @return applications|bool - An applications object, or FALSE on failure
         */
        public function applications()
        {
            try {
                if (!isset($this->applications)) {
                    $this->applications = new \applications($this);
                    if ($this->debug) $this->stasisLogger->log("applications class had been initiated", \yii\log\Logger::LEVEL_INFO);
                }

                return $this->applications;
            } catch (Exception $e) {
                if ($this->debug) $this->stasisLogger->log("applications has failed initialization", \yii\log\Logger::LEVEL_INFO);
                $this->lasterror = "applications class failed to initialize properly";

                return FALSE;
            }
        }

        /**
         * @return asterisk|bool - An asterisk object, or FALSE on failure
         */
        public function asterisk()
        {
            try {
                if (!isset($this->asterisk)) {
                    $this->asterisk = new \asterisk($this);
                    if ($this->debug) $this->stasisLogger->log("asterisk class had been initiated", \yii\log\Logger::LEVEL_INFO);
                }

                return $this->asterisk;
            } catch (Exception $e) {
                if ($this->debug) $this->stasisLogger->log("asterisk has failed initialization", \yii\log\Logger::LEVEL_INFO);
                $this->lasterror = "asterisk class failed to initialize properly";

                return FALSE;
            }
        }

        /**
         * @return bridges|bool - An bridges object, or FALSE on failure
         */
        public function bridges()
        {
            try {
                if (!isset($this->bridges)) {
                    $this->bridges = new \bridges($this);
                    if ($this->debug) $this->stasisLogger->log("bridges class had been initiated", \yii\log\Logger::LEVEL_INFO);
                }

                return $this->bridges;
            } catch (Exception $e) {
                if ($this->debug) $this->stasisLogger->log("bridges has failed initialization", \yii\log\Logger::LEVEL_INFO);
                $this->lasterror = "bridges class failed to initialize properly";

                return FALSE;
            }
        }

        /**
         * @return channels|bool - An channels object, or FALSE on failure
         */
        public function channels()
        {
            try {
                if (!isset($this->channels)) {
                    $this->channels = new \channels($this);
                    if ($this->debug) $this->stasisLogger->log("channels class had been initiated", \yii\log\Logger::LEVEL_INFO);
                }

                return $this->channels;
            } catch (Exception $e) {
                if ($this->debug) $this->stasisLogger->log("channels has failed initialization", \yii\log\Logger::LEVEL_INFO);
                $this->lasterror = "channels class failed to initialize properly";

                return FALSE;
            }
        }

        /**
         * @return devicestates|bool - An devicestates object, or FALSE on failure
         */
        public function devicestates()
        {
            try {
                if (!isset($this->devicestates)) {
                    $this->devicestates = new \devicestates($this);
                    if ($this->debug) $this->stasisLogger->log("devicestates class had been initiated", \yii\log\Logger::LEVEL_INFO);
                }

                return $this->devicestates;
            } catch (Exception $e) {
                if ($this->debug) $this->stasisLogger->log("devicestates has failed initialization", \yii\log\Logger::LEVEL_INFO);
                $this->lasterror = "devicestates class failed to initialize properly";

                return FALSE;
            }
        }

        /**
         * @return endpoints|bool - An endpoints object, or FALSE on failure
         */
        public function endpoints()
        {
            try {
                if (!isset($this->endpoints)) {
                    $this->endpoints = new \endpoints($this);
                    if ($this->debug) $this->stasisLogger->log("endpoints class had been initiated", \yii\log\Logger::LEVEL_INFO);
                }

                return $this->endpoints;
            } catch (Exception $e) {
                if ($this->debug) $this->stasisLogger->log("endpoints has failed initialization", \yii\log\Logger::LEVEL_INFO);
                $this->lasterror = "endpoints class failed to initialize properly";

                return FALSE;
            }
        }

        /**
         * @return events|bool - An events object, or FALSE on failure
         */
        public function events()
        {
            try {
                if (!isset($this->events)) {
                    $this->events = new \events($this);
                    if ($this->debug) $this->stasisLogger->log("events class had been initiated", \yii\log\Logger::LEVEL_INFO);
                }

                return $this->events;
            } catch (Exception $e) {
                if ($this->debug) $this->stasisLogger->log("events has failed initialization", \yii\log\Logger::LEVEL_INFO);
                $this->lasterror = "events class failed to initialize properly";

                return FALSE;
            }
        }

        /**
         * @return mailboxes|bool - An mailboxes object, or FALSE on failure
         */
        public function mailboxes()
        {
            try {
                if (!isset($this->mailboxes)) {
                    $this->mailboxes = new \mailboxes($this);
                    if ($this->debug) $this->stasisLogger->log("mailboxes class had been initiated", \yii\log\Logger::LEVEL_INFO);
                }

                return $this->mailboxes;
            } catch (Exception $e) {
                if ($this->debug) $this->stasisLogger->log("mailboxes has failed initialization", \yii\log\Logger::LEVEL_INFO);
                $this->lasterror = "mailboxes class failed to initialize properly";

                return FALSE;
            }
        }

        /**
         * @return recordings|bool - An recordings object, or FALSE on failure
         */
        public function recordings()
        {
            try {
                if (!isset($this->recordings)) {
                    $this->recordings = new \recordings($this);
                    if ($this->debug) $this->stasisLogger->log("recordings class had been initiated", \yii\log\Logger::LEVEL_INFO);
                }

                return $this->recordings;
            } catch (Exception $e) {
                if ($this->debug) $this->stasisLogger->log("recordings has failed initialization", \yii\log\Logger::LEVEL_INFO);
                $this->lasterror = "recordings class failed to initialize properly";

                return FALSE;
            }
        }

        /**
         * @return sounds|bool - An sounds object, or FALSE on failure
         */
        public function sounds()
        {
            try {
                if (!isset($this->sounds)) {
                    $this->sounds = new \sounds($this);
                    if ($this->debug) $this->stasisLogger->log("sounds class had been initiated", \yii\log\Logger::LEVEL_INFO);
                }

                return $this->sounds;
            } catch (Exception $e) {
                if ($this->debug) $this->stasisLogger->log("sounds has failed initialization", \yii\log\Logger::LEVEL_INFO);
                $this->lasterror = "sounds class failed to initialize properly";

                return FALSE;
            }
        }

        /**
         * @return playbacks|bool - An playbacks object, or FALSE on failure
         */
        public function playbacks()
        {
            try {
                if (!isset($this->playbacks)) {
                    $this->playbacks = new \playbacks($this);
                    if ($this->debug) $this->stasisLogger->log("playbacks class had been initiated", \yii\log\Logger::LEVEL_INFO);
                }

                return $this->playbacks;
            } catch (Exception $e) {
                if ($this->debug) $this->stasisLogger->log("playbacks has failed initialization", \yii\log\Logger::LEVEL_INFO);
                $this->lasterror = "playbacks class failed to initialize properly";

                return FALSE;
            }
        }

    }
