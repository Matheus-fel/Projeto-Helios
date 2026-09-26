<?php

class MongoConnection {
    private static $instance = null;
    private $client;

    private string $uri = 'mongodb://root:root@mongo:27017/?authSource=admin';
    private string $database = 'helios_logs';

    private function __construct() {
        $uri = getenv('MONGO_URI') ?: $this->uri;
        $this->client = new MongoDB\Client($uri);
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getClient(): MongoDB\Client {
        return $this->client;
    }

    public function collection(string $name): MongoDB\Collection {
        return $this->client->{$this->database}->$name;
    }
}