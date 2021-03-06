<?php

namespace VisualComposer\Modules\System\Ajax;

if (!defined('ABSPATH')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit;
}

use VisualComposer\Framework\Container;
use VisualComposer\Framework\Illuminate\Support\Module;
use VisualComposer\Helpers\Logger;
use VisualComposer\Helpers\Nonce;
use VisualComposer\Helpers\Request;
use VisualComposer\Helpers\Str;
use VisualComposer\Helpers\Traits\EventsFilters;
use VisualComposer\Helpers\PostType;

class Controller extends Container implements Module
{
    use EventsFilters;

    protected $scope = 'ajax';

    public function __construct()
    {
        /** @see \VisualComposer\Modules\System\Ajax\Controller::listenAjax */
        $this->addEvent(
            'vcv:inited',
            'listenAjax',
            100
        );
    }

    protected function getResponse($requestAction)
    {
        $response = vcfilter('vcv:' . $this->scope, '');
        $response = vcfilter('vcv:' . $this->scope . ':' . $requestAction, $response);

        return $response;
    }

    protected function renderResponse($response)
    {
        if (is_string($response)) {
            return $response;
        }

        return json_encode($response);
    }

    protected function listenAjax(Request $requestHelper)
    {
        if ($requestHelper->exists(VCV_AJAX_REQUEST)) {
            if (!vcvenv('VCV_DEBUG')) {
                error_reporting(0);
            }
            $this->setGlobals();
            /** @see \VisualComposer\Modules\System\Ajax\Controller::parseRequest */
            $rawResponse = $this->call('parseRequest');
            $output = $this->renderResponse($rawResponse);
            $this->output($output, $rawResponse);
        }
    }

    protected function setGlobals()
    {
        if (!defined('VCV_AJAX_REQUEST_CALL')) {
            define('VCV_AJAX_REQUEST_CALL', true);
        }
        if (!defined('DOING_AJAX')) {
            define('DOING_AJAX', true);
        }
    }

    /**
     * @param \VisualComposer\Helpers\Request $requestHelper
     * @param \VisualComposer\Helpers\PostType $postTypeHelper
     */
    protected function setSource(Request $requestHelper, PostType $postTypeHelper)
    {
        if ($requestHelper->exists('vcv-source-id')) {
            $postTypeHelper->setupPost((int)$requestHelper->input('vcv-source-id'));
        }
    }

    protected function output($response, $rawResponse)
    {
        if (vcIsBadResponse($rawResponse)) {
            if (!headers_sent()) {
                header('Status: 403', true, 403);
                header('HTTP/1.0 403 Forbidden', true, 403);
            }
            $loggerHelper = vchelper('Logger');
            if (is_wp_error($rawResponse)) {
                /** @var $rawResponse \WP_Error */
                wp_die(
                    json_encode(['status' => false, 'message' => implode('. ', $rawResponse->get_error_messages())])
                );
            } elseif (is_array($rawResponse)) {
                wp_die(
                    json_encode(
                        [
                            'status' => false,
                            'message' => isset($rawResponse['body']) ? $rawResponse['body'] : $rawResponse,
                            'details' => ['message' => $loggerHelper->all(), 'details' => $loggerHelper->details()],
                        ]
                    )
                );
            } elseif ($loggerHelper->all()) {
                wp_die(
                    json_encode(
                        [
                            'status' => false,
                            'response' => $rawResponse,
                            'message' => $loggerHelper->all(),
                            'details' => $loggerHelper->details(),
                        ]
                    )
                );
            } else {
                wp_die(json_encode(['status' => false, 'response' => $rawResponse]));
            }
        }

        wp_die($response);
    }

    protected function parseRequest(Request $requestHelper, Logger $loggerHelper)
    {
        // Require an action parameter.
        if (!$requestHelper->exists('vcv-action')) {
            $loggerHelper->log('Action doesn`t set');

            return false;
        }
        $requestAction = $requestHelper->input('vcv-action');
        /** @see \VisualComposer\Modules\System\Ajax\Controller::validateNonce */
        $validateNonce = $this->call('validateNonce', [$requestAction]);
        if ($validateNonce) {
            /** @see \VisualComposer\Modules\System\Ajax\Controller::setSource */
            $this->call('setSource');

            /** @see \VisualComposer\Modules\System\Ajax\Controller::getResponse */
            return $this->call('getResponse', [$requestAction]);
        } else {
            $loggerHelper->log('Nonce not validated');
        }

        return false;
    }

    protected function validateNonce($requestAction, Request $requestHelper, Str $strHelper, Nonce $nonceHelper)
    {
        if ($strHelper->contains($requestAction, ':nonce')) {
            return $nonceHelper->verifyUser(
                $requestHelper->input('vcv-nonce')
            );
        } elseif ($strHelper->contains($requestAction, ':adminNonce')) {
            return $nonceHelper->verifyAdmin(
                $requestHelper->input('vcv-nonce')
            );
        }

        return true;
    }
}
