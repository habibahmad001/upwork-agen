<?php

namespace App\Contracts;

/**
 * Crawler Service Interface
 *
 * Defines the contract for web scraping services that extract job data.
 */
interface CrawlerServiceInterface
{
    /**
     * Execute the crawler and return raw job data.
     *
     * @return array<int, array<string, mixed>> Array of job data
     * @throws \App\Exceptions\CrawlerException
     */
    public function crawl(): array;

    /**
     * Check if authenticated with the target service.
     *
     * @return bool True if authenticated, false otherwise
     */
    public function isAuthenticated(): bool;

    /**
     * Perform login and save session.
     *
     * @return bool True if login successful
     * @throws \App\Exceptions\CrawlerException
     */
    public function login(): bool;

    /**
     * Get current session information.
     *
     * @return array<string, mixed> Session details
     */
    public function getSessionInfo(): array;
}
