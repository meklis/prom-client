<?php

namespace Meklis\PromClient;

use Curl\Curl;
use Curl\MultiCurl;

class Client
{
    /**
     * @var MultiCurl
     */
    protected $multiCurl;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     *
     * @param $baseUrl
     * @param $timeout
     * @throws \ErrorException
     */
    function __construct($baseUrl, $timeout = 30)
    {
        $client = new MultiCurl($baseUrl);
        $client->setTimeout($timeout);
        $this->multiCurl = $client;
        $this->multiCurl->setHeader('Content-Type', 'application/x-www-form-urlencoded');
        $this->multiCurl->setJsonDecoder(function ($response) {
            return json_decode($response, true);
        });

        $this->curl = new Curl($baseUrl);
        $this->curl->setHeader('Content-Type', 'application/x-www-form-urlencoded');
        $this->curl->setJsonDecoder(function ($response) {
            return json_decode($response, true);
        });
    }

    /**
     * Expression queries
     * Query language expressions may be evaluated at a single instant or over a range of time. The sections below describe the API endpoints for each type of expression query.
     *
     * @param string $query
     * @param int|null $time
     * @return mixed
     */
    function query(string $query, ?int $time = null)
    {
        $client = clone $this->multiCurl;
        $request = [
            'query' => $query,
            'time' => $time !== null ? $time : time(),
        ];
        $curl = $client->addPost('/api/v1/query', $request);
        $curl->request = $request;
        return $this->fetchQueryResults($client)[0];
    }

    /**
     * Expression queries
     * Query language expressions may be evaluated at a single instant or over a range of time. The sections below describe the API endpoints for each type of expression query.
     *
     * @param string[] $queries
     * @param int|null $time
     * @return array
     */
    function queries(array $queries, ?int $time = null)
    {
        $client = clone $this->multiCurl;
        foreach ($queries as $query) {
            $request = [
                'query' => $query,
                'time' => $time !== null ? $time : time(),
            ];
            $curl = $client->addPost('/api/v1/query', $request);
            $curl->request = $request;
        }
        return $this->fetchQueryResults($client);
    }

    /**
     * Range queries
     * The following endpoint evaluates an expression query over a range of time
     *
     * If null received to time range set to 24h - now
     *
     * @param string $query
     * @param int|null $start
     * @param int|null $end
     * @param string $step
     * @return mixed
     */
    function queryRange(string $query, ?int $start = null, ?int $end = null, string $step = '1m')
    {
        $client = clone $this->multiCurl;
        $request = [
            'query' => $query,
            'start' => $start !== null ? $start : time() - (60 * 60 * 24),
            'end' => $end !== null ? $end : time(),
            'step' => $step,
        ];
        $curl = $client->addPost('/api/v1/query_range', $request);
        $curl->request = $request;
        return $this->fetchQueryResults($client)[0];
    }

    /**
     * Range queries
     * The following endpoint evaluates an expression query over a range of time
     *
     * If null received to time range set to 24h - now
     *
     * @param string $query
     * @param int|null $start
     * @param int|null $end
     * @param string $step
     * @return mixed
     */
    function queriesRange(array $queries, ?int $start = null, ?int $end = null, string $step = '1m')
    {
        $client = clone $this->multiCurl;
        foreach ($queries as $query) {
            $request = [
                'query' => $query,
                'start' => $start !== null ? $start : time() - (60 * 60 * 24),
                'end' => $end !== null ? $end : time(),
                'step' => $step,
            ];
            $curl = $client->addPost('/api/v1/query_range', $request);
            $curl->request = $request;
        }
        return $this->fetchQueryResults($client);
    }

    /**
     * Getting label names
     * The following endpoint returns a list of label names:
     *
     * @param int|null $start
     * @param int|null $end
     * @return mixed|null
     */
    function labels(?int $start = null, ?int $end = null)
    {
        $client = clone $this->curl;
        $request = [
            'start' => $start !== null ? $start : time() - (60 * 60 * 24),
            'end' => $end !== null ? $end : time(),
        ];
        $curl = $client->post('/api/v1/labels', $request);
        return $curl;
    }

    /**
     * Querying label values
     * The following endpoint returns a list of label values for a provided label name
     *
     * @param $labelName
     * @param int|null $start
     * @param int|null $end
     * @param $match
     * @return mixed|null
     */
    function labelValues($labelName, ?int $start = null, ?int $end = null, $match = [])
    {
        $client = clone $this->curl;
        $request = [
            'start' => $start !== null ? $start : time() - (60 * 60 * 24),
            'end' => $end !== null ? $end : time(),
            'match' => $match,
        ];
        $curl = $client->get("/api/v1/label/{$labelName}/values", $request);
        return $curl;
    }

    /**
     * Targets
     * The following endpoint returns an overview of the current state of the Prometheus target discovery
     *
     * @param $state
     * @return mixed|null
     */
    function targets($state = null)
    {
        $client = clone $this->curl;
        $request = [];
        if ($state) {
            $request['state'] = $state;
        }
        $curl = $client->post('/api/v1/targets', $request);
        return $curl;
    }

    /**
     * Rules
     * The /rules API endpoint returns a list of alerting and recording rules that are currently loaded. In addition it returns the currently active alerts fired by the Prometheus instance of each alerting rule.
     *
     * As the /rules endpoint is fairly new, it does not have the same stability guarantees as the overarching API v1.
     * @return mixed|null
     */
    function rules()
    {
        $client = clone $this->curl;
        $request = [];
        $curl = $client->post('/api/v1/rules', $request);
        return $curl;
    }

    /**
     * Alerts
     * The /alerts endpoint returns a list of all active alerts.
     * As the /alerts endpoint is fairly new, it does not have the same stability guarantees as the overarching API v1.
     *
     * @return mixed|null
     */
    function alerts()
    {
        $client = clone $this->curl;
        $request = [];
        $curl = $client->post('/api/v1/alerts', $request);
        return $curl;
    }

    /**
     * Alertmanagers
     * The following endpoint returns an overview of the current state of the Prometheus alertmanager discovery
     *
     * @return mixed|null
     */
    function alertmanagers()
    {
        $client = clone $this->curl;
        $request = [];
        $curl = $client->post('/api/v1/alertmanagers', $request);
        return $curl;
    }

    protected function fetchQueryResults(MultiCurl $client)
    {
        $responses = [];
        $client->success(function ($instance) use (&$responses) {
            $resp = $instance->response['data']['result'];
            if (isset($instance->request)) {
                foreach ($resp as $k => $v) {
                    $resp[$k]['request'] = $instance->request;
                }
            }
            $responses[] = $resp;
        });
        $client->error(function ($instance) {
            $this->catchError($instance);
            throw new \Exception("Error get response for " . json_encode($instance->request));
        });
        $client->start();
        return $responses;
    }

    protected function catchError(Curl $instance)
    {
        if ($instance->response) {
            throw new \Exception("Client return error type: {$instance->response['errorType']}, error: {$instance->response['error']}");
        }
        if ($instance->getHttpStatusCode() >= 400) {
            throw new \Exception("Client return error with status code: {$instance->getHttpStatusCode()}, message: {$instance->getHttpErrorMessage()}");
        }
        return $instance;
    }
}