<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | The PIN and admin login rate limits, and the access log, rely on the
    | visitor's real IP. Behind a load balancer or CDN that IP only arrives
    | in the X-Forwarded-For header, which must be read from trusted proxies
    | only, or visitors could forge it to dodge the limits.
    |
    | TRUSTED_PROXIES accepts:
    |   (empty)       Not behind a proxy. Forwarded headers are ignored.
    |   REMOTE_ADDR   Behind exactly one proxy: trust whatever connects to the app.
    |   IPs / CIDRs   Comma separated, e.g. "10.0.0.0/8,172.16.0.0/12".
    |
    | Avoid "*": it trusts every hop, so a visitor can forge their own IP.
    | Leaving this empty also stops Laravel from assuming "*" on hosted
    | platforms such as Laravel Cloud.
    |
    */

    'proxies' => env('TRUSTED_PROXIES') ?: [],

];
