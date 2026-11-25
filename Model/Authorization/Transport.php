<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Authorization;

/**
 * One HTTP call to the license server.
 */
// phpcs:ignore Magento2.NamingConvention.InterfaceName -- short name kept, same style as HttpClient
interface Transport
{
    /**
     * Sends the request.
     *
     * @param string $method GET or POST
     * @param string $url
     * @param array $headers name => value
     * @param string|null $body
     * @return array{status:int,headers:array,body:string} header names in lower case
     * @throws \RuntimeException when nothing usable came back (no connection, timeout, not allowed)
     */
    public function send(string $method, string $url, array $headers = [], ?string $body = null): array;
}
